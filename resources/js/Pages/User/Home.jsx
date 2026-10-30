import { Head, Link, usePage } from '@inertiajs/react';
import UserLayout from '../../Layouts/UserLayout';

function formatRupiah(value) {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(Number(value) || 0);
}

function namaPengguna(pengguna) {
    if (!pengguna) {
        return 'Tamu';
    }

    return [pengguna.nama_depan, pengguna.nama_balakang].filter(Boolean).join(' ') || 'Tamu';
}

function BellIcon() {
    return (
        <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5" fill="none" viewBox="0 0 24 24" strokeWidth={1.8} stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
        </svg>
    );
}

function SearchIcon() {
    return (
        <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5" fill="none" viewBox="0 0 24 24" strokeWidth={1.8} stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" d="m21 21-4.35-4.35M17 11a6 6 0 1 1-12 0 6 6 0 0 1 12 0Z" />
        </svg>
    );
}

function ChevronRightIcon() {
    return (
        <svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4" fill="none" viewBox="0 0 24 24" strokeWidth={2} stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
        </svg>
    );
}

function FoodIcon({ className = 'h-5 w-5' }) {
    return (
        <svg xmlns="http://www.w3.org/2000/svg" className={className} fill="none" viewBox="0 0 24 24" strokeWidth={1.8} stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" d="M3.75 11.25h16.5a8.25 8.25 0 0 1-16.5 0ZM9 7.5c0-1.2 1.25-1.6 1.25-2.75M13.75 7.5c0-1.2 1.25-1.6 1.25-2.75" />
        </svg>
    );
}

function DrinkIcon({ className = 'h-5 w-5' }) {
    return (
        <svg xmlns="http://www.w3.org/2000/svg" className={className} fill="none" viewBox="0 0 24 24" strokeWidth={1.8} stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" d="M7.5 4.5h9l-1.1 14.1a1.5 1.5 0 0 1-1.5 1.4h-3.8a1.5 1.5 0 0 1-1.5-1.4L7.5 4.5ZM14.5 4.5 17 2M7.5 9h9" />
        </svg>
    );
}

function SnackIcon({ className = 'h-5 w-5' }) {
    return (
        <svg xmlns="http://www.w3.org/2000/svg" className={className} fill="none" viewBox="0 0 24 24" strokeWidth={1.8} stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" d="M6.75 9h10.5l-1.2 10.2a1.5 1.5 0 0 1-1.5 1.3H9.45a1.5 1.5 0 0 1-1.5-1.3L6.75 9ZM8 9a4 4 0 0 1 8 0M9.5 12.75v3.5M12 12.75v3.5M14.5 12.75v3.5" />
        </svg>
    );
}

function MahjongTile({ label, tone, className = '' }) {
    return (
        <span
            className={`flex h-16 w-12 items-center justify-center rounded-xl border-b-4 border-emerald-950/25 bg-gradient-to-b from-white to-emerald-50 shadow-xl ${className}`}
        >
            <span className={`text-2xl font-black leading-none ${tone}`}>{label}</span>
        </span>
    );
}

