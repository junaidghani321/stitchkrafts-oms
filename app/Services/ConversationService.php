<?php

namespace App\Services;

use App\Enums\MessageKind;
use App\Enums\RevisionStatus;
use App\Models\Order;
use App\Models\OrderFile;
use App\Models\OrderMessage;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

/**
 * The write path into order_messages.
 *
 * What is left of the conversation feature: the thread itself is gone — no
 * posting, no inbox, no read marks — but a revision IS a kind = revision row
 * in this table, and the work handed in against one is a reply to it carrying
 * the files. RevisionService owns that lifecycle and delegates the writing
 * here, which is why this class outlived the chat it was built for.
 */
class ConversationService
{
    public function __construct(
        private readonly OrderFileService $files,
    ) {}

    /**
     * Write a row into the order's thread.
     *
     * $facet carries the revision columns. It is built by RevisionService and
     * never from request input, because kind is a privilege column — see
     * MessageKind.
     *
     * Notifying is the caller's job: each revision event has its own audience
     * and its own preference key, which only RevisionService knows.
     *
     * @param  array<int, UploadedFile>  $attachments
     * @param  array{kind?:MessageKind, parent_id?:?int, revision_number?:?string, source?:?string, reason?:?string, priority?:?string, status?:RevisionStatus|string|null, due_date?:?string}  $facet
     */
    public function post(
        Order $order,
        User $author,
        string $body,
        array $attachments = [],
        array $facet = [],
        string $category = OrderFile::CATEGORY_MESSAGE,
    ): OrderMessage {
        /*
         | An employee attaching a file is handing work in, and the customer
         | must not meet an employee's work before an approver has released
         | it. Storing it as submitted work puts it behind that one gate:
         | hidden from the customer, listed on the approval panel for review
         | and replacement, released when the order is signed off.
         |
         | Only when the caller did not name a category. RevisionService names
         | its own, and what a customer or an approver attaches is not
         | somebody's submitted work.
         */
        if ($category === OrderFile::CATEGORY_MESSAGE && $author->isEmployee()) {
            $category = OrderFile::CATEGORY_REVISION_DELIVERABLE;
        }

        return DB::transaction(function () use ($order, $author, $body, $attachments, $facet, $category) {
            $message = $order->messages()->create([
                'user_id' => $author->id,
                'body' => $body,
            ]);

            // forceFill: the facet is deliberately not mass-assignable, so a
            // request can never promote its own message to a revision.
            if ($facet !== []) {
                $message->forceFill($facet)->save();
            }

            if ($attachments !== []) {
                $this->files->store($attachments, $message, $category, $author);
            }

            return $message;
        });
    }
}
