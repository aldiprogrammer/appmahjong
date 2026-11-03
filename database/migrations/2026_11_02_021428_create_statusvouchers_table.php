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
        Schema::create('statusvouchers', function (Blueprint $table) {
            $table->id();
            $table->string('status_voucher', 50);
            $table->string('jumah_booking', 50);
            $table->string('jumah_jam', 50);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('statusvouchers');
    }
};
