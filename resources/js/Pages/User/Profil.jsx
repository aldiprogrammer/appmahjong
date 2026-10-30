import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import UserLayout from '../../Layouts/UserLayout';

function namaPengguna(pengguna) {
    if (!pengguna) {
        return 'Tamu';
    }

    return [pengguna.nama_depan, pengguna.nama_balakang].filter(Boolean).join(' ') || 'Tamu';
}

function statusMeta(status) {
    if (status === 'aktif') {
        return { label: 'Aktif', className: 'bg-emerald-50 text-emerald-700 ring-emerald-100', dot: 'bg-emerald-500' };
    }

    if (status === 'nonaktif') {
        return { label: 'Nonaktif', className: 'bg-slate-100 text-slate-600 ring-slate-200', dot: 'bg-slate-400' };
    }

    return { label: status || '-', className: 'bg-slate-100 text-slate-600 ring-slate-200', dot: 'bg-slate-400' };
}

function InfoRow({ icon, label, value }) {
    return (
        <div className="flex items-center gap-3 px-4 py-3">
            <span className="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600">
                {icon}
            </span>
            <div className="min-w-0 flex-1">
                <p className="text-[10px] font-medium uppercase tracking-wide text-slate-400">{label}</p>
                <p className="truncate text-sm font-semibold text-slate-700">{value || '-'}</p>
            </div>
        </div>
    );
}

function MailIcon() {
    return (
        <svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4" fill="none" viewBox="0 0 24 24" strokeWidth={1.8} stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
        </svg>
    );
}

function PhoneIcon() {
    return (
        <svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4" fill="none" viewBox="0 0 24 24" strokeWidth={1.8} stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.362-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z" />
        </svg>
    );
}

function IdIcon() {
    return (
        <svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4" fill="none" viewBox="0 0 24 24" strokeWidth={1.8} stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" d="M15 9h3.75M15 12h3.75M15 15h3.75M4.5 19.5h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Zm6-10.125a1.875 1.875 0 1 1-3.75 0 1.875 1.875 0 0 1 3.75 0Zm1.294 6.336a6.721 6.721 0 0 1-3.17.789 6.721 6.721 0 0 1-3.168-.789 3.376 3.376 0 0 1 6.338 0Z" />
        </svg>
    );
}

function ShieldIcon() {
    return (
        <svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4" fill="none" viewBox="0 0 24 24" strokeWidth={1.8} stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" />
        </svg>
    );
}

