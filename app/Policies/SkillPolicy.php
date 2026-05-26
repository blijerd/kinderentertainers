<?php

namespace App\Policies;

use App\Models\Skill;
use App\Models\User;

class SkillPolicy
{
    public function before(User $user): ?bool
    {
        return $user->hasRole('admin') ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'entertainer']);
    }

    public function view(User $user, Skill $skill): bool
    {
        return $user->hasAnyRole(['admin', 'entertainer']);
    }

    public function create(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function update(User $user, Skill $skill): bool
    {
        return $user->hasRole('admin');
    }

    public function delete(User $user, Skill $skill): bool
    {
        return $user->hasRole('admin');
    }
}
