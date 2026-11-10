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
        Schema::table('bookings', function (Blueprint $table) {
            $table->string('kode_room', 50)->nullable()->after('tipe_booking')->index();
            $table->unsignedSmallInteger('kapasitas_user')->nullable()->after('kode_room');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropIndex(['kode_room']);
            $table->dropColumn(['kode_room', 'kapasitas_user']);
        });
    }
};
