<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderFile;
use App\Models\OrderMessage;
use App\Models\OrderTask;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Final approval of a revision round.
 *
 * Work produced for a revision is attached to the reply it was handed in
 * with, not to the order, so it reaches approval by a different route than a
 * first-round deliverable. The approver's powers over it are the same: swap
 * the file, or sign off what the employee handed in.
 */
class RevisionDeliverableApprovalTest extends TestCase
{
    use RefreshDatabase;

    private User $customer;

    private User $admin;

    private User $employee;

    private Order $order;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('orders');
        Notification::fake();

        $this->customer = User::factory()->customer()->create();
        $this->admin = User::factory()->admin()->create();
        $this->employee = User::factory()->employee()->create(['status' => 'active']);
        $this->order = Order::factory()->create(['customer_id' => $this->customer->id]);

        $this->completeFirstRound();
    }

    /** Round one, start to finish: assigned, handed in, signed off. */
    private function completeFirstRound(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.tasks.store'), [
                'order_id' => $this->order->id,
                'employee_id' => $this->employee->id,
                'duration_hours' => 3,
            ])
            ->assertRedirect();

        $this->actingAs($this->employee)
            ->post(route('employee.tasks.complete', $this->order->refresh()->tasks()->sole()), [
                'files' => [UploadedFile::fake()->create('round-one.dst', 10, 'application/octet-stream')],
            ])
            ->assertRedirect();

        $this->actingAs($this->admin)
            ->post(route('admin.orders.approve', $this->order), [])
            ->assertRedirect();
    }

    /** The customer asks for a change and an employee hands the rework in. */
    private function submitRevision(string $filename = 'revised-by-employee.dst'): OrderFile
    {
        $this->actingAs($this->customer)
            ->post(route('user.revisions.store'), [
                'order_id' => $this->order->id,
                'reason' => 'size',
                'comments' => 'The crest is too small.',
            ])
            ->assertRedirect();

        $revision = $this->order->refresh()->revisions()->sole();

        $this->actingAs($this->admin)
            ->post(route('admin.revisions.assign', $revision), [
                'employee_id' => $this->employee->id,
                'duration_hours' => 2,
            ])
            ->assertRedirect();

        $task = OrderTask::query()->whereNotNull('order_message_id')->sole();

        $this->actingAs($this->employee)
            ->post(route('employee.tasks.complete', $task), [
                'files' => [UploadedFile::fake()->create($filename, 10, 'application/octet-stream')],
            ])
            ->assertRedirect();

        return OrderFile::query()
            ->where('category', OrderFile::CATEGORY_REVISION_DELIVERABLE)
            ->sole();
    }

    /** Every file the customer is offered a download link for. */
    private function customerFiles(): Collection
    {
        $order = $this->actingAs($this->customer)
            ->get(route('user.orders.show', $this->order))
            ->assertOk()
            ->viewData('order');

        return collect($order->deliverables)
            ->concat($order->messages->flatMap->deliverables)
            ->filter(fn (OrderFile $file) => $file->approved_at !== null)
            ->pluck('original_name')
            ->sort()
            ->values();
    }

    public function test_customer_gets_the_replacement_when_a_revision_deliverable_is_replaced(): void
    {
        $submitted = $this->submitRevision();
        $submittedPath = $submitted->path;

        $this->actingAs($this->admin)
            ->post(route('admin.orders.files.replace', [$this->order, $submitted]), [
                'file' => UploadedFile::fake()->create('revised-by-approver.dst', 12, 'application/octet-stream'),
            ])
            ->assertRedirect();

        $this->actingAs($this->admin)
            ->post(route('admin.orders.approve', $this->order), [])
            ->assertRedirect();

        // Gone from the database and from the disk.
        $this->assertDatabaseMissing('order_files', ['id' => $submitted->id]);
        Storage::disk('orders')->assertMissing($submittedPath);

        // The replacement took its place in the thread, and round one's file
        // is untouched.
        $this->assertSame(['revised-by-approver.dst', 'round-one.dst'], $this->customerFiles()->all());

        $replacement = OrderFile::query()
            ->where('category', OrderFile::CATEGORY_REVISION_DELIVERABLE)
            ->sole();

        $this->assertTrue($replacement->attachable instanceof OrderMessage);
        $this->assertSame($this->admin->id, $replacement->uploaded_by_id);

        $this->actingAs($this->customer)
            ->get(route('files.download', $replacement))
            ->assertOk()
            ->assertDownload('revised-by-approver.dst');

        $this->actingAs($this->customer)
            ->get(route('files.download', $submitted->id))
            ->assertNotFound();
    }

    public function test_customer_gets_the_employee_file_when_the_revision_is_approved_unchanged(): void
    {
        $submitted = $this->submitRevision();

        $this->actingAs($this->admin)
            ->post(route('admin.orders.approve', $this->order), [])
            ->assertRedirect();

        $this->assertSame(['revised-by-employee.dst', 'round-one.dst'], $this->customerFiles()->all());

        $this->actingAs($this->customer)
            ->get(route('files.download', $submitted))
            ->assertOk()
            ->assertDownload('revised-by-employee.dst');

        Storage::disk('orders')->assertExists($submitted->fresh()->path);
    }

    public function test_the_approval_panel_offers_the_file_that_is_awaiting_approval(): void
    {
        $submitted = $this->submitRevision();

        $page = $this->actingAs($this->admin)
            ->get(route('admin.orders.show', $this->order))
            ->assertOk();

        // The file under review is listed with a replace control...
        $page->assertSee('revised-by-employee.dst');
        $page->assertSee('replace_'.$submitted->id);

        // ...and round one's file, which the customer already holds, is not
        // offered for replacement.
        $page->assertDontSee('replace_'.OrderFile::where('original_name', 'round-one.dst')->sole()->id);
    }

    public function test_a_file_the_customer_already_holds_cannot_be_replaced_in_a_later_round(): void
    {
        $this->submitRevision();

        $roundOne = OrderFile::query()->where('original_name', 'round-one.dst')->sole();

        $this->actingAs($this->admin)
            ->post(route('admin.orders.files.replace', [$this->order, $roundOne]), [
                'file' => UploadedFile::fake()->create('rewriting-history.dst', 9, 'application/octet-stream'),
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('order_files', [
            'id' => $roundOne->id,
            'original_name' => 'round-one.dst',
        ]);
    }

    public function test_a_file_from_another_order_cannot_be_replaced_through_this_one(): void
    {
        $submitted = $this->submitRevision();

        $otherOrder = Order::factory()->pendingApproval()->create([
            'customer_id' => User::factory()->customer(),
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.orders.files.replace', [$otherOrder, $submitted]), [
                'file' => UploadedFile::fake()->create('wrong-order.dst', 9, 'application/octet-stream'),
            ])
            ->assertNotFound();

        $this->assertDatabaseHas('order_files', ['id' => $submitted->id]);
    }
}
