<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('__autonumber', function (Blueprint $table) {
            $table->string('form_id', 50)->primary();
            $table->unsignedInteger('last_no')->nullable();
            $table->date('last_tgl')->nullable();
            $table->string('create_by', 20)->nullable();
            $table->dateTime('create_time')->nullable();
            $table->string('update_by', 20)->nullable();
            $table->dateTime('update_time')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('__autonumber');
    }
};