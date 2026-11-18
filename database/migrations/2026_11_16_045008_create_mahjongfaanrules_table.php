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
        Schema::create('mahjongfaanrules', function (Blueprint $table) {
            $table->id();
            $table->string('id_aturanpoint', 11);
            $table->string('kode', 50);
            $table->string('nama', 50);
            $table->string('faan_value', 50);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mahjongfaanrules');
    }
};
