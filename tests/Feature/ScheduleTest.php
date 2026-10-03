<?php

namespace Tests\Feature;

use App\Exports\SchedulesExport;
use Database\Factories\ArenaFactory;
use Database\Factories\CategoryFactory;
use Database\Factories\MatchupFactory;
use Database\Factories\ScheduleFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\Concerns\CreatesUsers;
use Tests\TestCase;

class ScheduleTest extends TestCase
{
    use CreatesUsers, RefreshDatabase;

    public function test_schedule_index_can_be_rendered(): void
    {
        $user = $this->userWithPermissions(['schedules.view']);
        ScheduleFactory::new()->create(['match_no' => 'P-777']);

        $response = $this->actingAs($user)->get('/admin/schedules');

        $response->assertOk();
        $response->assertSee('P-777');
    }

    public function test_schedule_index_can_be_filtered_by_status(): void
    {
        $user = $this->userWithPermissions(['schedules.view']);
        ScheduleFactory::new()->create(['match_no' => 'PENDING-1', 'status' => 'pending']);
        ScheduleFactory::new()->create(['match_no' => 'FINISH-1', 'status' => 'finished']);

        $response = $this->actingAs($user)->get('/admin/schedules?status=finished');

        $response->assertOk();
        $response->assertSee('FINISH-1');
        $response->assertDontSee('PENDING-1');
    }

    public function test_schedule_can_be_created(): void
    {
        $user = $this->userWithPermissions(['schedules.create']);
        $category = CategoryFactory::new()->daeryun()->create();
        $arena = ArenaFactory::new()->create();

        $response = $this->actingAs($user)->post('/admin/schedules', [
            'match_no' => 'X-001',
            'category_id' => $category->id,
            'arena_id' => $arena->id,
            'type' => 'daeryun',
            'round' => '',
            'order_no' => '',
            'match_date' => '2026-10-20',
            'start_time' => '9:30',
            'end_time' => '10:15',
            'status' => 'pending',
        ]);

        $response->assertRedirect(route('admin.schedules.index'));
        $response->assertSessionHas('success', 'Jadwal berhasil ditambahkan.');
        $this->assertDatabaseHas('schedules', [
            'match_no' => 'X-001',
            'round' => 'penyisihan',
            'order_no' => 1,
            'start_time' => '09:30:00',
            'end_time' => '10:15:00',
            'status' => 'pending',
        ]);
    }

    public function test_schedule_rejects_invalid_time_format(): void
    {
        $user = $this->userWithPermissions(['schedules.create']);
        $category = CategoryFactory::new()->create();

        $response = $this->actingAs($user)->from('/admin/schedules')->post('/admin/schedules', [
            'match_no' => 'X-002',
            'category_id' => $category->id,
            'type' => 'daeryun',
            'match_date' => '2026-10-20',
            'start_time' => 'delapan',
            'status' => 'pending',
        ]);

        $response->assertSessionHasErrors('start_time', 'Format jam mulai tidak valid (HH:MM).');
        $this->assertDatabaseCount('schedules', 0);
    }

    public function test_duplicate_match_no_is_rejected(): void
    {
        $user = $this->userWithPermissions(['schedules.create']);
        ScheduleFactory::new()->create(['match_no' => 'SAME-1']);

        $response = $this->actingAs($user)->from('/admin/schedules')->post('/admin/schedules', [
            'match_no' => 'SAME-1',
            'category_id' => CategoryFactory::new()->create()->id,
            'type' => 'daeryun',
            'match_date' => '2026-10-20',
            'status' => 'pending',
        ]);

        $response->assertSessionHasErrors('match_no', 'Kode laga sudah digunakan.');
    }

    public function test_schedule_detail_is_returned_as_json(): void
    {
        $user = $this->userWithPermissions(['schedules.view']);
        $schedule = ScheduleFactory::new()->create(['match_no' => 'JSON-1', 'start_time' => '08:05:00']);

        $response = $this->actingAs($user)->getJson("/admin/schedules/{$schedule->id}");

        $response->assertOk();
        $response->assertJsonStructure([
            'id', 'match_no', 'category', 'arena', 'type', 'type_label', 'round',
            'order_no', 'match_date', 'start_time', 'end_time', 'status', 'status_label', 'athletes',
        ]);
        $response->assertJsonPath('match_no', 'JSON-1');
        $response->assertJsonPath('start_time', '08:05');
    }

    public function test_schedule_can_be_updated(): void
    {
        $user = $this->userWithPermissions(['schedules.update']);
        $schedule = ScheduleFactory::new()->create();

        $response = $this->actingAs($user)->put("/admin/schedules/{$schedule->id}", [
            'match_no' => $schedule->match_no,
            'category_id' => $schedule->category_id,
            'arena_id' => '',
            'type' => 'art',
            'round' => 'semi final',
            'order_no' => 5,
            'match_date' => '2026-11-01',
            'start_time' => '',
            'end_time' => '',
            'status' => 'preparation',
        ]);

        $response->assertRedirect(route('admin.schedules.index'));
        $response->assertSessionHas('success', 'Jadwal berhasil diperbarui.');
        $this->assertDatabaseHas('schedules', [
            'id' => $schedule->id,
            'type' => 'art',
            'round' => 'semi final',
            'status' => 'preparation',
        ]);
    }

