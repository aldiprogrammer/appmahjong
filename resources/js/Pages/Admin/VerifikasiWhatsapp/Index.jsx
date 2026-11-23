import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
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

function maskToken(token) {
    const nilai = String(token ?? '');

    if (nilai.length <= 12) {
        return nilai;
    }

    return `${nilai.slice(0, 4)}…${nilai.slice(-4)}`;
}

function EmptyIcon() {
    return (
        <svg xmlns="http://www.w3.org/2000/svg" className="h-10 w-10" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" d="M20.25 11.67a8.25 8.25 0 0 1-12.6 7.02L3.75 20.25l1.62-3.75A8.25 8.25 0 1 1 20.25 11.67Z" />
            <path strokeLinecap="round" strokeLinejoin="round" d="m9 12 2 2 4.5-4.5" />
        </svg>
    );
}

export default function Index({ verifikasiWhatsapps, statuses = [], filters }) {
    const { flash } = usePage().props;
    const [search, setSearch] = useState(filters?.search ?? '');
    const [statusFilter, setStatusFilter] = useState(filters?.status ?? '');
    const [successMessage, setSuccessMessage] = useState(flash?.success ?? null);
    const [formOpen, setFormOpen] = useState(false);
    const [editing, setEditing] = useState(null);
    const [deleteTarget, setDeleteTarget] = useState(null);
    const [deleting, setDeleting] = useState(false);

    const { data, setData, post, put, processing, errors, reset, clearErrors } = useForm({
        nowa: '',
        token: '',
        status: 'aktif',
    });

    useEffect(() => {
        setSuccessMessage(flash?.success ?? null);
    }, [flash?.success]);

    const submitSearch = (event) => {
        event.preventDefault();

        router.get('/admin/verifikasi-whatsapps', { search, status: statusFilter }, { preserveState: true, replace: true });
    };

    const resetSearch = () => {
        setSearch('');
        setStatusFilter('');
        router.get('/admin/verifikasi-whatsapps', {}, { preserveState: true, replace: true });
    };

    const openCreateModal = () => {
        reset();
        clearErrors();
        setEditing(null);
        setFormOpen(true);
    };

    const openEditModal = (item) => {
        clearErrors();
        setData({
            nowa: item.nowa,
            token: item.token,
            status: item.status,
        });
        setEditing(item);
        setFormOpen(true);
    };

    const closeFormModal = () => {
        if (processing) {
            return;
        }

        setFormOpen(false);
        setEditing(null);
        reset();
        clearErrors();
    };

    const submitForm = (event) => {
        event.preventDefault();

        const options = {
            preserveScroll: true,
            onSuccess: () => closeFormModal(),
        };

        if (editing) {
            put(`/admin/verifikasi-whatsapps/${editing.id}`, options);

            return;
        }

        post('/admin/verifikasi-whatsapps', options);
    };

    const confirmDelete = () => {
        if (!deleteTarget) {
            return;
        }

        setDeleting(true);

        router.delete(`/admin/verifikasi-whatsapps/${deleteTarget.id}`, {
            preserveScroll: true,
            onSuccess: () => setDeleteTarget(null),
            onFinish: () => setDeleting(false),
        });
    };

    return (
        <>
            <Head title="Verifikasi WhatsApp" />

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
                            <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5" fill="none" viewBox="0 0 24 24" strokeWidth={2} stroke="currentColor">
                                <path strokeLinecap="round" strokeLinejoin="round" d="M6 18 18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                )}

                <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 className="text-xl font-bold text-slate-900">Verifikasi WhatsApp</h1>
                        <p className="text-sm text-slate-500">Kelola nomor dan token WhatsApp gateway untuk pengiriman OTP.</p>
                    </div>
                    <button
                        type="button"
                        onClick={openCreateModal}
                        className="inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4" fill="none" viewBox="0 0 24 24" strokeWidth={2.2} stroke="currentColor">
                            <path strokeLinecap="round" strokeLinejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>
                        Tambah Verifikasi
                    </button>
                </div>

                <div className="rounded-xl border border-emerald-100 bg-emerald-50/60 px-4 py-3 text-sm text-emerald-900">
                    Token berstatus <span className="font-semibold">aktif</span> dipakai untuk mengirim kode OTP. Bila belum ada token aktif, sistem memakai{' '}
                    <code className="rounded bg-white/70 px-1.5 py-0.5 text-xs">FONNTE_TOKEN</code> dari file <code className="rounded bg-white/70 px-1.5 py-0.5 text-xs">.env</code>.
                </div>

                <div className="overflow-hidden rounded-2xl border border-emerald-100 bg-white shadow-sm">
                    <div className="flex flex-col gap-3 border-b border-emerald-100 px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-5">
                        <form onSubmit={submitSearch} className="flex w-full flex-col gap-2 sm:max-w-xl sm:flex-row">
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
                                    placeholder="Cari nomor atau token..."
                                    className="w-full rounded-xl border border-slate-200 bg-white py-2.5 pl-10 pr-3 text-sm text-slate-800 shadow-sm transition placeholder:text-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20"
                                />
                            </div>
                            <select
                                value={statusFilter}
                                onChange={(event) => setStatusFilter(event.target.value)}
                                className="rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-800 shadow-sm transition focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20"
                            >
                                <option value="">Semua status</option>
                                {statuses.map((status) => (
                                    <option key={status} value={status}>
                                        {status === 'aktif' ? 'Aktif' : 'Nonaktif'}
                                    </option>
                                ))}
                            </select>
                            <button
                                type="submit"
                                className="inline-flex items-center justify-center rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-sm font-semibold text-emerald-700 transition hover:bg-emerald-100"
                            >
                                Cari
                            </button>
                            {(filters?.search || filters?.status) && (
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
                            Total <span className="font-semibold text-slate-700">{verifikasiWhatsapps.total}</span> data
                        </p>
                    </div>

                    {verifikasiWhatsapps.data.length === 0 ? (
                        <div className="flex flex-col items-center justify-center px-6 py-16 text-center">
                            <span className="flex h-16 w-16 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-500">
                                <EmptyIcon />
                            </span>
                            <h2 className="mt-4 text-sm font-semibold text-slate-800">
                                {filters?.search || filters?.status ? 'Data tidak ditemukan' : 'Belum ada verifikasi WhatsApp'}
                            </h2>
                            <p className="mt-1 max-w-sm text-sm text-slate-500">
                                {filters?.search || filters?.status
                                    ? 'Coba gunakan kata kunci atau status lain.'
                                    : 'Tambahkan nomor dan token WhatsApp gateway untuk mulai mengirim OTP.'}
                            </p>
                        </div>
                    ) : (
                        <>
                            <div className="overflow-x-auto">
                                <table className="w-full min-w-[720px] text-left text-sm">
                                    <thead className="bg-emerald-50/60 text-xs uppercase tracking-wide text-emerald-800">
                                        <tr>
                                            <th className="px-5 py-3 font-semibold">No</th>
                                            <th className="px-5 py-3 font-semibold">Nomor WhatsApp</th>
                                            <th className="px-5 py-3 font-semibold">Token</th>
                                            <th className="px-5 py-3 font-semibold">Status</th>
                                            <th className="px-5 py-3 font-semibold">Dibuat</th>
                                            <th className="px-5 py-3 text-right font-semibold">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-emerald-50">
                                        {verifikasiWhatsapps.data.map((item, index) => (
                                            <tr key={item.id} className="transition hover:bg-emerald-50/40">
                                                <td className="px-5 py-3.5 text-slate-500">{(verifikasiWhatsapps.from ?? 0) + index}</td>
                                                <td className="px-5 py-3.5 font-medium text-slate-800">{item.nowa}</td>
                                                <td className="px-5 py-3.5">
                                                    <code className="rounded-lg bg-slate-100 px-2 py-1 font-mono text-xs text-slate-700" title={item.token}>
                                                        {maskToken(item.token)}
                                                    </code>
                                                </td>
                                                <td className="px-5 py-3.5">
                                                    <span
                                                        className={`inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ${
                                                            item.status === 'aktif'
                                                                ? 'bg-emerald-50 text-emerald-700 ring-emerald-100'
                                                                : 'bg-slate-100 text-slate-600 ring-slate-200'
                                                        }`}
                                                    >
                                                        <span
                                                            className={`h-1.5 w-1.5 rounded-full ${
                                                                item.status === 'aktif' ? 'bg-emerald-500' : 'bg-slate-400'
                                                            }`}
                                                        />
                                                        {item.status === 'aktif' ? 'Aktif' : 'Nonaktif'}
                                                    </span>
                                                </td>
                                                <td className="px-5 py-3.5 text-slate-500">{formatDate(item.created_at)}</td>
                                                <td className="px-5 py-3.5">
                                                    <div className="flex items-center justify-end gap-2">
                                                        <button
                                                            type="button"
                                                            onClick={() => openEditModal(item)}
                                                            className="inline-flex items-center gap-1.5 rounded-lg border border-emerald-100 bg-white px-3 py-1.5 text-xs font-semibold text-emerald-700 transition hover:bg-emerald-50"
                                                        >
                                                            Edit
                                                        </button>
                                                        <button
                                                            type="button"
                                                            onClick={() => setDeleteTarget(item)}
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
                                    Menampilkan <span className="font-semibold text-slate-700">{verifikasiWhatsapps.from ?? 0}</span>–
                                    <span className="font-semibold text-slate-700">{verifikasiWhatsapps.to ?? 0}</span> dari{' '}
                                    <span className="font-semibold text-slate-700">{verifikasiWhatsapps.total}</span> data
                                </p>

                                {verifikasiWhatsapps.last_page > 1 && (
                                    <div className="flex flex-wrap items-center gap-1.5">
                                        {verifikasiWhatsapps.links.map((link, index) =>
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

            {formOpen && (
                <div
                    className="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-slate-900/50 p-4 backdrop-blur-sm"
                    onMouseDown={(event) => {
                        if (event.target === event.currentTarget) {
                            closeFormModal();
                        }
                    }}
                >
                    <div className="w-full max-w-lg rounded-2xl bg-white shadow-2xl">
                        <div className="flex items-start justify-between gap-4 border-b border-slate-100 px-6 py-5">
                            <div>
                                <h2 className="text-base font-semibold text-slate-900">
                                    {editing ? 'Edit Verifikasi WhatsApp' : 'Tambah Verifikasi WhatsApp'}
                                </h2>
                                <p className="mt-0.5 text-sm text-slate-500">Nomor dan token ini dipakai untuk mengirim kode OTP registrasi.</p>
                            </div>
                            <button
                                type="button"
                                onClick={closeFormModal}
                                disabled={processing}
                                className="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-600 disabled:opacity-60"
                                aria-label="Tutup modal"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5" fill="none" viewBox="0 0 24 24" strokeWidth={2} stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" d="M6 18 18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <form onSubmit={submitForm} className="space-y-5 px-6 py-5">
                            <div>
                                <label htmlFor="nowa" className="mb-1.5 block text-sm font-medium text-slate-700">
                                    Nomor WhatsApp <span className="text-rose-500">*</span>
                                </label>
                                <input
                                    id="nowa"
                                    type="text"
                                    value={data.nowa}
                                    onChange={(event) => setData('nowa', event.target.value)}
                                    placeholder="Contoh: 08123456789"
                                    autoComplete="off"
                                    required
                                    className={`w-full rounded-xl border px-3.5 py-2.5 text-sm text-slate-800 shadow-sm transition placeholder:text-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 ${
                                        errors.nowa ? 'border-rose-300 bg-rose-50/40' : 'border-slate-200 bg-white'
                                    }`}
                                />
                                {errors.nowa && <p className="mt-1.5 text-xs font-medium text-rose-600">{errors.nowa}</p>}
                            </div>

                            <div>
                                <label htmlFor="token" className="mb-1.5 block text-sm font-medium text-slate-700">
                                    Token <span className="text-rose-500">*</span>
                                </label>
                                <input
                                    id="token"
                                    type="text"
                                    value={data.token}
                                    onChange={(event) => setData('token', event.target.value)}
                                    placeholder="Token dari dashboard Fonnte"
                                    autoComplete="off"
                                    required
                                    className={`w-full rounded-xl border px-3.5 py-2.5 font-mono text-sm text-slate-800 shadow-sm transition placeholder:text-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 ${
                                        errors.token ? 'border-rose-300 bg-rose-50/40' : 'border-slate-200 bg-white'
                                    }`}
                                />
                                {errors.token && <p className="mt-1.5 text-xs font-medium text-rose-600">{errors.token}</p>}
                            </div>

                            <div>
                                <label htmlFor="status" className="mb-1.5 block text-sm font-medium text-slate-700">
                                    Status <span className="text-rose-500">*</span>
                                </label>
                                <select
                                    id="status"
                                    value={data.status}
                                    onChange={(event) => setData('status', event.target.value)}
                                    required
                                    className={`w-full rounded-xl border px-3.5 py-2.5 text-sm text-slate-800 shadow-sm transition focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 ${
                                        errors.status ? 'border-rose-300 bg-rose-50/40' : 'border-slate-200 bg-white'
                                    }`}
                                >
                                    {statuses.map((status) => (
                                        <option key={status} value={status}>
                                            {status === 'aktif' ? 'Aktif' : 'Nonaktif'}
                                        </option>
                                    ))}
                                </select>
                                {errors.status && <p className="mt-1.5 text-xs font-medium text-rose-600">{errors.status}</p>}
                                <p className="mt-1.5 text-xs text-slate-500">Menjadikan satu data aktif akan menonaktifkan token lainnya.</p>
                            </div>

                            <div className="flex flex-col-reverse gap-3 border-t border-slate-100 pt-5 sm:flex-row sm:justify-end">
                                <button
                                    type="button"
                                    onClick={closeFormModal}
                                    disabled={processing}
                                    className="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-600 transition hover:bg-slate-50 disabled:opacity-60"
                                >
                                    Batal
                                </button>
                                <button
                                    type="submit"
                                    disabled={processing}
                                    className="inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60"
                                >
                                    {processing ? 'Menyimpan...' : 'Simpan'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}

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
                                <h2 className="text-base font-semibold text-slate-900">Hapus verifikasi ini?</h2>
                                <p className="mt-1 text-sm leading-relaxed text-slate-500">
                                    Data nomor <span className="font-semibold text-slate-700">{deleteTarget.nowa}</span> akan dihapus permanen.
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
                                className="inline-flex items-center justify-center rounded-xl bg-rose-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-rose-700 disabled:cursor-not-allowed disabled:opacity-60"
                            >
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
    <AdminLayout title="Verifikasi WhatsApp" subtitle="Pengaturan token WhatsApp untuk OTP">
        {page}
    </AdminLayout>
);
