<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('door_logs', function (Blueprint $table) {
            $table->id();
            $table->string('uid', 50)->nullable();
            $table->string('name', 100);
            $table->string('role', 50)->nullable();
            $table->string('door', 50)->default('gudang');
            $table->string('direction', 10)->default('in'); // 'in' or 'out'
            $table->string('method', 50)->default('rfid'); // 'rfid', 'two_step', 'exit_button', 'alarm'
            $table->string('companion', 100)->nullable();
            $table->string('result', 20)->default('granted'); // 'granted' or 'denied'
            $table->string('reason', 255)->nullable();
            $table->timestamp('at')->useCurrent();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('door_logs');
    }
};

