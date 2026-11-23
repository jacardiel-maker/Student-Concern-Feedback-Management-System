<?php

namespace App\Services;

use App\Models\Concern;
use App\Models\User;

class LastVisitedConcernService
{
    public function remember(Concern $concern): void
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            return;
        }

        $user->forceFill(['last_visited_concern_id' => $concern->id])->save();
    }
}
