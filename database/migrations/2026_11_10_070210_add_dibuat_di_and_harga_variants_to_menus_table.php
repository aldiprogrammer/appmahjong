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
        Schema::table('menus', function (Blueprint $table) {
            $table->string('dibuat_di', 20)->default('Dapur')->after('nama');
            $table->string('harga_dingin', 50)->nullable()->after('harga');
            $table->string('harga_panas', 50)->nullable()->after('harga_dingin');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('menus', function (Blueprint $table) {
            $table->dropColumn(['dibuat_di', 'harga_dingin', 'harga_panas']);
        });
    }
};
