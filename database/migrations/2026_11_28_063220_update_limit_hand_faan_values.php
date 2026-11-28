<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Samakan nilai limit hand AM001 dengan HK klasik Tilerank.
     */
    public function up(): void
    {
        $this->ubahNilai('5', '13');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->ubahNilai('13', '5');
    }

    private function ubahNilai(string $dari, string $ke): void
    {
        $kategoriId = DB::table('kategorifaans')->where('nama', 'Limit hand')->value('id');
        $aturanIds = DB::table('aturanmahjongs')->where('kode', 'AM001')->pluck('id');

        if ($kategoriId === null || $aturanIds->isEmpty()) {
            return;
        }

        DB::table('faanhands')
            ->whereIn('id_aturanmahjong', $aturanIds->all())
            ->where('id_kategorifaan', $kategoriId)
            ->where('faan', $dari)
            ->update([
                'faan_asli' => $ke,
                'faan' => $ke,
            ]);
    }
};
