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
        Schema::create('paket_hargajammains', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vanue_id')->nullable()->constrained('vanues')->nullOnDelete();
            $table->string('hari', 50);
            $table->unsignedInteger('minimal_jam');
            $table->decimal('diskon_persen', 5, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('paket_hargajammains');
    }
};
