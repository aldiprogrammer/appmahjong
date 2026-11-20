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
            // Booking manual admin menyimpan identitas tamu langsung di booking
            // tanpa membuat akun pengguna, sehingga id_pengguna jadi opsional.
            $table->string('nama_pengguna', 100)->nullable()->after('id_pengguna');
            $table->string('no_wa', 20)->nullable()->after('nama_pengguna');
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->string('id_pengguna', 50)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->string('id_pengguna', 50)->nullable(false)->change();
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['nama_pengguna', 'no_wa']);
        });
    }
};
