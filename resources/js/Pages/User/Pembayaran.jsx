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
        sistem_pembayaran: 'transfer',
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

    const buktiWajib = data.sistem_pembayaran === 'transfer';
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
    const [nomorTersalin, setNomorTersalin] = useState(false);

    const salinNomorRekening = async () => {
        const nomor = bank.account_number ?? '';

        if (!nomor) {
            return;
        }

        try {
            await navigator.clipboard.writeText(nomor);
        } catch {
            // Fallback lama bila clipboard API tidak tersedia (konteks non-HTTPS).
            const area = document.createElement('textarea');

            area.value = nomor;
            document.body.appendChild(area);
            area.select();
            document.execCommand('copy');
            area.remove();
        }

        setNomorTersalin(true);
        setTimeout(() => setNomorTersalin(false), 2000);
    };

    const bankTerisi = Boolean(bank.name || bank.account_number || bank.account_holder);

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
                                Pembayaran tunai tidak lagi tersedia. Silakan transfer ke rekening di bawah ini
                                dan unggah bukti pembayarannya.
                            </div>
                        )}

                        <section className="rounded-2xl border border-emerald-100 bg-white p-4 shadow-sm">
                            <h2 className="mb-3 text-sm font-bold text-slate-800">Metode Pembayaran</h2>

                            <button
                                type="button"
                                onClick={() => setData('sistem_pembayaran', 'transfer')}
                                className="flex w-full items-center gap-3 rounded-xl border border-emerald-600 bg-emerald-600 px-3 py-2.5 text-left text-white shadow-sm transition hover:bg-emerald-700"
                            >
                                <span className="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-white/15">
                                    <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5" fill="none" viewBox="0 0 24 24" strokeWidth={1.9} stroke="currentColor">
                                        <path strokeLinecap="round" strokeLinejoin="round" d="M3 7.5h18M14.25 3 21 9.75M3 16.5h18M9.75 21 3 14.25" />
                                    </svg>
                                </span>
                                <span className="min-w-0 flex-1">
                                    <span className="block text-xs font-bold">Transfer Bank</span>
                                    <span className="block text-[10px] text-emerald-100">
                                        Transfer ke rekening resmi di bawah ini
                                    </span>
                                </span>
                                <CheckIcon className="h-5 w-5 shrink-0" />
                            </button>

                            {errors.sistem_pembayaran && (
                                <p className="mt-1.5 text-xs font-medium text-rose-600">{errors.sistem_pembayaran}</p>
                            )}

                            {data.sistem_pembayaran === 'transfer' && (
                                bankTerisi ? (
                                    <div className="mt-3 overflow-hidden rounded-2xl border border-emerald-200 bg-gradient-to-br from-emerald-50 via-white to-white shadow-sm">
                                        <div className="flex items-center gap-3 border-b border-emerald-100/80 px-4 py-3">
                                            <span className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-600 text-sm font-black text-white shadow-sm">
                                                {(bank.name || 'B').charAt(0).toUpperCase()}
                                            </span>
                                            <div className="min-w-0">
                                                <p className="text-[10px] font-medium uppercase tracking-wide text-slate-400">Bank tujuan</p>
                                                <p className="truncate text-sm font-bold text-slate-800">{bank.name || 'Bank'}</p>
                                            </div>
                                            <span className="ml-auto shrink-0 rounded-full bg-emerald-100 px-2.5 py-1 text-[10px] font-bold text-emerald-700">
                                                Rekening resmi
                                            </span>
                                        </div>

                                        <div className="px-4 py-3.5">
                                            <p className="text-[10px] font-medium uppercase tracking-wide text-slate-400">Nomor rekening</p>
                                            <div className="mt-1 flex items-center justify-between gap-3">
                                                <p className="font-mono text-xl font-black tracking-wider text-slate-900">
                                                    {bank.account_number || '-'}
                                                </p>
                                                <button
                                                    type="button"
                                                    onClick={salinNomorRekening}
                                                    disabled={!bank.account_number}
                                                    className={`inline-flex shrink-0 items-center gap-1.5 rounded-xl px-3 py-2 text-[11px] font-bold transition ${nomorTersalin
                                                        ? 'bg-emerald-600 text-white'
                                                        : 'border border-emerald-200 bg-white text-emerald-700 hover:bg-emerald-50'
                                                        } disabled:cursor-not-allowed disabled:opacity-60`}
                                                >
                                                    {nomorTersalin ? (
                                                        <>
                                                            <CheckIcon className="h-3.5 w-3.5" />
                                                            Tersalin
                                                        </>
                                                    ) : (
                                                        <>
                                                            <svg xmlns="http://www.w3.org/2000/svg" className="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" strokeWidth={1.9} stroke="currentColor">
                                                                <path strokeLinecap="round" strokeLinejoin="round" d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 0 1-1.125-1.125V7.875c0-.621.504-1.125 1.125-1.125H6.75a9.06 9.06 0 0 1 1.5.124m7.5 10.376h3.375c.621 0 1.125-.504 1.125-1.125V11.25c0-4.46-3.243-8.161-7.5-8.876a9.06 9.06 0 0 0-1.5-.124H9.375c-.621 0-1.125.504-1.125 1.125v3.5m7.5 10.375H9.375a1.125 1.125 0 0 1-1.125-1.125v-9.25m12 6.625v-1.875a3.375 3.375 0 0 0-3.375-3.375h-1.5a1.125 1.125 0 0 1-1.125-1.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H9.75" />
                                                            </svg>
                                                            Salin
                                                        </>
                                                    )}
                                                </button>
                                            </div>

                                            <div className="mt-3 flex items-center justify-between gap-3 rounded-xl bg-emerald-50/70 px-3 py-2.5">
                                                <div>
                                                    <p className="text-[10px] font-medium uppercase tracking-wide text-slate-400">Atas nama</p>
                                                    <p className="text-sm font-bold text-emerald-800">{bank.account_holder || '-'}</p>
                                                </div>
                                                <svg xmlns="http://www.w3.org/2000/svg" className="h-8 w-8 shrink-0 text-emerald-600" fill="none" viewBox="0 0 24 24" strokeWidth={1.6} stroke="currentColor" aria-hidden="true">
                                                    <path strokeLinecap="round" strokeLinejoin="round" d="M15 9h3.75M15 12h3.75M15 15h3.75M4.5 19.5h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Zm6-10.125a1.875 1.875 0 1 1-3.75 0 1.875 1.875 0 0 1 3.75 0Z" />
                                                </svg>
                                            </div>

                                            <p className="mt-3 flex items-start gap-1.5 rounded-lg bg-amber-50 px-3 py-2 text-[11px] leading-relaxed text-amber-700">
                                                <svg xmlns="http://www.w3.org/2000/svg" className="mt-0.5 h-3.5 w-3.5 shrink-0" fill="none" viewBox="0 0 24 24" strokeWidth={2} stroke="currentColor" aria-hidden="true">
                                                    <path strokeLinecap="round" strokeLinejoin="round" d="M12 9v3.75m9 5.25a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V14.25Z" />
                                                </svg>
                                                Transfer <span className="font-bold">{formatRupiah(pembayaran.total)}</span> sesuai
                                                nominal agar verifikasi cepat.
                                            </p>
                                        </div>
                                    </div>
                                ) : (
                                    <div className="mt-3 rounded-xl border border-dashed border-emerald-200 bg-emerald-50/50 px-3 py-3 text-[11px] leading-relaxed text-slate-600">
                                        Informasi rekening bank akan segera tersedia. Sementara, hubungi admin untuk
                                        nomor rekening, lalu unggah bukti pembayarannya di bawah.
                                    </div>
                                )
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
                            {processing ? 'Mengirim pembayaran...' : 'Kirim Bukti Pembayaran'}
                        </button>
                    </form>
                )}
            </div>
        </>
    );
}

Pembayaran.layout = (page) => <UserLayout>{page}</UserLayout>;
