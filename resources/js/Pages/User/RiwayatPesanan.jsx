import { Head, Link, router } from '@inertiajs/react';
import UserLayout from '../../Layouts/UserLayout';

const statusMeta = {
    baru: {
        label: 'Baru',
        badge: 'bg-amber-50 text-amber-700 ring-amber-200',
        dot: 'bg-amber-500',
    },
    diproses: {
        label: 'Diproses',
        badge: 'bg-sky-50 text-sky-700 ring-sky-200',
        dot: 'bg-sky-500',
    },
    selesai: {
        label: 'Selesai',
        badge: 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        dot: 'bg-emerald-500',
    },
    dibatalkan: {
        label: 'Dibatalkan',
        badge: 'bg-rose-50 text-rose-700 ring-rose-200',
        dot: 'bg-rose-500',
    },
};

const tabStatus = [
    { key: '', label: 'Semua' },
    { key: 'baru', label: 'Baru' },
    { key: 'diproses', label: 'Diproses' },
    { key: 'selesai', label: 'Selesai' },
    { key: 'dibatalkan', label: 'Dibatalkan' },
];

function formatRupiah(value) {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(Number(value) || 0);
}

function formatTanggal(value) {
    if (!value) {
        return '-';
    }

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
        return '-';
    }

    return new Intl.DateTimeFormat('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    }).format(date);
}

function labelHalaman(label) {
    if (label.includes('Previous')) {
        return '‹';
    }

    if (label.includes('Next')) {
        return '›';
    }

    return label;
}

function ClockIcon({ className = 'h-4 w-4' }) {
    return (
        <svg xmlns="http://www.w3.org/2000/svg" className={className} fill="none" viewBox="0 0 24 24" strokeWidth={1.8} stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" d="M12 6v6l4 2m6-2a10 10 0 1 1-20 0 10 10 0 0 1 20 0Z" />
        </svg>
    );
}

function ReceiptIcon({ className = 'h-8 w-8' }) {
    return (
        <svg xmlns="http://www.w3.org/2000/svg" className={className} fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" d="M9 8.25h6m-6 3h6m-6 3h3.75M6.75 3h10.5A1.5 1.5 0 0 1 18.75 4.5v16.06a.75.75 0 0 1-1.14.64L15 19.5l-2.1 1.4a1.5 1.5 0 0 1-1.66 0L9.15 19.5l-2.61 1.7a.75.75 0 0 1-1.14-.64V4.5A1.5 1.5 0 0 1 6.75 3Z" />
        </svg>
    );
}

function ArrowLeftIcon({ className = 'h-4 w-4' }) {
    return (
        <svg xmlns="http://www.w3.org/2000/svg" className={className} fill="none" viewBox="0 0 24 24" strokeWidth={2} stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
        </svg>
    );
}

function StatusBadge({ status }) {
    const meta = statusMeta[status] ?? statusMeta.baru;

    return (
        <span className={`inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[11px] font-semibold ring-1 ${meta.badge}`}>
            <span className={`h-1.5 w-1.5 rounded-full ${meta.dot}`} />
            {meta.label}
        </span>
    );
}

function BadgeSuhu({ suhu }) {
    if (!suhu) {
        return null;
    }

    return (
        <span className="rounded-full bg-sky-50 px-1.5 py-0.5 text-[9px] font-bold uppercase tracking-wide text-sky-700 ring-1 ring-sky-100">
            {suhu}
        </span>
    );
}

function BadgeTujuan({ tujuan }) {
    if (!tujuan) {
        return null;
    }

    return (
        <span
            className={`rounded-full px-1.5 py-0.5 text-[9px] font-bold uppercase tracking-wide ring-1 ${tujuan === 'Bar'
                    ? 'bg-indigo-50 text-indigo-700 ring-indigo-100'
                    : 'bg-amber-50 text-amber-700 ring-amber-100'
                }`}
        >
            {tujuan}
        </span>
    );
}

