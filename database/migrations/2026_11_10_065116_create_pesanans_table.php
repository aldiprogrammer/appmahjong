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
        Schema::create('pesanans', function (Blueprint $table) {
            $table->id();
            $table->string('kode_pesanan', 30)->unique();
            $table->string('id_pengguna', 50)->nullable();
            $table->string('nomor_meja', 20);
            $table->string('nama_pemesan', 100)->nullable();
            $table->string('catatan', 255)->nullable();
            $table->unsignedInteger('total')->default(0);
            $table->string('status', 20)->default('baru');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pesanans');
    }
};
