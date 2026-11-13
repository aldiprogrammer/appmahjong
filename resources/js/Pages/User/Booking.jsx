import { Deferred, Head, Link, router, useForm, usePage } from '@inertiajs/react';
import Swal from 'sweetalert2';
import 'sweetalert2/dist/sweetalert2.min.css';
import { useEffect, useState } from 'react';
import UserLayout from '../../Layouts/UserLayout';

const hariMap = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];

const langkahBooking = [
    { nomor: 1, label: 'Tanggal & Meja', judul: 'Pilih tanggal dan meja' },
    { nomor: 2, label: 'Jam Main', judul: 'Pilih jam main' },
    { nomor: 3, label: 'Pembayaran', judul: 'Konfirmasi & bayar' },
];

function formatTime(value) {
    if (!value) {
        return '-';
    }

    return value.length > 5 ? value.slice(0, 5) : value;
}

function formatRupiah(value) {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(Number(value) || 0);
}

function todayISO() {
    const now = new Date();
    const month = String(now.getMonth() + 1).padStart(2, '0');
    const day = String(now.getDate()).padStart(2, '0');

    return `${now.getFullYear()}-${month}-${day}`;
}

function formatTanggal(value) {
    if (!value) {
        return '-';
    }

    const date = new Date(`${value}T00:00:00`);

    if (Number.isNaN(date.getTime())) {
        return value;
    }

    return new Intl.DateTimeFormat('id-ID', {
        day: '2-digit',
        month: 'long',
        year: 'numeric',
    }).format(date);
}

function namaPengguna(pengguna) {
    if (!pengguna) {
        return '';
    }

    return [pengguna.nama_depan, pengguna.nama_balakang].filter(Boolean).join(' ');
}

function BackIcon() {
    return (
        <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5" fill="none" viewBox="0 0 24 24" strokeWidth={2} stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
        </svg>
    );
}

function ClockIcon({ className = 'h-4 w-4' }) {
    return (
        <svg xmlns="http://www.w3.org/2000/svg" className={className} fill="none" viewBox="0 0 24 24" strokeWidth={1.9} stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" d="M12 6v6l4 2m6-2a10 10 0 1 1-20 0 10 10 0 0 1 20 0Z" />
        </svg>
    );
}

function CheckIcon({ className = 'h-4 w-4' }) {
    return (
        <svg xmlns="http://www.w3.org/2000/svg" className={className} fill="none" viewBox="0 0 24 24" strokeWidth={2.4} stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" d="M4.5 12.75l6 6 9-13.5" />
        </svg>
    );
}

function EditIcon({ className = 'h-3 w-3' }) {
    return (
        <svg xmlns="http://www.w3.org/2000/svg" className={className} fill="none" viewBox="0 0 24 24" strokeWidth={2} stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125" />
        </svg>
    );
}

function StepBadge({ children }) {
    return (
        <span className="flex h-6 w-6 items-center justify-center rounded-full bg-emerald-100 text-xs font-bold text-emerald-700">
            {children}
        </span>
    );
}

