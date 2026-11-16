<?php

namespace Tests\Feature;

use App\Models\RfidCard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class TechnicianDoorVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_technician_waits_for_admin_card_before_access_is_granted(): void
    {
        Cache::flush();
        $technician = RfidCard::create([
            'uid' => '0786334B',
            'name' => 'Teknisi Gudang',
            'role' => 'teknisi',
            'active' => true,
        ]);
        RfidCard::create([
            'uid' => '823E19B0',
            'name' => 'Admin Gudang',
            'role' => 'admin',
            'active' => true,
        ]);

        $this->postJson('/api/door/check', ['uid' => $technician->uid])
            ->assertOk()
            ->assertJson([
                'granted' => false,
                'pending' => true,
                'name' => 'Teknisi Gudang',
                'timeout' => 30,
            ]);

        $this->assertDatabaseHas('door_logs', [
            'uid' => $technician->uid,
            'result' => 'pending',
            'reason' => 'Menunggu tap kartu Admin',
        ]);

        $this->postJson('/api/door/check', ['uid' => '823E19B0'])
            ->assertOk()
            ->assertJson([
                'granted' => true,
                'name' => 'Teknisi Gudang',
                'role' => 'TEKNISI',
                'verified_by' => 'Admin Gudang',
            ]);

        $this->assertDatabaseHas('door_logs', [
            'uid' => $technician->uid,
            'result' => 'granted',
            'companion' => 'Admin Gudang (Admin)',
        ]);
    }

    public function test_owner_card_cannot_complete_a_technician_pending_verification(): void
    {
        Cache::flush();
        $technician = RfidCard::create([
            'uid' => '0786334B',
            'name' => 'Teknisi Gudang',
            'role' => 'teknisi',
            'active' => true,
        ]);
        RfidCard::create([
            'uid' => 'D07D1654',
            'name' => 'Owner Gudang',
            'role' => 'owner',
            'active' => true,
        ]);

        $this->postJson('/api/door/check', ['uid' => $technician->uid])->assertJson(['pending' => true]);

        $this->postJson('/api/door/check', ['uid' => 'D07D1654'])
            ->assertOk()
            ->assertJson([
                'granted' => false,
                'pending' => true,
                'message' => 'Hanya kartu Admin yang dapat menyelesaikan verifikasi',
            ]);

        $this->assertDatabaseHas('door_logs', [
            'uid' => $technician->uid,
            'result' => 'pending',
        ]);
    }
}
