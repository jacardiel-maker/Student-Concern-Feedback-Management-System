<?php

namespace App\Services;

use App\Mail\ConcernNotificationMail;
use App\Models\Concern;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Mail;

class NotificationService
{
    public function send(
        User $user,
        string $type,
        string $title,
        string $body,
        ?Concern $concern = null,
    ): Notification {
        $notification = Notification::create([
            'user_id' => $user->id,
            'concern_id' => $concern?->id,
            'type' => $type,
            'title' => mb_strimwidth($title, 0, 150, ''),
            'body' => mb_strimwidth($body, 0, 300, ''),
        ]);

        if ($user->email_opt_in && config('services.notifications.email_enabled')) {
            Mail::to($user)->send(
                (new ConcernNotificationMail($title, $body, $concern))->afterCommit(),
            );
        }

        return $notification;
    }

    /**
     * @param  Collection<int, User>  $users
     */
    public function sendToUsers(
        Collection $users,
        string $type,
        string $title,
        string $body,
        ?Concern $concern = null,
    ): void {
        foreach ($users as $user) {
            $this->send($user, $type, $title, $body, $concern);
        }
    }

    public function notifyAdministrators(string $title, string $body, ?Concern $concern = null): void
    {
        $administrators = User::active()->withRole('admin')->get();

        $this->sendToUsers($administrators, 'urgent_alert', $title, $body, $concern);
    }
}
