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
            // Nominal diskon paket per baris booking agar halaman pembayaran
            // bisa menampilkan harga asli lalu keterangan diskon paket.
            $table->string('diskon_paket', 50)->default('0')->after('total_harga');
            $table->decimal('diskon_paket_persen', 5, 2)->nullable()->after('diskon_paket');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['diskon_paket', 'diskon_paket_persen']);
        });
    }
};
