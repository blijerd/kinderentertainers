<?php

namespace App\Policies;

use App\Models\ContentMedia;
use App\Models\User;

class ContentMediaPolicy
{
    public function before(User $user): ?bool
    {
        return $user->hasRole('admin') ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return false;
    }

    public function view(User $user, ContentMedia $contentMedia): bool
    {
        return false;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, ContentMedia $contentMedia): bool
    {
        return false;
    }

    public function delete(User $user, ContentMedia $contentMedia): bool
    {
        return false;
    }
}
