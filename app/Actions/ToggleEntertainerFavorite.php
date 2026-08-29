<?php

namespace App\Actions;

use App\Models\Entertainer;
use App\Models\User;
use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ToggleEntertainerFavorite
{
    public function handle(User $user, Entertainer $entertainer, bool $favorite): void
    {
        if ($favorite) {
            if (! $entertainer->active) {
                throw new HttpException(Response::HTTP_NOT_FOUND);
            }

            $user->favoriteEntertainers()->syncWithoutDetaching([$entertainer->id]);

            return;
        }

        $user->favoriteEntertainers()->detach($entertainer->id);
    }
}
