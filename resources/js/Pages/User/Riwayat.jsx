import { Head, Link, usePage } from '@inertiajs/react';
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

function statusMeta(status) {
    if (status === '200') {
        return { label: 'Disetujui', className: 'bg-emerald-50 text-emerald-700 ring-emerald-100', dot: 'bg-emerald-500' };
    }

    if (status === '400') {
        return { label: 'Ditolak', className: 'bg-rose-50 text-rose-700 ring-rose-100', dot: 'bg-rose-500' };
    }

    return { label: 'Menunggu', className: 'bg-amber-50 text-amber-700 ring-amber-100', dot: 'bg-amber-500' };
}

function StatusBadge({ status }) {
    const meta = statusMeta(status);

    return (
        <span className={`inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[11px] font-semibold ring-1 ${meta.className}`}>
            <span className={`h-1.5 w-1.5 rounded-full ${meta.dot}`} />
            {meta.label}
        </span>
    );
}

function TableIcon({ className = 'h-4 w-4' }) {
    return (
        <svg xmlns="http://www.w3.org/2000/svg" className={className} fill="none" viewBox="0 0 24 24" strokeWidth={1.8} stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" d="M3 10h18M5 10v8m14-8v8M7 21h10M6 10V6a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v4" />
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

function TicketIcon({ className = 'h-10 w-10' }) {
    return (
        <svg xmlns="http://www.w3.org/2000/svg" className={className} fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" d="M16.5 6v.75m0 3v.75m0 3v.75m0 3V18m-9-12v.75m0 3v.75m0 3v.75m0 3V18M3.75 6.75h16.5A1.5 1.5 0 0 1 21.75 8.25v1.5a2.25 2.25 0 0 0 0 4.5v1.5a1.5 1.5 0 0 1-1.5 1.5H3.75a1.5 1.5 0 0 1-1.5-1.5v-1.5a2.25 2.25 0 0 0 0-4.5v-1.5a1.5 1.5 0 0 1 1.5-1.5Z" />
        </svg>
    );
}

export default function Riwayat({ pengguna, bookings }) {
    const { flash } = usePage().props;
    const total = bookings.length;
    const disetujui = bookings.filter((booking) => booking.status_booking === '200').length;
    const menunggu = bookings.filter((booking) => !['200', '400'].includes(booking.status_booking)).length;

    return (
        <>
            <Head title="Riwayat Booking" />

            <header className="relative overflow-hidden rounded-b-[2rem] bg-gradient-to-br from-emerald-600 to-emerald-800 px-5 pb-12 pt-6 text-white">
                <div className="pointer-events-none absolute -right-10 -top-12 h-40 w-40 rounded-full bg-white/10" />
                <div className="relative">
                    <h1 className="text-lg font-bold">Riwayat Booking</h1>
                    <p className="mt-0.5 text-xs text-emerald-100">Semua booking yang kamu buat</p>

                    <div className="mt-5 grid grid-cols-3 gap-2 text-center">
                        {[
                            { label: 'Total', value: total },
                            { label: 'Disetujui', value: disetujui },
                            { label: 'Menunggu', value: menunggu },
                        ].map((item) => (
                            <div key={item.label} className="rounded-xl bg-white/10 py-2 ring-1 ring-white/15">
                                <p className="text-base font-bold">{item.value}</p>
                                <p className="text-[10px] text-emerald-100">{item.label}</p>
                            </div>
                        ))}
                    </div>
                </div>
            </header>

            <div className="relative -mt-7 space-y-3 px-5 pb-8">
                {flash?.success && (
                    <div className="flex items-start gap-3 rounded-2xl border border-emerald-200 bg-white px-4 py-3 text-sm text-emerald-800 shadow-sm">
                        <span className="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-emerald-600 text-white">
                            <svg xmlns="http://www.w3.org/2000/svg" className="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" strokeWidth={2.4} stroke="currentColor">
                                <path strokeLinecap="round" strokeLinejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                            </svg>
                        </span>
                        <p className="font-medium leading-relaxed">{flash.success}</p>
                    </div>
                )}

                {!pengguna || bookings.length === 0 ? (
                    <div className="flex flex-col items-center rounded-2xl border border-emerald-100 bg-white px-6 py-12 text-center shadow-sm">
                        <span className="flex h-16 w-16 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-500">
                            <TicketIcon />
                        </span>
                        <h2 className="mt-4 text-sm font-bold text-slate-800">
                            {pengguna ? 'Belum ada booking' : 'Kamu belum booking'}
                        </h2>
                        <p className="mt-1 max-w-xs text-xs leading-relaxed text-slate-500">
                            Booking meja pertamamu sekarang dan lihat status persetujuannya di halaman ini.
                        </p>
                        <Link
                            href="/booking"
                            className="mt-5 inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2.5 text-xs font-bold text-white shadow-sm transition hover:bg-emerald-700"
                        >
                            Booking Sekarang
                        </Link>
                    </div>
                ) : (
                    <>
                        {bookings.map((booking) => (
                            <article
                                key={booking.id}
                                className="overflow-hidden rounded-2xl border border-emerald-100 bg-white shadow-sm"
                            >
                                <div className="flex items-center justify-between gap-2 border-b border-dashed border-emerald-100 px-4 py-3">
                                    <div>
                                        <p className="text-sm font-bold text-slate-800">{booking.kode_booking}</p>
                                        <p className="mt-0.5 text-[10px] text-slate-400">
                                            Dipesan {formatDate(booking.tgl_booking)}
                                        </p>
                                    </div>
                                    <StatusBadge status={booking.status_booking} />
                                </div>

                                <div className="space-y-2 px-4 py-3">
                                    <div className="flex items-center gap-2 text-xs text-slate-600">
                                        <span className="flex h-7 w-7 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600">
                                            <ClockIcon />
                                        </span>
                                        <div>
                                            <p className="font-semibold text-slate-800">{formatDate(booking.tgl_main)}</p>
                                            <p className="text-[10px] text-slate-500">
                                                {formatTime(booking.jam_mulai)} - {formatTime(booking.jam_selesai)}
                                            </p>
                                        </div>
                                    </div>
                                    <div className="flex items-center gap-2 text-xs text-slate-600">
                                        <span className="flex h-7 w-7 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600">
                                            <TableIcon />
                                        </span>
                                        <div>
                                            <p className="font-semibold text-slate-800">Meja {booking.id_meja || '-'}</p>
                                            <p className="text-[10px] capitalize text-slate-500">
                                                Pembayaran {booking.sistem_pembayaran || '-'}
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <div className="flex items-center justify-between bg-emerald-50/60 px-4 py-2.5">
                                    <span className="text-[11px] font-medium text-slate-500">Total bayar</span>
                                    <span className="text-sm font-bold text-emerald-700">
                                        {formatRupiah(booking.total_harga)}
                                    </span>
                                </div>
                            </article>
                        ))}

                        <Link
                            href="/booking"
                            className="flex w-full items-center justify-center rounded-2xl border border-emerald-200 bg-white px-5 py-3 text-xs font-bold text-emerald-700 transition hover:bg-emerald-50"
                        >
                            Buat Booking Baru
                        </Link>
                    </>
                )}
            </div>
        </>
    );
}

Riwayat.layout = (page) => <UserLayout>{page}</UserLayout>;
