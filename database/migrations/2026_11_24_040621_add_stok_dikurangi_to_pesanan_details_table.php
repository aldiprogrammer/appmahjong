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
        Schema::table('pesanan_details', function (Blueprint $table) {
            $table->boolean('stok_dikurangi')->default(false)->after('subtotal');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pesanan_details', function (Blueprint $table) {
            $table->dropColumn('stok_dikurangi');
        });
    }
};
