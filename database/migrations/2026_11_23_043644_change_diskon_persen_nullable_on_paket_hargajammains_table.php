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
            // Null berarti paket memakai harga tetap, bukan diskon persen.
            $table->decimal('diskon_persen', 5, 2)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('paket_hargajammains', function (Blueprint $table) {
            $table->decimal('diskon_persen', 5, 2)->nullable(false)->change();
        });
    }
};
