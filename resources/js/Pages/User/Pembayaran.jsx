import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import Swal from 'sweetalert2';
import 'sweetalert2/dist/sweetalert2.min.css';
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

function TrashIcon({ className = 'h-3.5 w-3.5' }) {
    return (
        <svg xmlns="http://www.w3.org/2000/svg" className={className} fill="none" viewBox="0 0 24 24" strokeWidth={1.9} stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
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

function TableIcon({ className = 'h-4 w-4' }) {
    return (
        <svg xmlns="http://www.w3.org/2000/svg" className={className} fill="none" viewBox="0 0 24 24" strokeWidth={1.8} stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" d="M3 10h18M5 10v8m14-8v8M7 21h10M6 10V6a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v4" />
        </svg>
    );
}

function BankIcon({ className = 'h-5 w-5' }) {
    return (
        <svg xmlns="http://www.w3.org/2000/svg" className={className} fill="none" viewBox="0 0 24 24" strokeWidth={1.9} stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" d="M3 7.5h18M14.25 3 21 9.75M3 16.5h18M9.75 21 3 14.25" />
        </svg>
    );
}

function QrisIcon({ className = 'h-5 w-5' }) {
    return (
        <svg xmlns="http://www.w3.org/2000/svg" className={className} fill="none" viewBox="0 0 24 24" strokeWidth={1.8} stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" d="M3.75 4.5h4.5v4.5h-4.5zM15.75 4.5h4.5v4.5h-4.5zM3.75 15h4.5v4.5h-4.5zM9 9h1.5v1.5H9zM13.5 9H15v1.5h-1.5zM9 13.5h1.5V15H9zM13.5 12h1.5v1.5h-1.5zM16.5 15h1.5v1.5h-1.5zM19.5 18h1.5v1.5h-1.5z" />
        </svg>
    );
}

function DownloadIcon({ className = 'h-4 w-4' }) {
    return (
        <svg xmlns="http://www.w3.org/2000/svg" className={className} fill="none" viewBox="0 0 24 24" strokeWidth={1.9} stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
        </svg>
    );
}

export default function Pembayaran({ pembayaran, pengaturanPembayaran }) {
    const { flash } = usePage().props;
    const metodePembayaran = pengaturanPembayaran?.metode ?? [];
    const menggunakanVoucher = Boolean(pembayaran.menggunakan_voucher);
    const [previewBukti, setPreviewBukti] = useState(null);
    const [setuju, setSetuju] = useState(false);
    const meta = menggunakanVoucher
        ? { label: 'Menunggu konfirmasi', className: 'bg-amber-100 text-amber-700', dot: 'bg-amber-500' }
        : statusMeta(pembayaran.status_pembayaran);
    const lunas = pembayaran.status_pembayaran === 'lunas';
    const [menghapusId, setMenghapusId] = useState(null);
    const bisaHapusSesi =
        Boolean(pembayaran.bisa_hapus_sesi) && !lunas && pembayaran.status_pembayaran !== 'menunggu_verifikasi';

    const { data, setData, post, processing, errors } = useForm({
        sistem_pembayaran: menggunakanVoucher ? 'voucher' : metodePembayaran[0]?.metode ?? 'transfer',
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

    const buktiWajib = !menggunakanVoucher && ['transfer', 'qris'].includes(data.sistem_pembayaran);
    const buktiTersedia = Boolean(data.bukti_pembayaran) || Boolean(pembayaran.bukti_pembayaran_url);
    const buktiKurang = buktiWajib && !buktiTersedia;

    const handleBuktiChange = (event) => {
        setData('bukti_pembayaran', event.target.files?.[0] ?? null);
        // Kosongkan input agar memilih file yang sama tetap memicu perubahan.
        event.target.value = '';
    };

    const hapusSesi = async (booking) => {
        const konfirmasi = await Swal.fire({
            icon: 'warning',
            title: 'Hapus sesi ini?',
            text: `Sesi ${booking.jam_mulai} - ${booking.jam_selesai} (${formatDate(booking.tgl_main)}) akan dihapus dan total bayar dihitung ulang.`,
            showCancelButton: true,
            confirmButtonText: 'Ya, Hapus',
            cancelButtonText: 'Batal',
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
                cancelButton: 'app-alert-cancel',
            },
        });

        if (!konfirmasi.isConfirmed) {
            return;
        }

        setMenghapusId(booking.id);

        router.delete(`/booking/pembayaran/${pembayaran.kode_pembayaran}/sesi/${booking.id}`, {
            preserveScroll: true,
            onFinish: () => setMenghapusId(null),
        });
    };

    const submit = (event) => {
        event.preventDefault();

        post(`/booking/pembayaran/${pembayaran.kode_pembayaran}`, {
            forceFormData: true,
            preserveScroll: true,
        });
    };

    const metodeTerpilih =
        metodePembayaran.find((item) => item.metode === data.sistem_pembayaran) ?? metodePembayaran[0] ?? null;
    const bank =
        metodeTerpilih?.metode === 'transfer'
            ? {
                name: metodeTerpilih.bank,
                account_number: metodeTerpilih.norek,
                account_holder: metodeTerpilih.atas_nama,
            }
            : {};
    const qrisUrl = metodeTerpilih?.metode === 'qris' ? metodeTerpilih.qris_image_url : null;
    const ekstensiQris = qrisUrl ? (qrisUrl.split('?')[0].split('.').pop() || 'png').toLowerCase() : 'png';
    const namaFileQris = `qris-${pembayaran.kode_pembayaran}.${ekstensiQris}`;
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

                    <p className="text-[10px] font-medium uppercase tracking-wide text-emerald-200">
                        {pembayaran.harga_paket != null ? 'Total bayar (harga paket)' : 'Total bayar'}
                    </p>
                    <p className="text-2xl font-bold">
                        {formatRupiah(pembayaran.harga_paket ?? pembayaran.total)}
                    </p>
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
                                    <div className="mt-1.5 flex flex-wrap items-center gap-1.5">
                                        <span className="inline-flex items-center gap-1 rounded-full bg-white px-2 py-0.5 text-[10px] font-bold text-emerald-700 shadow-sm ring-1 ring-emerald-100">
                                            <ClockIcon className="h-3 w-3" />
                                            {booking.jam_mulai} - {booking.jam_selesai}
                                        </span>
                                        <span className="inline-flex items-center gap-1 rounded-full bg-white px-2 py-0.5 text-[10px] font-bold text-emerald-700 shadow-sm ring-1 ring-emerald-100">
                                            <TableIcon className="h-3 w-3" />
                                            Meja {booking.id_meja || '-'}
                                        </span>
                                    </div>
                                </div>
                                <div className="flex shrink-0 flex-col items-end gap-1 text-right">
                                    {booking.harga_paket != null ? (
                                        <>
                                            <span className="text-[10px] font-semibold uppercase tracking-wide text-emerald-600">
                                                Harga paket
                                            </span>
                                            <span className="text-xs font-bold text-emerald-700">
                                                {formatRupiah(booking.harga_paket)}
                                            </span>
                                        </>
                                    ) : (
                                        <>
                                            <span className="text-xs font-bold text-emerald-700">
                                                {formatRupiah(booking.harga)}
                                            </span>
                                            {(() => {
                                                const diskonPaket = Number(booking.diskon_paket) || 0;
                                                const total = Number(booking.total_harga) || 0;
                                                const hargaPaketAktif = booking.diskon_paket_persen == null && diskonPaket > 0;
                                                const diskonVoucher = booking.pakai_voucher
                                                    ? Math.max(0, Number(booking.harga) - total)
                                                    : 0;
                                                const diskonJam = booking.pakai_voucher
                                                    ? 0
                                                    : Math.max(0, Number(booking.harga) - total - diskonPaket);

                                                return (
                                                    <>
                                                        {diskonJam > 0 && (
                                                            <span className="text-[10px] font-semibold text-rose-500">
                                                                Diskon -{formatRupiah(diskonJam)}
                                                            </span>
                                                        )}
                                                        {hargaPaketAktif && (
                                                            <span className="text-[10px] font-semibold text-rose-500">
                                                                Harga paket -{formatRupiah(diskonPaket)}
                                                            </span>
                                                        )}
                                                        {diskonPaket > 0 && !hargaPaketAktif && (
                                                            <span className="text-[10px] font-semibold text-rose-500">
                                                                Diskon paket ({booking.diskon_paket_persen}%) -{formatRupiah(diskonPaket)}
                                                            </span>
                                                        )}
                                                        {diskonVoucher > 0 && (
                                                            <span className="text-[10px] font-semibold text-rose-500">
                                                                Diskon voucher -{formatRupiah(diskonVoucher)}
                                                            </span>
                                                        )}
                                                        {(diskonJam > 0 || diskonPaket > 0 || diskonVoucher > 0 || total !== Number(booking.harga)) && (
                                                            <span className="text-xs font-bold text-slate-800">
                                                                {formatRupiah(total)}
                                                            </span>
                                                        )}
                                                    </>
                                                );
                                            })()}
                                        </>
                                    )}
                                    {bisaHapusSesi && (
                                        <button
                                            type="button"
                                            onClick={() => hapusSesi(booking)}
                                            disabled={menghapusId !== null}
                                            aria-label={`Hapus sesi ${booking.jam_mulai} - ${booking.jam_selesai}`}
                                            className="inline-flex items-center gap-1 rounded-lg border border-rose-200 bg-white px-2 py-1 text-[10px] font-bold text-rose-600 shadow-sm transition hover:bg-rose-50 active:scale-95 disabled:cursor-not-allowed disabled:opacity-60"
                                        >
                                            <TrashIcon className="h-3 w-3" />
                                            {menghapusId === booking.id ? 'Menghapus...' : 'Hapus'}
                                        </button>
                                    )}
                                </div>
                            </div>
                        ))}
                    </div>

                    <div className="mt-3 space-y-1.5 rounded-xl border border-emerald-100 bg-white px-3.5 py-3">
                        {pembayaran.harga_paket != null ? (
                            <>
                                <div className="flex items-center justify-between gap-3 text-xs text-slate-500">
                                    <span>Harga jam ({pembayaran.bookings.length} sesi)</span>
                                    <span className="line-through">{formatRupiah(pembayaran.subtotal)}</span>
                                </div>
                                <div className="flex items-center justify-between gap-3 text-xs">
                                    <span className="font-semibold text-emerald-700">Harga paket</span>
                                    <span className="font-bold text-emerald-700">
                                        {formatRupiah(pembayaran.harga_paket)}
                                    </span>
                                </div>
                            </>
                        ) : (
                            <>
                                <div className="flex items-center justify-between gap-3 text-xs text-slate-600">
                                    <span>Harga jam ({pembayaran.bookings.length} sesi)</span>
                                    <span className="font-semibold text-slate-800">{formatRupiah(pembayaran.subtotal)}</span>
                                </div>
                                {pembayaran.diskon > 0 && (
                                    <div className="flex items-center justify-between gap-3 text-xs">
                                        <span className="text-emerald-700">{pembayaran.label_diskon ?? 'Diskon'}</span>
                                        <span className="font-semibold text-emerald-700">
                                            -{formatRupiah(pembayaran.diskon)}
                                        </span>
                                    </div>
                                )}
                            </>
                        )}
                        <div className="flex items-center justify-between gap-3 border-t border-dashed border-emerald-100 pt-1.5">
                            <span className="text-xs font-bold text-slate-800">Total dibayar</span>
                            <span className="text-sm font-bold text-emerald-700">
                                {formatRupiah(pembayaran.total)}
                            </span>
                        </div>
                    </div>

                    {bisaHapusSesi && (
                        <p className="mt-3 flex items-start gap-1.5 rounded-lg bg-amber-50 px-3 py-2 text-[10px] leading-relaxed text-amber-700">
                            <svg xmlns="http://www.w3.org/2000/svg" className="mt-0.5 h-3 w-3 shrink-0" fill="none" viewBox="0 0 24 24" strokeWidth={2} stroke="currentColor" aria-hidden="true">
                                <path strokeLinecap="round" strokeLinejoin="round" d="M12 9v3.75m9 5.25a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V14.25Z" />
                            </svg>
                            Hapus sesi yang tidak jadi dipakai. Total bayar otomatis menyesuaikan. Jika semua sesi
                            dihapus, booking dibatalkan.
                        </p>
                    )}
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
                        {Object.keys(errors).length > 0 && (
                            <div className="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-xs leading-relaxed text-rose-700">
                                <p className="font-semibold">Booking belum tersimpan.</p>
                                <p className="mt-0.5">
                                    Periksa kembali jadwal dan data pembayaranmu, lalu kirim lagi.
                                </p>
                                {errors.jam_main_ids && <p className="mt-1">{errors.jam_main_ids}</p>}
                                {errors.kode_voucher && <p className="mt-1">{errors.kode_voucher}</p>}
                            </div>
                        )}

                        {menggunakanVoucher && (
                            <div className="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-xs leading-relaxed text-emerald-700">
                                Booking ini memakai voucher. Tidak perlu mengunggah bukti pembayaran; tekan tombol konfirmasi
                                untuk menyimpan booking.
                            </div>
                        )}

                        {pembayaran.status_pembayaran === 'menunggu_verifikasi' && (
                            <div className="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-xs leading-relaxed text-amber-700">
                                Bukti pembayaran sudah dikirim dan sedang menunggu verifikasi admin. Kamu masih bisa
                                mengunggah ulang bukti jika ada kesalahan.
                            </div>
                        )}

                        {pembayaran.status_pembayaran === 'bayar_di_tempat' && (
                            <div className="rounded-2xl border border-sky-200 bg-sky-50 px-4 py-3 text-xs leading-relaxed text-sky-700">
                                Pembayaran tunai tidak lagi tersedia. Silakan bayar melalui metode di bawah ini dan
                                unggah bukti pembayarannya.
                            </div>
                        )}

                        {!menggunakanVoucher && (
                            <section className="rounded-2xl border border-emerald-100 bg-white p-4 shadow-sm">
                                <h2 className="mb-3 text-sm font-bold text-slate-800">Metode Pembayaran</h2>

                                <div className="space-y-2">
                                    {metodePembayaran.map((metode) => {
                                        const aktif = data.sistem_pembayaran === metode.metode;
                                        const qris = metode.metode === 'qris';

                                        return (
                                            <button
                                                key={metode.id}
                                                type="button"
                                                onClick={() => setData('sistem_pembayaran', metode.metode)}
                                                aria-pressed={aktif}
                                                className={`flex w-full items-center gap-3 rounded-xl border px-3 py-2.5 text-left shadow-sm transition ${aktif
                                                    ? 'border-emerald-600 bg-emerald-600 text-white hover:bg-emerald-700'
                                                    : 'border-emerald-100 bg-white text-slate-700 hover:border-emerald-300 hover:bg-emerald-50'
                                                    }`}
                                            >
                                                <span
                                                    className={`flex h-9 w-9 shrink-0 items-center justify-center rounded-lg ${aktif ? 'bg-white/15 text-white' : 'bg-emerald-50 text-emerald-600'
                                                        }`}
                                                >
                                                    {qris ? <QrisIcon /> : <BankIcon />}
                                                </span>
                                                <span className="min-w-0 flex-1">
                                                    <span className="block text-xs font-bold">
                                                        {qris ? 'QRIS' : `Transfer ${metode.bank ? metode.bank : 'Bank'}`}
                                                    </span>
                                                    <span
                                                        className={`block text-[10px] ${aktif ? 'text-emerald-100' : 'text-slate-500'
                                                            }`}
                                                    >
                                                        {qris
                                                            ? 'Scan kode QRIS lalu unggah bukti'
                                                            : 'Transfer ke rekening resmi venue'}
                                                    </span>
                                                </span>
                                                {aktif ? (
                                                    <CheckIcon className="h-5 w-5 shrink-0" />
                                                ) : (
                                                    <span className="h-4 w-4 shrink-0 rounded-full border-2 border-slate-300" />
                                                )}
                                            </button>
                                        );
                                    })}
                                </div>

                                {errors.sistem_pembayaran && (
                                    <p className="mt-1.5 text-xs font-medium text-rose-600">{errors.sistem_pembayaran}</p>
                                )}

                                {metodeTerpilih?.metode === 'transfer' && (
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

                                {metodeTerpilih?.metode === 'qris' && (
                                    <div className="mt-3 rounded-2xl border border-emerald-200 bg-gradient-to-br from-emerald-50 via-white to-white p-4 text-center shadow-sm">
                                        <p className="text-xs font-bold text-slate-800">Scan QRIS</p>
                                        {metodeTerpilih?.atas_nama && (
                                            <p className="mt-1 text-[11px] text-slate-500">
                                                Atas nama:{' '}
                                                <span className="font-bold text-slate-800">{metodeTerpilih.atas_nama}</span>
                                            </p>
                                        )}
                                        {qrisUrl ? (
                                            <img
                                                src={qrisUrl}
                                                alt="Kode QRIS pembayaran"
                                                className="mx-auto mt-3 h-56 w-56 rounded-xl bg-white object-contain p-2 ring-1 ring-emerald-100"
                                            />
                                        ) : (
                                            <p className="mt-3 rounded-xl border border-dashed border-emerald-200 bg-emerald-50/50 px-3 py-4 text-[11px] leading-relaxed text-slate-600">
                                                Gambar QRIS belum tersedia. Sementara, hubungi admin untuk kode QRIS
                                                pembayaran.
                                            </p>
                                        )}
                                        {qrisUrl && (
                                            <a
                                                href={qrisUrl}
                                                download={namaFileQris}
                                                className="mt-3 inline-flex items-center justify-center gap-2 rounded-xl border border-emerald-200 bg-white px-4 py-2 text-xs font-bold text-emerald-700 shadow-sm transition hover:bg-emerald-50"
                                            >
                                                <DownloadIcon />
                                                Download QRIS
                                            </a>
                                        )}
                                        <p className="mt-3 text-[11px] leading-relaxed text-slate-500">
                                            Scan dengan aplikasi bank atau e-wallet, bayar{' '}
                                            <span className="font-bold text-slate-700">{formatRupiah(pembayaran.total)}</span>,
                                            lalu unggah bukti pembayaran di bawah.
                                        </p>
                                    </div>
                                )}
                            </section>
                        )}

                        {buktiWajib && (
                            <section className="rounded-2xl border border-emerald-100 bg-white p-4 shadow-sm">
                                <h2 className="mb-3 text-sm font-bold text-slate-800">Bukti Pembayaran</h2>

                                <input
                                    id="bukti_pembayaran_kamera"
                                    type="file"
                                    accept="image/*"
                                    capture="environment"
                                    onChange={handleBuktiChange}
                                    className="sr-only"
                                />
                                <input
                                    id="bukti_pembayaran_galeri"
                                    type="file"
                                    accept=".jpg,.jpeg,.png,image/jpeg,image/png"
                                    onChange={handleBuktiChange}
                                    className="sr-only"
                                />

                                <div
                                    className={`flex flex-col items-center gap-2 rounded-xl border border-dashed px-4 py-5 text-center ${errors.bukti_pembayaran
                                        ? 'border-rose-300 bg-rose-50/40'
                                        : 'border-emerald-200 bg-emerald-50/40'
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
                                        {data.bukti_pembayaran ? data.bukti_pembayaran.name : 'Belum ada bukti pembayaran dipilih'}
                                    </span>
                                    <span className="text-[10px] text-slate-500">
                                        Format JPG, JPEG, atau PNG. Maksimal 10 MB.
                                    </span>
                                </div>

                                <div className="mt-2 grid grid-cols-2 gap-2">
                                    <label
                                        htmlFor="bukti_pembayaran_kamera"
                                        className="flex cursor-pointer items-center justify-center gap-2 rounded-xl bg-emerald-600 px-3 py-2.5 text-xs font-bold text-white shadow-sm transition hover:bg-emerald-700"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4" fill="none" viewBox="0 0 24 24" strokeWidth={1.8} stroke="currentColor">
                                            <path strokeLinecap="round" strokeLinejoin="round" d="M6.827 6.175A2.31 2.31 0 0 1 5.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 0 0-1.134-.175 2.31 2.31 0 0 1-1.64-1.055l-.822-1.316a2.192 2.192 0 0 0-1.736-1.039 48.774 48.774 0 0 0-5.232 0 2.192 2.192 0 0 0-1.736 1.039l-.821 1.316Z" />
                                            <path strokeLinecap="round" strokeLinejoin="round" d="M16.5 12.75a4.5 4.5 0 1 1-9 0 4.5 4.5 0 0 1 9 0Z" />
                                        </svg>
                                        Ambil Foto
                                    </label>
                                    <label
                                        htmlFor="bukti_pembayaran_galeri"
                                        className="flex cursor-pointer items-center justify-center gap-2 rounded-xl border border-emerald-200 bg-white px-3 py-2.5 text-xs font-bold text-emerald-700 shadow-sm transition hover:bg-emerald-50"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4" fill="none" viewBox="0 0 24 24" strokeWidth={1.8} stroke="currentColor">
                                            <path strokeLinecap="round" strokeLinejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909M12 6.75h.008v.008H12V6.75Zm9 9.75V6.75A2.25 2.25 0 0 0 18.75 4.5H5.25A2.25 2.25 0 0 0 3 6.75v10.5A2.25 2.25 0 0 0 5.25 19.5h13.5A2.25 2.25 0 0 0 21 16.5Z" />
                                        </svg>
                                        Pilih Galeri
                                    </label>
                                </div>
                                <p className="mt-1.5 text-[10px] leading-relaxed text-slate-500">
                                    Di HP, tombol Ambil Foto langsung membuka kamera. Di laptop, pilih file dari galeri.
                                </p>
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
                                Setujui jika tanggal, jam booking, dan meja yang Anda pilih sudah benar. Booking
                                <span className="font-bold"> baru tersimpan setelah tombol di bawah ditekan</span> dan
                                jadwalnya tidak dapat diubah lagi.
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
                                ? 'Menyimpan booking...'
                                : menggunakanVoucher
                                    ? 'Konfirmasi Booking Voucher'
                                    : 'Kirim Bukti Pembayaran'}
                        </button>
                    </form>
                )}
            </div>
        </>
    );
}

Pembayaran.layout = (page) => <UserLayout>{page}</UserLayout>;
