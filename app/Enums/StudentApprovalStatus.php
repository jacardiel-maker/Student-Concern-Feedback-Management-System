<?php

namespace App\Enums;

enum StudentApprovalStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';

    public function label(): string
    {
        return __('labels.'.$this->value);
    }
}
