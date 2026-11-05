import { Head, Link } from '@inertiajs/react';
import UserLayout from '../../Layouts/UserLayout';

function formatDate(value) {
    if (!value) {
        return '-';
    }

    return new Intl.DateTimeFormat('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    }).format(new Date(value));
}

export default function Voucher({ pengguna, vouchers = [] }) {
    const namaPengguna = [pengguna?.nama_depan, pengguna?.nama_balakang].filter(Boolean).join(' ');

    return (
        <>
            <Head title="Voucher" />

            <header className="relative overflow-hidden rounded-b-[2rem] bg-gradient-to-br from-emerald-600 to-emerald-800 px-5 pb-12 pt-6 text-white">
                <div className="relative">
                    <h1 className="text-lg font-bold">Voucher Saya</h1>
                    <p className="text-xs text-emerald-100">Gunakan voucher untuk booking gratis sesuai kuota jam.</p>
                </div>
            </header>

            <main className="relative -mt-7 space-y-4 px-5 pb-8">
                {vouchers.length === 0 ? (
                    <div className="rounded-2xl border border-dashed border-slate-200 bg-white px-4 py-10 text-center text-sm text-slate-500">
                        Belum ada voucher. Voucher akan muncul jika akun anda memenuhi syarat.
                    </div>
                ) : (
                    vouchers.map((voucher) => (
                        <div
                            key={voucher.id}
                            className="overflow-hidden rounded-2xl border border-emerald-100 bg-white p-4 shadow-sm"
                        >
                            <div className="flex items-center justify-between gap-3">
                                <div>
                                    <p className="text-xs font-medium uppercase tracking-wide text-slate-400">Kode Voucher</p>
                                    <p className="mt-1 text-base font-bold text-emerald-700">{voucher.kode_voucher}</p>
                                </div>
                                <span
                                    className={`inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ${voucher.is_used
                                        ? 'bg-slate-100 text-slate-600 ring-slate-200'
                                        : 'bg-emerald-50 text-emerald-700 ring-emerald-100'
                                        }`}
                                >
                                    <span className={`h-1.5 w-1.5 rounded-full ${voucher.is_used ? 'bg-slate-400' : 'bg-emerald-500'}`} />
                                    {voucher.is_used ? 'Sudah Digunakan' : 'Belum Digunakan'}
                                </span>
                            </div>

                            <div className="mt-3 grid grid-cols-2 gap-3 text-xs">
                                <div>
                                    <p className="text-slate-400">Status Voucher</p>
                                    <p className="mt-0.5 font-semibold text-slate-800">{voucher.status_voucher ?? '-'}</p>
                                </div>
                                <div>
                                    <p className="text-slate-400">Kuota Jam</p>
                                    <p className="mt-0.5 font-semibold text-slate-800">{voucher.jumah_jam ?? '-'} jam</p>
                                </div>
                                <div>
                                    <p className="text-slate-400">Diterima</p>
                                    <p className="mt-0.5 font-semibold text-slate-800">{formatDate(voucher.tanggal_diterima)}</p>
                                </div>
                                <div>
                                    <p className="text-slate-400">Digunakan</p>
                                    <p className="mt-0.5 font-semibold text-slate-800">{formatDate(voucher.tanggal_digunakan)}</p>
                                </div>
                            </div>

                            {!voucher.is_used && (
                                <div className="mt-4">
                                    <Link
                                        href={`/booking?voucher=${encodeURIComponent(voucher.kode_voucher)}`}
                                        className="inline-flex w-full items-center justify-center rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-700"
                                    >
                                        Gunakan untuk Booking
                                    </Link>
                                </div>
                            )}
                        </div>
                    ))
                )}

                <div className="rounded-2xl border border-emerald-100 bg-white p-4 text-xs text-slate-500">
                    Hello <span className="font-semibold text-slate-700">{namaPengguna || '-'}</span>. Pilih voucher di halaman booking sebelum membuat booking.
                </div>
            </main>
        </>
    );
}

Voucher.layout = (page) => <UserLayout>{page}</UserLayout>;
