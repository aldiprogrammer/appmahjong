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
        Schema::table('statusvouchers', function (Blueprint $table) {
            $table->date('tanggal_mulai')->nullable()->after('jumah_jam');
            $table->date('tanggal_berakhir')->nullable()->after('tanggal_mulai');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('statusvouchers', function (Blueprint $table) {
            $table->dropColumn(['tanggal_mulai', 'tanggal_berakhir']);
        });
    }
};
