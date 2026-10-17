<?php

namespace App\Policies;

use App\Models\OrderMessage;
use App\Models\User;

/**
 * Replaces OrderRevisionPolicy. The write gates are preserved exactly.
 */
class OrderMessagePolicy
{
    /**
     * Who may see the order, minus the revisions that are none of their
     * business yet.
     *
     * The order gate is deferred to OrderPolicy@view so "who may see this
     * order" stays in one place. On top of it, an employee reads a revision
     * only once an admin or manager has assigned it to them — the rule
     * OrderMessage::scopeVisibleTo() applies to the thread query, repeated
     * here so a guessed id cannot walk around it.
     *
     * Ordinary comments are unaffected: the thread is still something
     * everybody on the order can read and reply to.
     */
    public function view(User $user, OrderMessage $message): bool
    {
        return $user->can('view', $message->order) && $message->isVisibleTo($user);
    }

    public function assign(User $user, OrderMessage $message): bool
    {
        return $message->isRevision() && $user->isAdminOrManager() && $message->isOpen();
    }

    public function reject(User $user, OrderMessage $message): bool
    {
        return $message->isRevision() && $user->isAdminOrManager() && $message->isOpen();
    }

    /**
     * Deliberately NOT role-based: the employee holding the task closes it
     * out, and an admin who is not assigned cannot. Preserved verbatim from
     * OrderRevisionPolicy.
     */
    public function complete(User $user, OrderMessage $message): bool
    {
        return $message->isRevision()
            && $message->isOpen()
            && $message->tasks()->where('employee_id', $user->id)->exists();
    }
}
