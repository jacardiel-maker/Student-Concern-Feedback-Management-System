<?php

namespace Database\Seeders;

use App\Enums\ConcernPriority;
use App\Enums\ConcernStatus;
use App\Models\Category;
use App\Models\Role;
use App\Models\User;
use App\Services\ConcernService;
use Illuminate\Database\Seeder;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $adminRoleId = Role::where('name', 'admin')->value('id');
        $teacherRoleId = Role::where('name', 'teacher')->value('id');
        $studentRoleId = Role::where('name', 'student')->value('id');

        $admin = User::updateOrCreate(
            ['email' => 'admin@scfms.test'],
            [
                'name' => 'Admin User',
                'role_id' => $adminRoleId,
                'student_number' => null,
                'department' => 'Student Affairs',
                'password' => 'admin123',
                'is_active' => true,
                'email_opt_in' => false,
            ],
        );

        $santos = User::updateOrCreate(
            ['email' => 'j.santos@scfms.test'],
            [
                'name' => 'Juan Santos',
                'role_id' => $teacherRoleId,
                'student_number' => null,
                'department' => 'Guidance Office',
                'password' => 'teacher123',
                'is_active' => true,
                'email_opt_in' => false,
            ],
        );

        $reyes = User::updateOrCreate(
            ['email' => 'm.reyes@scfms.test'],
            [
                'name' => 'Maria Reyes',
                'role_id' => $teacherRoleId,
                'student_number' => null,
                'department' => 'Mathematics',
                'password' => 'teacher123',
                'is_active' => true,
                'email_opt_in' => false,
            ],
        );

        $cruz = User::updateOrCreate(
            ['email' => 'p.cruz@scfms.test'],
            [
                'name' => 'Pedro Cruz',
                'role_id' => $teacherRoleId,
                'student_number' => null,
                'department' => 'Registrar',
                'password' => 'teacher123',
                'is_active' => true,
                'email_opt_in' => false,
            ],
        );

        $delacruz = User::updateOrCreate(
            ['email' => 's.delacruz@scfms.test'],
            [
                'name' => 'Sofia Dela Cruz',
                'role_id' => $studentRoleId,
                'student_number' => 'ST-2026-001',
                'department' => 'Computer Science',
                'password' => 'student123',
                'is_active' => true,
                'email_opt_in' => false,
            ],
        );

        $bautista = User::updateOrCreate(
            ['email' => 'a.bautista@scfms.test'],
            [
                'name' => 'Ana Bautista',
                'role_id' => $studentRoleId,
                'student_number' => 'ST-2026-002',
                'department' => 'Education',
                'password' => 'student123',
                'is_active' => true,
                'email_opt_in' => false,
            ],
        );

        $garcia = User::updateOrCreate(
            ['email' => 'l.garcia@scfms.test'],
            [
                'name' => 'Luis Garcia',
                'role_id' => $studentRoleId,
                'student_number' => 'ST-2026-003',
                'department' => 'Engineering',
                'password' => 'student123',
                'is_active' => true,
                'email_opt_in' => false,
            ],
        );

        $torres = User::updateOrCreate(
            ['email' => 'k.torres@scfms.test'],
            [
                'name' => 'Katrina Torres',
                'role_id' => $studentRoleId,
                'student_number' => 'ST-2026-004',
                'department' => 'Business',
                'password' => 'student123',
                'is_active' => true,
                'email_opt_in' => false,
            ],
        );

        $categories = Category::query()->get()->keyBy('name');
        $concerns = app(ConcernService::class);

        // Sofia Dela Cruz: 2 concerns (1 anonymous bullying) - pending, assigned to Santos
        $bullying = $concerns->create([
            'category_id' => $categories['Bullying']->id,
            'recipient_id' => $santos->id,
            'subject' => 'Bullying incident in hallway',
            'description' => 'I witnessed repeated bullying near the hallway after class. I need this handled confidentially.',
            'priority' => ConcernPriority::Normal->value,
            'is_anonymous' => true,
        ], $delacruz);

        $delacruzPending = $concerns->create([
            'category_id' => $categories['Facilities']->id,
            'recipient_id' => $cruz->id,
            'subject' => 'Broken chair in classroom',
            'description' => 'Several chairs in Room 204 are broken and unsafe to use.',
            'priority' => ConcernPriority::Normal->value,
            'is_anonymous' => false,
        ], $delacruz);

        // Ana Bautista: 1 resolved concern with rating
        $bautistaResolved = $concerns->create([
            'category_id' => $categories['Academic']->id,
            'recipient_id' => $reyes->id,
            'subject' => 'Missing class materials',
            'description' => 'The learning materials for last week were not uploaded to the portal.',
            'priority' => ConcernPriority::Normal->value,
            'is_anonymous' => false,
        ], $bautista);
        $concerns->updateStatus($bautistaResolved, ConcernStatus::UnderReview, $reyes);
        $concerns->updateStatus($bautistaResolved, ConcernStatus::InProgress, $reyes);
        $concerns->updateStatus($bautistaResolved, ConcernStatus::Resolved, $reyes);
        $concerns->rate($bautistaResolved, $bautista, ['score' => 5, 'comment' => 'Resolved quickly, thank you.']);

        // Luis Garcia: 2 concerns (1 urgent safety)
        $urgentSafety = $concerns->create([
            'category_id' => $categories['Safety']->id,
            'recipient_id' => $santos->id,
            'subject' => 'Exposed wiring near laboratory',
            'description' => 'There is exposed electrical wiring near the science laboratory entrance.',
            'priority' => ConcernPriority::Normal->value,
            'is_anonymous' => false,
        ], $garcia);

        $garciaClosed = $concerns->create([
            'category_id' => $categories['Others']->id,
            'recipient_id' => $cruz->id,
            'subject' => 'Lost ID replacement process',
            'description' => 'I need help understanding the process for replacing a lost student ID.',
            'priority' => ConcernPriority::Low->value,
            'is_anonymous' => false,
        ], $garcia);
        $concerns->updateStatus($garciaClosed, ConcernStatus::UnderReview, $cruz);
        $concerns->updateStatus($garciaClosed, ConcernStatus::InProgress, $cruz);
        $concerns->updateStatus($garciaClosed, ConcernStatus::Resolved, $cruz);
        $concerns->rate($garciaClosed, $garcia, ['score' => 4, 'comment' => 'Clear instructions provided.']);
        $concerns->updateStatus($garciaClosed, ConcernStatus::Closed, $admin);

        // Katrina Torres: 1 under review
        $torresUnderReview = $concerns->create([
            'category_id' => $categories['Teacher-Related']->id,
            'recipient_id' => $santos->id,
            'subject' => 'Concern about class schedule communication',
            'description' => 'There have been repeated last-minute schedule changes without notice.',
            'priority' => ConcernPriority::Normal->value,
            'is_anonymous' => false,
        ], $torres);
        $concerns->updateStatus($torresUnderReview, ConcernStatus::UnderReview, $santos);

        // Additional statuses for demo coverage: in_progress with replies + internal note
        $inProgress = $concerns->create([
            'category_id' => $categories['Grades']->id,
            'recipient_id' => $reyes->id,
            'subject' => 'Grade computation clarification',
            'description' => 'I would like clarification on how my final grade was computed.',
            'priority' => ConcernPriority::Normal->value,
            'is_anonymous' => false,
        ], $delacruz);
        $concerns->addReply($inProgress, $reyes, 'I received your concern and I am reviewing your grade records.');
        $concerns->addReply($inProgress, $reyes, 'Internal: verify quiz 2 score against grade sheet.', true);
        $concerns->updateStatus($inProgress, ConcernStatus::UnderReview, $reyes);
        $concerns->updateStatus($inProgress, ConcernStatus::InProgress, $reyes);

        // Ensure the bullying case is the Santos-highlighted one and urgent safety exists; urgent escalation already handled by category.
        // Keep $bullying and $urgentSafety referenced for clarity.
        $bullying->refresh();
        $urgentSafety->refresh();
    }
}
