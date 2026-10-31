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
        Schema::table('bookings', function (Blueprint $table) {
            $table->string('kode_invoice', 50)->nullable()->after('kode_booking');
            $table->string('status_pembayaran', 30)->default('belum_bayar')->after('status_booking');
            $table->timestamp('dibayar_pada')->nullable()->after('status_pembayaran');
            $table->index('kode_invoice');
        });

        DB::table('bookings')->orderBy('id')->each(function (object $booking): void {
            $statusPembayaran = 'belum_bayar';

            if ((string) $booking->status_booking === '200') {
                $statusPembayaran = 'lunas';
            } elseif ((string) $booking->bukti_pembayaran !== '') {
                $statusPembayaran = 'menunggu_verifikasi';
            }

            DB::table('bookings')
                ->where('id', $booking->id)
                ->update([
                    'kode_invoice' => $booking->kode_invoice ?: $booking->kode_booking,
                    'status_pembayaran' => $statusPembayaran,
                ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropIndex(['kode_invoice']);
            $table->dropColumn(['kode_invoice', 'status_pembayaran', 'dibayar_pada']);
        });
    }
};
