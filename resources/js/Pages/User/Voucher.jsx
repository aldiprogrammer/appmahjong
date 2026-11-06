import { Deferred, Head, Link, router, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
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

function VoucherSkeletonCard() {
    return (
        <div className="overflow-hidden rounded-2xl border border-emerald-100 bg-white p-4 shadow-sm">
            <div className="flex items-center justify-between gap-3">
                <div className="space-y-2">
                    <div className="h-2.5 w-20 rounded bg-slate-100" />
                    <div className="h-4 w-28 rounded bg-slate-200" />
                </div>
                <div className="h-6 w-24 rounded-full bg-emerald-100" />
            </div>

            <div className="mt-3 grid grid-cols-2 gap-3">
                <div className="space-y-1.5">
                    <div className="h-2.5 w-16 rounded bg-slate-100" />
                    <div className="h-3 w-24 rounded bg-slate-200" />
                </div>
                <div className="space-y-1.5">
                    <div className="h-2.5 w-16 rounded bg-slate-100" />
                    <div className="h-3 w-20 rounded bg-slate-200" />
                </div>
                <div className="space-y-1.5">
                    <div className="h-2.5 w-14 rounded bg-slate-100" />
                    <div className="h-3 w-20 rounded bg-slate-200" />
                </div>
                <div className="space-y-1.5">
                    <div className="h-2.5 w-14 rounded bg-slate-100" />
                    <div className="h-3 w-16 rounded bg-slate-200" />
                </div>
            </div>

            <div className="mt-4 h-10 w-full rounded-xl bg-emerald-100" />
        </div>
    );
}

function VoucherSkeleton({ count = 2 }) {
    return (
        <div aria-busy="true" aria-label="Memuat voucher" className="space-y-4">
            {Array.from({ length: count }).map((_, index) => (
                <div key={index} className="animate-pulse">
                    <VoucherSkeletonCard />
                </div>
            ))}
        </div>
    );
}

function DeferredTimeoutError({ onReload }) {
    return (
        <div
            role="status"
            className="flex flex-col items-center rounded-2xl border border-amber-200 bg-white px-6 py-10 text-center shadow-sm"
        >
            <span className="flex h-12 w-12 items-center justify-center rounded-full bg-amber-50 text-amber-500">
                <svg xmlns="http://www.w3.org/2000/svg" className="h-6 w-6" fill="none" viewBox="0 0 24 24" strokeWidth={1.8} stroke="currentColor">
                    <path strokeLinecap="round" strokeLinejoin="round" d="M12 8v3.75m0 3h.008v.008H12v-.008ZM12 3.75 2.25 20.25h19.5L12 3.75Z" />
                </svg>
            </span>
            <h2 className="mt-3 text-sm font-bold text-slate-800">Data belum termuat</h2>
            <p className="mt-1 max-w-xs text-xs leading-relaxed text-slate-500">
                Memuat data memakan waktu lebih lama dari biasanya. Coba muat ulang.
            </p>
            <button
                type="button"
                onClick={onReload}
                className="mt-4 inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2 text-xs font-bold text-white shadow-sm transition hover:bg-emerald-700"
            >
                Coba Lagi
            </button>
        </div>
    );
}

export default function Voucher({ pengguna, vouchers = [] }) {
    // vouchers sengaja di-default agar aman saat deferred (belum dimuat).
    const voucherDimuat = usePage().props.vouchers !== undefined;

    const [deferredStuck, setDeferredStuck] = useState(false);
    const [deferredRetry, setDeferredRetry] = useState(0);

    useEffect(() => {
        if (voucherDimuat) {
            setDeferredStuck(false);

            return;
        }

        // Jangan biarkan halaman macet selamanya saat request deferred gagal senyap.
        const timer = setTimeout(() => setDeferredStuck(true), 10000);

        return () => clearTimeout(timer);
    }, [voucherDimuat, deferredRetry]);

    const retryDeferred = () => {
        setDeferredStuck(false);
        setDeferredRetry((count) => count + 1);
        router.reload({ only: ['vouchers'] });
    };

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
                <Deferred
                    data={['vouchers']}
                    // fallback wajib truthy di @inertiajs/react (throw jika falsy) — pakai skeleton, bukan null.
                    fallback={deferredStuck ? <DeferredTimeoutError onReload={retryDeferred} /> : <VoucherSkeleton />}
                >
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
                </Deferred>

                <div className="rounded-2xl border border-emerald-100 bg-white p-4 text-xs text-slate-500">
                    Hello <span className="font-semibold text-slate-700">{namaPengguna || '-'}</span>. Pilih voucher di halaman booking sebelum membuat booking.
                </div>
            </main>
        </>
    );
}

Voucher.layout = (page) => <UserLayout>{page}</UserLayout>;
