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
        Schema::create('mahjong_scans', function (Blueprint $table) {
            $table->id();
            $table->string('id_pengguna', 50)->index();
            $table->string('image_path')->nullable();
            $table->json('tiles')->nullable();
            $table->json('combinations')->nullable();
            $table->json('unmatched')->nullable();
            $table->string('message')->nullable();
            $table->boolean('is_winning_hand')->default(false);
            $table->unsignedInteger('points')->default(0);
            $table->string('status', 20)->default('pending');
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mahjong_scans');
    }
};
