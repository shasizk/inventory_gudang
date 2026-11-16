<?php

namespace Tests\Feature;

use App\Models\DoorLog;
use App\Models\RfidCard;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AttendanceFromRfidCardsTest extends TestCase
{
    use RefreshDatabase;

    public function test_attendance_lists_registered_technician_cards_and_uses_granted_door_taps(): void
    {
        $this->actingAs(User::factory()->create([
            'username' => 'admin.test',
            'phone' => '08123456789',
            'role' => 'Admin',
        ]));

        $technician = RfidCard::create([
            'uid' => '0786334B',
            'name' => 'Teknisi Gudang',
            'role' => 'teknisi',
            'active' => true,
        ]);
        RfidCard::create([
            'uid' => 'D07D1654',
            'name' => 'NOC Gudang',
            'role' => 'noc',
            'active' => true,
        ]);
        RfidCard::create([
            'uid' => '823E19B0',
            'name' => 'Admin Gudang',
            'role' => 'admin',
            'active' => true,
        ]);

        DoorLog::create([
            'uid' => $technician->uid,
            'name' => $technician->name,
            'role' => $technician->role,
            'door' => 'gudang',
            'direction' => 'in',
            'method' => 'two_step',
            'result' => 'granted',
            'at' => Carbon::parse('2026-11-12 08:00:00'),
        ]);
        DoorLog::create([
            'uid' => $technician->uid,
            'name' => $technician->name,
            'role' => $technician->role,
            'door' => 'gudang',
            'direction' => 'out',
            'method' => 'two_step',
            'result' => 'granted',
            'at' => Carbon::parse('2026-11-12 16:00:00'),
        ]);
        DoorLog::create([
            'uid' => $technician->uid,
            'name' => $technician->name,
            'role' => $technician->role,
            'door' => 'gudang',
            'direction' => 'in',
            'method' => 'two_step',
            'result' => 'granted',
            'at' => Carbon::parse('2026-11-11 08:00:00'),
        ]);

        $response = $this->get('/absensi?date=2026-11-12');

        $response->assertOk()
            ->assertSee('Teknisi Gudang')
            ->assertSee('NOC Gudang')
            ->assertDontSee('Admin Gudang')
            ->assertDontSee('Rizky Pratama');

        $attendance = $response->viewData('attendance')->keyBy('name');

        $this->assertCount(2, $attendance);
        $this->assertSame('08:00', $attendance['Teknisi Gudang']->first_tap->format('H:i'));
        $this->assertSame('16:00', $attendance['Teknisi Gudang']->last_tap->format('H:i'));
        $this->assertNull($attendance['NOC Gudang']->first_tap);
    }

    public function test_door_exit_is_paired_with_latest_unmatched_technician_entry(): void
    {
        $this->actingAs(User::factory()->create([
            'username' => 'admin.test',
            'phone' => '08123456789',
            'role' => 'Admin',
        ]));

        $technicianOne = RfidCard::create([
            'uid' => '0786334B',
            'name' => 'Teknisi Pertama',
            'role' => 'teknisi',
            'active' => true,
        ]);
        $technicianTwo = RfidCard::create([
            'uid' => 'D07D1654',
            'name' => 'Teknisi Terakhir',
            'role' => 'teknisi',
            'active' => true,
        ]);

        foreach ([
            [$technicianOne, '2026-11-12 08:00:00'],
            [$technicianTwo, '2026-11-12 09:00:00'],
        ] as [$card, $at]) {
            DoorLog::create([
                'uid' => $card->uid,
                'name' => $card->name,
                'role' => $card->role,
                'door' => 'gudang',
                'direction' => 'in',
                'method' => 'two_step',
                'result' => 'granted',
                'at' => Carbon::parse($at),
            ]);
        }

        DoorLog::create([
            'uid' => null,
            'name' => 'Door Exit',
            'role' => 'Akses Keluar',
            'door' => 'gudang',
            'direction' => 'out',
            'method' => 'exit_button',
            'result' => 'granted',
            'reason' => 'Keluar lewat tombol dalam',
            'at' => Carbon::parse('2026-11-12 10:00:00'),
        ]);

        $attendance = $this->get('/absensi?date=2026-11-12')
            ->assertOk()
            ->viewData('attendance')
            ->keyBy('name');

        $this->assertNull($attendance['Teknisi Pertama']->last_tap);
        $this->assertSame('10:00', $attendance['Teknisi Terakhir']->last_tap->format('H:i'));
    }
}
