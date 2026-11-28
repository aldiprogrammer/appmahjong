<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const MENU = 'faan-hands';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $admins = DB::table('admins')
            ->whereNotNull('hak_akses')
            ->get(['id', 'hak_akses']);

        foreach ($admins as $admin) {
            $hakAkses = json_decode((string) $admin->hak_akses, true);

            if (! is_array($hakAkses) || in_array(self::MENU, $hakAkses, true)) {
                continue;
            }

            $hakAkses[] = self::MENU;

            DB::table('admins')
                ->where('id', $admin->id)
                ->update(['hak_akses' => json_encode(array_values($hakAkses))]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $admins = DB::table('admins')
            ->whereNotNull('hak_akses')
            ->get(['id', 'hak_akses']);

        foreach ($admins as $admin) {
            $hakAkses = json_decode((string) $admin->hak_akses, true);

            if (! is_array($hakAkses) || ! in_array(self::MENU, $hakAkses, true)) {
                continue;
            }

            $hakAkses = array_values(array_filter(
                $hakAkses,
                fn (string $menu): bool => $menu !== self::MENU,
            ));

            DB::table('admins')
                ->where('id', $admin->id)
                ->update(['hak_akses' => json_encode($hakAkses)]);
        }
    }
};
