import { Head, Link } from '@inertiajs/react';
import AdminLayout from '../../../Layouts/AdminLayout';

function formatDate(value) {
    if (!value) {
        return '-';
    }

    return new Intl.DateTimeFormat('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    }).format(new Date(value));
}

function formatTime(value) {
    if (!value) {
        return '-';
    }

    return String(value).slice(0, 5);
}

function formatRupiah(value) {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(Number(value) || 0);
}

export default function Show({ voucher, bookings = [] }) {
    return (
        <>
            <Head title={`Detail Voucher ${voucher.kode_voucher}`} />

            <div className="mx-auto max-w-7xl space-y-6">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 className="text-xl font-bold text-slate-900">Detail Voucher</h1>
                        <p className="text-sm text-slate-500">Voucher ini didapatkan dari booking berikut.</p>
                    </div>
                    <Link
                        href="/admin/vouchers"
                        className="inline-flex items-center justify-center rounded-xl border border-emerald-200 bg-white px-4 py-2.5 text-sm font-semibold text-emerald-700 transition hover:bg-emerald-50"
                    >
                        Kembali
                    </Link>
                </div>

                <div className="overflow-hidden rounded-2xl border border-emerald-100 bg-white shadow-sm">
                    <div className="grid gap-4 p-5 sm:grid-cols-2 lg:grid-cols-4">
                        <div>
                            <p className="text-xs text-slate-400">Kode Voucher</p>
                            <p className="mt-1 font-semibold text-emerald-700">{voucher.kode_voucher}</p>
                        </div>
                        <div>
                            <p className="text-xs text-slate-400">Kode User</p>
                            <p className="mt-1 font-semibold text-slate-800">{voucher.kode_user}</p>
                        </div>
                        <div>
                            <p className="text-xs text-slate-400">Status Voucher</p>
                            <p className="mt-1 font-semibold text-slate-800">{voucher.status_voucher ?? '-'}</p>
                        </div>
                        <div>
                            <p className="text-xs text-slate-400">Kuota Jam</p>
                            <p className="mt-1 font-semibold text-slate-800">{voucher.jumah_jam ?? '-'} jam</p>
                        </div>
                        <div>
                            <p className="text-xs text-slate-400">Status</p>
                            <p className="mt-1 font-semibold text-slate-800">{voucher.status}</p>
                        </div>
                        <div>
                            <p className="text-xs text-slate-400">Diterima</p>
                            <p className="mt-1 font-semibold text-slate-800">{formatDate(voucher.tanggal_diterima)}</p>
                        </div>
                        <div>
                            <p className="text-xs text-slate-400">Digunakan</p>
                            <p className="mt-1 font-semibold text-slate-800">{formatDate(voucher.tanggal_digunakan)}</p>
                        </div>
                    </div>
                </div>

                <div className="overflow-hidden rounded-2xl border border-emerald-100 bg-white shadow-sm">
                    <div className="border-b border-emerald-100 px-5 py-4">
                        <h2 className="text-sm font-bold text-slate-900">Booking Terkait</h2>
                    </div>

                    {bookings.length === 0 ? (
                        <div className="px-6 py-12 text-center text-sm text-slate-500">
                            Belum ada booking yang menggunakan voucher ini.
                        </div>
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="w-full min-w-[760px] text-left text-sm">
                                <thead className="bg-emerald-50/60 text-xs uppercase tracking-wide text-emerald-800">
                                    <tr>
                                        <th className="px-5 py-3 font-semibold">Kode Booking</th>
                                        <th className="px-5 py-3 font-semibold">Pengguna</th>
                                        <th className="px-5 py-3 font-semibold">Meja</th>
                                        <th className="px-5 py-3 font-semibold">Tanggal Main</th>
                                        <th className="px-5 py-3 font-semibold">Jam</th>
                                        <th className="px-5 py-3 font-semibold">Status Booking</th>
                                        <th className="px-5 py-3 font-semibold">Pembayaran</th>
                                        <th className="px-5 py-3 text-right font-semibold">Total</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-emerald-50">
                                    {bookings.map((booking) => (
                                        <tr key={booking.id} className="transition hover:bg-emerald-50/40">
                                            <td className="px-5 py-3.5">
                                                <span className="inline-flex rounded-lg bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-100">
                                                    {booking.kode_booking}
                                                </span>
                                            </td>
                                            <td className="px-5 py-3.5 font-medium text-slate-800">{booking.pengguna}</td>
                                            <td className="px-5 py-3.5 text-slate-700">{booking.id_meja}</td>
                                            <td className="px-5 py-3.5 text-slate-500">{formatDate(booking.tgl_main)}</td>
                                            <td className="px-5 py-3.5 text-slate-700">
                                                {formatTime(booking.jam_mulai)} - {formatTime(booking.jam_selesai)}
                                            </td>
                                            <td className="px-5 py-3.5 text-slate-700">{booking.status_booking}</td>
                                            <td className="px-5 py-3.5 text-slate-700">{booking.status_pembayaran}</td>
                                            <td className="px-5 py-3.5 text-right font-semibold text-slate-800">{formatRupiah(booking.total_harga)}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                </div>
            </div>
        </>
    );
}

Show.layout = (page) => (
    <AdminLayout title="Detail Voucher" subtitle="Booking terkait voucher">
        {page}
    </AdminLayout>
);
