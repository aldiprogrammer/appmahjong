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
        Schema::table('mahjong_scans', function (Blueprint $table) {
            $table->string('seat_wind', 2)->nullable()->after('faan_details');
            $table->string('round_wind', 2)->nullable()->after('seat_wind');
            $table->boolean('is_self_draw')->default(false)->after('round_wind');
            $table->boolean('is_concealed')->default(false)->after('is_self_draw');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mahjong_scans', function (Blueprint $table) {
            $table->dropColumn(['seat_wind', 'round_wind', 'is_self_draw', 'is_concealed']);
        });
    }
};
