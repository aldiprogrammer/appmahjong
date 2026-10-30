import { Head } from '@inertiajs/react';
import AdminLayout from '../../Layouts/AdminLayout';

function MejaIcon() {
    return (
        <svg xmlns="http://www.w3.org/2000/svg" className="h-6 w-6" fill="none" viewBox="0 0 24 24" strokeWidth={1.8} stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" d="M3 10h18M5 10v8m14-8v8M7 21h10M6 10V6a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v4" />
        </svg>
    );
}

function ClockIcon() {
    return (
        <svg xmlns="http://www.w3.org/2000/svg" className="h-6 w-6" fill="none" viewBox="0 0 24 24" strokeWidth={1.8} stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" d="M12 6v6l4 2m6-2a10 10 0 1 1-20 0 10 10 0 0 1 20 0Z" />
        </svg>
    );
}

function PriceIcon() {
    return (
        <svg xmlns="http://www.w3.org/2000/svg" className="h-6 w-6" fill="none" viewBox="0 0 24 24" strokeWidth={1.8} stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" d="M12 6v12m3-9.5c0-1.38-1.34-2.5-3-2.5s-3 1.12-3 2.5 1.34 2.5 3 2.5 3 1.12 3 2.5-1.34 2.5-3 2.5-3-1.12-3-2.5" />
        </svg>
    );
}

function CheckIcon({ className = 'h-4 w-4' }) {
    return (
        <svg xmlns="http://www.w3.org/2000/svg" className={className} fill="none" viewBox="0 0 24 24" strokeWidth={2} stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" d="M4.5 12.75l6 6 9-13.5" />
        </svg>
    );
}

function PlusIcon({ className = 'h-4 w-4' }) {
    return (
        <svg xmlns="http://www.w3.org/2000/svg" className={className} fill="none" viewBox="0 0 24 24" strokeWidth={2} stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
        </svg>
    );
}

