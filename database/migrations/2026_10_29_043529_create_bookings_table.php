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
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('kode_booking', 50);
            $table->string('id_pengguna', 50);
            $table->string('id_meja', 50);
            $table->string('jam_mulai', 20);
            $table->string('jam_selesai', 20);
            $table->string('tgl_main', 20);
            $table->string('tgl_booking', 20);
            $table->string('sistem_pembayaran', 50);
            $table->string('harga', 50);
            $table->string('total_harga', 50);
            $table->string('bukti_pembayaran', 200);
            $table->string('status_booking', 11);
            $table->string('status_hapus', 11);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
