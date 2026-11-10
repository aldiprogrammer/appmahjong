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
        Schema::create('settingbookingrooms', function (Blueprint $table) {
            $table->id();
            $table->string('pembayaran', 50);
            $table->string('jumlah_user', 11);
            $table->string('minimal_user', 11);
            $table->string('maksimal_user', 11);
            $table->string('status', 50);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settingbookingrooms');
    }
};
