<?php

namespace App\Policies;

use App\Models\OrderTask;
use App\Models\User;

class OrderTaskPolicy
{
    public function view(User $user, OrderTask $task): bool
    {
        return $user->isAdminOrManager() || $task->employee_id === $user->id;
    }

    /** Only the assigned employee works the task. */
    public function start(User $user, OrderTask $task): bool
    {
        return $task->employee_id === $user->id && $task->isOpen();
    }

    public function complete(User $user, OrderTask $task): bool
    {
        return $task->employee_id === $user->id && $task->isOpen();
    }

    /** Extending a deadline is a supervision act, not the worker's call. */
    public function extend(User $user, OrderTask $task): bool
    {
        return $user->isAdminOrManager() && $task->isOpen();
    }

    public function reassign(User $user, OrderTask $task): bool
    {
        return $user->isAdminOrManager();
    }
}
