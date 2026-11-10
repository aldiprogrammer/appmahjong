import { Head, Link } from '@inertiajs/react';
import UserLayout from '../../Layouts/UserLayout';

function formatPoint(value) {
    return new Intl.NumberFormat('id-ID').format(Number(value) || 0);
}

function TrophyIcon({ className = 'h-5 w-5' }) {
    return (
        <svg xmlns="http://www.w3.org/2000/svg" className={className} fill="none" viewBox="0 0 24 24" strokeWidth={1.8} stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" d="M16.5 18.75h-9m9 0a3 3 0 0 1 3 3h-15a3 3 0 0 1 3-3m9 0v-3.375c0-.621-.503-1.125-1.125-1.125h-.871M7.5 18.75v-3.375c0-.621.504-1.125 1.125-1.125h.872m5.007 0H9.497m5.007 0a7.454 7.454 0 0 1-.982-3.172M9.497 14.25a7.454 7.454 0 0 0 .981-3.172M5.25 4.236c-.982.143-1.954.317-2.916.52A6.003 6.003 0 0 0 7.73 9.728M5.25 4.236V4.5c0 2.108.966 3.99 2.48 5.228M5.25 4.236V2.721C7.456 2.41 9.71 2.25 12 2.25c2.291 0 4.545.16 6.75.47v1.516M7.73 9.728a6.726 6.726 0 0 0 2.748 1.35m8.272-6.842V4.5c0 2.108-.966 3.99-2.48 5.228m2.48-5.492a46.32 46.32 0 0 1 2.916.52 6.003 6.003 0 0 1-5.395 4.972m0 0a6.726 6.726 0 0 1-2.749 1.35m0 0a6.772 6.772 0 0 1-3.044 0" />
        </svg>
    );
}

function StarIcon({ className = 'h-3.5 w-3.5' }) {
    return (
        <svg xmlns="http://www.w3.org/2000/svg" className={className} viewBox="0 0 24 24" fill="currentColor">
            <path d="M11.48 3.499a.562.562 0 0 1 1.04 0l2.125 5.111a.563.563 0 0 0 .475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 0 0-.182.557l1.285 5.385a.562.562 0 0 1-.84.61l-4.725-2.885a.562.562 0 0 0-.586 0L6.982 20.54a.562.562 0 0 1-.84-.61l1.285-5.386a.562.562 0 0 0-.182-.557l-4.204-3.602a.562.562 0 0 1 .321-.988l5.518-.442a.563.563 0 0 0 .475-.345L11.48 3.5Z" />
        </svg>
    );
}

function CrownIcon({ className = 'h-5 w-5' }) {
    return (
        <svg xmlns="http://www.w3.org/2000/svg" className={className} viewBox="0 0 24 24" fill="currentColor">
            <path d="M11.25 3.75 8.4 7.95 4.05 6.3a.75.75 0 0 0-.99.87l1.8 8.55a1.5 1.5 0 0 0 1.47 1.2h11.34a1.5 1.5 0 0 0 1.47-1.2l1.8-8.55a.75.75 0 0 0-.99-.87L15.6 7.95 12.75 3.75a.9.9 0 0 0-1.5 0ZM6 19.5h12v1.5H6v-1.5Z" />
        </svg>
    );
}

function ArrowIcon({ className = 'h-3.5 w-3.5' }) {
    return (
        <svg xmlns="http://www.w3.org/2000/svg" className={className} fill="none" viewBox="0 0 24 24" strokeWidth={2.2} stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
        </svg>
    );
}

const gayaPodium = {
    1: {
        kartu: 'border-amber-200 bg-gradient-to-b from-amber-50 via-amber-50/40 to-white',
        ring: 'ring-amber-300',
        badge: 'bg-gradient-to-br from-amber-400 to-amber-500 text-white shadow-md shadow-amber-500/30',
        label: 'text-amber-600',
        tinggi: 'pt-6 pb-4',
    },
    2: {
        kartu: 'border-slate-200 bg-gradient-to-b from-slate-50 via-slate-50/40 to-white',
        ring: 'ring-slate-300',
        badge: 'bg-gradient-to-br from-slate-400 to-slate-500 text-white shadow-md shadow-slate-500/25',
        label: 'text-slate-500',
        tinggi: 'pt-5 pb-3.5',
    },
    3: {
        kartu: 'border-orange-200 bg-gradient-to-b from-orange-50 via-orange-50/40 to-white',
        ring: 'ring-orange-300',
        badge: 'bg-gradient-to-br from-orange-400 to-orange-500 text-white shadow-md shadow-orange-500/25',
        label: 'text-orange-600',
        tinggi: 'pt-5 pb-3.5',
    },
};

