<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderFile;
use App\Models\OrderMessage;
use App\Models\Quote;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Every upload in the order module goes through here.
 *
 * Files are renamed to a ULID on write and stored on the private `orders`
 * disk. That kills path traversal, name collisions and shell.php.jpg games in
 * one move, while the customer still downloads Sapling-left-chest.dst.
 */
class OrderFileService
{
    /**
     * @param  array<int, UploadedFile|null>  $files
     * @return array<int, OrderFile>
     */
    public function store(array $files, Model $attachable, string $category, ?User $uploader = null): array
    {
        $disk = config('orders.uploads.disk', 'orders');
        $stored = [];

        foreach (array_filter($files) as $file) {
            $extension = strtolower($file->getClientOriginalExtension());
            $name = (string) Str::ulid().($extension !== '' ? '.'.$extension : '');
            $path = $this->directoryFor($attachable, $category).'/'.$name;

            // putFileAs on a disk with throw => true raises on IO failure, so
            // we never record a row pointing at a file that was not written.
            Storage::disk($disk)->putFileAs(dirname($path), $file, basename($path));

            $stored[] = $attachable->files()->create([
                'category' => $category,
                'disk' => $disk,
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getClientMimeType(),
                'size' => $file->getSize() ?: 0,
                'uploaded_by_id' => $uploader?->id,
            ]);
        }

        return $stored;
    }

    /**
     * orders/{order}/artwork
     * orders/{order}/deliverables
     * orders/{order}/messages/{message}
     * orders/{order}/changes/{message}/request
     * orders/{order}/changes/{message}/deliverable
     *
     * Files uploaded against a revision before it became a message still
     * resolve from the old orders/{order}/revisions/{revision}/… tree, because
     * order_files.path is stored absolutely and the merge deliberately did not
     * move any blobs. A mixed tree is expected: the path is storage, the
     * category is meaning.
     */
    private function directoryFor(Model $attachable, string $category): string
    {
        if ($attachable instanceof Order) {
            $leaf = $category === OrderFile::CATEGORY_DELIVERABLE ? 'deliverables' : 'artwork';

            return "orders/{$attachable->id}/{$leaf}";
        }

        if ($attachable instanceof OrderMessage) {
            return match ($category) {
                OrderFile::CATEGORY_REVISION_REQUEST => "orders/{$attachable->order_id}/changes/{$attachable->id}/request",
                OrderFile::CATEGORY_REVISION_DELIVERABLE => "orders/{$attachable->order_id}/changes/{$attachable->id}/deliverable",
                default => "orders/{$attachable->order_id}/messages/{$attachable->id}",
            };
        }

        if ($attachable instanceof Quote) {
            return "quotes/{$attachable->id}/artwork";
        }

        return 'orders/misc';
    }

    /**
     * Stream the file back under its original name. Never a redirect to a
     * storage URL — the disk is private and has no public route.
     */
    public function download(OrderFile $file): StreamedResponse
    {
        return Storage::disk($file->disk)->download($file->path, $file->original_name);
    }

    /** The blob is removed by the OrderFile `deleting` model event. */
    public function delete(OrderFile $file): void
    {
        $file->delete();
    }

    /**
     * Swap a file out for a new upload, destroying the old one.
     *
     * Used by final approval, where an admin or manager corrects the
     * employee's submission before the customer ever sees it.
     *
     * The replacement is attached to the same record in the same category, so
     * it lands wherever the original would have — on the order for a
     * first-round deliverable, on the revision reply for a revision one — and,
     * being a new row, it is unapproved until the approver signs the order
     * off.
     */
    public function replace(OrderFile $old, UploadedFile $new, ?User $actor = null): OrderFile
    {
        return DB::transaction(function () use ($old, $new, $actor) {
            $attachable = $old->attachable;

            if ($attachable === null) {
                throw new RuntimeException("Order file {$old->id} is not attached to anything.");
            }

            // store() takes an array and returns one; a single upload cannot
            // silently produce nothing, hence the explicit guard.
            $stored = $this->store([$new], $attachable, $old->category, $actor);

            if ($stored === []) {
                throw new RuntimeException('The replacement file could not be stored.');
            }

            /*
             | The original is deleted outright, blob and all — the OrderFile
             | `deleting` hook removes the file from disk. It is NOT kept as a
             | superseded copy: an approver replacing a file means that file
             | was wrong, and leaving it downloadable invites the wrong one
             | being sent.
             |
             | What survives is the audit trail: OrderApprovalService logs a
             | file.replaced activity naming both the original and the
             | replacement, so "what did the employee hand in?" is still
             | answerable even though the bytes are gone.
             */
            $old->delete();

            return $stored[0];
        });
    }

    /**
     * Duplicate a file onto another record.
     *
     * The bytes are genuinely copied, not just the row. Two rows pointing at
     * one path would look fine until either was deleted — the OrderFile
     * `deleting` event removes the blob, which would silently break the other.
     */
    public function copy(OrderFile $file, Model $attachable, string $category, ?User $actor = null): ?OrderFile
    {
        $disk = $file->disk;

        if (! Storage::disk($disk)->exists($file->path)) {
            return null;
        }

        $extension = pathinfo($file->path, PATHINFO_EXTENSION);
        $name = (string) Str::ulid().($extension !== '' ? '.'.$extension : '');
        $path = $this->directoryFor($attachable, $category).'/'.$name;

        Storage::disk($disk)->copy($file->path, $path);

        return $attachable->files()->create([
            'category' => $category,
            'disk' => $disk,
            'path' => $path,
            'original_name' => $file->original_name,
            'mime_type' => $file->mime_type,
            'size' => $file->size,
            'uploaded_by_id' => $file->uploaded_by_id ?? $actor?->id,
        ]);
    }
}