export default function Dashboard({ stats = { meja: 0, jam_main: 0, harga: 0 }, version }) {
    const cards = [
        { label: 'Total Meja', value: stats.meja, note: 'Jumlah meja yang terdaftar', icon: MejaIcon },
        { label: 'Jam Main', value: stats.jam_main, note: 'Sesi jam main tersedia', icon: ClockIcon },
        { label: 'Harga Jam Main', value: stats.harga, note: 'Daftar tarif yang dikonfigurasi', icon: PriceIcon },
    ];

    const activities = [
        {
            icon: CheckIcon,
            title: 'Panel admin berhasil disiapkan',
            note: 'Tema hijau putih dengan Inertia + React',
            time: 'Baru saja',
        },
        {
            icon: PlusIcon,
            title: 'Data meja tersedia',
            note: `${stats.meja} meja terdaftar di sistem`,
            time: 'Hari ini',
        },
        {
            icon: ClockIcon,
            title: 'Konfigurasi jam main',
            note: `${stats.jam_main} sesi jam main aktif`,
            time: 'Hari ini',
        },
    ];

    return (
        <>
            <Head title="Dashboard" />

            <div className="mx-auto max-w-7xl space-y-6">
                <section className="overflow-hidden rounded-2xl bg-gradient-to-r from-emerald-600 to-emerald-800 p-6 text-white shadow-lg shadow-emerald-900/10 sm:p-8">
                    <div className="flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
                        <div className="max-w-xl">
                            <span className="inline-flex items-center gap-1.5 rounded-full bg-white/15 px-3 py-1 text-xs font-medium ring-1 ring-white/20">
                                <span className="h-1.5 w-1.5 rounded-full bg-emerald-300" />
                                Ringkasan hari ini
                            </span>
                            <h1 className="mt-4 text-2xl font-bold sm:text-3xl">Selamat datang kembali, Admin!</h1>
                            <p className="mt-2 text-sm leading-relaxed text-emerald-100">
                                Pantau data meja, jam main, dan harga dalam satu tampilan yang ringkas.
                            </p>
                        </div>
                        <div className="grid grid-cols-2 gap-3 text-center sm:min-w-64">
                            <div className="rounded-xl bg-white/10 p-4 ring-1 ring-white/15">
                                <p className="text-2xl font-bold">{stats.meja}</p>
                                <p className="mt-1 text-xs text-emerald-100">Meja</p>
                            </div>
                            <div className="rounded-xl bg-white/10 p-4 ring-1 ring-white/15">
                                <p className="text-2xl font-bold">{stats.harga}</p>
                                <p className="mt-1 text-xs text-emerald-100">Harga</p>
                            </div>
                        </div>
                    </div>
                </section>

                <section className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    {cards.map((card) => {
                        const Icon = card.icon;

                        return (
                            <div
                                key={card.label}
                                className="rounded-2xl border border-emerald-100 bg-white p-5 shadow-sm transition hover:shadow-md"
                            >
                                <div className="flex items-start justify-between">
                                    <div>
                                        <p className="text-sm font-medium text-slate-500">{card.label}</p>
                                        <p className="mt-2 text-3xl font-bold text-slate-900">{card.value}</p>
                                    </div>
                                    <span className="flex h-12 w-12 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600">
                                        <Icon />
                                    </span>
                                </div>
                                <p className="mt-4 text-xs text-slate-400">{card.note}</p>
                            </div>
                        );
                    })}
                </section>

                <section className="grid gap-4 lg:grid-cols-3">
                    <div className="rounded-2xl border border-emerald-100 bg-white shadow-sm lg:col-span-2">
                        <div className="flex items-center justify-between border-b border-emerald-100 px-5 py-4">
                            <div>
                                <h2 className="text-sm font-semibold text-slate-900">Aktivitas Terbaru</h2>
                                <p className="text-xs text-slate-500">Ringkasan perubahan data terakhir</p>
                            </div>
                            <span className="rounded-full bg-emerald-50 px-3 py-1 text-xs font-medium text-emerald-700">
                                Hari ini
                            </span>
                        </div>
                        <div className="divide-y divide-emerald-50">
                            {activities.map((activity) => {
                                const Icon = activity.icon;

                                return (
                                    <div key={activity.title} className="flex items-center gap-3 px-5 py-4">
                                        <span className="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600">
                                            <Icon />
                                        </span>
                                        <div className="min-w-0">
                                            <p className="truncate text-sm font-medium text-slate-700">{activity.title}</p>
                                            <p className="text-xs text-slate-400">{activity.note}</p>
                                        </div>
                                        <span className="ml-auto whitespace-nowrap text-xs text-slate-400">{activity.time}</span>
                                    </div>
                                );
                            })}
                        </div>
                    </div>

                    <div className="rounded-2xl border border-emerald-100 bg-white p-5 shadow-sm">
                        <h2 className="text-sm font-semibold text-slate-900">Ringkasan Sistem</h2>
                        <p className="text-xs text-slate-500">Status singkat aplikasi</p>

                        <dl className="mt-5 space-y-4">
                            <div className="flex items-center justify-between">
                                <dt className="text-sm text-slate-500">Status</dt>
                                <dd className="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700">
                                    <span className="h-1.5 w-1.5 rounded-full bg-emerald-500" />
                                    Aktif
                                </dd>
                            </div>
                            <div className="flex items-center justify-between">
                                <dt className="text-sm text-slate-500">Versi Laravel</dt>
                                <dd className="text-sm font-medium text-slate-700">{version}</dd>
                            </div>
                            <div className="flex items-center justify-between">
                                <dt className="text-sm text-slate-500">Frontend</dt>
                                <dd className="text-sm font-medium text-slate-700">Inertia + React</dd>
                            </div>
                            <div className="flex items-center justify-between">
                                <dt className="text-sm text-slate-500">Tema</dt>
                                <dd className="text-sm font-medium text-slate-700">Hijau Putih</dd>
                            </div>
                        </dl>

                        <div className="mt-6 rounded-xl bg-emerald-50 p-4">
                            <p className="text-xs leading-relaxed text-emerald-800">
                                Gunakan menu di samping untuk menavigasi panel admin. Menu lain akan ditambahkan sesuai
                                kebutuhan.
                            </p>
                        </div>
                    </div>
                </section>
            </div>
        </>
    );
}

Dashboard.layout = (page) => <AdminLayout title="Dashboard">{page}</AdminLayout>;
