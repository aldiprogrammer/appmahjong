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
            // Null berarti paket memakai diskon persen, bukan harga tetap.
            $table->decimal('harga_paket', 12, 2)->nullable()->after('diskon_persen');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('paket_hargajammains', function (Blueprint $table) {
            $table->dropColumn('harga_paket');
        });
    }
};
