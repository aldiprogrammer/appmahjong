import { Head, Link, router, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import AdminLayout from '../../../Layouts/AdminLayout';

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
        month: 'short',
        year: 'numeric',
    }).format(date);
}

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

function namaPengguna(pengguna) {
    if (!pengguna) {
        return '';
    }

    return [pengguna.nama_depan, pengguna.nama_balakang].filter(Boolean).join(' ').trim();
}

function statusMeta(status) {
    if (status === '200') {
        return {
            label: 'Disetujui',
            className: 'bg-emerald-50 text-emerald-700 ring-emerald-100',
            dot: 'bg-emerald-500',
        };
    }

    if (status === '400') {
        return {
            label: 'Ditolak',
            className: 'bg-rose-50 text-rose-700 ring-rose-100',
            dot: 'bg-rose-500',
        };
    }

    return {
        label: 'Menunggu',
        className: 'bg-amber-50 text-amber-700 ring-amber-100',
        dot: 'bg-amber-500',
    };
}

function StatusBadge({ status }) {
    const meta = statusMeta(status);

    return (
        <span className={`inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ${meta.className}`}>
            <span className={`h-1.5 w-1.5 rounded-full ${meta.dot}`} />
            {meta.label}
        </span>
    );
}

function paymentStatusMeta(status) {
    if (status === 'lunas') {
        return { label: 'Lunas', className: 'bg-emerald-50 text-emerald-700 ring-emerald-100', dot: 'bg-emerald-500' };
    }

    if (status === 'menunggu_verifikasi') {
        return { label: 'Menunggu verifikasi', className: 'bg-amber-50 text-amber-700 ring-amber-100', dot: 'bg-amber-500' };
    }

    if (status === 'bayar_di_tempat') {
        return { label: 'Bayar di tempat', className: 'bg-sky-50 text-sky-700 ring-sky-100', dot: 'bg-sky-500' };
    }

    return { label: 'Belum bayar', className: 'bg-rose-50 text-rose-700 ring-rose-100', dot: 'bg-rose-500' };
}

function PaymentBadge({ status }) {
    const meta = paymentStatusMeta(status);

    return (
        <span className={`inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[11px] font-semibold ring-1 ${meta.className}`}>
            <span className={`h-1.5 w-1.5 rounded-full ${meta.dot}`} />
            {meta.label}
        </span>
    );
}

function EmptyIcon() {
    return (
        <svg xmlns="http://www.w3.org/2000/svg" className="h-10 w-10" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
        </svg>
    );
}

