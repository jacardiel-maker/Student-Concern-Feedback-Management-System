<?php

namespace Tests\Feature;

use App\Enums\ConcernStatus;
use App\Mail\DailyTicketDigestMail;
use App\Models\Concern;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class DailyTicketDigestTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_digest_is_sent_only_to_opted_in_teachers_with_open_tickets(): void
    {
        $this->seed(RoleSeeder::class);
        config()->set('services.notifications.email_enabled', true);
        Mail::fake();

        $optedIn = User::factory()->teacher()->create(['email_opt_in' => true]);
        $optedOut = User::factory()->teacher()->create(['email_opt_in' => false]);
        $openConcern = Concern::factory()->create(['recipient_id' => $optedIn->id]);
        Concern::factory()->create(['recipient_id' => $optedOut->id]);
        Concern::factory()->create([
            'recipient_id' => $optedIn->id,
            'status' => ConcernStatus::Resolved,
        ]);

        $this->artisan('app:send-daily-ticket-digest')->assertSuccessful();

        Mail::assertQueued(DailyTicketDigestMail::class, 1);
        Mail::assertQueued(DailyTicketDigestMail::class, function (DailyTicketDigestMail $mail) use ($optedIn, $openConcern): bool {
            return $mail->hasTo($optedIn->email)
                && $mail->concerns->contains($openConcern)
                && $mail->concerns->count() === 1;
        });
    }
}
