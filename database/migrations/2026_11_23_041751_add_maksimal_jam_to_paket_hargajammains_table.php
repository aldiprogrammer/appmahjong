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
        Schema::table('paket_hargajammains', function (Blueprint $table) {
            // Null berarti paket tidak memiliki batas atas durasi.
            $table->unsignedInteger('maksimal_jam')->nullable()->after('minimal_jam');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('paket_hargajammains', function (Blueprint $table) {
            $table->dropColumn('maksimal_jam');
        });
    }
};
