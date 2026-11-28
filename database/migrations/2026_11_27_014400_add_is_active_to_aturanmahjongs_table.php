<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('aturanmahjongs', function (Blueprint $table) {
            $table->boolean('is_active')->default(false)->after('nama_aturan');
        });

        // Aturan lama tetap dipakai sampai admin memilih aturan aktif yang lain.
        $idPertama = DB::table('aturanmahjongs')->min('id');

        if ($idPertama !== null) {
            DB::table('aturanmahjongs')
                ->where('id', $idPertama)
                ->update(['is_active' => true]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('aturanmahjongs', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });
    }
};
