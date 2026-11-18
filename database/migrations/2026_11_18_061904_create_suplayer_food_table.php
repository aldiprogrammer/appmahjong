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
        Schema::create('suplayer_food', function (Blueprint $table) {
            $table->id();
            $table->string('kode_suplayer', 30);
            $table->string('nama_suplayer', 30);
            $table->string('alamat');
            $table->string('nowa', 30);
            $table->string('email', 30);
            $table->string('password', 30);
            $table->string('tangggl_daftar', 30);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('suplayer_food');
    }
};
