<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Concern;
use App\Models\Rating;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ReportControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_admin_reports_index_renders_kpis_charts_and_latest_activity(): void
    {
        $this->seed(RoleSeeder::class);

        $admin = User::factory()->admin()->create();
        $teacher = User::factory()->teacher()->create(['name' => 'Rated Teacher']);
        $category = Category::factory()->create(['name' => 'Academic']);
        $concern = Concern::factory()->for($category)->create([
            'subject' => 'Report concern',
            'recipient_id' => $teacher->id,
        ]);
        Rating::factory()->for($concern)->create(['score' => 5]);

        AuditLog::factory()->create([
            'user_id' => $admin->id,
            'concern_id' => $concern->id,
            'action' => 'status_changed',
        ]);

        $this->actingAs($admin)->get(route('admin.reports.index'))
            ->assertOk()
            ->assertSee(__('labels.total_concerns'))
            ->assertSee(__('labels.avg_resolution_hours'))
            ->assertSee(__('labels.average_rating'))
            ->assertSee(__('labels.open_unresolved'))
            ->assertSee('categoryChart')
            ->assertSee('statusChart')
            ->assertSee('priorityChart')
            ->assertSee('satisfactionChart')
            ->assertSee('Rated Teacher')
            ->assertSee(__('labels.latest_activity'))
            ->assertSee('status_changed')
            ->assertSee($concern->ticket_no);
    }

    public function test_reports_apply_date_range_filter_to_metrics(): void
    {
        $this->seed(RoleSeeder::class);

        $admin = User::factory()->admin()->create();
        Concern::factory()->create(['subject' => 'Old concern', 'created_at' => now()->subYear(), 'updated_at' => now()->subYear()]);
        Concern::factory()->create(['subject' => 'Recent concern', 'created_at' => now()->subDay(), 'updated_at' => now()->subDay()]);

        $response = $this->actingAs($admin)->get(route('admin.reports.index', [
            'from' => now()->subWeek()->toDateString(),
            'to' => now()->toDateString(),
        ]));

        $response->assertOk();
        $response->assertViewHas('total', 1);
    }

    public function test_csv_export_contains_bom_anonymity_and_audits_export(): void
    {
        $this->seed(RoleSeeder::class);

        $admin = User::factory()->admin()->create();
        $student = User::factory()->student()->create(['name' => 'Secret Student']);
        $anonymousConcern = Concern::factory()->anonymous()->for($student, 'author')->create(['subject' => 'Anonymous CSV concern']);
        $namedConcern = Concern::factory()->create(['subject' => 'Named CSV concern']);

        $response = $this->actingAs($admin)->get(route('admin.reports.csv', [
            'from' => now()->startOfYear()->toDateString(),
            'to' => now()->toDateString(),
        ]));

        $response->assertOk()->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $content = $response->streamedContent();

        $this->assertStringStartsWith("\xEF\xBB\xBF", $content);
        $this->assertStringContainsString($anonymousConcern->ticket_no, $content);
        $this->assertStringContainsString('anonymous', $content);
        $this->assertStringNotContainsString('Secret Student', $content);
        $this->assertStringContainsString($namedConcern->ticket_no, $content);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'action' => 'report_exported',
            'new_value' => 'csv',
        ]);
    }

    public function test_print_report_renders_and_audits_export(): void
    {
        $this->seed(RoleSeeder::class);

        $admin = User::factory()->admin()->create();
        Concern::factory()->create();

        $response = $this->actingAs($admin)->get(route('admin.reports.print', [
            'from' => now()->startOfYear()->toDateString(),
            'to' => now()->toDateString(),
        ]));

        $response->assertOk()
            ->assertSee(__('labels.report_confidentiality_notice'))
            ->assertSee(__('labels.generated_by'))
            ->assertSee($admin->name)
            ->assertSee('window.print()');

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'action' => 'report_exported',
            'new_value' => 'print',
        ]);
    }

    public function test_non_admin_cannot_access_reports(): void
    {
        $this->seed(RoleSeeder::class);

        $teacher = User::factory()->teacher()->create();

        $this->actingAs($teacher)->get(route('admin.reports.index'))->assertForbidden();
        $this->actingAs($teacher)->get(route('admin.reports.csv'))->assertForbidden();
        $this->actingAs($teacher)->get(route('admin.reports.print'))->assertForbidden();
    }
}
