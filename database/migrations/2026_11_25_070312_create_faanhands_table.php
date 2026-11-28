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
        Schema::create('faanhands', function (Blueprint $table) {
            $table->id();
            $table->string('id_aturanmahjong', 11);
            $table->string('nama_faan', 50);
            $table->string('inggris', 50);
            $table->string('faan_asli', 11);
            $table->string('faan', 11);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('faanhands');
    }
};
