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
            $table->boolean('is_no_flowers')->default(false)->after('is_concealed');
            $table->boolean('is_last_tile')->default(false)->after('is_no_flowers');
            $table->boolean('is_robbing_kong')->default(false)->after('is_last_tile');
            $table->boolean('is_kong_replacement')->default(false)->after('is_robbing_kong');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mahjong_scans', function (Blueprint $table) {
            $table->dropColumn([
                'is_no_flowers',
                'is_last_tile',
                'is_robbing_kong',
                'is_kong_replacement',
            ]);
        });
    }
};
