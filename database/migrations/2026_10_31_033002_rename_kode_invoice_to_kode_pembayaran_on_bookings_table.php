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
        Schema::table('bookings', function (Blueprint $table) {
            $table->renameColumn('kode_invoice', 'kode_pembayaran');
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->dropIndex(['kode_invoice']);
            $table->index('kode_pembayaran');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropIndex(['kode_pembayaran']);
            $table->renameColumn('kode_pembayaran', 'kode_invoice');
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->index('kode_invoice');
        });
    }
};