function BookingStepper({ step, bisaKe, onPilih }) {
    return (
        <ol
            aria-label="Langkah booking"
            className="flex items-center gap-1 rounded-2xl border border-emerald-100 bg-white p-2 shadow-sm"
        >
            {langkahBooking.map((langkah, index) => {
                const aktif = step === langkah.nomor;
                const selesai = step > langkah.nomor;
                const bisa = bisaKe(langkah.nomor);

                return (
                    <li key={langkah.nomor} className="flex min-w-0 flex-1 items-center gap-1">
                        <button
                            type="button"
                            onClick={() => onPilih(langkah.nomor)}
                            disabled={!bisa}
                            aria-current={aktif ? 'step' : undefined}
                            aria-label={`Langkah ${langkah.nomor}: ${langkah.label}`}
                            className={`flex min-w-0 flex-1 items-center gap-1.5 rounded-xl px-1.5 py-1.5 text-left transition ${aktif
                                ? 'bg-emerald-600 text-white shadow-sm'
                                : selesai
                                    ? 'bg-emerald-50 text-emerald-800'
                                    : 'text-slate-400'
                                } ${bisa ? 'cursor-pointer' : 'cursor-not-allowed'} ${!aktif && bisa ? 'hover:bg-emerald-50' : ''
                                }`}
                        >
                            <span
                                className={`flex h-6 w-6 shrink-0 items-center justify-center rounded-full text-[11px] font-bold ${aktif
                                    ? 'bg-white/20 text-white'
                                    : selesai
                                        ? 'bg-emerald-600 text-white'
                                        : 'bg-slate-100 text-slate-400'
                                    }`}
                            >
                                {selesai ? <CheckIcon className="h-3.5 w-3.5" /> : langkah.nomor}
                            </span>
                            <span className="min-w-0">
                                <span
                                    className={`block text-[8px] font-semibold uppercase tracking-wide ${aktif ? 'text-emerald-100' : 'text-slate-400'
                                        }`}
                                >
                                    Langkah {langkah.nomor}
                                </span>
                                <span className="block truncate text-[10px] font-bold leading-tight">{langkah.label}</span>
                            </span>
                        </button>
                        {index < langkahBooking.length - 1 && (
                            <span
                                className={`h-0.5 w-2 shrink-0 rounded-full ${selesai ? 'bg-emerald-500' : 'bg-slate-200'}`}
                            />
                        )}
                    </li>
                );
            })}
        </ol>
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

function BookingSkeleton() {
    return (
        <div aria-busy="true" aria-label="Memuat data booking" className="space-y-4">
            {Array.from({ length: 3 }).map((_, index) => (
                <div key={index} className="animate-pulse rounded-2xl border border-emerald-100 bg-white p-4 shadow-sm">
                    <div className="mb-3 flex items-center gap-2">
                        <div className="h-6 w-6 rounded-full bg-emerald-100" />
                        <div className="h-3.5 w-28 rounded bg-slate-200" />
                    </div>
                    {index === 0 ? (
                        <div className="h-10 w-full rounded-xl bg-slate-100" />
                    ) : index === 1 ? (
                        <div className="grid grid-cols-2 gap-2.5">
                            <div className="h-30 rounded-2xl bg-slate-100" />
                            <div className="h-30 rounded-2xl bg-slate-100" />
                        </div>
                    ) : (
                        <div className="grid grid-cols-2 gap-2">
                            <div className="h-14 rounded-xl bg-slate-100" />
                            <div className="h-14 rounded-xl bg-slate-100" />
                        </div>
                    )}
                </div>
            ))}

            <div className="animate-pulse overflow-hidden rounded-2xl bg-emerald-50 p-4">
                <div className="h-3.5 w-36 rounded bg-emerald-100" />
                <div className="mt-3 space-y-2">
                    <div className="h-2.5 w-full rounded bg-emerald-100/70" />
                    <div className="h-2.5 w-2/3 rounded bg-emerald-100/70" />
                </div>
            </div>
        </div>
    );
}

// mejas/jamMains/hargas/bookedSlots/settingBookingRooms/vouchers sengaja di-default agar aman saat deferred (belum dimuat).
export default function Booking({ pengguna, mejas = [], jamMains = [], hargas = [], bookedSlots = [], settingBookingRooms = [], vouchers = [] }) {
    // Default destrukturisasi menyembunyikan undefined, cek status deferred via page props.
    const deferredKeys = ['mejas', 'jamMains', 'hargas', 'bookedSlots', 'settingBookingRooms', 'vouchers'];
    const pageProps = usePage().props;
    const bookingDataDimuat = deferredKeys.every((key) => pageProps[key] !== undefined);

    const presetVoucher = new URLSearchParams(window.location.search).get('voucher') || '';
    const voucherPresetValid = vouchers.some((voucher) => voucher.kode_voucher === presetVoucher);

    const { data, setData, post, processing, errors } = useForm({
        tgl_main: todayISO(),
        meja_id: '',
        jam_main_ids: [],
        kode_voucher: voucherPresetValid ? presetVoucher : '',
        tipe_booking: '',
    });

    const [step, setStep] = useState(1);
    const [sekarang, setSekarang] = useState(() => new Date());

    useEffect(() => {
        const timer = setInterval(() => setSekarang(new Date()), 30000);

        return () => clearInterval(timer);
    }, []);

    // Voucher preset dari URL diisi ulang setelah data voucher deferred selesai dimuat.
    useEffect(() => {
        if (presetVoucher && voucherPresetValid && !data.kode_voucher) {
            setData('kode_voucher', presetVoucher);
        }
    }, [presetVoucher, voucherPresetValid, data.kode_voucher, setData]);

    const [deferredStuck, setDeferredStuck] = useState(false);
    const [deferredRetry, setDeferredRetry] = useState(0);

    useEffect(() => {
        if (bookingDataDimuat) {
            setDeferredStuck(false);

            return;
        }

        // Jangan biarkan halaman macet selamanya saat request deferred gagal senyap.
        const timer = setTimeout(() => setDeferredStuck(true), 10000);

        return () => clearTimeout(timer);
    }, [bookingDataDimuat, deferredRetry]);

    const retryDeferred = () => {
        setDeferredStuck(false);
        setDeferredRetry((count) => count + 1);
        router.reload({ only: deferredKeys, preserveScroll: true });
    };

    const tanggalSekarang = [
        sekarang.getFullYear(),
        String(sekarang.getMonth() + 1).padStart(2, '0'),
        String(sekarang.getDate()).padStart(2, '0'),
    ].join('-');
    const jamSekarang = `${String(sekarang.getHours()).padStart(2, '0')}:${String(sekarang.getMinutes()).padStart(2, '0')}`;

    const jamSudahLewat = (jam) => data.tgl_main === tanggalSekarang && formatTime(jam.jam_mulai) <= jamSekarang;

    const inputClass = (field) =>
        `w-full rounded-xl border px-3.5 py-2.5 text-sm text-slate-800 shadow-sm transition focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 ${errors[field] ? 'border-rose-300 bg-rose-50/40' : 'border-slate-200 bg-white'
        }`;

    const hariTerpilih = data.tgl_main ? hariMap[new Date(`${data.tgl_main}T00:00:00`).getDay()] : '';
    const mejaTerpilih = mejas.find((meja) => String(meja.id) === String(data.meja_id));
    const mejaKey = mejaTerpilih ? String(mejaTerpilih.kode_meja || mejaTerpilih.id) : '';
    // Harga jam main mengikuti venue meja yang dipilih (tanpa fallback antar venue).
    const vanueTerpilih = mejaTerpilih?.vanue_id ?? null;
    const hargaVenue = (jam) =>
        hargas.find(
            (item) =>
                String(item.jam_main_id) === String(jam.id) &&
                item.hari === hariTerpilih &&
                String(item.vanue_id ?? '') === String(vanueTerpilih ?? ''),
        );

    const jamSudahDibooking = (jam) =>
        mejaKey !== '' &&
        bookedSlots.some(
            (booking) =>
                String(booking.id_meja) === mejaKey &&
                booking.tgl_main === data.tgl_main &&
                formatTime(booking.jam_mulai) === formatTime(jam.jam_mulai) &&
                formatTime(booking.jam_selesai) === formatTime(jam.jam_selesai),
        );

    const jamTerpilih = jamMains.filter((jam) => data.jam_main_ids.includes(String(jam.id)));
    const jamTampil = jamMains.filter((jam) => !jamSudahLewat(jam));
    const rincian = jamTerpilih.map((jam) => {
        const harga = hargaVenue(jam);

        return {
            jam,
            tersedia: Boolean(harga),
            hargaAsli: Number(harga?.harga) || 0,
            diskon: Number(harga?.diskon) || 0,
            total: Number(harga?.harga_akhir) || 0,
        };
    });
    const hargaAsli = rincian.reduce((total, item) => total + item.hargaAsli, 0);
    const totalDiskon = rincian.reduce((total, item) => total + item.diskon, 0);
    const total = rincian.reduce((total, item) => total + item.total, 0);
    const hargaTidakTersedia = rincian.some((item) => !item.tersedia);
    const jamError = errors.jam_main_ids || errors['jam_main_ids.0'];
    const hasErrors = Object.keys(errors).length > 0;
    const voucherTerpilih = vouchers.find((voucher) => voucher.kode_voucher === data.kode_voucher);
    const voucherJamMismatch = Boolean(voucherTerpilih) && data.jam_main_ids.length !== Number(voucherTerpilih.jumah_jam);
    const voucherJamMessage = 'Jumlah jam yang Anda pilih kurang atau lebih dari jumlah jam yang sudah ditentukan untuk voucher ini.';

    const tampilkanPeringatanDurasiVoucher = () => {
        Swal.fire({
            icon: 'warning',
            title: 'Durasi Voucher Belum Sesuai',
            text: voucherJamMessage,
            confirmButtonText: 'Periksa Jam',
            buttonsStyling: false,
            width: 'min(26rem, calc(100vw - 2rem))',
            padding: '2rem 1.75rem',
            backdrop: 'rgba(15, 23, 42, 0.58)',
            customClass: {
                container: 'app-alert-container',
                popup: 'app-alert',
                title: 'app-alert-title',
                icon: 'app-alert-icon',
                htmlContainer: 'app-alert-message',
                actions: 'app-alert-actions',
                confirmButton: 'app-alert-confirm',
            },
        });
    };

    const settingAktif = settingBookingRooms[0] ?? null;
    const jumlahUser = Math.max(1, Number(settingAktif?.jumlah_user) || 1);
    const hargaDibagi = String(settingAktif?.pembayaran ?? '').toLowerCase() === 'all';
    const opsiBooking = settingAktif
        ? [
            {
                value: 'public',
                label: 'Public Booking',
                description: hargaDibagi
                    ? `Anda dapat mencari teman bermain anda secara random disini`
                    : 'Anda dapat mencari teman bermain anda secara random disini',
            },
            {
                value: 'private',
                label: 'Private Booking',
                description: 'Anda dapat bermain dengan teman-teman anda',
            },
        ]
        : [];
    const opsiBookingKey = opsiBooking.map((opsi) => opsi.value).join(',');
    const tipeBookingTerpilih = opsiBooking.find((opsi) => opsi.value === data.tipe_booking);
    const totalDibayar = data.tipe_booking === 'public' && hargaDibagi
        ? rincian.reduce((jumlah, item) => jumlah + Math.round(item.total / jumlahUser), 0)
        : total;

    const step1Lengkap = Boolean(data.tgl_main && data.meja_id);
    const step2Lengkap = data.jam_main_ids.length > 0 && (opsiBooking.length === 0 || Boolean(data.tipe_booking));

    // Sinkronkan pilihan tipe booking dengan setting aktif yang tersedia.
    useEffect(() => {
        if (!bookingDataDimuat) {
            return;
        }

        const values = opsiBookingKey === '' ? [] : opsiBookingKey.split(',');

        if (data.tipe_booking && !values.includes(data.tipe_booking)) {
            setData('tipe_booking', '');
        }
    }, [bookingDataDimuat, opsiBookingKey, data.tipe_booking, setData]);

    const bisaKeStep = (target) => {
        if (target <= step) {
            return true;
        }

        if (target === 2) {
            return step1Lengkap;
        }

        if (target === 3) {
            return step1Lengkap && step2Lengkap;
        }

        return false;
    };

    const pindahStep = (target) => {
        if (!bisaKeStep(target)) {
            return;
        }

        // Voucher dengan durasi belum sesuai harus diselesaikan dulu di langkah 2.
        if (target === 3 && voucherJamMismatch) {
            tampilkanPeringatanDurasiVoucher();

            return;
        }

        setStep(target);
        window.scrollTo({ top: 0, behavior: 'smooth' });
    };

    // Setelah submit gagal, kembalikan user ke langkah yang bermasalah.
    useEffect(() => {
        if (!hasErrors) {
            return;
        }

        if (errors.tgl_main || errors.meja_id) {
            setStep(1);
        } else if (errors.jam_main_ids || errors['jam_main_ids.0'] || errors.kode_voucher || errors.tipe_booking) {
            setStep(2);
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [errors]);

    const toggleJam = (id) => {
        const value = String(id);
        const sudahDipilih = data.jam_main_ids.includes(value);

        setData(
            'jam_main_ids',
            sudahDipilih
                ? data.jam_main_ids.filter((item) => item !== value)
                : [...data.jam_main_ids, value],
        );
    };

    useEffect(() => {
        if (!mejaKey || !data.tgl_main) {
            return;
        }

        const tersedia = data.jam_main_ids.filter((id) => {
            const jam = jamMains.find((item) => String(item.id) === String(id));

            return jam ? !jamSudahDibooking(jam) && !jamSudahLewat(jam) : false;
        });

        if (tersedia.length !== data.jam_main_ids.length) {
            setData('jam_main_ids', tersedia);
        }
    }, [mejaKey, data.tgl_main, data.jam_main_ids, bookedSlots, jamMains, sekarang]);

    const submit = (event) => {
        event.preventDefault();

        // Enter pada input step 1/2 hanya memajukan langkah, bukan membuat booking.
        if (step < 3) {
            pindahStep(step + 1);

            return;
        }

        if (voucherJamMismatch) {
            tampilkanPeringatanDurasiVoucher();

            return;
        }

        post('/booking', {
            preserveScroll: true,
        });
    };

    return (
        <>
            <Head title="Booking Mahjong" />

            <header className="relative overflow-hidden rounded-b-[2rem] bg-gradient-to-br from-emerald-600 to-emerald-800 px-5 pb-12 pt-6 text-white">
                <div className="pointer-events-none absolute -right-10 -top-10 h-36 w-36 rounded-full bg-white/10" />
                <div className="relative flex items-center gap-3">
                    <Link
                        href="/home"
                        className="flex h-10 w-10 items-center justify-center rounded-full bg-white/10 ring-1 ring-white/20 transition hover:bg-white/20"
                        aria-label="Kembali ke home"
                    >
                        <BackIcon />
                    </Link>
                    <div>
                        <h1 className="text-lg font-bold">Booking Mahjong</h1>
                        <p className="text-xs text-emerald-100">
                            Langkah {step} dari 3 &middot; {langkahBooking[step - 1].judul}
                        </p>
                    </div>
                </div>
            </header>

            <form onSubmit={submit} className="relative -mt-7 space-y-4 px-5 pb-8">
                <BookingStepper step={step} bisaKe={bisaKeStep} onPilih={pindahStep} />

                <Deferred
                    data={deferredKeys}
                    // fallback wajib truthy di @inertiajs/react (throw jika falsy) — pakai skeleton, bukan null.
                    fallback={deferredStuck ? <DeferredTimeoutError onReload={retryDeferred} /> : <BookingSkeleton />}
                >
                    <div className="space-y-4">
                        {voucherJamMismatch && step >= 2 && data.jam_main_ids.length > 0 && (
                            <div role="alert" className="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                                {voucherJamMessage}
                            </div>
                        )}
                        {hasErrors && (
                            <div className="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                                <p className="font-semibold">Periksa kembali data booking.</p>
                                <p className="mt-0.5 text-xs text-rose-600">Beberapa data belum lengkap atau tidak valid.</p>
                            </div>
                        )}

                        {step === 1 && (
                            <section className="rounded-2xl border border-emerald-100 bg-white p-4 shadow-sm">
                                <div className="mb-3 flex items-center gap-2">
                                    <StepBadge>1</StepBadge>
                                    <h2 className="text-sm font-bold text-slate-800">Tanggal & Meja</h2>
                                </div>

                                <label htmlFor="tgl_main" className="mb-1.5 block text-xs font-medium text-slate-600">
                                    Pilih tanggal main
                                </label>
                                <input
                                    id="tgl_main"
                                    type="date"
                                    value={data.tgl_main}
                                    min={todayISO()}
                                    onChange={(event) => setData('tgl_main', event.target.value)}
                                    className={inputClass('tgl_main')}
                                />
                                {errors.tgl_main && <p className="mt-1.5 text-xs font-medium text-rose-600">{errors.tgl_main}</p>}
                                <div className="flex flex-wrap items-center gap-2">
                                    {hariTerpilih && (
                                        <p className="mt-2 inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-1 text-[11px] font-semibold text-emerald-700">
                                            <span className="h-1.5 w-1.5 rounded-full bg-emerald-500" />
                                            Hari {hariTerpilih}
                                        </p>
                                    )}
                                    {mejaTerpilih && (
                                        <p className="mt-2 inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-1 text-[11px] font-semibold text-emerald-700">
                                            <span className="h-1.5 w-1.5 rounded-full bg-emerald-500" />
                                            {mejaTerpilih.nama_meja}
                                        </p>
                                    )}
                                </div>

                                <div className="my-3 border-t border-dashed border-emerald-100" />

                                <p className="mb-2 text-xs font-medium text-slate-600">Pilih meja</p>

                                {!data.tgl_main ? (
                                    <div className="rounded-xl border border-dashed border-slate-200 bg-slate-50 px-3 py-4 text-center text-xs text-slate-500">
                                        Pilih tanggal main terlebih dahulu.
                                    </div>
                                ) : mejas.length === 0 ? (
                                    <div className="rounded-xl border border-dashed border-slate-200 bg-slate-50 px-3 py-4 text-center text-xs text-slate-500">
                                        Belum ada meja tersedia.
                                    </div>
                                ) : (
                                    <div className="grid grid-cols-2 gap-2.5">
                                        {mejas.map((meja) => {
                                            const active = String(data.meja_id) === String(meja.id);

                                            return (
                                                <button
                                                    key={meja.id}
                                                    type="button"
                                                    onClick={() => setData('meja_id', String(meja.id))}
                                                    aria-pressed={active}
                                                    className={`group overflow-hidden rounded-2xl border text-left transition duration-200 ${active
                                                        ? 'scale-[1.02] border-emerald-600 shadow-lg shadow-emerald-900/25 ring-2 ring-emerald-500/60'
                                                        : 'border-slate-200 hover:-translate-y-0.5 hover:border-emerald-300 hover:shadow-md hover:shadow-emerald-900/10'
                                                        }`}
                                                >
                                                    <div className="relative aspect-[4/3] overflow-hidden bg-emerald-900">
                                                        <img
                                                            src="/images/meja-mahjong.svg"
                                                            alt={`Meja ${meja.nama_meja}`}
                                                            loading="lazy"
                                                            className={`h-full w-full object-cover transition duration-500 ${active ? 'scale-110 brightness-110' : 'group-hover:scale-105'
                                                                }`}
                                                        />
                                                        <div
                                                            className={`absolute inset-0 bg-gradient-to-t from-emerald-950/80 via-emerald-950/10 to-transparent transition ${active ? 'via-emerald-800/40' : ''
                                                                }`}
                                                        />
                                                        {active && (
                                                            <div className="absolute inset-0 bg-emerald-500/15 ring-2 ring-inset ring-emerald-400/70" />
                                                        )}
                                                        <span className="absolute left-1.5 top-1.5 rounded-full bg-white/90 px-1.5 py-0.5 text-[9px] font-bold text-emerald-700 shadow-sm backdrop-blur">
                                                            {meja.kode_meja || 'MEJA'}
                                                        </span>
                                                        {active ? (
                                                            <span className="absolute right-1.5 top-1.5 inline-flex items-center gap-1 rounded-full bg-emerald-500 px-2 py-0.5 text-[9px] font-bold text-white shadow-md">
                                                                <CheckIcon className="h-2.5 w-2.5" />
                                                                Dipilih
                                                            </span>
                                                        ) : (
                                                            <span className="absolute right-1.5 top-1.5 flex h-5 w-5 items-center justify-center rounded-full bg-white/80 text-emerald-700 shadow-sm backdrop-blur">
                                                                <span className="h-1.5 w-1.5 rounded-full bg-emerald-500" />
                                                            </span>
                                                        )}
                                                        <div className="absolute inset-x-2 bottom-1.5 flex items-end justify-between gap-2">
                                                            <p className="truncate text-[11px] font-bold text-white drop-shadow">
                                                                {meja.nama_meja}
                                                            </p>
                                                            {active && (
                                                                <span className="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-emerald-500 text-white shadow-md ring-2 ring-white/80">
                                                                    <CheckIcon className="h-3.5 w-3.5" />
                                                                </span>
                                                            )}
                                                        </div>
                                                    </div>
                                                </button>
                                            );
                                        })}
                                    </div>
                                )}
                                {errors.meja_id && <p className="mt-1.5 text-xs font-medium text-rose-600">{errors.meja_id}</p>}
                            </section>
                        )}

                        {step === 2 && (
                            <>
                                <section className="rounded-2xl border border-emerald-100 bg-white p-4 shadow-sm">
                                    <div className="mb-3 flex items-center justify-between gap-2">
                                        <div className="flex items-center gap-2">
                                            <StepBadge>2</StepBadge>
                                            <h2 className="text-sm font-bold text-slate-800"> Pilih   Jam Main</h2>
                                        </div>
                                        {data.jam_main_ids.length > 0 && (
                                            <span className="rounded-full bg-emerald-50 px-2.5 py-0.5 text-[10px] font-bold text-emerald-700">
                                                {data.jam_main_ids.length} sesi dipilih
                                            </span>
                                        )}
                                    </div>

                                    <div className="mb-3 flex flex-wrap items-center gap-1.5 rounded-xl bg-emerald-50/70 px-3 py-2 text-[11px] font-semibold text-emerald-800">
                                        <span className="rounded-full bg-white px-2 py-0.5 shadow-sm">
                                            {formatTanggal(data.tgl_main)}
                                        </span>
                                        <span className="rounded-full bg-white px-2 py-0.5 shadow-sm">
                                            {mejaTerpilih?.nama_meja || 'Meja belum dipilih'}
                                        </span>
                                        <button
                                            type="button"
                                            onClick={() => pindahStep(1)}
                                            className="ml-auto inline-flex items-center gap-1 rounded-full bg-emerald-600 px-2.5 py-1 text-[10px] font-bold text-white shadow-sm shadow-emerald-600/30 transition hover:bg-emerald-700 hover:shadow-md active:scale-95"
                                        >
                                            <EditIcon className="h-3 w-3" />
                                            Ubah
                                        </button>
                                    </div>

                                    {!data.meja_id ? (
                                        <div className="rounded-xl border border-dashed border-slate-200 bg-slate-50 px-3 py-4 text-center text-xs text-slate-500">
                                            Pilih meja terlebih dahulu.
                                        </div>
                                    ) : jamTampil.length === 0 ? (
                                        <div className="rounded-xl border border-dashed border-slate-200 bg-slate-50 px-3 py-4 text-center text-xs text-slate-500">
                                            {data.tgl_main === tanggalSekarang
                                                ? 'Semua jam main untuk hari ini sudah lewat.'
                                                : 'Belum ada jam main aktif.'}
                                        </div>
                                    ) : (
                                        <>
                                            <p className="mb-2 text-[11px] text-slate-500">
                                                Bisa pilih lebih dari satu sesi jam main.
                                            </p>
                                            <div className="grid grid-cols-2 gap-2">
                                                {jamTampil.map((jam) => {
                                                    const booked = jamSudahDibooking(jam);
                                                    const active = !booked && data.jam_main_ids.includes(String(jam.id));
                                                    const hargaJam = hargaVenue(jam);
                                                    const diskonJam = Number(hargaJam?.diskon) || 0;

                                                    return (
                                                        <button
                                                            key={jam.id}
                                                            type="button"
                                                            onClick={() => toggleJam(jam.id)}
                                                            disabled={booked}
                                                            aria-pressed={active}
                                                            aria-disabled={booked}
                                                            className={`flex items-start gap-2 rounded-xl border px-3 py-2.5 text-left transition ${booked
                                                                ? 'cursor-not-allowed border-slate-200 bg-slate-100 text-slate-400'
                                                                : active
                                                                    ? 'border-emerald-600 bg-emerald-600 text-white shadow-sm'
                                                                    : 'border-slate-200 bg-white text-slate-700 hover:border-emerald-300'
                                                                }`}
                                                        >
                                                            <span
                                                                className={`mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full ${booked
                                                                    ? 'bg-slate-200 text-slate-400'
                                                                    : active
                                                                        ? 'bg-white/20 text-white'
                                                                        : 'bg-emerald-50 text-emerald-600'
                                                                    }`}
                                                            >
                                                                {active ? <CheckIcon className="h-3 w-3" /> : <ClockIcon className="h-3.5 w-3.5" />}
                                                            </span>
                                                            <span className="min-w-0 flex-1">
                                                                <span className={`block text-xs font-semibold ${booked ? 'line-through' : ''}`}>
                                                                    {formatTime(jam.jam_mulai)} - {formatTime(jam.jam_selesai)}
                                                                </span>
                                                                {booked ? (
                                                                    <span className="mt-0.5 inline-flex items-center gap-1 text-[10px] font-bold text-rose-500">
                                                                        <span className="h-1.5 w-1.5 rounded-full bg-rose-500" />
                                                                        Booked
                                                                    </span>
                                                                ) : hargaJam ? (
                                                                    <span
                                                                        className={`mt-0.5 flex flex-wrap items-center gap-1 text-[10px] font-bold ${active ? 'text-emerald-50' : 'text-emerald-700'
                                                                            }`}
                                                                    >
                                                                        {formatRupiah(hargaJam.harga_akhir)}
                                                                        {diskonJam > 0 && (
                                                                            <span
                                                                                className={`font-medium line-through ${active ? 'text-emerald-200/80' : 'text-slate-400'
                                                                                    }`}
                                                                            >
                                                                                {formatRupiah(hargaJam.harga)}
                                                                            </span>
                                                                        )}
                                                                    </span>
                                                                ) : (
                                                                    <span
                                                                        className={`mt-0.5 block text-[10px] ${active ? 'text-emerald-100' : 'text-slate-400'
                                                                            }`}
                                                                    >
                                                                        Harga belum tersedia
                                                                    </span>
                                                                )}
                                                            </span>
                                                        </button>
                                                    );
                                                })}
                                            </div>
                                        </>
                                    )}
                                    {jamError && <p className="mt-1.5 text-xs font-medium text-rose-600">{jamError}</p>}
                                </section>

                                {opsiBooking.length > 0 && (
                                    <section className="rounded-2xl border border-emerald-100 bg-white p-4 shadow-sm">
                                        <div className="mb-3 flex items-center gap-2">
                                            <StepBadge>B</StepBadge>
                                            <h2 className="text-sm font-bold text-slate-800">Tipe Booking</h2>
                                        </div>
                                        <p className="mb-2 text-[11px] text-slate-500">
                                            Pilih tipe booking sesuai aturan pembayaran yang sedang aktif.
                                        </p>
                                        <div className="grid gap-2 sm:grid-cols-2">
                                            {opsiBooking.map((opsi) => {
                                                const active = data.tipe_booking === opsi.value;

                                                return (
                                                    <button
                                                        key={opsi.value}
                                                        type="button"
                                                        onClick={() => setData('tipe_booking', opsi.value)}
                                                        aria-pressed={active}
                                                        className={`flex items-start gap-2 rounded-xl border px-3 py-2.5 text-left transition ${active
                                                            ? 'border-emerald-600 bg-emerald-600 text-white shadow-sm'
                                                            : 'border-slate-200 bg-white text-slate-700 hover:border-emerald-300'
                                                            }`}
                                                    >
                                                        <span
                                                            className={`mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full ${active ? 'bg-white/20 text-white' : 'bg-emerald-50 text-emerald-600'
                                                                }`}
                                                        >
                                                            {active ? <CheckIcon className="h-3 w-3" /> : <span className="h-1.5 w-1.5 rounded-full bg-emerald-500" />}
                                                        </span>
                                                        <span className="min-w-0 flex-1">
                                                            <span className="block text-xs font-semibold">{opsi.label}</span>
                                                            <span className={`mt-0.5 block text-[10px] ${active ? 'text-emerald-50' : 'text-slate-500'}`}>
                                                                {opsi.description}
                                                            </span>
                                                            {total > 0 && (
                                                                <span className={`mt-1 block text-[11px] font-bold ${active ? 'text-white' : 'text-emerald-700'}`}>
                                                                    {formatRupiah(opsi.value === 'public' && hargaDibagi ? totalDibayar : total)}
                                                                    {opsi.value === 'public' && hargaDibagi ? ' / user' : ''}
                                                                </span>
                                                            )}
                                                        </span>
                                                    </button>
                                                );
                                            })}
                                        </div>
                                        {errors.tipe_booking && (
                                            <p className="mt-1.5 text-xs font-medium text-rose-600">{errors.tipe_booking}</p>
                                        )}
                                    </section>
                                )}

                                {voucherPresetValid && (
                                    <section className="rounded-2xl border border-emerald-100 bg-emerald-50 p-4 shadow-sm">
                                        <p className="text-sm font-semibold text-emerald-800">
                                            Voucher digunakan: {data.kode_voucher}
                                        </p>
                                        <p className="mt-1 text-[11px] text-emerald-700">
                                            Pilih tepat sesuai kuota jam voucher sebelum melanjutkan booking.
                                        </p>
                                    </section>
                                )}

                                {vouchers.length > 0 && !voucherPresetValid && (
                                    <section className="rounded-2xl border border-emerald-100 bg-white p-4 shadow-sm">
                                        <div className="mb-3 flex items-center gap-2">
                                            {/* <StepBadge>V</StepBadge> */}
                                            <h2 className="text-sm font-bold text-slate-800">Gunakan Voucher</h2>
                                        </div>
                                        <label htmlFor="kode_voucher" className="mb-1.5 block text-xs font-medium text-slate-600">
                                            Pilih voucher (opsional)
                                        </label>
                                        <select
                                            id="kode_voucher"
                                            value={data.kode_voucher}
                                            onChange={(event) => setData('kode_voucher', event.target.value)}
                                            className="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-800 shadow-sm transition focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20"
                                        >
                                            <option value="">Tanpa voucher</option>
                                            {vouchers.map((voucher) => (
                                                <option key={voucher.id} value={voucher.kode_voucher}>
                                                    {voucher.kode_voucher} - {voucher.jumah_jam ?? 0} jam gratis
                                                </option>
                                            ))}
                                        </select>
                                        {errors.kode_voucher && (
                                            <p className="mt-1.5 text-xs font-medium text-rose-600">{errors.kode_voucher}</p>
                                        )}
                                        <p className="mt-2 text-[11px] text-slate-500">
                                            {voucherTerpilih
                                                ? `Pilih tepat ${voucherTerpilih.jumah_jam} sesi jam untuk menggunakan voucher ini.`
                                                : 'Voucher hanya berlaku untuk jumlah sesi yang sesuai kuota jam voucher.'}
                                        </p>
                                    </section>
                                )}
                            </>
                        )}

                        {step === 3 && (
                            <>
                                <section className="flex items-center gap-3 rounded-2xl border border-emerald-100 bg-white px-4 py-3 shadow-sm">
                                    <span className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-sm font-bold uppercase text-emerald-700">
                                        {pengguna?.nama_depan?.charAt(0) ?? '?'}
                                    </span>
                                    <div className="min-w-0 flex-1">
                                        <p className="text-[10px] font-medium uppercase tracking-wide text-slate-400">Booking untuk</p>
                                        <p className="truncate text-sm font-semibold text-slate-800">{namaPengguna(pengguna) || '-'}</p>
                                        <p className="text-[11px] text-slate-500">{pengguna?.nowa || '-'}</p>
                                    </div>
                                    <Link href="/profil" className="text-[11px] font-bold text-emerald-700">
                                        Profil
                                    </Link>
                                </section>

                                <section className="overflow-hidden rounded-2xl bg-gradient-to-br from-emerald-700 to-emerald-900 p-4 text-white shadow-lg shadow-emerald-200">
                                    <div className="flex items-center justify-between">
                                        <h2 className="text-sm font-bold">Ringkasan Booking</h2>
                                        <span className="rounded-full bg-white/15 px-2.5 py-0.5 text-[10px] font-semibold">
                                            {hariTerpilih || 'Pilih tanggal'}
                                        </span>
                                    </div>

                                    <div className="mt-3 space-y-2 text-xs text-emerald-100">
                                        <div className="flex items-start justify-between gap-3">
                                            <span className="shrink-0">Jam main</span>
                                            {jamTerpilih.length > 0 ? (
                                                <div className="flex flex-wrap justify-end gap-1">
                                                    {jamTerpilih.map((jam) => (
                                                        <span
                                                            key={jam.id}
                                                            className="rounded-full bg-white/15 px-2 py-0.5 text-[10px] font-semibold text-white"
                                                        >
                                                            {formatTime(jam.jam_mulai)} - {formatTime(jam.jam_selesai)}
                                                        </span>
                                                    ))}
                                                </div>
                                            ) : (
                                                <span className="font-semibold text-white">Belum dipilih</span>
                                            )}
                                        </div>
                                        <div className="flex items-center justify-between">
                                            <span>Meja</span>
                                            <span className="font-semibold text-white">
                                                {mejaTerpilih ? mejaTerpilih.nama_meja : 'Belum dipilih'}
                                            </span>
                                        </div>
                                        {tipeBookingTerpilih && (
                                            <div className="flex items-center justify-between">
                                                <span>Tipe booking</span>
                                                <span className="font-semibold text-white">{tipeBookingTerpilih.label}</span>
                                            </div>
                                        )}
                                    </div>

                                    <div className="my-3 border-t border-white/15" />

                                    {hargaTidakTersedia ? (
                                        <p className="rounded-xl bg-rose-500/20 px-3 py-2 text-xs font-medium text-rose-100">
                                            Harga untuk hari dan jam yang dipilih belum tersedia. Silakan pilih jadwal lain.
                                        </p>
                                    ) : jamTerpilih.length === 0 ? (
                                        <p className="text-xs text-emerald-100">Pilih jam main untuk melihat total bayar.</p>
                                    ) : (
                                        <div className="space-y-1.5 text-xs">
                                            <div className="flex items-center justify-between text-emerald-100">
                                                <span>Harga ({jamTerpilih.length} sesi)</span>
                                                <span>{formatRupiah(hargaAsli)}</span>
                                            </div>
                                            {totalDiskon > 0 && (
                                                <div className="flex items-center justify-between text-rose-200">
                                                    <span>Diskon</span>
                                                    <span>-{formatRupiah(totalDiskon)}</span>
                                                </div>
                                            )}
                                            <div className="flex items-end justify-between pt-1">
                                                <span className="text-xs text-emerald-100">
                                                    {data.tipe_booking === 'public' && hargaDibagi ? 'Total bayar / user' : 'Total bayar'}
                                                </span>
                                                <span className="text-xl font-bold text-white">
                                                    {data.kode_voucher ? 'Gratis (Voucher)' : formatRupiah(totalDibayar)}
                                                </span>
                                            </div>
                                            {data.tipe_booking === 'public' && hargaDibagi && !data.kode_voucher && jumlahUser > 1 && (
                                                <p className="text-[10px] text-emerald-100">
                                                    Harga {formatRupiah(total)} dibagi {jumlahUser} user.
                                                </p>
                                            )}
                                        </div>
                                    )}

                                    <div className="mt-3 flex items-center justify-between border-t border-white/15 pt-3 text-[11px] text-emerald-100">
                                        <span>Tanggal main</span>
                                        <button
                                            type="button"
                                            onClick={() => pindahStep(1)}
                                            className="inline-flex items-center gap-1 rounded-full bg-white/15 px-2.5 py-1 text-[10px] font-bold text-white ring-1 ring-white/20 transition hover:bg-white/25 active:scale-95"
                                        >
                                            {formatTanggal(data.tgl_main)}
                                            <EditIcon className="h-3 w-3" />
                                        </button>
                                    </div>
                                </section>

                                <section className="rounded-2xl border border-emerald-100 bg-white p-4 shadow-sm">
                                    <div className="mb-3 flex items-center gap-2">
                                        <StepBadge>3</StepBadge>
                                        <h2 className="text-sm font-bold text-slate-800">Pembayaran</h2>
                                    </div>
                                    <p className="text-[11px] leading-relaxed text-slate-500">
                                        Tekan <span className="font-semibold text-slate-700">Lanjut ke Pembayaran</span> untuk
                                        membuat booking. Kamu akan diarahkan ke halaman pembayaran untuk transfer dan
                                        mengunggah bukti bayar.
                                    </p>
                                </section>
                            </>
                        )}

                        <div className="flex items-center gap-2">
                            {step > 1 && (
                                <button
                                    type="button"
                                    onClick={() => pindahStep(step - 1)}
                                    className="flex items-center justify-center gap-1.5 rounded-2xl border border-emerald-200 bg-white px-4 py-3.5 text-sm font-bold text-emerald-700 shadow-sm transition hover:bg-emerald-50"
                                >
                                    Kembali
                                </button>
                            )}

                            {step < 3 ? (
                                <button
                                    type="button"
                                    onClick={() => pindahStep(step + 1)}
                                    disabled={step === 1 ? !step1Lengkap : !step2Lengkap}
                                    className="flex flex-1 items-center justify-center gap-2 rounded-2xl bg-emerald-600 px-5 py-3.5 text-sm font-bold text-white shadow-lg shadow-emerald-200 transition hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-60"
                                >
                                    {step === 1 ? 'Lanjut Pilih Jam Main' : 'Lanjut ke Pembayaran'}
                                </button>
                            ) : (
                                <button
                                    type="submit"
                                    disabled={processing || hargaTidakTersedia || jamTerpilih.length === 0}
                                    className="flex flex-1 items-center justify-center gap-2 rounded-2xl bg-emerald-600 px-5 py-3.5 text-sm font-bold text-white shadow-lg shadow-emerald-200 transition hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-60"
                                >
                                    {processing ? 'Membuat booking...' : 'Lanjut ke Pembayaran'}
                                </button>
                            )}
                        </div>

                        {step === 1 && !step1Lengkap && (
                            <p className="text-center text-[11px] text-slate-400">
                                Pilih tanggal dan meja untuk melanjutkan.
                            </p>
                        )}
                        {step === 2 && !step2Lengkap && (
                            <p className="text-center text-[11px] text-slate-400">
                                {data.jam_main_ids.length === 0
                                    ? 'Pilih minimal satu jam main untuk melanjutkan.'
                                    : 'Pilih tipe booking untuk melanjutkan.'}
                            </p>
                        )}

                        {step === 3 && (
                            <p className="text-center text-[11px] leading-relaxed text-slate-400">
                                Selesaikan pembayaran di halaman berikutnya. Booking berstatus{' '}
                                <span className="font-semibold text-amber-500">menunggu</span> hingga disetujui admin.
                            </p>
                        )}
                    </div>
                </Deferred>
            </form>
        </>
    );
}

Booking.layout = (page) => <UserLayout>{page}</UserLayout>;