function Avatar({ nama, avatar, ukuran = 'h-14 w-14', ring = 'ring-2 ring-white' }) {
    const inisial = nama?.charAt(0)?.toUpperCase() ?? '?';

    if (avatar) {
        return (
            <img
                src={avatar}
                alt={nama}
                referrerPolicy="no-referrer"
                className={`${ukuran} shrink-0 rounded-full object-cover ${ring}`}
            />
        );
    }

    return (
        <span
            className={`${ukuran} flex shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-emerald-500 to-emerald-700 font-black text-white ${ring}`}
        >
            {inisial}
        </span>
    );
}

function KartuPodium({ item, posisi }) {
    const gaya = gayaPodium[posisi];

    if (!item) {
        return (
            <div className="flex flex-col items-center justify-end">
                <div className="flex h-24 w-full items-center justify-center rounded-2xl border border-dashed border-slate-200 bg-slate-50/60 text-[10px] font-semibold text-slate-400">
                    Belum ada
                </div>
            </div>
        );
    }

    return (
        <div
            className={`relative flex flex-col items-center rounded-2xl border px-2 text-center shadow-sm ${gaya.kartu} ${gaya.tinggi} ${
                item.saya ? 'ring-2 ring-emerald-400/70' : ''
            }`}
        >
            {posisi === 1 && (
                <span className="absolute -top-3 left-1/2 flex h-7 w-7 -translate-x-1/2 items-center justify-center rounded-full bg-amber-400 text-white shadow-md shadow-amber-500/40">
                    <CrownIcon className="h-4 w-4" />
                </span>
            )}

            <span
                className={`mb-2 flex h-6 min-w-6 items-center justify-center rounded-full px-1.5 text-[10px] font-black ${gaya.badge}`}
            >
                #{posisi}
            </span>

            <Avatar
                nama={item.nama}
                avatar={item.avatar}
                ukuran={posisi === 1 ? 'h-16 w-16' : 'h-12 w-12'}
                ring={`ring-2 ring-white ring-offset-2 ring-offset-transparent ${gaya.ring}`}
            />

            <p className="mt-2 w-full truncate text-[11px] font-bold text-slate-800" title={item.nama}>
                {item.nama}
            </p>

            {item.saya && (
                <span className="mt-0.5 rounded-full bg-emerald-100 px-1.5 py-0.5 text-[8px] font-bold uppercase tracking-wide text-emerald-700">
                    Kamu
                </span>
            )}

            <span className={`mt-1.5 inline-flex items-center gap-1 text-[11px] font-black ${gaya.label}`}>
                <StarIcon className="h-3 w-3" />
                {formatPoint(item.point)}
            </span>
        </div>
    );
}

