<?php

namespace Tests\Feature;

use Tests\TestCase;

class SidebarRoleNavigationTest extends TestCase
{
    public function test_technicians_and_noc_only_see_outbound_inventory_and_attendance(): void
    {
        foreach (['teknisi', 'noc'] as $role) {
            $navigation = $this->navigationFor($role);

            foreach (['Stok barang', 'Barang keluar', 'Absensi Teknisi & NOC'] as $label) {
                $this->assertStringContainsString($label, $navigation);
            }

            foreach (['Dashboard', 'Kategori barang', 'Serial number', 'Kartu RFID', 'Akun web', 'Laporan & ekspor', 'Riwayat pintu'] as $label) {
                $this->assertStringNotContainsString($label, $navigation);
            }
        }
    }

    public function test_admin_sees_all_sections_except_inbound_goods(): void
    {
        $navigation = $this->navigationFor('admin');

        foreach (['Dashboard', 'Stok barang', 'Barang keluar', 'Absensi Teknisi & NOC', 'Kartu RFID', 'Akun web', 'Laporan & ekspor', 'Riwayat pintu'] as $label) {
            $this->assertStringContainsString($label, $navigation);
        }

        foreach (['Kategori barang', 'Serial number', 'Barang masuk'] as $label) {
            $this->assertStringNotContainsString($label, $navigation);
        }
    }

    public function test_owner_sees_inbound_goods_and_all_other_sections(): void
    {
        $navigation = $this->navigationFor('owner');

        foreach (['Dashboard', 'Kategori barang', 'Serial number', 'Barang masuk', 'Stok barang', 'Barang keluar', 'Absensi Teknisi & NOC', 'Kartu RFID', 'Akun web', 'Laporan & ekspor', 'Riwayat pintu'] as $label) {
            $this->assertStringContainsString($label, $navigation);
        }
    }

    private function navigationFor(string $role): string
    {
        config()->set('app.ui_demo', true);
        session(['ui_as' => $role]);

        $html = view('layouts.partials.sidebar')->render();
        $start = strpos($html, '<nav class="sidebar-scroll');
        $end = $start === false ? false : strpos($html, '</nav>', $start);

        $this->assertNotFalse($start);
        $this->assertNotFalse($end);

        preg_match_all('/<span class="truncate">(.*?)<\/span>/s', substr($html, $start, $end + strlen('</nav>') - $start), $matches);

        return html_entity_decode(implode(' ', $matches[1]), ENT_QUOTES | ENT_HTML5);
    }
}