export default function Home({ pengguna, mejas, hargas }) {
    const { flash } = usePage().props;
    const nama = namaPengguna(pengguna);
    const inisial = nama.trim().charAt(0).toUpperCase() || 'M';
    const maxDiskon = Math.max(...hargas.map((harga) => Number(harga.diskon) || 0), 0);

    return (
        <>
            <Head title="Home" />

            <header className="relative overflow-hidden rounded-b-[2rem] bg-gradient-to-br from-emerald-600 via-emerald-700 to-emerald-900 px-5 pb-20 pt-6 text-white">
                <div className="pointer-events-none absolute -right-12 -top-12 h-44 w-44 rounded-full bg-white/10" />
                <div className="pointer-events-none absolute -bottom-20 -left-12 h-48 w-48 rounded-full bg-emerald-300/20" />

                <div className="relative">
                    <div className="flex items-center justify-between">
                        <div className="flex items-center gap-3">
                            <Link
                                href="/profil"
                                aria-label="Buka profil"
                                className="flex h-11 w-11 items-center justify-center rounded-2xl bg-white/15 text-lg font-bold ring-1 ring-white/25 transition hover:bg-white/25"
                            >
                                {inisial}
                            </Link>
                            <div className="leading-tight">
                                <p className="text-base font-semibold">{nama}</p>
                                <p className="text-xs text-emerald-200">Main nyaman, booking mudah</p>
                            </div>
                        </div>
                        <button
                            type="button"
                            className="relative flex h-10 w-10 items-center justify-center rounded-full bg-white/10 ring-1 ring-white/20 transition hover:bg-white/20"
                            aria-label="Notifikasi"
                        >
                            <BellIcon />
                            <span className="absolute right-2.5 top-2.5 h-2 w-2 rounded-full bg-rose-500 ring-2 ring-emerald-700" />
                        </button>
                    </div>

                    <div className="mt-7">
                        <p className="text-sm text-emerald-100">Halo, {nama}</p>
                        <h1 className="mt-1 text-2xl font-bold leading-snug">Siap main mahjong hari ini?</h1>
                    </div>

                    <Link
                        href="/booking"
                        className="mt-5 flex items-center gap-3 rounded-2xl bg-white px-4 py-3.5 text-sm text-slate-500 shadow-lg shadow-emerald-950/20 transition hover:bg-emerald-50"
                    >
                        <SearchIcon />
                        <span className="flex-1">Cari meja favoritmu...</span>
                        <span className="rounded-full bg-emerald-600 px-3 py-1 text-xs font-semibold text-white">
                            Booking
                        </span>
                    </Link>
                </div>
            </header>

            <div className="relative -mt-12 space-y-6 px-5 pb-8">
                {flash?.success && (
                    <div className="flex items-start gap-3 rounded-2xl border border-emerald-200 bg-white px-4 py-3 text-sm text-emerald-800 shadow-sm">
                        <span className="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-emerald-600 text-white">
                            <svg xmlns="http://www.w3.org/2000/svg" className="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" strokeWidth={2.4} stroke="currentColor">
                                <path strokeLinecap="round" strokeLinejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                            </svg>
                        </span>
                        <p className="font-medium leading-relaxed">{flash.success}</p>
                    </div>
                )}

                {!pengguna && (
                    <div className="rounded-2xl border border-emerald-100 bg-white p-4 shadow-sm">
                        <p className="text-sm font-bold text-slate-800">Masuk untuk mulai booking</p>
                        <p className="mt-0.5 text-xs leading-relaxed text-slate-500">
                            Daftar akun atau masuk dengan nomor WhatsApp dan email yang terdaftar.
                        </p>
                        <div className="mt-3 flex gap-2">
                            <Link
                                href="/register"
                                className="flex-1 rounded-xl bg-emerald-600 px-4 py-2.5 text-center text-xs font-bold text-white transition hover:bg-emerald-700"
                            >
                                Daftar
                            </Link>
                            <Link
                                href="/login"
                                className="flex-1 rounded-xl border border-emerald-200 bg-white px-4 py-2.5 text-center text-xs font-bold text-emerald-700 transition hover:bg-emerald-50"
                            >
                                Masuk
                            </Link>
                        </div>
                    </div>
                )}

                <section className="relative overflow-hidden rounded-3xl bg-gradient-to-br from-emerald-700 via-emerald-800 to-emerald-950 p-5 text-white shadow-xl shadow-emerald-900/25">
                    <div className="pointer-events-none absolute -left-10 -top-12 h-32 w-32 rounded-full bg-emerald-400/20 blur-2xl" />
                    <div className="pointer-events-none absolute -bottom-16 -right-10 h-40 w-40 rounded-full bg-lime-300/10 blur-2xl" />
                    <div
                        className="pointer-events-none absolute inset-0 opacity-[0.08]"
                        style={{
                            backgroundImage: 'radial-gradient(circle, #ffffff 1px, transparent 1px)',
                            backgroundSize: '14px 14px',
                        }}
                    />

                    <div className="relative flex items-center gap-3">
                        <div className="min-w-0 flex-1">
                            {/* <span className="inline-flex items-center gap-1.5 rounded-full bg-white/15 px-2.5 py-1 text-[10px] font-bold uppercase tracking-widest ring-1 ring-white/20">
                                <span className="h-1.5 w-1.5 rounded-full bg-lime-300" />
                                Ayo Main
                            </span> */}
                            <h2 className="mt-2.5 text-2xl font-black leading-tight">
                                Serunya Main
                                <br />
                                Mahjong Bareng
                            </h2>
                            <p className="mt-1.5 max-w-[15rem] text-xs leading-relaxed text-emerald-100">
                                Ajak temanmu, pilih meja favorit, dan nikmati serunya bermain mahjong bersama.
                            </p>
                            <Link
                                href="/booking"
                                className="mt-3.5 inline-flex items-center gap-1.5 rounded-full bg-white px-4 py-2 text-xs font-bold text-emerald-800 shadow-md transition hover:bg-emerald-50"
                            >
                                Booking Sekarang
                                <ChevronRightIcon />
                            </Link>
                        </div>

                        <div className="relative flex shrink-0 items-center">
                            <MahjongTile label="中" tone="text-rose-600" className="-mr-5 -rotate-12" />
                            <MahjongTile label="發" tone="text-emerald-600" className="z-10 rotate-3" />
                        </div>
                    </div>
                </section>

                <Link
                    href="/booking"
                    className="relative flex items-center gap-4 overflow-hidden rounded-2xl bg-gradient-to-r from-rose-500 to-rose-600 p-4 text-white shadow-lg shadow-rose-200"
                >
                    <div className="pointer-events-none absolute -right-6 -top-8 h-24 w-24 rounded-full bg-white/15" />
                    <div className="pointer-events-none absolute -bottom-10 right-12 h-20 w-20 rounded-full bg-white/10" />
                    <div className="relative flex-1">
                        <span className="inline-flex rounded-full bg-white/20 px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wide">
                            Promo
                        </span>
                        <p className="mt-2 text-base font-bold leading-tight">
                            {maxDiskon > 0 ? `Diskon hingga ${formatRupiah(maxDiskon)}` : 'Harga spesial setiap hari'}
                        </p>
                        <p className="mt-1 text-xs text-rose-50">Khusus booking meja pilihan.</p>
                    </div>
                    <span className="relative flex h-10 w-10 items-center justify-center rounded-full bg-white text-rose-600">
                        <ChevronRightIcon />
                    </span>
                </Link>

                <section>
                    <div className="mb-3 flex items-end justify-between">
                        <div>
                            <h2 className="text-sm font-bold text-slate-800">Meja Tersedia</h2>
                            <p className="text-xs text-slate-500">Pilihan meja terbaik untukmu</p>
                        </div>
                        <Link href="/booking" className="text-xs font-semibold text-emerald-700">
                            Lihat semua
                        </Link>
                    </div>

                    {mejas.length === 0 ? (
                        <div className="rounded-2xl border border-dashed border-emerald-200 bg-white p-4 text-center text-xs text-slate-500">
                            Belum ada meja yang tersedia.
                        </div>
                    ) : (
                        <div className="grid grid-cols-2 gap-3">
                            {mejas.map((meja) => (
                                <Link
                                    key={meja.id}
                                    href="/booking"
                                    className="group overflow-hidden rounded-2xl border border-emerald-100 bg-white shadow-sm transition duration-300 hover:-translate-y-0.5 hover:border-emerald-300 hover:shadow-lg hover:shadow-emerald-900/10"
                                >
                                    <div className="relative aspect-[4/3] overflow-hidden bg-emerald-900">
                                        <img
                                            src="/images/meja-mahjong.svg"
                                            alt={`Meja ${meja.nama_meja}`}
                                            loading="lazy"
                                            className="h-full w-full object-cover transition duration-500 group-hover:scale-105"
                                        />
                                        <div className="absolute inset-0 bg-gradient-to-t from-emerald-950/80 via-emerald-950/10 to-transparent" />
                                        <span className="absolute left-2 top-2 rounded-full bg-white/90 px-2 py-0.5 text-[10px] font-bold text-emerald-700 shadow-sm backdrop-blur">
                                            {meja.kode_meja || 'MEJA'}
                                        </span>
                                        <span className="absolute right-2 top-2 inline-flex items-center gap-1 rounded-full bg-emerald-500/90 px-2 py-0.5 text-[10px] font-bold text-white shadow-sm backdrop-blur">
                                            <span className="h-1.5 w-1.5 rounded-full bg-white" />
                                            Tersedia
                                        </span>
                                        <div className="absolute inset-x-2 bottom-2">
                                            <p className="truncate text-sm font-bold text-white drop-shadow">
                                                {meja.nama_meja}
                                            </p>
                                        </div>
                                    </div>
                                    <div className="flex items-center justify-between px-3 py-2.5">
                                        <span className="text-[11px] font-medium text-slate-500">Meja premium</span>
                                        <span className="flex h-6 w-6 items-center justify-center rounded-full bg-emerald-50 text-emerald-600 transition group-hover:bg-emerald-600 group-hover:text-white">
                                            <ChevronRightIcon />
                                        </span>
                                    </div>
                                </Link>
                            ))}
                        </div>
                    )}
                </section>

                <section>
                    <div className="mb-3">
                        <h2 className="text-sm font-bold text-slate-800">Kenapa Main di Sini?</h2>
                        <p className="text-xs text-slate-500">Pengalaman mahjong yang beda</p>
                    </div>

                    <div className="grid grid-cols-2 gap-3">
                        <div className="relative col-span-2 overflow-hidden rounded-2xl bg-gradient-to-br from-emerald-700 to-emerald-900 p-4 text-white shadow-lg shadow-emerald-900/15">
                            <div className="pointer-events-none absolute -right-6 -top-6 h-24 w-24 rounded-full bg-white/10" />
                            <div className="pointer-events-none absolute -bottom-10 right-16 h-20 w-20 rounded-full bg-lime-300/10" />
                            <div className="relative flex items-center gap-3">
                                <span className="flex h-12 w-10 shrink-0 items-center justify-center rounded-lg border-b-4 border-emerald-950/30 bg-gradient-to-b from-white to-emerald-50 text-xl font-black text-rose-600 shadow-md">
                                    中
                                </span>
                                <div>
                                    <p className="text-sm font-bold">Meja Premium</p>
                                    <p className="mt-0.5 text-xs leading-relaxed text-emerald-100">
                                        Meja berkualitas dengan suasana nyaman untuk main lebih seru.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div className="rounded-2xl border border-emerald-100 bg-white p-3.5 shadow-sm transition hover:border-emerald-300 hover:shadow-md">
                            <span className="flex h-9 w-9 items-center justify-center rounded-lg bg-emerald-50 text-base font-black text-emerald-600">
                                發
                            </span>
                            <p className="mt-2 text-xs font-bold text-slate-800">Suasana Santai</p>
                            <p className="mt-0.5 text-[11px] leading-relaxed text-slate-500">
                                Ruang bersih dan nyaman untuk berlama-lama.
                            </p>
                        </div>

                        <div className="rounded-2xl border border-emerald-100 bg-white p-3.5 shadow-sm transition hover:border-emerald-300 hover:shadow-md">
                            <span className="flex h-9 w-9 items-center justify-center rounded-lg bg-sky-50 text-base font-black text-sky-600">
                                東
                            </span>
                            <p className="mt-2 text-xs font-bold text-slate-800">Harga Bersahabat</p>
                            <p className="mt-0.5 text-[11px] leading-relaxed text-slate-500">
                                Main seru tanpa bikin kantong bolong.
                            </p>
                        </div>
                    </div>
                </section>

                <section>
                    <div className="mb-3">
                        <h2 className="text-sm font-bold text-slate-800">Teman Main Makin Lengkap</h2>
                        <p className="text-xs text-slate-500">Makanan, minuman, dan snack tersedia</p>
                    </div>

                    <div className="grid grid-cols-3 gap-2.5">
                        {[
                            {
                                label: 'Makanan',
                                note: 'Makanan yang lezat',
                                icon: FoodIcon,
                                bg: 'bg-amber-50',
                                tone: 'text-amber-600',
                            },
                            {
                                label: 'Minuman',
                                note: 'Minuman yang segar',
                                icon: DrinkIcon,
                                bg: 'bg-sky-50',
                                tone: 'text-sky-600',
                            },
                            {
                                label: 'Snack',
                                note: 'Camilan ringan',
                                icon: SnackIcon,
                                bg: 'bg-rose-50',
                                tone: 'text-rose-600',
                            },
                        ].map((item) => {
                            const Icon = item.icon;

                            return (
                                <div
                                    key={item.label}
                                    className="rounded-2xl border border-emerald-100 bg-white p-3 text-center shadow-sm transition hover:-translate-y-0.5 hover:border-emerald-300 hover:shadow-md"
                                >
                                    <span className={`mx-auto flex h-10 w-10 items-center justify-center rounded-xl ${item.bg} ${item.tone}`}>
                                        <Icon />
                                    </span>
                                    <p className="mt-2 text-xs font-bold text-slate-800">{item.label}</p>
                                    <p className="mt-0.5 text-[10px] leading-relaxed text-slate-500">{item.note}</p>
                                </div>
                            );
                        })}
                    </div>

                    <div className="mt-3 flex items-center gap-2.5 rounded-2xl border border-emerald-100 bg-emerald-50/60 px-4 py-3">
                        <span className="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-emerald-600 text-white">
                            <svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4" fill="none" viewBox="0 0 24 24" strokeWidth={2.2} stroke="currentColor">
                                <path strokeLinecap="round" strokeLinejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                            </svg>
                        </span>
                        <p className="text-[11px] leading-relaxed text-emerald-800">
                            Pesan makanan, minuman, atau snack langsung di tempat saat kamu main.
                        </p>
                    </div>
                </section>

                <p className="pt-2 text-center text-[11px] text-slate-400">
                    Mahjong Medan &middot; Booking mudah, main nyaman
                </p>
            </div>
        </>
    );
}

Home.layout = (page) => <UserLayout>{page}</UserLayout>;
