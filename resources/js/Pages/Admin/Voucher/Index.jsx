import { Head, Link, router, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
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

function EmptyIcon() {
    return (
        <svg xmlns="http://www.w3.org/2000/svg" className="h-10 w-10" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V19a2 2 0 0 1-2 2Z" />
        </svg>
    );
}

export default function Index({ vouchers, filters }) {
    const { flash } = usePage().props;
    const [search, setSearch] = useState(filters?.search ?? '');
    const [successMessage, setSuccessMessage] = useState(flash?.success ?? null);
    const [deleteTarget, setDeleteTarget] = useState(null);
    const [deleting, setDeleting] = useState(false);

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

    const confirmDelete = () => {
        if (!deleteTarget) {
            return;
        }

        setDeleting(true);

        router.delete(`/admin/vouchers/${deleteTarget.id}`, {
            preserveScroll: true,
            onSuccess: () => setDeleteTarget(null),
            onFinish: () => setDeleting(false),
        });
    };

    const submitSearch = (event) => {
        event.preventDefault();
        router.get('/admin/vouchers', { search }, { preserveState: true, replace: true });
    };

    const resetSearch = () => {
        setSearch('');
        router.get('/admin/vouchers', {}, { preserveState: true, replace: true });
    };

    return (
        <>
            <Head title="Voucher" />

            <div className="mx-auto max-w-7xl space-y-6">
                {successMessage && (
                    <div className="flex items-center gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
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

                <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 className="text-xl font-bold text-slate-900">Voucher</h1>
                        <p className="text-sm text-slate-500">Data voucher yang diambil dari tabel voucher.</p>
                    </div>
                </div>

                <div className="overflow-hidden rounded-2xl border border-emerald-100 bg-white shadow-sm">
                    <div className="flex flex-col gap-3 border-b border-emerald-100 px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-5">
                        <form onSubmit={submitSearch} className="flex w-full gap-2 sm:max-w-md">
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
                                    placeholder="Cari kode voucher atau user..."
                                    className="w-full rounded-xl border border-slate-200 bg-white py-2.5 pl-10 pr-3 text-sm text-slate-800 shadow-sm transition placeholder:text-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20"
                                />
                            </div>
                            <button
                                type="submit"
                                className="inline-flex items-center justify-center rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-sm font-semibold text-emerald-700 transition hover:bg-emerald-100"
                            >
                                Cari
                            </button>
                            {filters?.search && (
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
                            Total <span className="font-semibold text-slate-700">{vouchers.total}</span> voucher
                        </p>
                    </div>

                    {vouchers.data.length === 0 ? (
                        <div className="flex flex-col items-center justify-center px-6 py-16 text-center">
                            <span className="flex h-16 w-16 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-500">
                                <EmptyIcon />
                            </span>
                            <h2 className="mt-4 text-sm font-semibold text-slate-800">
                                {filters?.search ? 'Voucher tidak ditemukan' : 'Belum ada data voucher'}
                            </h2>
                            <p className="mt-1 max-w-sm text-sm text-slate-500">
                                {filters?.search
                                    ? 'Coba gunakan kata kunci lain untuk menemukan voucher yang dicari.'
                                    : 'Data voucher akan tampil di sini setelah voucher dibuat dari booking yang disetujui.'}
                            </p>
                        </div>
                    ) : (
                        <>
                            <div className="overflow-x-auto">
                                <table className="w-full min-w-[760px] text-left text-sm">
                                    <thead className="bg-emerald-50/60 text-xs uppercase tracking-wide text-emerald-800">
                                        <tr>
                                            <th className="px-5 py-3 font-semibold">No</th>
                                            <th className="px-5 py-3 font-semibold">Kode Voucher</th>
                                            <th className="px-5 py-3 font-semibold">Kode User</th>
                                            <th className="px-5 py-3 font-semibold">Status Voucher</th>
                                            <th className="px-5 py-3 font-semibold">Kuota Jam</th>
                                            <th className="px-5 py-3 font-semibold">Diterima</th>
                                            <th className="px-5 py-3 font-semibold">Digunakan</th>
                                            <th className="px-5 py-3 font-semibold">Status</th>
                                            <th className="px-5 py-3 text-right font-semibold">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-emerald-50">
                                        {vouchers.data.map((voucher, index) => (
                                            <tr key={voucher.id} className="transition hover:bg-emerald-50/40">
                                                <td className="px-5 py-3.5 text-slate-500">
                                                    {(vouchers.from ?? 0) + index}
                                                </td>
                                                <td className="px-5 py-3.5">
                                                    <span className="inline-flex rounded-lg bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-100">
                                                        {voucher.kode_voucher}
                                                    </span>
                                                </td>
                                                <td className="px-5 py-3.5 font-medium text-slate-800">{voucher.kode_user}</td>
                                                <td className="px-5 py-3.5 text-slate-700">{voucher.status_voucher ?? '-'}</td>
                                                <td className="px-5 py-3.5 text-slate-700">{voucher.jumah_jam ?? '-'}</td>
                                                <td className="px-5 py-3.5 text-slate-500">{formatDate(voucher.tanggal_diterima)}</td>
                                                <td className="px-5 py-3.5 text-slate-500">{formatDate(voucher.tanggal_digunakan)}</td>
                                                <td className="px-5 py-3.5">
                                                    <span
                                                        className={`inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ${
                                                            voucher.is_used
                                                                ? 'bg-slate-100 text-slate-600 ring-slate-200'
                                                                : 'bg-emerald-50 text-emerald-700 ring-emerald-100'
                                                        }`}
                                                    >
                                                        <span className={`h-1.5 w-1.5 rounded-full ${voucher.is_used ? 'bg-slate-400' : 'bg-emerald-500'}`} />
                                                        {voucher.is_used ? 'Digunakan' : 'Belum Digunakan'}
                                                    </span>
                                                </td>
                                                <td className="px-5 py-3.5">
                                                    <div className="flex items-center justify-end gap-2">
                                                        <Link
                                                            href={`/admin/vouchers/${voucher.id}`}
                                                            className="inline-flex items-center gap-1.5 rounded-lg border border-emerald-100 bg-white px-3 py-1.5 text-xs font-semibold text-emerald-700 transition hover:bg-emerald-50"
                                                        >
                                                            Detail
                                                        </Link>
                                                        <button
                                                            type="button"
                                                            onClick={() => {
                                                                router.patch(`/admin/vouchers/${voucher.id}/status`, {}, { preserveScroll: true });
                                                            }}
                                                            className={`inline-flex items-center gap-1.5 rounded-lg border px-3 py-1.5 text-xs font-semibold transition ${
                                                                voucher.status === 'aktif'
                                                                    ? 'border-amber-100 bg-white text-amber-600 hover:bg-amber-50'
                                                                    : 'border-emerald-100 bg-white text-emerald-700 hover:bg-emerald-50'
                                                            }`}
                                                        >
                                                            {voucher.status === 'aktif' ? 'Nonaktifkan' : 'Aktifkan'}
                                                        </button>
                                                        <button
                                                            type="button"
                                                            onClick={() => setDeleteTarget(voucher)}
                                                            className="inline-flex items-center gap-1.5 rounded-lg border border-rose-100 bg-white px-3 py-1.5 text-xs font-semibold text-rose-600 transition hover:bg-rose-50"
                                                        >
                                                            Hapus
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>

                            <div className="flex flex-col gap-3 border-t border-emerald-100 px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-5">
                                <p className="text-xs text-slate-500">
                                    Menampilkan <span className="font-semibold text-slate-700">{vouchers.from ?? 0}</span>–
                                    <span className="font-semibold text-slate-700">{vouchers.to ?? 0}</span> dari{' '}
                                    <span className="font-semibold text-slate-700">{vouchers.total}</span> voucher
                                </p>

                                {vouchers.last_page > 1 && (
                                    <div className="flex flex-wrap items-center gap-1.5">
                                        {vouchers.links.map((link, index) =>
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
                                <h2 className="text-base font-semibold text-slate-900">Hapus voucher ini?</h2>
                                <p className="mt-1 text-sm leading-relaxed text-slate-500">
                                    Voucher <span className="font-semibold text-slate-700">{deleteTarget.kode_voucher}</span> akan dihapus permanen. Tindakan ini tidak dapat dibatalkan.
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
    <AdminLayout title="Voucher" subtitle="Data voucher mahjong">
        {page}
    </AdminLayout>
);