export default function Profil({ pengguna, stats }) {
    const [confirmLogout, setConfirmLogout] = useState(false);
    const [loggingOut, setLoggingOut] = useState(false);
    const meta = statusMeta(pengguna?.status);
    const initial = pengguna?.nama_depan?.charAt(0)?.toUpperCase() ?? '?';

    const logout = () => {
        setLoggingOut(true);

        router.post(
            '/profil/keluar',
            {},
            {
                onFinish: () => {
                    setLoggingOut(false);
                    setConfirmLogout(false);
                },
            },
        );
    };

    return (
        <>
            <Head title="Profil" />

            <header className="relative overflow-hidden rounded-b-[2rem] bg-gradient-to-br from-emerald-600 to-emerald-800 px-5 pb-16 pt-6 text-white">
                <div className="pointer-events-none absolute -right-10 -top-12 h-40 w-40 rounded-full bg-white/10" />
                <div className="relative flex flex-col items-center text-center">
                    <span className="flex h-20 w-20 items-center justify-center rounded-full bg-white/15 text-2xl font-bold ring-4 ring-white/20">
                        {initial}
                    </span>
                    <h1 className="mt-3 text-lg font-bold">{namaPengguna(pengguna)}</h1>
                    <p className="mt-0.5 text-xs text-emerald-100">
                        {pengguna ? pengguna.kode_user || '-' : 'Belum ada sesi aktif'}
                    </p>
                    {pengguna && (
                        <span className={`mt-2 inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[11px] font-semibold ring-1 ${meta.className}`}>
                            <span className={`h-1.5 w-1.5 rounded-full ${meta.dot}`} />
                            Akun {meta.label}
                        </span>
                    )}
                </div>
            </header>

            <div className="relative -mt-9 space-y-4 px-5 pb-8">
                <div className="grid grid-cols-3 divide-x divide-emerald-100 rounded-2xl border border-emerald-100 bg-white py-3.5 shadow-sm">
                    {[
                        { label: 'Booking', value: stats.total },
                        { label: 'Disetujui', value: stats.disetujui },
                        { label: 'Menunggu', value: stats.menunggu },
                    ].map((item) => (
                        <div key={item.label} className="px-2 text-center">
                            <p className="text-lg font-bold text-emerald-700">{item.value}</p>
                            <p className="mt-0.5 text-[10px] font-medium text-slate-500">{item.label}</p>
                        </div>
                    ))}
                </div>

                {pengguna ? (
                    <>
                        <section className="overflow-hidden rounded-2xl border border-emerald-100 bg-white shadow-sm">
                            <div className="divide-y divide-emerald-50">
                                <InfoRow icon={<IdIcon />} label="Kode Pengguna" value={pengguna.kode_user} />
                                <InfoRow icon={<MailIcon />} label="Email" value={pengguna.email} />
                                <InfoRow icon={<PhoneIcon />} label="No. WhatsApp" value={pengguna.nowa} />
                                <InfoRow icon={<ShieldIcon />} label="Status Akun" value={meta.label} />
                            </div>
                        </section>

                        <section className="rounded-2xl border border-emerald-100 bg-white p-4 shadow-sm">
                            <h2 className="text-sm font-bold text-slate-800">Tentang Aplikasi</h2>
                            <p className="mt-1 text-xs leading-relaxed text-slate-500">
                                Mahjong Medan, Booking dengan mudah,  memilih jam main, dan memantau status
                                booking langsung dari aplikasi anda.
                            </p>
                        </section>

                        <button
                            type="button"
                            onClick={() => setConfirmLogout(true)}
                            className="flex w-full items-center justify-center gap-2 rounded-2xl border border-rose-200 bg-white px-5 py-3 text-sm font-bold text-rose-600 transition hover:bg-rose-50"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4" fill="none" viewBox="0 0 24 24" strokeWidth={2} stroke="currentColor">
                                <path strokeLinecap="round" strokeLinejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15M12 9l-3 3m0 0 3 3m-3-3h12.75" />
                            </svg>
                            Keluar Dari Akun Anda
                        </button>
                    </>
                ) : (
                    <div className="flex flex-col items-center rounded-2xl border border-emerald-100 bg-white px-6 py-10 text-center shadow-sm">
                        <span className="flex h-14 w-14 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-500">
                            <IdIcon />
                        </span>
                        <h2 className="mt-4 text-sm font-bold text-slate-800">Belum ada sesi aktif</h2>
                        <p className="mt-1 max-w-xs text-xs leading-relaxed text-slate-500">
                            Buat booking pertamamu untuk mengaktifkan profil pengguna.
                        </p>
                        <Link
                            href="/booking"
                            className="mt-4 inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2.5 text-xs font-bold text-white transition hover:bg-emerald-700"
                        >
                            Booking Sekarang
                        </Link>
                    </div>
                )}
            </div>

            {confirmLogout && (
                <div className="fixed inset-0 z-50 flex items-end justify-center bg-slate-900/50 p-4 backdrop-blur-sm sm:items-center">
                    <div className="w-full max-w-md rounded-2xl bg-white p-5 shadow-2xl">
                        <div className="flex items-start gap-3">
                            <span className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-rose-50 text-rose-600">
                                <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5" fill="none" viewBox="0 0 24 24" strokeWidth={1.9} stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" d="M12 9v3.75m0 3h.008v.008H12v-.008ZM12 3.75 2.25 20.25h19.5L12 3.75Z" />
                                </svg>
                            </span>
                            <div>
                                <h2 className="text-sm font-bold text-slate-900">Keluar dari akun kamu?</h2>
                                <p className="mt-1 text-xs leading-relaxed text-slate-500">
                                    Kamu bisa masuk kembali menggunakan nomor WhatsApp/email dan password akunmu.
                                </p>
                            </div>
                        </div>

                        <div className="mt-5 flex gap-2">
                            <button
                                type="button"
                                onClick={() => setConfirmLogout(false)}
                                disabled={loggingOut}
                                className="flex-1 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-bold text-slate-600 transition hover:bg-slate-50 disabled:opacity-60"
                            >
                                Batal
                            </button>
                            <button
                                type="button"
                                onClick={logout}
                                disabled={loggingOut}
                                className="flex-1 rounded-xl bg-rose-600 px-4 py-2.5 text-xs font-bold text-white transition hover:bg-rose-700 disabled:cursor-not-allowed disabled:opacity-60"
                            >
                                {loggingOut ? 'Keluar...' : 'Ya, Keluar'}
                            </button>
                        </div>
                    </div>
                </div>
            )}
        </>
    );
}

Profil.layout = (page) => <UserLayout>{page}</UserLayout>;
