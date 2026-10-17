<?php

namespace App\Policies;

use App\Enums\UserType;
use App\Models\User;

/**
 * User administration is an admin-only function.
 *
 * The two guards that matter are on delete and on demotion: an admin must not
 * be able to lock everybody — including themselves — out of the system.
 */
class UserPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->isAdmin();
    }

    public function view(User $actor, User $user): bool
    {
        return $actor->isAdmin();
    }

    public function create(User $actor): bool
    {
        return $actor->isAdmin();
    }

    public function update(User $actor, User $user): bool
    {
        return $actor->isAdmin();
    }

    /**
     * Deleting is soft, so history survives — but you still cannot delete
     * yourself (you would be signed out mid-request) or the last admin
     * account (nobody could administer the system afterwards).
     */
    public function delete(User $actor, User $user): bool
    {
        if (! $actor->isAdmin() || $actor->id === $user->id) {
            return false;
        }

        return ! ($user->isAdmin() && $this->adminCount() <= 1);
    }

    /**
     * Changing a role away from admin is the same risk as deleting: it can
     * remove the last administrator.
     */
    public function changeRole(User $actor, User $user, string $newType): bool
    {
        if (! $actor->isAdmin()) {
            return false;
        }

        if ($user->isAdmin() && $newType !== UserType::Admin->slug() && $this->adminCount() <= 1) {
            return false;
        }

        // Demoting yourself would revoke your own access on the next request.
        return ! ($actor->id === $user->id && $newType !== UserType::Admin->slug());
    }

    private function adminCount(): int
    {
        return User::query()->ofType(UserType::Admin)->count();
    }
}
