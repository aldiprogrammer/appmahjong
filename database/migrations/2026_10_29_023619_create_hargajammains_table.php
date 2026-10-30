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
        Schema::create('hargajammains', function (Blueprint $table) {
            $table->id();
            $table->string("jam_main_id", 50);
            $table->string("hari", 50);
            $table->string("harga", 50);
            $table->string("diskon", 50);
            $table->string("harga_akhir", 50);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hargajammains');
    }
};
