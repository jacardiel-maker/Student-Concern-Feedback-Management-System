<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Concern;
use App\Models\User;
use Illuminate\Http\Request;

class AuditService
{
    public function __construct(private readonly Request $request) {}

    public function record(
        string $action,
        ?User $user,
        ?Concern $concern,
        ?string $oldValue = null,
        ?string $newValue = null,
        ?string $reason = null,
    ): AuditLog {
        return AuditLog::create([
            'user_id' => $user?->id,
            'concern_id' => $concern?->id,
            'action' => $action,
            'old_value' => $oldValue === null ? null : mb_strimwidth($oldValue, 0, 150, ''),
            'new_value' => $newValue === null ? null : mb_strimwidth($newValue, 0, 150, ''),
            'reason' => $reason === null ? null : mb_strimwidth($reason, 0, 300, ''),
            'ip_address' => $this->request->ip(),
        ]);
    }
}