    public function test_running_schedule_cannot_be_deleted(): void
    {
        $user = $this->userWithPermissions(['schedules.delete']);
        $schedule = ScheduleFactory::new()->create();
        MatchupFactory::new()->create(['schedule_id' => $schedule->id, 'status' => 'running']);

        $response = $this->actingAs($user)->delete("/admin/schedules/{$schedule->id}");

        $response->assertSessionHas('error', 'Jadwal tidak bisa dihapus saat pertandingan sedang berlangsung.');
        $this->assertDatabaseHas('schedules', ['id' => $schedule->id]);
    }

    public function test_schedule_can_be_deleted(): void
    {
        $user = $this->userWithPermissions(['schedules.delete']);
        $schedule = ScheduleFactory::new()->create();

        $response = $this->actingAs($user)->delete("/admin/schedules/{$schedule->id}");

        $response->assertRedirect(route('admin.schedules.index'));
        $response->assertSessionHas('success', 'Jadwal berhasil dihapus.');
        $this->assertDatabaseMissing('schedules', ['id' => $schedule->id]);
    }

    public function test_schedule_can_be_exported(): void
    {
        $user = $this->userWithPermissions(['schedules.export']);
        ScheduleFactory::new()->create(['match_no' => 'EXP-1']);

        $response = $this->actingAs($user)->get('/admin/schedules-export');

        $response->assertOk();
        $this->assertStringContainsString('.xlsx', (string) $response->headers->get('content-disposition'));
        $this->assertGreaterThan(0, (int) $response->baseResponse->getFile()?->getSize());
    }

    public function test_schedule_template_can_be_downloaded(): void
    {
        $user = $this->userWithPermissions(['schedules.import']);

        $response = $this->actingAs($user)->get('/admin/schedules-template');

        $response->assertOk();
        $this->assertStringContainsString('.xlsx', (string) $response->headers->get('content-disposition'));
    }

    public function test_schedule_import_requires_file(): void
    {
        $user = $this->userWithPermissions(['schedules.import']);

        $response = $this->actingAs($user)->post('/admin/schedules-import', []);

        $response->assertSessionHasErrors('file');
        $this->assertDatabaseCount('schedules', 0);
    }

    public function test_schedule_can_be_imported_from_xlsx(): void
    {
        $user = $this->userWithPermissions(['schedules.import']);
        $category = CategoryFactory::new()->daeryun()->create(['name' => 'Import Category']);
        $arena = ArenaFactory::new()->create(['name' => 'Arena Impor']);

        $file = $this->xlsxFile([
            ['IMP-1', 'Import Category', 'Arena Impor', 'daeryun', 'penyisihan', 3, '2026-10-25', '08:45', '09:45', 'pending'],
        ]);

        $response = $this->actingAs($user)->post('/admin/schedules-import', ['file' => $file]);

        $response->assertRedirect(route('admin.schedules.index'));
        $response->assertSessionHas('success', 'Jadwal berhasil diimpor dari file Excel.');
        $this->assertDatabaseHas('schedules', [
            'match_no' => 'IMP-1',
            'category_id' => $category->id,
            'arena_id' => $arena->id,
            'type' => 'daeryun',
            'order_no' => 3,
            'start_time' => '08:45:00',
            'status' => 'pending',
        ]);
        $this->assertSame('2026-10-25', (string) \App\Models\Schedule::first()?->match_date?->toDateString());
    }

    public function test_schedule_import_with_unknown_category_fails(): void
    {
        $user = $this->userWithPermissions(['schedules.import']);

        $file = $this->xlsxFile([
            ['IMP-2', 'Tidak Ada', '', 'daeryun', '', '', '2026-10-25', '', '', 'pending'],
        ]);

        $response = $this->actingAs($user)->post('/admin/schedules-import', ['file' => $file]);

        $response->assertSessionHas('error');
        $this->assertStringContainsString(
            'Import gagal.',
            (string) session('error')
        );
        $this->assertDatabaseCount('schedules', 0);
    }

    public function test_schedule_export_requires_permission(): void
    {
        $user = $this->userWithPermissions(['schedules.view']);

        $this->actingAs($user)->get('/admin/schedules-export')->assertForbidden();
        $this->actingAs($user)->get('/admin/schedules-template')->assertForbidden();
    }

    private function xlsxFile(array $rows): UploadedFile
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray(array_merge([SchedulesExport::HEADINGS], $rows), null, 'A1');

        ob_start();
        (new Xlsx($spreadsheet))->save('php://output');
        $content = (string) ob_get_clean();

        $spreadsheet->disconnectWorksheets();

        return UploadedFile::fake()->createWithContent('jadwal.xlsx', $content);
    }
}
