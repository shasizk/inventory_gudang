<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('serial_numbers', function (Blueprint $table) {
            $table->id();
            $table->string('sn_no', 30)->unique();      // nomor otomatis dari __autonumber (form_id: sn_no)
            $table->string('serial', 64)->unique();     // serial number yang dipakai di QR; TIDAK BOLEH kembar
            $table->string('item_name', 120);
            $table->foreignId('category_id')->constrained('categories')->restrictOnDelete();
            $table->string('source', 10)->default('sistem');  // sistem = dibuat di menu ini, pabrik = bawaan pabrik (nanti dari scan barang masuk)
            $table->string('status', 10)->default('gudang');  // gudang | keluar
            $table->string('created_by', 20)->nullable();
            $table->timestamps();

            $table->index(['status', 'source']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('serial_numbers');
    }
};