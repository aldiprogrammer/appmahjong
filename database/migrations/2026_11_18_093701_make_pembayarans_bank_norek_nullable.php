<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Kolom bank / atas_nama / norek diizinkan null agar metode QRIS
        // (yang tidak memakai data rekening) dapat disimpan dengan nilai kosong.
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE `pembayarans` MODIFY `bank` VARCHAR(30) NULL');
            DB::statement('ALTER TABLE `pembayarans` MODIFY `atas_nama` VARCHAR(30) NULL');
            DB::statement('ALTER TABLE `pembayarans` MODIFY `norek` VARCHAR(30) NULL');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE `pembayarans` MODIFY `bank` VARCHAR(30) NOT NULL');
            DB::statement('ALTER TABLE `pembayarans` MODIFY `atas_nama` VARCHAR(30) NOT NULL');
            DB::statement('ALTER TABLE `pembayarans` MODIFY `norek` VARCHAR(30) NOT NULL');
        }
    }
};