export default function RiwayatPesanan({ pesanans, ringkasan, filters }) {
    const statusAktif = filters?.status ?? '';
    const daftarPesanan = Array.isArray(pesanans?.data) ? pesanans.data : [];
    const tautanHalaman = Array.isArray(pesanans?.links) ? pesanans.links : [];

    const pilihStatus = (status) => {
        router.get(
            '/pesan/riwayat',
            status ? { status } : {},
            { preserveState: true, replace: true },
        );
    };

    return (
        <>
            <Head title="Riwayat Pesanan" />

            <header className="relative overflow-hidden rounded-b-[2rem] bg-gradient-to-br from-emerald-600 via-emerald-700 to-emerald-900 px-5 pb-14 pt-6 text-white">
                <div className="pointer-events-none absolute -right-12 -top-12 h-44 w-44 rounded-full bg-white/10" />
                <div className="pointer-events-none absolute -bottom-20 -left-12 h-48 w-48 rounded-full bg-emerald-300/20" />

                <div className="relative">
                    <Link
                        href="/pesan"
                        className="inline-flex items-center gap-1.5 rounded-full bg-white/15 px-3 py-1.5 text-[10px] font-bold uppercase tracking-widest ring-1 ring-white/20 transition hover:bg-white/25"
                    >
                        <ArrowLeftIcon className="h-3.5 w-3.5" />
                        Kembali ke Pesan
                    </Link>

                    <h1 className="mt-3 text-xl font-bold leading-snug">Riwayat Pesanan</h1>
                    <p className="mt-1 max-w-[18rem] text-xs leading-relaxed text-emerald-100">
                        Semua pesanan makanan yang kamu buat dengan akun ini beserta statusnya.
                    </p>

                    <div className="mt-5 grid grid-cols-3 gap-2 text-center">
                        {[
                            { label: 'Total', value: ringkasan?.total ?? 0 },
                            { label: 'Diproses', value: ringkasan?.diproses ?? 0 },
                            { label: 'Selesai', value: ringkasan?.selesai ?? 0 },
                        ].map((kartu) => (
                            <div key={kartu.label} className="rounded-2xl bg-white/10 px-2 py-3 ring-1 ring-white/15 backdrop-blur">
                                <p className="text-base font-bold">{kartu.value}</p>
                                <p className="mt-0.5 text-[10px] font-semibold uppercase tracking-wide text-emerald-100">
                                    {kartu.label}
                                </p>
                            </div>
                        ))}
                    </div>
                </div>
            </header>

            <main className="relative -mt-8 space-y-4 px-5 pb-8">
                <div className="grid grid-cols-2 gap-1.5 rounded-2xl border border-emerald-100 bg-white p-1.5 shadow-sm">
                    <Link
                        href="/riwayat"
                        className="flex items-center justify-center gap-1.5 rounded-xl px-3 py-2.5 text-[11px] font-bold text-emerald-700 transition hover:bg-emerald-50"
                    >
                        Booking Meja
                    </Link>
                    <Link
                        href="/riwayat/pesanan"
                        className="flex items-center justify-center gap-1.5 rounded-xl bg-gradient-to-br from-emerald-600 to-emerald-700 px-3 py-2.5 text-[11px] font-bold text-white shadow-md shadow-emerald-600/25"
                    >
                        Pesanan Makanan
                    </Link>
                </div>

                {/* <div className="rounded-2xl border border-emerald-100 bg-white p-2.5 shadow-lg shadow-emerald-900/5">
                    <div className="flex gap-2 overflow-x-auto pb-0.5 [-ms-overflow-style:none] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                        {tabStatus.map((tab) => {
                            const aktif = statusAktif === tab.key;

                            return (
                                <button
                                    key={tab.key || 'semua'}
                                    type="button"
                                    onClick={() => pilihStatus(tab.key)}
                                    aria-pressed={aktif}
                                    className={`flex shrink-0 items-center rounded-xl px-3 py-2 text-[11px] font-semibold transition-all duration-200 active:scale-95 ${
                                        aktif
                                            ? 'bg-gradient-to-br from-emerald-600 to-emerald-700 text-white shadow-md shadow-emerald-600/25'
                                            : 'bg-emerald-50/70 text-emerald-700 ring-1 ring-inset ring-emerald-100 hover:bg-emerald-100'
                                    }`}
                                >
                                    {tab.label}
                                </button>
                            );
                        })}
                    </div>
                </div> */}

                {daftarPesanan.length === 0 ? (
                    <div className="flex flex-col items-center rounded-2xl border border-emerald-100 bg-white px-6 py-12 text-center shadow-sm">
                        <span className="flex h-16 w-16 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-500">
                            <ReceiptIcon />
                        </span>
                        <h2 className="mt-4 text-sm font-bold text-slate-800">
                            {statusAktif ? 'Belum ada pesanan di status ini' : 'Belum ada riwayat pesanan'}
                        </h2>
                        <p className="mt-1 max-w-[15rem] text-xs leading-relaxed text-slate-500">
                            {statusAktif
                                ? 'Coba pilih status lain untuk melihat pesananmu.'
                                : 'Pesanan yang kamu buat lewat halaman Pesan akan muncul di sini.'}
                        </p>
                        <Link
                            href="/pesan"
                            className="mt-5 inline-flex items-center justify-center rounded-xl bg-emerald-600 px-4 py-2.5 text-xs font-bold text-white shadow-md shadow-emerald-500/30 transition hover:bg-emerald-700"
                        >
                            Pesan Sekarang
                        </Link>
                    </div>
                ) : (
                    <div className="space-y-3">
                        {daftarPesanan.map((pesanan) => (
                            <article
                                key={pesanan.id}
                                className="overflow-hidden rounded-2xl border border-emerald-100 bg-white shadow-sm"
                            >
                                <div className="flex items-start justify-between gap-3 border-b border-slate-100 px-4 py-3.5">
                                    <div className="min-w-0">
                                        <p className="truncate text-sm font-bold text-slate-800">
                                            {pesanan.kode_pesanan}
                                        </p>
                                        <p className="mt-1 flex flex-wrap items-center gap-x-1.5 gap-y-1 text-[11px] text-slate-500">
                                            <span className="inline-flex items-center gap-1">
                                                <ClockIcon className="h-3 w-3" />
                                                {formatTanggal(pesanan.created_at)}
                                            </span>
                                            <span className="text-slate-300">•</span>
                                            <span className="font-semibold text-slate-700">Meja {pesanan.nomor_meja}</span>
                                        </p>
                                    </div>
                                    <StatusBadge status={pesanan.status} />
                                </div>

                                <div className="px-4 py-3">
                                    <ul className="space-y-1.5">
                                        {(pesanan.details ?? []).map((detail) => (
                                            <li key={detail.id} className="flex items-start justify-between gap-3 text-xs">
                                                <span className="flex min-w-0 flex-wrap items-center gap-1.5 text-slate-600">
                                                    <span className="font-semibold text-slate-800">{detail.jumlah}×</span>
                                                    <span className="truncate">{detail.nama_menu}</span>
                                                    <BadgeSuhu suhu={detail.suhu} />
                                                    <BadgeTujuan tujuan={detail.dibuat_di} />
                                                </span>
                                                <span className="shrink-0 font-medium text-slate-500">
                                                    {formatRupiah(detail.subtotal)}
                                                </span>
                                            </li>
                                        ))}
                                    </ul>

                                    {pesanan.catatan && (
                                        <p className="mt-2.5 rounded-xl bg-amber-50 px-3 py-2 text-[11px] leading-relaxed text-amber-800">
                                            <span className="font-semibold">Catatan:</span> {pesanan.catatan}
                                        </p>
                                    )}
                                </div>

                                <div className="flex items-center justify-between border-t border-slate-100 bg-emerald-50/40 px-4 py-3">
                                    <span className="text-[11px] font-semibold text-emerald-800">Total Pembayaran</span>
                                    <span className="text-sm font-bold text-emerald-700">{formatRupiah(pesanan.total)}</span>
                                </div>
                            </article>
                        ))}

                        {(pesanans?.last_page ?? 1) > 1 && (
                            <div className="flex items-center justify-center gap-1.5 pt-1">
                                {tautanHalaman.map((tautan, index) =>
                                    tautan.url ? (
                                        <Link
                                            key={`${tautan.label}-${index}`}
                                            href={tautan.url}
                                            preserveState
                                            className={`flex h-8 min-w-8 items-center justify-center rounded-lg px-2 text-xs font-semibold transition ${tautan.active
                                                    ? 'bg-emerald-600 text-white shadow-sm'
                                                    : 'bg-white text-slate-600 ring-1 ring-slate-200 hover:bg-emerald-50'
                                                }`}
                                        >
                                            {labelHalaman(tautan.label)}
                                        </Link>
                                    ) : (
                                        <span
                                            key={`${tautan.label}-${index}`}
                                            className="flex h-8 min-w-8 items-center justify-center rounded-lg px-2 text-xs font-semibold text-slate-300"
                                        >
                                            {labelHalaman(tautan.label)}
                                        </span>
                                    ),
                                )}
                            </div>
                        )}
                    </div>
                )}
            </main>
        </>
    );
}

RiwayatPesanan.layout = (page) => <UserLayout>{page}</UserLayout>;
