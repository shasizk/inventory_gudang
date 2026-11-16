<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_report_page_renders_without_attendance_data(): void
    {
        $this->actingAs(User::factory()->create([
            'username' => 'admin.test',
            'phone' => '08123456789',
            'role' => 'Admin',
        ]));

        $this->get('/laporan')
            ->assertOk()
            ->assertSee('Pratinjau')
            ->assertSee('Absensi')
            ->assertSee('Ekspor dokumen');
    }
}
