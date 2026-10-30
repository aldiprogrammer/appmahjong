import { Head, Link, useForm } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import UserLayout from '../../Layouts/UserLayout';

const hariMap = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];

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

function StepBadge({ children }) {
    return (
        <span className="flex h-6 w-6 items-center justify-center rounded-full bg-emerald-100 text-xs font-bold text-emerald-700">
            {children}
        </span>
    );
}

export default function Booking({ pengguna, mejas, jamMains, hargas, bookedSlots = [] }) {
    const { data, setData, post, processing, errors } = useForm({
        tgl_main: todayISO(),
        meja_id: '',
        jam_main_ids: [],
        sistem_pembayaran: 'transfer',
    });

    const [sekarang, setSekarang] = useState(() => new Date());

    useEffect(() => {
        const timer = setInterval(() => setSekarang(new Date()), 30000);

        return () => clearInterval(timer);
    }, []);

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
        const harga = hargas.find(
            (item) => String(item.jam_main_id) === String(jam.id) && item.hari === hariTerpilih,
        );

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

        post('/booking', {
            preserveScroll: true,
        });
    };

    return (
        <>
            <Head title="Booking Meja" />

            <header className="relative overflow-hidden rounded-b-[2rem] bg-gradient-to-br from-emerald-600 to-emerald-800 px-5 pb-12 pt-6 text-white">
                <div className="pointer-events-none absolute -right-10 -top-10 h-36 w-36 rounded-full bg-white/10" />
                <div className="relative flex items-center gap-3">
                    <Link
                        href="/"
                        className="flex h-10 w-10 items-center justify-center rounded-full bg-white/10 ring-1 ring-white/20 transition hover:bg-white/20"
                        aria-label="Kembali ke home"
                    >
                        <BackIcon />
                    </Link>
                    <div>
                        <h1 className="text-lg font-bold">Booking Meja</h1>
                        <p className="text-xs text-emerald-100">Pilih tanggal, meja, lalu jam main</p>
                    </div>
                </div>
            </header>

            <form onSubmit={submit} className="relative -mt-7 space-y-4 px-5 pb-8">
                {hasErrors && (
                    <div className="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                        <p className="font-semibold">Periksa kembali data booking.</p>
                        <p className="mt-0.5 text-xs text-rose-600">Beberapa data belum lengkap atau tidak valid.</p>
                    </div>
                )}

                <section className="rounded-2xl border border-emerald-100 bg-white p-4 shadow-sm">
                    <div className="mb-3 flex items-center gap-2">
                        <StepBadge>1</StepBadge>
                        <h2 className="text-sm font-bold text-slate-800">Tanggal Main</h2>
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
                    {hariTerpilih && (
                        <p className="mt-2 inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-1 text-[11px] font-semibold text-emerald-700">
                            <span className="h-1.5 w-1.5 rounded-full bg-emerald-500" />
                            Hari {hariTerpilih}
                        </p>
                    )}
                </section>

                <section className="rounded-2xl border border-emerald-100 bg-white p-4 shadow-sm">
                    <div className="mb-3 flex items-center gap-2">
                        <StepBadge>2</StepBadge>
                        <h2 className="text-sm font-bold text-slate-800">Pilih Meja</h2>
                    </div>

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
                                                className={`h-full w-full object-cover transition duration-500 ${
                                                    active ? 'scale-110 brightness-110' : 'group-hover:scale-105'
                                                }`}
                                            />
                                            <div
                                                className={`absolute inset-0 bg-gradient-to-t from-emerald-950/80 via-emerald-950/10 to-transparent transition ${
                                                    active ? 'via-emerald-800/40' : ''
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

                <section className="rounded-2xl border border-emerald-100 bg-white p-4 shadow-sm">
                    <div className="mb-3 flex items-center justify-between gap-2">
                        <div className="flex items-center gap-2">
                            <StepBadge>3</StepBadge>
                            <h2 className="text-sm font-bold text-slate-800">Jam Main</h2>
                        </div>
                        {data.jam_main_ids.length > 0 && (
                            <span className="rounded-full bg-emerald-50 px-2.5 py-0.5 text-[10px] font-bold text-emerald-700">
                                {data.jam_main_ids.length} sesi dipilih
                            </span>
                        )}
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
                                    const hargaJam = hargas.find(
                                        (item) =>
                                            String(item.jam_main_id) === String(jam.id) && item.hari === hariTerpilih,
                                    );
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

                <section className="rounded-2xl border border-emerald-100 bg-white p-4 shadow-sm">
                    <div className="mb-3 flex items-center gap-2">
                        <StepBadge>4</StepBadge>
                        <h2 className="text-sm font-bold text-slate-800">Sistem Pembayaran</h2>
                    </div>

                    <div className="grid grid-cols-3 gap-2">
                        {[
                            { value: 'transfer', label: 'Transfer', desc: 'Bank' },
                            { value: 'qris', label: 'QRIS', desc: 'Scan QR' },
                            { value: 'tunai', label: 'Tunai', desc: 'Di tempat' },
                        ].map((payment) => {
                            const active = data.sistem_pembayaran === payment.value;

                            return (
                                <button
                                    key={payment.value}
                                    type="button"
                                    onClick={() => setData('sistem_pembayaran', payment.value)}
                                    className={`rounded-xl border px-2 py-2.5 text-center transition ${active
                                        ? 'border-emerald-600 bg-emerald-600 text-white shadow-sm'
                                        : 'border-slate-200 bg-white text-slate-700 hover:border-emerald-300'
                                        }`}
                                >
                                    <span className="block text-xs font-semibold">{payment.label}</span>
                                    <span className={`block text-[10px] ${active ? 'text-emerald-100' : 'text-slate-500'}`}>
                                        {payment.desc}
                                    </span>
                                </button>
                            );
                        })}
                    </div>
                    {errors.sistem_pembayaran && (
                        <p className="mt-1.5 text-xs font-medium text-rose-600">{errors.sistem_pembayaran}</p>
                    )}
                </section>

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
                        <div className="flex items-center justify-between">
                            <span>Pembayaran</span>
                            <span className="font-semibold capitalize text-white">{data.sistem_pembayaran}</span>
                        </div>
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
                                <span className="text-xs text-emerald-100">Total bayar</span>
                                <span className="text-xl font-bold text-white">{formatRupiah(total)}</span>
                            </div>
                        </div>
                    )}
                </section>

                <button
                    type="submit"
                    disabled={processing || hargaTidakTersedia}
                    className="flex w-full items-center justify-center gap-2 rounded-2xl bg-emerald-600 px-5 py-3.5 text-sm font-bold text-white shadow-lg shadow-emerald-200 transition hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-60"
                >
                    {processing ? 'Mengirim booking...' : 'Konfirmasi Booking'}
                </button>

                <p className="text-center text-[11px] leading-relaxed text-slate-400">
                    Booking akan berstatus <span className="font-semibold text-amber-500">menunggu</span> hingga
                    disetujui admin.
                </p>
            </form>
        </>
    );
}

Booking.layout = (page) => <UserLayout>{page}</UserLayout>;
