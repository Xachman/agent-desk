<?php

namespace App\Traits;

use App\Models\Group;
use App\Models\User;

trait HasGroupRole
{
    /**
     * Check if the given user can act as an admin (owner/admin) for this resource's group.
     */
    public function canAdmin(User $user): bool
    {
        if ($this->group_id === null) {
            return $this->user_id === $user->id || $user->isAdmin();
        }

        if (!$this->group) {
            return $user->isAdmin();
        }

        return $this->group->owner_id === $user->id
            || $this->group->memberRole($user) === 'admin'
            || $user->isAdmin();
    }

    /**
     * Check if the given user can interact with (but not manage) this resource.
     */
    public function canInteract(User $user): bool
    {
        if ($this->group_id === null) {
            return $this->user_id === $user->id || $user->isAdmin();
        }

        if (!$this->group) {
            return $user->isAdmin();
        }

        $role = $this->group->memberRole($user);

        return $this->group->owner_id === $user->id
            || in_array($role, ['admin', 'member'], true)
            || $user->isAdmin();
    }
}
