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
        Schema::create('aturanpointvenues', function (Blueprint $table) {
            $table->id();
            $table->string('nama_aturan', 200);
            $table->string('slug', 200);
            $table->string('deskripsi', 200);
            $table->string('min_faan', 20);
            $table->string('max_faan', 20);
            $table->string('status', 50);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('aturanpointvenues');
    }
};
