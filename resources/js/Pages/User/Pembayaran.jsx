import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import UserLayout from '../../Layouts/UserLayout';

function formatDate(value) {
    if (!value) {
        return '-';
    }

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
        return value;
    }

    return new Intl.DateTimeFormat('id-ID', {
        day: '2-digit',
        month: 'long',
        year: 'numeric',
    }).format(date);
}

function formatRupiah(value) {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(Number(value) || 0);
}

function statusMeta(status) {
    if (status === 'lunas') {
        return { label: 'Lunas', className: 'bg-emerald-100 text-emerald-700', dot: 'bg-emerald-500' };
    }

    if (status === 'menunggu_verifikasi') {
        return { label: 'Menunggu verifikasi', className: 'bg-amber-100 text-amber-700', dot: 'bg-amber-500' };
    }

    if (status === 'bayar_di_tempat') {
        return { label: 'Bayar di tempat', className: 'bg-sky-100 text-sky-700', dot: 'bg-sky-500' };
    }

    return { label: 'Belum bayar', className: 'bg-rose-100 text-rose-700', dot: 'bg-rose-500' };
}

function BackIcon() {
    return (
        <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5" fill="none" viewBox="0 0 24 24" strokeWidth={2} stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
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

export default function Pembayaran({ pembayaran, pengaturanPembayaran }) {
    const { flash } = usePage().props;
    const [previewBukti, setPreviewBukti] = useState(null);
    const [setuju, setSetuju] = useState(false);
    const meta = statusMeta(pembayaran.status_pembayaran);
    const lunas = pembayaran.status_pembayaran === 'lunas';

    const { data, setData, post, processing, errors } = useForm({
        sistem_pembayaran: pembayaran.sistem_pembayaran || 'transfer',
        bukti_pembayaran: null,
    });

    useEffect(() => {
        if (!(data.bukti_pembayaran instanceof File)) {
            setPreviewBukti(null);

            return;
        }

        const url = URL.createObjectURL(data.bukti_pembayaran);
        setPreviewBukti(url);

        return () => URL.revokeObjectURL(url);
    }, [data.bukti_pembayaran]);

    const buktiWajib = ['transfer', 'qris'].includes(data.sistem_pembayaran);
    const buktiTersedia = Boolean(data.bukti_pembayaran) || Boolean(pembayaran.bukti_pembayaran_url);
    const buktiKurang = buktiWajib && !buktiTersedia;

    const handleBuktiChange = (event) => {
        setData('bukti_pembayaran', event.target.files?.[0] ?? null);
    };

    const submit = (event) => {
        event.preventDefault();

        post(`/booking/pembayaran/${pembayaran.kode_pembayaran}`, {
            forceFormData: true,
            preserveScroll: true,
        });
    };

    const bank = pengaturanPembayaran?.bank ?? {};

    return (
        <>
            <Head title="Pembayaran Booking" />

            <header className="relative overflow-hidden rounded-b-[2rem] bg-gradient-to-br from-emerald-600 to-emerald-800 px-5 pb-12 pt-6 text-white">
                <div className="pointer-events-none absolute -right-10 -top-10 h-36 w-36 rounded-full bg-white/10" />
                <div className="relative flex items-center gap-3">
                    <Link
                        href="/riwayat"
                        className="flex h-10 w-10 items-center justify-center rounded-full bg-white/10 ring-1 ring-white/20 transition hover:bg-white/20"
                        aria-label="Kembali ke riwayat"
                    >
                        <BackIcon />
                    </Link>
                    <div>
                        <h1 className="text-lg font-bold">Pembayaran</h1>
                        <p className="text-xs text-emerald-100">Selesaikan pembayaran untuk mengunci jadwal</p>
                    </div>
                </div>
            </header>

            <div className="relative -mt-7 space-y-4 px-5 pb-8">
                {flash?.success && (
                    <div className="rounded-2xl border border-emerald-200 bg-white px-4 py-3 text-sm text-emerald-800 shadow-sm">
                        {flash.success}
                    </div>
                )}

                {flash?.error && (
                    <div className="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                        {flash.error}
                    </div>
                )}

                <section className="overflow-hidden rounded-2xl bg-gradient-to-br from-emerald-700 to-emerald-900 p-4 text-white shadow-lg shadow-emerald-200">
                    <div className="flex items-start justify-between gap-3">
                        <div>
                            <p className="text-[10px] font-medium uppercase tracking-wide text-emerald-200">Kode Pembayaran</p>
                            <p className="text-sm font-bold">{pembayaran.kode_pembayaran}</p>
                        </div>
                        <span className={`inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[10px] font-bold ${meta.className}`}>
                            <span className={`h-1.5 w-1.5 rounded-full ${meta.dot}`} />
                            {meta.label}
                        </span>
                    </div>

                    <div className="my-3 border-t border-white/15" />

                    <p className="text-[10px] font-medium uppercase tracking-wide text-emerald-200">Total bayar</p>
                    <p className="text-2xl font-bold">{formatRupiah(pembayaran.total)}</p>
                    <p className="mt-1 text-[11px] text-emerald-100">{pembayaran.bookings.length} sesi main</p>
                </section>

                <section className="rounded-2xl border border-emerald-100 bg-white p-4 shadow-sm">
                    <h2 className="mb-3 text-sm font-bold text-slate-800">Rincian Jadwal</h2>

                    <div className="space-y-2">
                        {pembayaran.bookings.map((booking) => (
                            <div
                                key={booking.id}
                                className="flex items-start justify-between gap-3 rounded-xl border border-emerald-50 bg-emerald-50/40 px-3 py-2.5"
                            >
                                <div className="min-w-0">
                                    <p className="text-[10px] font-semibold text-emerald-700">{booking.kode_booking}</p>
                                    <p className="text-xs font-semibold text-slate-800">{formatDate(booking.tgl_main)}</p>
                                    <p className="text-[11px] text-slate-500">
                                        {booking.jam_mulai} - {booking.jam_selesai} · Meja {booking.id_meja}
                                    </p>
                                </div>
                                <span className="shrink-0 text-xs font-bold text-emerald-700">
                                    {formatRupiah(booking.total_harga)}
                                </span>
                            </div>
                        ))}
                    </div>
                </section>

                {lunas ? (
                    <section className="rounded-2xl border border-emerald-200 bg-white p-5 text-center shadow-sm">
                        <span className="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-emerald-100 text-emerald-600">
                            <CheckIcon className="h-7 w-7" />
                        </span>
                        <h2 className="mt-3 text-sm font-bold text-slate-800">Pembayaran sudah lunas</h2>
                        <p className="mt-1 text-xs text-slate-500">
                            {pembayaran.dibayar_pada
                                ? `Diverifikasi pada ${formatDate(pembayaran.dibayar_pada)}`
                                : 'Pembayaran sudah dikonfirmasi admin.'}
                        </p>

                        {pembayaran.bukti_pembayaran_url && (
                            <a
                                href={pembayaran.bukti_pembayaran_url}
                                target="_blank"
                                rel="noreferrer"
                                className="mt-4 inline-flex items-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-700 transition hover:bg-emerald-100"
                            >
                                Lihat bukti pembayaran
                            </a>
                        )}

                        <Link
                            href="/riwayat"
                            className="mt-4 flex w-full items-center justify-center rounded-2xl bg-emerald-600 px-5 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-emerald-700"
                        >
                            Kembali ke Riwayat
                        </Link>
                    </section>
                ) : (
                    <form onSubmit={submit} className="space-y-4">
                        {pembayaran.status_pembayaran === 'menunggu_verifikasi' && (
                            <div className="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-xs leading-relaxed text-amber-700">
                                Bukti pembayaran sudah dikirim dan sedang menunggu verifikasi admin. Kamu masih bisa
                                mengunggah ulang bukti jika ada kesalahan.
                            </div>
                        )}

                        {pembayaran.status_pembayaran === 'bayar_di_tempat' && (
                            <div className="rounded-2xl border border-sky-200 bg-sky-50 px-4 py-3 text-xs leading-relaxed text-sky-700">
                                Metode tunai dipilih. Silakan bayar langsung di tempat saat bermain, atau ubah ke
                                transfer/QRIS di bawah ini.
                            </div>
                        )}

                        <section className="rounded-2xl border border-emerald-100 bg-white p-4 shadow-sm">
                            <h2 className="mb-3 text-sm font-bold text-slate-800">Metode Pembayaran</h2>

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

                            {data.sistem_pembayaran === 'transfer' && (
                                <div className="mt-3 rounded-xl border border-emerald-100 bg-emerald-50/50 px-3 py-2.5 text-xs text-slate-600">
                                    <p className="font-semibold text-slate-800">
                                        {bank.name} · {bank.account_number}
                                    </p>
                                    <p className="text-[11px]">a.n. {bank.account_holder}</p>
                                    <p className="mt-1 text-[11px] text-emerald-700">
                                        Transfer sesuai total {formatRupiah(pembayaran.total)}.
                                    </p>
                                </div>
                            )}

                            {data.sistem_pembayaran === 'qris' && (
                                <div className="mt-3 rounded-xl border border-emerald-100 bg-emerald-50/50 px-3 py-3 text-center text-xs text-slate-600">
                                    {pengaturanPembayaran?.qris_image ? (
                                        <img
                                            src={pengaturanPembayaran.qris_image}
                                            alt="QRIS pembayaran"
                                            className="mx-auto h-40 w-auto rounded-lg bg-white object-contain p-2 shadow-sm"
                                        />
                                    ) : (
                                        <p className="text-[11px] leading-relaxed">
                                            Scan QRIS yang tersedia di tempat, lalu unggah bukti pembayarannya.
                                        </p>
                                    )}
                                    <p className="mt-2 text-[11px] font-semibold text-emerald-700">
                                        Total {formatRupiah(pembayaran.total)}
                                    </p>
                                </div>
                            )}

                            {data.sistem_pembayaran === 'tunai' && (
                                <p className="mt-3 rounded-xl border border-sky-100 bg-sky-50 px-3 py-2.5 text-[11px] leading-relaxed text-sky-700">
                                    Bayar tunai sebesar {formatRupiah(pembayaran.total)} di tempat saat bermain.
                                    Tidak perlu mengunggah bukti.
                                </p>
                            )}
                        </section>

                        {buktiWajib && (
                            <section className="rounded-2xl border border-emerald-100 bg-white p-4 shadow-sm">
                                <h2 className="mb-3 text-sm font-bold text-slate-800">Bukti Pembayaran</h2>

                                <input
                                    id="bukti_pembayaran"
                                    type="file"
                                    accept=".jpg,.jpeg,.png,image/jpeg,image/png"
                                    onChange={handleBuktiChange}
                                    className="sr-only"
                                />
                                <label
                                    htmlFor="bukti_pembayaran"
                                    className={`flex cursor-pointer flex-col items-center gap-2 rounded-xl border border-dashed px-4 py-5 text-center transition ${errors.bukti_pembayaran
                                        ? 'border-rose-300 bg-rose-50/40'
                                        : 'border-emerald-200 bg-emerald-50/40 hover:border-emerald-400 hover:bg-emerald-50'
                                        }`}
                                >
                                    {previewBukti ? (
                                        <img
                                            src={previewBukti}
                                            alt="Pratinjau bukti pembayaran"
                                            className="h-32 w-auto max-w-full rounded-lg object-contain shadow-sm"
                                        />
                                    ) : (
                                        <span className="flex h-11 w-11 items-center justify-center rounded-full bg-emerald-100 text-emerald-600">
                                            <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5" fill="none" viewBox="0 0 24 24" strokeWidth={1.8} stroke="currentColor">
                                                <path strokeLinecap="round" strokeLinejoin="round" d="M3 16.5V18a2.25 2.25 0 0 0 2.25 2.25h13.5A2.25 2.25 0 0 0 21 18v-1.5M16.5 7.5 12 3m0 0L7.5 7.5M12 3v13.5" />
                                            </svg>
                                        </span>
                                    )}
                                    <span className="max-w-full truncate text-xs font-semibold text-emerald-700">
                                        {data.bukti_pembayaran ? data.bukti_pembayaran.name : 'Pilih gambar bukti pembayaran'}
                                    </span>
                                    <span className="text-[10px] text-slate-500">
                                        Format JPG, JPEG, atau PNG. Maksimal 2 MB.
                                    </span>
                                </label>
                                {errors.bukti_pembayaran && (
                                    <p className="mt-1.5 text-xs font-medium text-rose-600">{errors.bukti_pembayaran}</p>
                                )}
                            </section>
                        )}

                        <label
                            className={`flex cursor-pointer items-start gap-2.5 rounded-2xl border px-4 py-3 transition ${setuju
                                ? 'border-emerald-300 bg-emerald-50'
                                : 'border-amber-200 bg-amber-50'
                                }`}
                        >
                            <input
                                type="checkbox"
                                checked={setuju}
                                onChange={(event) => setSetuju(event.target.checked)}
                                className="mt-0.5 h-4 w-4 shrink-0 cursor-pointer rounded border-slate-300 accent-emerald-600"
                            />
                            <span className="text-[11px] leading-relaxed text-amber-800">
                                Setujui jika tanggal, jam booking, dan meja yang Anda pilih sudah benar. Jadwal yang
                                sudah dibooking <span className="font-bold">tidak dapat diubah lagi</span> setelah
                                melakukan pembayaran.
                            </span>
                        </label>

                        {buktiKurang && (
                            <p className="text-center text-[11px] font-medium text-rose-600">
                                Unggah bukti pembayaran terlebih dahulu untuk melanjutkan.
                            </p>
                        )}

                        <button
                            type="submit"
                            disabled={processing || !setuju || buktiKurang}
                            className="flex w-full items-center justify-center gap-2 rounded-2xl bg-emerald-600 px-5 py-3.5 text-sm font-bold text-white shadow-lg shadow-emerald-200 transition hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-60"
                        >
                            {processing
                                ? 'Mengirim pembayaran...'
                                : data.sistem_pembayaran === 'tunai'
                                    ? 'Pilih Bayar di Tempat'
                                    : 'Kirim Bukti Pembayaran'}
                        </button>
                    </form>
                )}
            </div>
        </>
    );
}

Pembayaran.layout = (page) => <UserLayout>{page}</UserLayout>;