export default function Index({ bookings, filters }) {
    const { flash } = usePage().props;
    const [search, setSearch] = useState(filters?.search ?? '');
    const [status, setStatus] = useState(filters?.status ?? '');
    const [successMessage, setSuccessMessage] = useState(flash?.success ?? null);
    const [deleteTarget, setDeleteTarget] = useState(null);
    const [deleting, setDeleting] = useState(false);
    const [processingId, setProcessingId] = useState(null);

    useEffect(() => {
        setSuccessMessage(flash?.success ?? null);
    }, [flash?.success]);

    useEffect(() => {
        if (!deleteTarget) {
            return;
        }

        const handleEscape = (event) => {
            if (event.key !== 'Escape' || deleting) {
                return;
            }

            setDeleteTarget(null);
        };

        window.addEventListener('keydown', handleEscape);

        return () => window.removeEventListener('keydown', handleEscape);
    }, [deleteTarget, deleting]);

    const submitSearch = (event) => {
        event.preventDefault();

        router.get('/admin/bookings', { search, status }, { preserveState: true, replace: true });
    };

    const resetSearch = () => {
        setSearch('');
        setStatus('');
        router.get('/admin/bookings', {}, { preserveState: true, replace: true });
    };

    const updateStatus = (booking, nextStatus) => {
        setProcessingId(booking.id);

        router.patch(
            `/admin/bookings/${booking.id}/${nextStatus === '200' ? 'approve' : 'reject'}`,
            {},
            {
                preserveScroll: true,
                onFinish: () => setProcessingId(null),
            },
        );
    };

    const verifikasiPembayaran = (booking) => {
        setProcessingId(booking.id);

        router.patch(
            `/admin/bookings/${booking.id}/verifikasi-pembayaran`,
            {},
            {
                preserveScroll: true,
                onFinish: () => setProcessingId(null),
            },
        );
    };

    const confirmDelete = () => {
        if (!deleteTarget) {
            return;
        }

        setDeleting(true);

        router.delete(`/admin/bookings/${deleteTarget.id}`, {
            preserveScroll: true,
            onSuccess: () => setDeleteTarget(null),
            onFinish: () => setDeleting(false),
        });
    };

    const hasFilter = Boolean(filters?.search) || Boolean(filters?.status);

    return (
        <>
            <Head title="Data Booking" />

            <div className="mx-auto max-w-7xl space-y-6">
                {successMessage && (
                    <div className="flex items-center gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                        <span className="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-emerald-600 text-white">
                            <svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4" fill="none" viewBox="0 0 24 24" strokeWidth={2.2} stroke="currentColor">
                                <path strokeLinecap="round" strokeLinejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                            </svg>
                        </span>
                        <p className="font-medium">{successMessage}</p>
                        <button
                            type="button"
                            onClick={() => setSuccessMessage(null)}
                            className="ml-auto inline-flex h-7 w-7 items-center justify-center rounded-lg text-emerald-600 transition hover:bg-emerald-100"
                            aria-label="Tutup notifikasi"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4" fill="none" viewBox="0 0 24 24" strokeWidth={2} stroke="currentColor">
                                <path strokeLinecap="round" strokeLinejoin="round" d="M6 18 18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                )}

                <div>
                    <h1 className="text-xl font-bold text-slate-900">Data Booking</h1>
                    <p className="text-sm text-slate-500">Setujui, tolak, atau sembunyikan booking dari daftar.</p>
                </div>

                <div className="overflow-hidden rounded-2xl border border-emerald-100 bg-white shadow-sm">
                    <div className="flex flex-col gap-3 border-b border-emerald-100 px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-5">
                        <form onSubmit={submitSearch} className="flex w-full flex-col gap-2 sm:max-w-2xl sm:flex-row">
                            <div className="relative flex-1">
                                <span className="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                                    <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5" fill="none" viewBox="0 0 24 24" strokeWidth={1.8} stroke="currentColor">
                                        <path strokeLinecap="round" strokeLinejoin="round" d="m21 21-4.35-4.35M17 11a6 6 0 1 1-12 0 6 6 0 0 1 12 0Z" />
                                    </svg>
                                </span>
                                <input
                                    type="text"
                                    value={search}
                                    onChange={(event) => setSearch(event.target.value)}
                                    placeholder="Cari kode booking, pengguna, meja, atau tanggal..."
                                    className="w-full rounded-xl border border-slate-200 bg-white py-2.5 pl-10 pr-3 text-sm text-slate-800 shadow-sm transition placeholder:text-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20"
                                />
                            </div>
                            <select
                                value={status}
                                onChange={(event) => setStatus(event.target.value)}
                                className="rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-700 shadow-sm transition focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 sm:w-44"
                            >
                                <option value="">Semua status</option>
                                <option value="menunggu">Menunggu</option>
                                <option value="200">Disetujui</option>
                                <option value="400">Ditolak</option>
                            </select>
                            <button
                                type="submit"
                                className="inline-flex items-center justify-center rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-sm font-semibold text-emerald-700 transition hover:bg-emerald-100"
                            >
                                Cari
                            </button>
                            {hasFilter && (
                                <button
                                    type="button"
                                    onClick={resetSearch}
                                    className="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm font-medium text-slate-500 transition hover:bg-slate-50"
                                >
                                    Reset
                                </button>
                            )}
                        </form>

                        <p className="text-xs text-slate-500">
                            Total <span className="font-semibold text-slate-700">{bookings.total}</span> booking
                        </p>
                    </div>

                    {bookings.data.length === 0 ? (
                        <div className="flex flex-col items-center justify-center px-6 py-16 text-center">
                            <span className="flex h-16 w-16 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-500">
                                <EmptyIcon />
                            </span>
                            <h2 className="mt-4 text-sm font-semibold text-slate-800">
                                {hasFilter ? 'Booking tidak ditemukan' : 'Belum ada data booking'}
                            </h2>
                            <p className="mt-1 max-w-sm text-sm text-slate-500">
                                {hasFilter
                                    ? 'Coba gunakan kata kunci atau status lain untuk menemukan booking yang dicari.'
                                    : 'Booking yang masuk dari pengguna akan tampil di halaman ini.'}
                            </p>
                        </div>
                    ) : (
                        <>
                            <div className="overflow-x-auto">
                                <table className="w-full min-w-[1140px] text-left text-sm">
                                    <thead className="bg-emerald-50/60 text-xs uppercase tracking-wide text-emerald-800">
                                        <tr>
                                            <th className="px-5 py-3 font-semibold">No</th>
                                            <th className="px-5 py-3 font-semibold">Kode Booking</th>
                                            <th className="px-5 py-3 font-semibold">Tgl Booking</th>
                                            <th className="px-5 py-3 font-semibold">Pengguna</th>
                                            <th className="px-5 py-3 font-semibold">Meja</th>
                                            <th className="px-5 py-3 font-semibold">Jadwal Main</th>
                                            <th className="px-5 py-3 font-semibold">Pembayaran</th>
                                            <th className="px-5 py-3 font-semibold">Status</th>
                                            <th className="px-5 py-3 text-right font-semibold">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-emerald-50">
                                        {bookings.data.map((booking, index) => {
                                            const busy = processingId === booking.id;

                                            return (
                                                <tr key={booking.id} className="transition hover:bg-emerald-50/40">
                                                    <td className="px-5 py-3.5 text-slate-500">
                                                        {(bookings.from ?? 0) + index}
                                                    </td>
                                                    <td className="px-5 py-3.5">
                                                        <p className="font-semibold text-slate-800">{booking.kode_booking}</p>
                                                    </td>
                                                    <td className="px-5 py-3.5 text-slate-600">
                                                        {formatDate(booking.tgl_booking)}
                                                    </td>
                                                    <td className="px-5 py-3.5">
                                                        {booking.pengguna ? (
                                                            <>
                                                                <p className="font-medium text-slate-700">
                                                                    {namaPengguna(booking.pengguna)}
                                                                </p>
                                                                <p className="mt-0.5 text-xs text-slate-500">
                                                                    ID: {booking.id_pengguna}
                                                                </p>
                                                            </>
                                                        ) : (
                                                            <span className="text-slate-600">{booking.id_pengguna || '-'}</span>
                                                        )}
                                                    </td>
                                                    <td className="px-5 py-3.5 text-slate-600">{booking.id_meja || '-'}</td>
                                                    <td className="px-5 py-3.5">
                                                        <p className="font-medium text-slate-700">{formatDate(booking.tgl_main)}</p>
                                                        <p className="mt-0.5 text-xs text-slate-500">
                                                            {formatTime(booking.jam_mulai)} - {formatTime(booking.jam_selesai)}
                                                        </p>
                                                    </td>
                                                    <td className="px-5 py-3.5">
                                                        <p className="font-semibold text-slate-800">{formatRupiah(booking.total_harga)}</p>
                                                        <p className="mt-0.5 text-xs capitalize text-slate-500">
                                                            {booking.sistem_pembayaran || '-'}
                                                        </p>
                                                        {booking.bukti_pembayaran_url && (
                                                            <a
                                                                href={booking.bukti_pembayaran_url}
                                                                target="_blank"
                                                                rel="noreferrer"
                                                                className="mt-1 inline-block text-xs font-semibold text-emerald-700 hover:underline"
                                                            >
                                                                Lihat bukti
                                                            </a>
                                                        )}
                                                        <div className="mt-1.5">
                                                            <PaymentBadge status={booking.status_pembayaran} />
                                                        </div>
                                                    </td>
                                                    <td className="px-5 py-3.5">
                                                        <StatusBadge status={booking.status_booking} />
                                                    </td>
                                                    <td className="px-5 py-3.5">
                                                        <div className="flex flex-wrap items-center justify-end gap-2">
                                                            {['menunggu_verifikasi', 'bayar_di_tempat'].includes(booking.status_pembayaran) && (
                                                                <button
                                                                    type="button"
                                                                    onClick={() => verifikasiPembayaran(booking)}
                                                                    disabled={busy}
                                                                    className="inline-flex items-center gap-1.5 rounded-lg border border-sky-200 bg-sky-600 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-sky-700 disabled:cursor-not-allowed disabled:opacity-50"
                                                                >
                                                                    <svg xmlns="http://www.w3.org/2000/svg" className="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" strokeWidth={2.2} stroke="currentColor">
                                                                        <path strokeLinecap="round" strokeLinejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                                                    </svg>
                                                                    Verifikasi bayar
                                                                </button>
                                                            )}
                                                            {booking.status_booking !== '200' && (
                                                                <button
                                                                    type="button"
                                                                    onClick={() => updateStatus(booking, '200')}
                                                                    disabled={busy}
                                                                    className="inline-flex items-center gap-1.5 rounded-lg border border-emerald-200 bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-50"
                                                                >
                                                                    <svg xmlns="http://www.w3.org/2000/svg" className="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" strokeWidth={2.2} stroke="currentColor">
                                                                        <path strokeLinecap="round" strokeLinejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                                                    </svg>
                                                                    Setujui
                                                                </button>
                                                            )}
                                                            {booking.status_booking !== '400' && (
                                                                <button
                                                                    type="button"
                                                                    onClick={() => updateStatus(booking, '400')}
                                                                    disabled={busy}
                                                                    className="inline-flex items-center gap-1.5 rounded-lg border border-rose-200 bg-white px-3 py-1.5 text-xs font-semibold text-rose-600 transition hover:bg-rose-50 disabled:cursor-not-allowed disabled:opacity-50"
                                                                >
                                                                    <svg xmlns="http://www.w3.org/2000/svg" className="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" strokeWidth={2.2} stroke="currentColor">
                                                                        <path strokeLinecap="round" strokeLinejoin="round" d="M6 18 18 6M6 6l12 12" />
                                                                    </svg>
                                                                    Tolak
                                                                </button>
                                                            )}
                                                            <button
                                                                type="button"
                                                                onClick={() => setDeleteTarget(booking)}
                                                                disabled={busy}
                                                                className="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-500 transition hover:bg-slate-50 hover:text-rose-600 disabled:cursor-not-allowed disabled:opacity-50"
                                                            >
                                                                <svg xmlns="http://www.w3.org/2000/svg" className="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" strokeWidth={1.9} stroke="currentColor">
                                                                    <path strokeLinecap="round" strokeLinejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                                                </svg>
                                                                Hapus
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            );
                                        })}
                                    </tbody>
                                </table>
                            </div>

                            <div className="flex flex-col gap-3 border-t border-emerald-100 px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-5">
                                <p className="text-xs text-slate-500">
                                    Menampilkan <span className="font-semibold text-slate-700">{bookings.from ?? 0}</span> sampai{' '}
                                    <span className="font-semibold text-slate-700">{bookings.to ?? 0}</span> dari{' '}
                                    <span className="font-semibold text-slate-700">{bookings.total}</span> booking
                                </p>

                                {bookings.last_page > 1 && (
                                    <div className="flex flex-wrap items-center gap-1.5">
                                        {bookings.links.map((link, index) =>
                                            link.url ? (
                                                <Link
                                                    key={index}
                                                    href={link.url}
                                                    preserveScroll
                                                    className={`inline-flex min-w-8 items-center justify-center rounded-lg border px-2.5 py-1.5 text-xs font-semibold transition ${
                                                        link.active
                                                            ? 'border-emerald-600 bg-emerald-600 text-white'
                                                            : 'border-slate-200 bg-white text-slate-600 hover:bg-emerald-50 hover:text-emerald-700'
                                                    }`}
                                                    dangerouslySetInnerHTML={{ __html: link.label }}
                                                />
                                            ) : (
                                                <span
                                                    key={index}
                                                    className="inline-flex min-w-8 cursor-not-allowed items-center justify-center rounded-lg border border-slate-100 bg-slate-50 px-2.5 py-1.5 text-xs font-semibold text-slate-300"
                                                    dangerouslySetInnerHTML={{ __html: link.label }}
                                                />
                                            ),
                                        )}
                                    </div>
                                )}
                            </div>
                        </>
                    )}
                </div>
            </div>

            {deleteTarget && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4 backdrop-blur-sm">
                    <div className="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl">
                        <div className="flex items-start gap-4">
                            <span className="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-rose-50 text-rose-600">
                                <svg xmlns="http://www.w3.org/2000/svg" className="h-6 w-6" fill="none" viewBox="0 0 24 24" strokeWidth={1.8} stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" d="M12 9v3.75m0 3h.008v.008H12v-.008ZM12 3.75 2.25 20.25h19.5L12 3.75Z" />
                                </svg>
                            </span>
                            <div className="min-w-0">
                                <h2 className="text-base font-semibold text-slate-900">Hapus booking dari daftar?</h2>
                                <p className="mt-1 text-sm leading-relaxed text-slate-500">
                                    Booking{' '}
                                    <span className="font-semibold text-slate-700">{deleteTarget.kode_booking}</span> akan
                                    disembunyikan dari daftar. Data tetap tersimpan di database dengan status hapus = 1.
                                </p>
                            </div>
                        </div>

                        <div className="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                            <button
                                type="button"
                                onClick={() => setDeleteTarget(null)}
                                disabled={deleting}
                                className="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-600 transition hover:bg-slate-50 disabled:opacity-60"
                            >
                                Batal
                            </button>
                            <button
                                type="button"
                                onClick={confirmDelete}
                                disabled={deleting}
                                className="inline-flex items-center justify-center gap-2 rounded-xl bg-rose-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-rose-700 disabled:cursor-not-allowed disabled:opacity-60"
                            >
                                {deleting && (
                                    <svg className="h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" />
                                        <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8V0C5.373 0 0 5.373 0 12h4Z" />
                                    </svg>
                                )}
                                {deleting ? 'Menghapus...' : 'Ya, Hapus'}
                            </button>
                        </div>
                    </div>
                </div>
            )}
        </>
    );
}

Index.layout = (page) => (
    <AdminLayout title="Data Booking" subtitle="Kelola persetujuan booking mahjong">
        {page}
    </AdminLayout>
);
