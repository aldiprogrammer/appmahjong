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
        Schema::create('notifikasi_bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vanue_id')->nullable()->constrained('vanues')->cascadeOnDelete();
            $table->string('tipe', 30)->default('booking');
            $table->string('judul');
            $table->text('pesan');
            $table->string('kode_booking', 50)->nullable();
            $table->boolean('dibaca')->default(false);
            $table->timestamps();

            $table->index(['vanue_id', 'dibaca']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifikasi_bookings');
    }
};
