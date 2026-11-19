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
        Schema::table('paket_hargajammains', function (Blueprint $table) {
            // Default 00:00-00:00 dipakai sebagai rentang seharian penuh.
            $table->string('jam_mulai', 5)->default('00:00')->after('minimal_jam');
            $table->string('jam_selesai', 5)->default('00:00')->after('jam_mulai');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('paket_hargajammains', function (Blueprint $table) {
            $table->dropColumn(['jam_mulai', 'jam_selesai']);
        });
    }
};
