<?php

namespace App\Policies;

use App\Models\OrderFile;
use App\Models\OrderMessage;
use App\Models\User;

/**
 * Files sit outside the role-prefixed route groups — `user-access` is
 * single-role, so a download route inside any one group is unreachable by the
 * other three. One route, guarded here.
 */
class OrderFilePolicy
{
    /**
     * Resolve the file back to its Order and defer to OrderPolicy@view, so
     * "who may see this order" is defined in exactly one place.
     */
    public function download(User $user, OrderFile $file): bool
    {
        $order = $file->order();

        if ($order === null) {
            return false;
        }

        /*
         | A customer may not pull a deliverable that has not been signed off.
         | Hiding it in the view is not enough on its own: the download route
         | takes a file id, so without this an unapproved file is one guessed
         | URL away. Staff see what they are about to approve.
         */
        if ($file->isAwaitingApproval() && ! $user->isAdminOrManager() && ! $user->isEmployee()) {
            return false;
        }

        /*
         | Files hanging off a message are only as visible as the message
         | itself — which, for a revision an employee has not been assigned,
         | is not at all. Same reasoning as above: the view hides the link,
         | this closes the guessed URL.
         */
        $attachable = $file->attachable;

        if ($attachable instanceof OrderMessage && ! $attachable->isVisibleTo($user)) {
            return false;
        }

        return $user->can('view', $order);
    }

    /**
     * Swapping a file out is part of signing the work off, so it is allowed
     * exactly while the order is up for approval and to exactly the people who
     * can approve it. After that the file the customer has is the file they
     * were given — correcting it is a revision, not an edit.
     *
     * Both kinds of submitted work qualify: a first-round deliverable hanging
     * off the order, and a revision deliverable hanging off the reply it was
     * handed in with. The customer is told about them at the same moment by
     * the same approval, so an approver who can correct one can correct the
     * other. Artwork and message attachments are somebody else's upload and
     * are never replaced here.
     *
     * isAwaitingApproval() is what keeps a second round honest: an order back
     * at PendingApproval still carries the files the customer was given last
     * time, and those are history now — replacing one would delete a file
     * they already hold.
     */
    public function replace(User $user, OrderFile $file): bool
    {
        if (! $file->isAwaitingApproval()) {
            return false;
        }

        $order = $file->order();

        return $order !== null && $user->can('approve', $order);
    }

    public function delete(User $user, OrderFile $file): bool
    {
        if ($user->isAdminOrManager()) {
            return true;
        }

        // The uploader may remove their own file while the order is still
        // open to editing.
        $order = $file->order();

        return $order !== null
            && $file->uploaded_by_id === $user->id
            && $user->can('update', $order);
    }
}