export default function Ranking({ ranking = [], peringkatSaya = null, ringkasan = {} }) {
    const daftar = Array.isArray(ranking) ? ranking : [];
    const podium = [
        { item: daftar[1], posisi: 2 },
        { item: daftar[0], posisi: 1 },
        { item: daftar[2], posisi: 3 },
    ];
    const sisanya = daftar.slice(3);

    return (
        <>
            <Head title="Ranking Pemain" />

            <header className="relative overflow-hidden rounded-b-[2rem] bg-gradient-to-br from-emerald-600 via-emerald-700 to-teal-800 px-5 pb-16 pt-6 text-white">
                <div className="pointer-events-none absolute -right-12 -top-14 h-44 w-44 rounded-full bg-white/10" />
                <div className="pointer-events-none absolute -bottom-20 -left-12 h-44 w-44 rounded-full bg-emerald-300/10" />
                <div className="pointer-events-none absolute right-10 top-20 h-2 w-2 rounded-full bg-amber-300/70" />
                <div className="pointer-events-none absolute right-20 top-10 h-1.5 w-1.5 rounded-full bg-white/50" />

                <div className="relative flex items-start justify-between gap-3">
                    <div>
                        <span className="inline-flex items-center gap-1.5 rounded-full bg-white/15 px-2.5 py-1 text-[9px] font-bold uppercase tracking-[0.18em] text-emerald-50 ring-1 ring-white/20">
                            <TrophyIcon className="h-3 w-3" />
                            Leaderboard
                        </span>
                        <h1 className="mt-3 text-xl font-black leading-tight">Ranking Pemain</h1>
                        <p className="mt-1 max-w-[16rem] text-xs leading-relaxed text-emerald-100">
                            Peringkat pemain Mahjong berdasarkan total point yang dikumpulkan.
                        </p>
                    </div>

                    <span className="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-white/15 text-amber-200 ring-4 ring-white/10">
                        <TrophyIcon className="h-7 w-7" />
                    </span>
                </div>
            </header>

            <main className="relative -mt-10 space-y-4 px-5 pb-8">
                <section className="grid grid-cols-3 gap-2 rounded-2xl border border-emerald-100 bg-white p-3 shadow-lg shadow-emerald-900/5">
                    {[
                        { label: 'Pemain', value: formatPoint(ringkasan.total_pemain ?? daftar.length) },
                        { label: 'Total Point', value: formatPoint(ringkasan.total_point ?? 0) },
                        { label: 'Tertinggi', value: formatPoint(ringkasan.point_tertinggi ?? 0) },
                    ].map((stat) => (
                        <div key={stat.label} className="rounded-xl bg-emerald-50/60 px-1 py-2 text-center ring-1 ring-inset ring-emerald-100/70">
                            <p className="truncate text-sm font-black leading-none text-emerald-700">{stat.value}</p>
                            <p className="mt-1 text-[9px] font-semibold uppercase tracking-wide text-slate-400">{stat.label}</p>
                        </div>
                    ))}
                </section>

                {peringkatSaya ? (
                    <section className="relative overflow-hidden rounded-2xl bg-gradient-to-r from-emerald-700 via-emerald-600 to-teal-600 p-4 text-white shadow-lg shadow-emerald-600/25">
                        <div className="pointer-events-none absolute -right-6 -top-8 h-24 w-24 rounded-full bg-white/10" />
                        <div className="pointer-events-none absolute -bottom-10 -left-6 h-24 w-24 rounded-full bg-white/5" />

                        <div className="relative flex items-center gap-3">
                            <div className="flex h-14 w-12 shrink-0 flex-col items-center justify-center rounded-xl bg-white/15 ring-1 ring-white/20">
                                <span className="text-[8px] font-bold uppercase tracking-wide text-emerald-100">Posisi</span>
                                <span className="text-lg font-black leading-none">#{peringkatSaya.peringkat}</span>
                            </div>

                            <Avatar
                                nama={peringkatSaya.nama}
                                avatar={peringkatSaya.avatar}
                                ukuran="h-11 w-11"
                                ring="ring-2 ring-white/70"
                            />

                            <div className="min-w-0 flex-1">
                                <p className="text-[10px] font-semibold uppercase tracking-wide text-emerald-100">Peringkat kamu</p>
                                <p className="truncate text-sm font-bold">{peringkatSaya.nama}</p>
                            </div>

                            <div className="shrink-0 text-right">
                                <p className="text-base font-black leading-none">{formatPoint(peringkatSaya.point)}</p>
                                <p className="mt-0.5 text-[8px] font-bold uppercase tracking-wide text-emerald-100">Point</p>
                            </div>
                        </div>
                    </section>
                ) : (
                    <Link
                        href="/point"
                        className="group flex items-center gap-3 rounded-2xl border border-emerald-200 bg-white p-3.5 shadow-sm transition hover:border-emerald-300 hover:bg-emerald-50/50"
                    >
                        <span className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600">
                            <StarIcon className="h-5 w-5" />
                        </span>
                        <div className="min-w-0 flex-1">
                            <p className="text-xs font-bold text-slate-800">Kamu belum masuk ranking</p>
                            <p className="mt-0.5 text-[10px] leading-relaxed text-slate-500">
                                Kumpulkan point dari winning hand untuk tampil di papan peringkat.
                            </p>
                        </div>
                        <span className="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-emerald-600 text-white transition group-hover:translate-x-0.5">
                            <ArrowIcon />
                        </span>
                    </Link>
                )}

                {daftar.length === 0 ? (
                    <section className="rounded-2xl border border-dashed border-emerald-200 bg-white px-6 py-10 text-center shadow-sm">
                        <span className="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-500">
                            <TrophyIcon className="h-8 w-8" />
                        </span>
                        <h2 className="mt-4 text-sm font-bold text-slate-800">Belum ada data ranking</h2>
                        <p className="mx-auto mt-1 max-w-[15rem] text-xs leading-relaxed text-slate-500">
                            Papan peringkat akan terisi setelah pemain mengumpulkan point dari winning hand.
                        </p>
                        <Link
                            href="/point"
                            className="mt-4 inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 px-4 py-2.5 text-xs font-bold text-white shadow-sm transition hover:bg-emerald-700"
                        >
                            Mulai Kumpulkan Point
                            <ArrowIcon />
                        </Link>
                    </section>
                ) : (
                    <>
                        <section className="rounded-2xl border border-emerald-100 bg-white p-4 shadow-sm">
                            <div className="flex items-center justify-between">
                                <h2 className="text-sm font-bold text-slate-800">Puncak Klasemen</h2>
                                <span className="rounded-full bg-emerald-50 px-2.5 py-1 text-[9px] font-bold uppercase tracking-wide text-emerald-700 ring-1 ring-emerald-100">
                                    Top 3
                                </span>
                            </div>

                            <div className="mt-5 grid grid-cols-3 items-end gap-2">
                                {podium.map(({ item, posisi }) => (
                                    <KartuPodium key={posisi} item={item} posisi={posisi} />
                                ))}
                            </div>
                        </section>

                        {sisanya.length > 0 && (
                            <section className="overflow-hidden rounded-2xl border border-emerald-100 bg-white shadow-sm">
                                <div className="flex items-center justify-between border-b border-emerald-50 px-4 py-3">
                                    <h2 className="text-sm font-bold text-slate-800">Klasemen Lengkap</h2>
                                    <span className="text-[10px] font-semibold text-slate-400">{daftar.length} pemain</span>
                                </div>

                                <ul className="divide-y divide-slate-100">
                                    {sisanya.map((item) => (
                                        <li
                                            key={item.kode_user}
                                            className={`flex items-center gap-3 px-4 py-3 transition ${
                                                item.saya ? 'bg-emerald-50/70' : ''
                                            }`}
                                        >
                                            <span
                                                className={`flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-[10px] font-black ring-1 ${
                                                    item.saya
                                                        ? 'bg-emerald-600 text-white ring-emerald-600'
                                                        : 'bg-slate-50 text-slate-500 ring-slate-200'
                                                }`}
                                            >
                                                {item.peringkat}
                                            </span>

                                            <Avatar
                                                nama={item.nama}
                                                avatar={item.avatar}
                                                ukuran="h-9 w-9"
                                                ring="ring-1 ring-emerald-100"
                                            />

                                            <div className="min-w-0 flex-1">
                                                <p className="flex items-center gap-1.5 truncate text-xs font-bold text-slate-800">
                                                    <span className="truncate">{item.nama}</span>
                                                    {item.saya && (
                                                        <span className="shrink-0 rounded-full bg-emerald-600 px-1.5 py-0.5 text-[8px] font-bold uppercase tracking-wide text-white">
                                                            Kamu
                                                        </span>
                                                    )}
                                                </p>
                                                <p className="mt-0.5 text-[10px] text-slate-400">Peringkat #{item.peringkat}</p>
                                            </div>

                                            <span className="inline-flex shrink-0 items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-1 text-emerald-700 ring-1 ring-inset ring-emerald-100">
                                                <StarIcon className="h-3 w-3" />
                                                <span className="text-[11px] font-black">{formatPoint(item.point)}</span>
                                            </span>
                                        </li>
                                    ))}
                                </ul>
                            </section>
                        )}

                        <p className="px-1 text-center text-[10px] leading-relaxed text-slate-400">
                            Peringkat dihitung dari total point pemain dan diperbarui otomatis.
                        </p>
                    </>
                )}
            </main>
        </>
    );
}

Ranking.layout = (page) => <UserLayout>{page}</UserLayout>;
