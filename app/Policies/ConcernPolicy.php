<?php

namespace App\Policies;

use App\Enums\ConcernStatus;
use App\Models\Concern;
use App\Models\User;

class ConcernPolicy
{
    public function view(User $user, Concern $concern): bool
    {
        return $concern->canBeViewedBy($user);
    }

    public function participate(User $user, Concern $concern): bool
    {
        return $this->view($user, $concern) && $concern->status !== ConcernStatus::Closed;
    }

    public function manage(User $user, Concern $concern): bool
    {
        return ($user->isTeacher() || $user->isAdmin()) && $this->view($user, $concern);
    }

    public function rate(User $user, Concern $concern): bool
    {
        return $concern->user_id === $user->id && $concern->isRateable();
    }
}
