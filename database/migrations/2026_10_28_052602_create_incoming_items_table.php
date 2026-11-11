<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kerangka awal tabel barang masuk. Menu Barang masuk nanti membaca/menulis tabel ini
     * (kolom mengikuti kontrak form: serial, name, qty, category_id, supplier).
     */
    public function up(): void
    {
        Schema::create('incoming_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('serial_number_id')->unique()->constrained('serial_numbers')->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('categories')->restrictOnDelete();
            $table->string('item_name', 120);
            $table->unsignedInteger('qty')->default(1);
            $table->string('supplier', 100)->nullable();
            $table->dateTime('received_at');
            $table->string('created_by', 20)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incoming_items');
    }
};