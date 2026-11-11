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
            $table->string('scoring_mode', 20)->default('chicken')->after('points');
            $table->unsignedTinyInteger('faan')->nullable()->after('scoring_mode');
            $table->json('faan_details')->nullable()->after('faan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mahjong_scans', function (Blueprint $table) {
            $table->dropColumn(['scoring_mode', 'faan', 'faan_details']);
        });
    }
};
