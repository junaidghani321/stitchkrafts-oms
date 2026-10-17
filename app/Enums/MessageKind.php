<?php

namespace App\Enums;

/**
 * What an order_messages row *is*.
 *
 * A Revision is a request for change that carries work state — status,
 * reason, priority, due date — and is assigned through an order_tasks row the
 * same way order work is. A Comment is a plain row: with the conversation
 * feature gone nothing writes one directly any more, but the replies that
 * settle a revision — the rejection reason, and the note the produced files
 * are attached to — are Comments pointing back through parent_id.
 *
 * Never read this from user input: writing a Revision is gated on
 * requestRevision, which is status-gated, so letting a request set the kind
 * would let a customer raise a revision on an order that is not revisable.
 * See OrderMessagePolicy.
 */
enum MessageKind: string
{
    case Comment = 'comment';
    case Revision = 'revision';

    public function label(): string
    {
        return match ($this) {
            self::Comment => 'Comment',
            self::Revision => 'Revision',
        };
    }
}
