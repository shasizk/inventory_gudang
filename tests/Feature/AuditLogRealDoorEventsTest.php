<?php

namespace Tests\Feature;

use App\Models\DoorLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class AuditLogRealDoorEventsTest extends TestCase
{
    use RefreshDatabase;

    public function test_audit_log_shows_real_forced_door_open_events_without_demo_rows(): void
    {
        $this->actingAs(User::factory()->create([
            'username' => 'admin.test',
            'phone' => '08123456789',
            'role' => 'Admin',
        ]));

        $this->postJson('/api/door/log', [
            'event' => 'alarm_forced',
            'door' => 'gudang',
        ])->assertOk();

        $this->get('/history/audit')
            ->assertOk()
            ->assertSee('Pintu dibuka paksa')
            ->assertSee('Pintu dibuka paksa!')
            ->assertDontSee('Rizky Pratama')
            ->assertDontSee('Dewi Lestari');

        $this->assertDatabaseHas('door_logs', [
            'method' => 'alarm',
            'result' => 'denied',
            'reason' => 'Pintu dibuka paksa!',
        ]);
    }

    public function test_audit_log_only_lists_real_gudang_door_logs(): void
    {
        $this->actingAs(User::factory()->create([
            'username' => 'admin.test',
            'phone' => '08123456789',
            'role' => 'Admin',
        ]));

        DoorLog::create([
            'name' => 'Kartu tidak dikenal',
            'role' => '-',
            'door' => 'ict',
            'direction' => 'in',
            'method' => 'rfid',
            'result' => 'denied',
            'reason' => 'Data dari pintu lain',
            'at' => now(),
        ]);

        $this->get('/history/audit')
            ->assertOk()
            ->assertDontSee('Data dari pintu lain')
            ->assertDontSee('Rizky Pratama');
    }

    public function test_forced_open_alert_is_returned_until_sensor_reports_closed(): void
    {
        Cache::flush();
        $this->actingAs(User::factory()->create([
            'username' => 'admin.test',
            'phone' => '08123456789',
            'role' => 'Admin',
        ]));

        $this->postJson('/api/door/log', [
            'event' => 'alarm_forced',
            'door' => 'gudang',
        ])->assertOk();

        $this->getJson('/ajax/door/status')
            ->assertOk()
            ->assertJsonPath('doors.0.forced_open.message', 'Pintu gudang dibuka paksa! Segera periksa lokasi.');

        $this->postJson('/api/door/state', [
            'door' => 'gudang',
            'door_open' => false,
            'locked' => true,
        ])->assertOk();

        $this->getJson('/ajax/door/status')
            ->assertOk()
            ->assertJsonPath('doors.0.forced_open', null);
    }

    public function test_door_status_responses_are_not_cached_and_show_latest_telemetry(): void
    {
        $this->actingAs(User::factory()->create([
            'username' => 'admin.test',
            'phone' => '08123456789',
            'role' => 'Admin',
        ]));

        $this->postJson('/api/door/state', [
            'door' => 'gudang',
            'door_open' => false,
            'locked' => true,
        ])->assertOk();

        $status = $this->getJson('/ajax/door/status')->assertOk();
        $this->assertStringContainsString('no-store', $status->headers->get('Cache-Control'));
        $status->assertJsonPath('doors.0.door_open', false)
            ->assertJsonPath('doors.0.locked', true);

        $this->postJson('/api/door/state', [
            'door' => 'gudang',
            'door_open' => true,
            'locked' => false,
        ])->assertOk();

        $this->getJson('/ajax/door/status')
            ->assertOk()
            ->assertJsonPath('doors.0.door_open', true)
            ->assertJsonPath('doors.0.locked', false);
    }

    public function test_door_history_latest_endpoint_returns_new_and_updated_logs_without_cache(): void
    {
        $this->actingAs(User::factory()->create([
            'username' => 'admin.test',
            'phone' => '08123456789',
            'role' => 'Admin',
        ]));

        $existing = DoorLog::create([
            'name' => 'Teknisi Uji',
            'role' => 'teknisi',
            'door' => 'gudang',
            'direction' => 'in',
            'method' => 'two_step',
            'result' => 'pending',
            'reason' => 'Menunggu tap kartu Admin',
            'at' => now(),
        ]);

        $newLog = DoorLog::create([
            'name' => 'Admin Uji',
            'role' => 'admin',
            'door' => 'gudang',
            'direction' => 'in',
            'method' => 'rfid',
            'result' => 'granted',
            'reason' => 'Akses diterima',
            'at' => now(),
        ]);

        $url = '/ajax/door/logs/latest?after=' . $existing->id . '&updated_after=' . urlencode(now()->subSecond()->format('Y-m-d H:i:s'));
        $response = $this->getJson($url)
            ->assertOk()
            ->assertJsonPath('latest_id', $newLog->id)
            ->assertJsonPath('html', fn ($html) => str_contains($html, 'Admin Uji'))
            ->assertJsonPath('updated_after', now()->format('Y-m-d H:i:s'));
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));

        $existing->update([
            'companion' => 'Admin Uji (Admin)',
            'result' => 'granted',
            'reason' => 'Akses diterima setelah verifikasi Admin',
        ]);

        $this->getJson('/ajax/door/logs/latest?after=' . $newLog->id . '&updated_after=' . urlencode(now()->subSecond()->format('Y-m-d H:i:s')))
            ->assertOk()
            ->assertJsonPath('html', fn ($html) => str_contains($html, 'Admin Uji (Admin)'));
    }
}
