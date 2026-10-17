<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderFile;
use App\Models\User;
use App\Services\OrderFileService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Final approval, from the customer's end.
 *
 * The two outcomes that matter are "the approver swapped the file" and "the
 * approver did not", and in both cases the customer must end up with exactly
 * one deliverable: the one the approver signed off.
 */
class OrderDeliverableApprovalTest extends TestCase
{
    use RefreshDatabase;

    private User $customer;

    private User $admin;

    private User $employee;

    private Order $order;

    protected function setUp(): void
    {
        parent::setUp();

        // Blobs go to a temp disk; the real storage/app/order-files tree is
        // never written to by the suite.
        Storage::fake('orders');
        Notification::fake();

        $this->customer = User::factory()->customer()->create();
        $this->admin = User::factory()->admin()->create();
        $this->employee = User::factory()->employee()->create();

        $this->order = Order::factory()
            ->pendingApproval()
            ->create(['customer_id' => $this->customer->id]);
    }

    /** What the employee handed in: the same call TaskAssignmentService makes. */
    private function employeeDeliverable(string $name = 'employee-original.dst'): OrderFile
    {
        return app(OrderFileService::class)->store(
            [UploadedFile::fake()->create($name, 12, 'application/octet-stream')],
            $this->order,
            OrderFile::CATEGORY_DELIVERABLE,
            $this->employee,
        )[0];
    }

    /** The deliverables the customer's own order page offers for download. */
    private function customerDeliverables(): Collection
    {
        $response = $this->actingAs($this->customer)
            ->get(route('user.orders.show', $this->order))
            ->assertOk();

        return collect($response->viewData('order')->deliverables)
            ->filter(fn (OrderFile $file) => $file->approved_at !== null)
            ->values();
    }

    public function test_customer_gets_the_replacement_and_never_the_file_it_replaced(): void
    {
        $original = $this->employeeDeliverable();
        $originalPath = $original->path;

        $this->actingAs($this->admin)
            ->post(route('admin.orders.files.replace', [$this->order, $original]), [
                'file' => UploadedFile::fake()->create('approver-corrected.dst', 14, 'application/octet-stream'),
            ])
            ->assertRedirect();

        $this->actingAs($this->admin)
            ->post(route('admin.orders.approve', $this->order), ['note' => 'Corrected the stitch count.'])
            ->assertRedirect();

        // The original is gone from both the database and the disk.
        $this->assertDatabaseMissing('order_files', ['id' => $original->id]);
        Storage::disk('orders')->assertMissing($originalPath);

        // ...and exactly one deliverable reaches the customer: the new one.
        $visible = $this->customerDeliverables();

        $this->assertCount(1, $visible);
        $this->assertSame('approver-corrected.dst', $visible->first()->original_name);
        $this->assertNotNull($visible->first()->approved_at);

        $this->actingAs($this->customer)
            ->get(route('files.download', $visible->first()))
            ->assertOk()
            ->assertDownload('approver-corrected.dst');

        // The superseded id is not downloadable by anyone any more.
        $this->actingAs($this->customer)
            ->get(route('files.download', $original->id))
            ->assertNotFound();
    }

    public function test_customer_gets_the_employee_file_when_the_approver_does_not_replace_it(): void
    {
        $original = $this->employeeDeliverable();

        $this->actingAs($this->admin)
            ->post(route('admin.orders.approve', $this->order), ['note' => null])
            ->assertRedirect();

        $visible = $this->customerDeliverables();

        $this->assertCount(1, $visible);
        $this->assertTrue($visible->first()->is($original->fresh()));
        $this->assertSame($this->employee->id, $visible->first()->uploaded_by_id);

        $this->actingAs($this->customer)
            ->get(route('files.download', $original))
            ->assertOk()
            ->assertDownload('employee-original.dst');

        Storage::disk('orders')->assertExists($original->path);
    }

    public function test_customer_cannot_see_or_download_a_deliverable_before_it_is_approved(): void
    {
        $original = $this->employeeDeliverable();

        $this->assertCount(0, $this->customerDeliverables());

        $this->actingAs($this->customer)
            ->get(route('files.download', $original))
            ->assertForbidden();
    }

    public function test_a_manager_may_also_replace_a_deliverable(): void
    {
        $manager = User::factory()->manager()->create();
        $original = $this->employeeDeliverable();

        $this->actingAs($manager)
            ->post(route('manager.orders.files.replace', [$this->order, $original]), [
                'file' => UploadedFile::fake()->create('manager-version.dst', 9, 'application/octet-stream'),
            ])
            ->assertRedirect();

        $this->assertDatabaseMissing('order_files', ['id' => $original->id]);
        $this->assertDatabaseHas('order_files', [
            'attachable_id' => $this->order->id,
            'category' => OrderFile::CATEGORY_DELIVERABLE,
            'original_name' => 'manager-version.dst',
            'uploaded_by_id' => $manager->id,
        ]);
    }

    public function test_a_deliverable_cannot_be_replaced_once_the_order_has_been_approved(): void
    {
        $original = $this->employeeDeliverable();

        $this->actingAs($this->admin)
            ->post(route('admin.orders.approve', $this->order), ['note' => null])
            ->assertRedirect();

        $this->actingAs($this->admin)
            ->post(route('admin.orders.files.replace', [$this->order, $original]), [
                'file' => UploadedFile::fake()->create('too-late.dst', 9, 'application/octet-stream'),
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('order_files', [
            'id' => $original->id,
            'original_name' => 'employee-original.dst',
        ]);
    }

    public function test_replacing_records_what_was_superseded(): void
    {
        $original = $this->employeeDeliverable();

        $this->actingAs($this->admin)
            ->post(route('admin.orders.files.replace', [$this->order, $original]), [
                'file' => UploadedFile::fake()->create('approver-corrected.dst', 14, 'application/octet-stream'),
            ])
            ->assertRedirect();

        // The bytes are gone, so the audit row is the only record of what the
        // employee actually handed in.
        $this->assertDatabaseHas('order_activities', [
            'order_id' => $this->order->id,
            'user_id' => $this->admin->id,
            'event' => 'file.replaced',
            'description' => 'Final file employee-original.dst replaced with approver-corrected.dst',
        ]);
    }
}
