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
            // Nominal harga paket yang benar-benar dipakai per sesi. Null berarti
            // sesi memakai harga jam main normal (atau diskon persen).
            $table->string('harga_paket', 50)->nullable()->after('diskon_paket_persen');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn('harga_paket');
        });
    }
};
