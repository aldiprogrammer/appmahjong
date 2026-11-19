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
        Schema::create('notifikasi_penggunas', function (Blueprint $table) {
            $table->id();
            $table->string('id_pengguna', 50);
            $table->string('tipe', 30)->default('booking');
            $table->string('judul');
            $table->text('pesan');
            $table->string('kode_booking', 50)->nullable();
            $table->boolean('dibaca')->default(false);
            $table->timestamps();

            $table->index(['id_pengguna', 'dibaca']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifikasi_penggunas');
    }
};
