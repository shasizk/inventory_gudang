<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('__autonumber_config', function (Blueprint $table) {
            $table->string('form_id', 50)->primary();
            $table->string('format_auto', 50)->nullable()->comment('Contoh: KTG{number}. Token: {number} {yyyy} {yy} {mm} {dd}');
            $table->unsignedInteger('number_digit')->nullable()->comment('Jumlah digit nomor urut (dengan nol di depan)');
            $table->char('reset_by', 1)->nullable()->comment('d=tgl / m=bln / y=thn / kosong=tidak reset');
            $table->string('create_by', 20)->nullable();
            $table->dateTime('create_time')->nullable();
            $table->string('update_by', 20)->nullable();
            $table->dateTime('update_time')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('__autonumber_config');
    }
};