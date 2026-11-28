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
            $table->string('winning_tile')->nullable()->after('is_concealed');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mahjong_scans', function (Blueprint $table) {
            $table->dropColumn('winning_tile');
        });
    }
};
