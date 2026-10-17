<?php

namespace Tests\Unit;

use App\Models\OrderFile;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * isAwaitingApproval() answers two questions at once — "is this work handed
 * in for sign-off?" and "has it been signed off?" — and both the customer's
 * download gate and the approver's right to replace a file hang off it.
 */
class OrderFileApprovalGateTest extends TestCase
{
    /** @return array<string, array{string, ?string, bool}> */
    public static function files(): array
    {
        return [
            'unsigned deliverable' => [OrderFile::CATEGORY_DELIVERABLE, null, true],
            'signed deliverable' => [OrderFile::CATEGORY_DELIVERABLE, '2026-10-17 10:00:00', false],
            'unsigned revision deliverable' => [OrderFile::CATEGORY_REVISION_DELIVERABLE, null, true],
            'signed revision deliverable' => [OrderFile::CATEGORY_REVISION_DELIVERABLE, '2026-10-17 10:00:00', false],
            'customer artwork' => [OrderFile::CATEGORY_ARTWORK, null, false],
            'revision request attachment' => [OrderFile::CATEGORY_REVISION_REQUEST, null, false],
            'message attachment' => [OrderFile::CATEGORY_MESSAGE, null, false],
        ];
    }

    #[DataProvider('files')]
    public function test_only_unsigned_work_handed_in_for_approval_counts(string $category, ?string $approvedAt, bool $expected): void
    {
        // forceFill: approved_at is not fillable — it is set by the approval
        // service, never by a payload.
        $file = (new OrderFile)->forceFill([
            'category' => $category,
            'approved_at' => $approvedAt,
        ]);

        $this->assertSame($expected, $file->isAwaitingApproval());
    }
}
