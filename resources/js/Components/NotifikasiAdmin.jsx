import { router, usePage } from '@inertiajs/react';
import { useCallback, useEffect, useRef, useState } from 'react';

function BellIcon({ className = 'h-5 w-5' }) {
    return (
        <svg xmlns="http://www.w3.org/2000/svg" className={className} fill="none" viewBox="0 0 24 24" strokeWidth={1.8} stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
        </svg>
    );
}

function ambilXsrfToken() {
    if (typeof document === 'undefined') {
        return '';
    }

    const cookie = document.cookie
        .split('; ')
        .find((baris) => baris.startsWith('XSRF-TOKEN='));

    return cookie ? decodeURIComponent(cookie.split('=').slice(1).join('=')) : '';
}

async function kirimPost(url) {
    const response = await fetch(url, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-XSRF-TOKEN': ambilXsrfToken(),
        },
    });

    return response.ok;
}

/**
 * Lonceng notifikasi booking masuk untuk panel admin.
 *
 * Notifikasi diambil dari endpoint /admin/notifikasi secara berkala
 * (fallback yang selalu bekerja), sekaligus mendaftarkan perangkat admin
 * ke OneSignal agar menerima push notification sesuai venue-nya.
 */
export default function NotifikasiAdmin() {
    const { props } = usePage();
    const admin = props.auth?.admin ?? null;
    const oneSignalAppId = props.oneSignalAppId ?? null;

    const [daftar, setDaftar] = useState([]);
    const [belumDibaca, setBelumDibaca] = useState(0);
    const [terbuka, setTerbuka] = useState(false);
    const [memuat, setMemuat] = useState(false);
    const [izinNotifikasi, setIzinNotifikasi] = useState('default');
    const [statusPush, setStatusPush] = useState('memuat');
    const wadahRef = useRef(null);

    const muat = useCallback(async () => {
        try {
            const response = await fetch('/admin/notifikasi', {
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (! response.ok) {
                return;
            }

            const data = await response.json();

            setDaftar(data.notifikasi ?? []);
            setBelumDibaca(data.belum_dibaca ?? 0);
            setMemuat(false);
        } catch {
            setMemuat(false);
        }
    }, []);

    useEffect(() => {
        if (typeof Notification !== 'undefined') {
            setIzinNotifikasi(Notification.permission);
        }

        muat();
        const interval = window.setInterval(muat, 20000);
        const saatFokus = () => muat();

        window.addEventListener('focus', saatFokus);

        return () => {
            window.clearInterval(interval);
            window.removeEventListener('focus', saatFokus);
        };
    }, [muat]);

    useEffect(() => {
        if (! terbuka) {
            return undefined;
        }

        const tutupDiLuar = (event) => {
            if (wadahRef.current && !wadahRef.current.contains(event.target)) {
                setTerbuka(false);
            }
        };

        document.addEventListener('mousedown', tutupDiLuar);

        return () => document.removeEventListener('mousedown', tutupDiLuar);
    }, [terbuka]);

    /**
     * Muat SDK OneSignal lalu daftarkan perangkat ini sebagai perangkat admin.
     * Tag `vanue_id` dipakai backend untuk mengarahkan push sesuai venue.
     */
    useEffect(() => {
        if (! oneSignalAppId || ! admin) {
            return;
        }

        window.OneSignalDeferred = window.OneSignalDeferred || [];
        window.OneSignalDeferred.push(async (OneSignal) => {
            try {
                await OneSignal.init({
                    appId: oneSignalAppId,
                    allowLocalhostAsSecureOrigin: true,
                    autoResubscribe: true,
                    notifyButton: { enable: false },
                });

                await OneSignal.login(`admin-${admin.id}`);
                await OneSignal.User.addTags({
                    vanue_id: String(admin.vanue?.id ?? 'all'),
                    admin_role: String(admin.role ?? 'admin'),
                });

                const perbaruiStatus = (subscription) => {
                    setStatusPush(subscription?.id ? 'aktif' : 'nonaktif');
                };

                perbaruiStatus(OneSignal.User.PushSubscription);
                OneSignal.User.PushSubscription.addEventListener('change', (event) =>
                    perbaruiStatus(event.current),
                );
                OneSignal.Notifications.addEventListener('permissionChange', (granted) => {
                    setIzinNotifikasi(granted ? 'granted' : 'denied');

                    if (granted) {
                        muat();
                    }
                });

                // Segarkan lonceng segera saat push masuk selagi panel terbuka.
                OneSignal.Notifications.addEventListener('foregroundWillDisplay', () => muat());
            } catch (error) {
                console.warn('OneSignal gagal diinisialisasi:', error);
                setStatusPush('nonaktif');
            }
        });

        if (! document.getElementById('onesignal-web-sdk')) {
            const script = document.createElement('script');
            script.id = 'onesignal-web-sdk';
            script.src = 'https://cdn.onesignal.com/sdks/web/v16/OneSignalSDK.page.js';
            script.defer = true;
            document.head.appendChild(script);
        }
    }, [oneSignalAppId, admin?.id, admin?.role, admin?.vanue?.id, muat]);

    const aktifkanPush = () => {
        if (! window.OneSignalDeferred) {
            return;
        }

        window.OneSignalDeferred.push(async (OneSignal) => {
            try {
                const diizinkan = await OneSignal.Notifications.requestPermission();

                if (diizinkan) {
                    await OneSignal.User.PushSubscription.optIn();
                }

                setIzinNotifikasi(Notification.permission);
                setStatusPush(OneSignal.User.PushSubscription.id ? 'aktif' : 'nonaktif');

                if (diizinkan) {
                    muat();
                }
            } catch {
                // Abaikan bila browser menolak permintaan izin.
            }
        });
    };

    const tandaiDibaca = async (item) => {
        if (! item.dibaca) {
            const berhasil = await kirimPost(`/admin/notifikasi/${item.id}/dibaca`);

            if (berhasil) {
                setDaftar((sebelumnya) =>
                    sebelumnya.map((baris) => (baris.id === item.id ? { ...baris, dibaca: true } : baris)),
                );
                setBelumDibaca((sebelumnya) => Math.max(0, sebelumnya - 1));
            }
        }

        setTerbuka(false);
        router.visit('/admin/bookings');
    };

    const tandaiSemuaDibaca = async () => {
        const berhasil = await kirimPost('/admin/notifikasi/dibaca-semua');

        if (berhasil) {
            setDaftar((sebelumnya) => sebelumnya.map((baris) => ({ ...baris, dibaca: true })));
            setBelumDibaca(0);
        }
    };

    return (
        <div ref={wadahRef} className="relative">
            <button
                type="button"
                onClick={() => {
                    setTerbuka((sebelumnya) => ! sebelumnya);

                    if (! terbuka) {
                        setMemuat(true);
                        muat();
                    }
                }}
                className="relative inline-flex h-9 w-9 items-center justify-center rounded-full border border-emerald-200 bg-white text-emerald-700 transition hover:bg-emerald-50"
                aria-label={`Notifikasi${belumDibaca > 0 ? `, ${belumDibaca} belum dibaca` : ''}`}
                aria-expanded={terbuka}
            >
                <BellIcon className="h-4 w-4" />
                {belumDibaca > 0 && (
                    <span className="absolute -right-1 -top-1 flex h-5 min-w-[1.25rem] items-center justify-center rounded-full bg-rose-500 px-1 text-[10px] font-bold text-white ring-2 ring-white">
                        {belumDibaca > 99 ? '99+' : belumDibaca}
                    </span>
                )}
            </button>

            {terbuka && (
                <div className="absolute right-0 z-50 mt-2 w-80 overflow-hidden rounded-2xl border border-emerald-100 bg-white shadow-xl sm:w-96">
                    <div className="flex items-center justify-between gap-2 border-b border-emerald-50 px-4 py-3">
                        <div>
                            <p className="text-sm font-bold text-slate-800">Notifikasi Booking</p>
                            <p className="text-[11px] text-slate-500">Booking masuk sesuai venue Anda.</p>
                        </div>
                        {belumDibaca > 0 && (
                            <button
                                type="button"
                                onClick={tandaiSemuaDibaca}
                                className="shrink-0 rounded-lg border border-emerald-100 bg-emerald-50 px-2 py-1 text-[11px] font-semibold text-emerald-700 transition hover:bg-emerald-100"
                            >
                                Tandai dibaca
                            </button>
                        )}
                    </div>

                    {oneSignalAppId && (
                        <div className="border-b border-emerald-50 px-4 py-2.5">
                            {statusPush === 'aktif' ? (
                                <p className="flex items-center gap-1.5 text-[11px] font-medium text-emerald-700">
                                    <span className="h-1.5 w-1.5 rounded-full bg-emerald-500" />
                                    Push notification aktif di perangkat ini.
                                </p>
                            ) : (
                                <>
                                    <p className="text-[11px] leading-relaxed text-amber-800">
                                        {izinNotifikasi === 'granted'
                                            ? 'Izin notifikasi sudah diberikan, tetapi perangkat ini belum terdaftar untuk push. Muat ulang halaman ini.'
                                            : 'Aktifkan notifikasi push agar tetap menerima pemberitahuan saat panel ditutup.'}
                                    </p>
                                    {izinNotifikasi !== 'granted' && (
                                        <button
                                            type="button"
                                            onClick={aktifkanPush}
                                            className="mt-1.5 rounded-lg bg-amber-500 px-2.5 py-1 text-[11px] font-semibold text-white transition hover:bg-amber-600"
                                        >
                                            Aktifkan notifikasi
                                        </button>
                                    )}
                                </>
                            )}
                        </div>
                    )}

                    <div className="max-h-96 overflow-y-auto">
                        {memuat && daftar.length === 0 ? (
                            <p className="px-4 py-6 text-center text-xs text-slate-500">Memuat notifikasi...</p>
                        ) : daftar.length === 0 ? (
                            <p className="px-4 py-8 text-center text-xs text-slate-500">
                                Belum ada booking masuk.
                            </p>
                        ) : (
                            daftar.map((item) => (
                                <button
                                    key={item.id}
                                    type="button"
                                    onClick={() => tandaiDibaca(item)}
                                    className={`flex w-full items-start gap-3 border-b border-slate-50 px-4 py-3 text-left transition last:border-0 hover:bg-emerald-50/60 ${
                                        item.dibaca ? 'bg-white' : 'bg-emerald-50/40'
                                    }`}
                                >
                                    <span
                                        className={`mt-1 h-2 w-2 shrink-0 rounded-full ${
                                            item.dibaca ? 'bg-slate-300' : 'bg-emerald-500'
                                        }`}
                                    />
                                    <span className="min-w-0">
                                        <span className="block text-xs font-bold text-slate-800">{item.judul}</span>
                                        <span className="mt-0.5 block text-[11px] leading-relaxed text-slate-600">
                                            {item.pesan}
                                        </span>
                                        <span className="mt-1 block text-[10px] font-medium text-slate-400">
                                            {item.waktu ?? '-'}
                                        </span>
                                    </span>
                                </button>
                            ))
                        )}
                    </div>
                </div>
            )}
        </div>
    );
}
