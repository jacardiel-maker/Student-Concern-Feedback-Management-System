<?php

namespace Tests\Feature;

use App\Models\Attachment;
use App\Models\Concern;
use App\Models\Reply;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AttachmentAuthorizationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_author_can_download_a_private_attachment(): void
    {
        $this->seed(RoleSeeder::class);

        Storage::fake('attachments');

        $student = User::factory()->student()->create();
        $concern = Concern::factory()->for($student, 'author')->create();
        $attachment = Attachment::factory()->for($concern)->create([
            'user_id' => $student->id,
            'stored_path' => '2026/11/proof.pdf',
            'original_name' => 'proof.pdf',
        ]);
        Storage::disk('attachments')->put($attachment->stored_path, 'private contents');

        $response = $this->actingAs($student)->get(route('attachments.download', $attachment));

        $response->assertOk();
        $this->assertSame('private contents', $response->streamedContent());
    }

    public function test_another_student_cannot_download_the_attachment(): void
    {
        $this->seed(RoleSeeder::class);

        Storage::fake('attachments');

        $author = User::factory()->student()->create();
        $otherStudent = User::factory()->student()->create();
        $concern = Concern::factory()->for($author, 'author')->create();
        $attachment = Attachment::factory()->for($concern)->create();
        Storage::disk('attachments')->put($attachment->stored_path, 'private contents');

        $this->actingAs($otherStudent)->get(route('attachments.download', $attachment))->assertForbidden();
    }

    public function test_student_cannot_download_an_attachment_on_an_internal_note(): void
    {
        $this->seed(RoleSeeder::class);

        Storage::fake('attachments');

        $student = User::factory()->student()->create();
        $teacher = User::factory()->teacher()->create();
        $concern = Concern::factory()->for($student, 'author')->create(['recipient_id' => $teacher->id]);
        $reply = Reply::factory()->for($concern)->for($teacher, 'user')->internal()->create();
        $attachment = Attachment::factory()->for($concern)->for($reply, 'reply')->create();
        Storage::disk('attachments')->put($attachment->stored_path, 'internal contents');

        $this->actingAs($student)->get(route('attachments.download', $attachment))->assertForbidden();
    }

    public function test_admin_can_download_an_attachment_on_an_internal_note(): void
    {
        $this->seed(RoleSeeder::class);

        Storage::fake('attachments');

        $admin = User::factory()->admin()->create();
        $concern = Concern::factory()->create();
        $reply = Reply::factory()->for($concern)->internal()->create();
        $attachment = Attachment::factory()->for($concern)->for($reply, 'reply')->create();
        Storage::disk('attachments')->put($attachment->stored_path, 'internal contents');

        $response = $this->actingAs($admin)->get(route('attachments.download', $attachment));

        $response->assertOk();
        $this->assertSame('internal contents', $response->streamedContent());
    }

    public function test_missing_attachment_redirects_with_an_error(): void
    {
        $this->seed(RoleSeeder::class);

        Storage::fake('attachments');

        $student = User::factory()->student()->create();
        $concern = Concern::factory()->for($student, 'author')->create();
        $attachment = Attachment::factory()->for($concern)->create();

        $this->actingAs($student)->get(route('attachments.download', $attachment))
            ->assertRedirect()
            ->assertSessionHas('error', __('messages.attachment_missing'));
    }
}
