<?php

namespace App\Actions;

use App\Models\ContentRedirect;

class DeleteContentRedirect
{
    public function handle(ContentRedirect $redirect): void
    {
        $redirect->delete();
    }
}
