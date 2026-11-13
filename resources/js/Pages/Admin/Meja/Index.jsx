import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import AdminLayout from '../../../Layouts/AdminLayout';
import MejaForm from './Partials/MejaForm';

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
            <path strokeLinecap="round" strokeLinejoin="round" d="M3 10h18M5 10v8m14-8v8M7 21h10M6 10V6a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v4" />
        </svg>
    );
}

function CloseIcon() {
    return (
        <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5" fill="none" viewBox="0 0 24 24" strokeWidth={2} stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" d="M6 18 18 6M6 6l12 12" />
        </svg>
    );
}

export default function Index({ mejas, vanues = [], filters }) {
    const { flash, auth } = usePage().props;
    const currentAdmin = auth?.admin ?? null;
    const [search, setSearch] = useState(filters?.search ?? '');
    const [vanueId, setVanueId] = useState(filters?.vanue_id ? String(filters.vanue_id) : '');
    const [successMessage, setSuccessMessage] = useState(flash?.success ?? null);
    const [errorMessage, setErrorMessage] = useState(flash?.error ?? null);
    const [deleteTarget, setDeleteTarget] = useState(null);
    const [deleting, setDeleting] = useState(false);
    const [formOpen, setFormOpen] = useState(false);
    const [editingMeja, setEditingMeja] = useState(null);
    const [qrTarget, setQrTarget] = useState(null);
    const kodeInputRef = useRef(null);

    const { data, setData, post, put, processing, errors, reset, clearErrors } = useForm({
        kode_meja: '',
        nama_meja: '',
        vanue_id: currentAdmin?.vanue?.id ? String(currentAdmin.vanue.id) : '',
    });

    useEffect(() => {
        setSuccessMessage(flash?.success ?? null);
    }, [flash?.success]);

    useEffect(() => {
        setErrorMessage(flash?.error ?? null);
    }, [flash?.error]);

    useEffect(() => {
        if (!formOpen) {
            return;
        }

        const timer = setTimeout(() => kodeInputRef.current?.focus(), 80);

        return () => clearTimeout(timer);
    }, [formOpen]);

    useEffect(() => {
        if (!formOpen && !deleteTarget && !qrTarget) {
            return;
        }

        const handleEscape = (event) => {
            if (event.key !== 'Escape' || processing || deleting) {
                return;
            }

            setFormOpen(false);
            setEditingMeja(null);
            setDeleteTarget(null);
            setQrTarget(null);
        };

        window.addEventListener('keydown', handleEscape);

        return () => window.removeEventListener('keydown', handleEscape);
    }, [formOpen, deleteTarget, qrTarget, processing, deleting]);

    const submitSearch = (event) => {
        event.preventDefault();

        router.get('/admin/mejas', { search, vanue_id: vanueId }, { preserveState: true, replace: true });
    };

    const resetSearch = () => {
        setSearch('');
        setVanueId('');
        router.get('/admin/mejas', {}, { preserveState: true, replace: true });
    };

    const openCreateModal = () => {
        reset();
        clearErrors();
        setEditingMeja(null);
        setFormOpen(true);
    };

    const openEditModal = (meja) => {
        clearErrors();
        setData({
            kode_meja: meja.kode_meja,
            nama_meja: meja.nama_meja,
            vanue_id: meja.vanue?.id ? String(meja.vanue.id) : '',
        });
        setEditingMeja(meja);
        setFormOpen(true);
    };

    const closeFormModal = () => {
        if (processing) {
            return;
        }

        setFormOpen(false);
        setEditingMeja(null);
        reset();
        clearErrors();
    };

    const submitForm = (event) => {
        event.preventDefault();

        const options = {
            preserveScroll: true,
            onSuccess: () => {
                setFormOpen(false);
                setEditingMeja(null);
                reset();
            },
        };

        if (editingMeja) {
            put(`/admin/mejas/${editingMeja.id}`, options);

            return;
        }

        post('/admin/mejas', options);
    };

    const confirmDelete = () => {
        if (!deleteTarget) {
            return;
        }

        setDeleting(true);

        router.delete(`/admin/mejas/${deleteTarget.id}`, {
            preserveScroll: true,
            onSuccess: () => setDeleteTarget(null),
            onFinish: () => setDeleting(false),
        });
    };

    const isVenueScoped = Boolean(currentAdmin?.vanue) && currentAdmin?.role !== 'super_admin';
    const hasFilter = Boolean(filters?.search) || Boolean(filters?.vanue_id);

    return (
        <>
            <Head title="Data Meja" />

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

                {errorMessage && (
                    <div className="flex items-center gap-3 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                        <span className="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-rose-600 text-white">
                            <svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4" fill="none" viewBox="0 0 24 24" strokeWidth={2.2} stroke="currentColor">
                                <path strokeLinecap="round" strokeLinejoin="round" d="M12 9v3.75m0 3h.008v.008H12v-.008ZM12 3.75 2.25 20.25h19.5L12 3.75Z" />
                            </svg>
                        </span>
                        <p className="font-medium">{errorMessage}</p>
                        <button
                            type="button"
                            onClick={() => setErrorMessage(null)}
                            className="ml-auto inline-flex h-7 w-7 items-center justify-center rounded-lg text-rose-600 transition hover:bg-rose-100"
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
                        <h1 className="text-xl font-bold text-slate-900">Data Meja</h1>
                        <p className="text-sm text-slate-500">Kelola daftar meja mahjong pada setiap venue.</p>
                        {isVenueScoped && (
                            <span className="mt-2 inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-100">
                                <svg xmlns="http://www.w3.org/2000/svg" className="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" strokeWidth={1.8} stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                    <path strokeLinecap="round" strokeLinejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
                                </svg>
                                Venue aktif: {currentAdmin?.vanue?.nama}
                            </span>
                        )}
                    </div>
                    <div className="flex flex-col gap-2 sm:flex-row sm:items-center">
                        <a
                            href="/admin/mejas/qr-code/semua"
                            className="inline-flex items-center justify-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-sm font-semibold text-emerald-700 transition hover:bg-emerald-100"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4" fill="none" viewBox="0 0 24 24" strokeWidth={1.9} stroke="currentColor">
                                <path strokeLinecap="round" strokeLinejoin="round" d="M3 4.5h5v5H3v-5Zm13 0h5v5h-5v-5ZM3 14.5h5v5H3v-5Zm9.5 0h2v2h-2v-2Zm3 0h2v2h-2v-2Zm-3 3h2v2h-2v-2Zm3 0h2v2h-2v-2Zm3 0h2v2h-2v-2Zm0-3h2v2h-2v-2Z" />
                            </svg>
                            Download Semua QR
                        </a>
                        <button
                            type="button"
                            onClick={openCreateModal}
                            className="inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4" fill="none" viewBox="0 0 24 24" strokeWidth={2.2} stroke="currentColor">
                                <path strokeLinecap="round" strokeLinejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                            </svg>
                            Tambah Meja
                        </button>
                    </div>
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
                                    placeholder="Cari kode atau nama meja..."
                                    className="w-full rounded-xl border border-slate-200 bg-white py-2.5 pl-10 pr-3 text-sm text-slate-800 shadow-sm transition placeholder:text-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20"
                                />
                            </div>
                            <select
                                value={vanueId}
                                onChange={(event) => setVanueId(event.target.value)}
                                className="rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-700 shadow-sm transition focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 sm:w-44"
                            >
                                <option value="">Semua venue</option>
                                {(vanues ?? []).map((vanue) => (
                                    <option key={vanue.id} value={vanue.id}>
                                        {vanue.nama}
                                    </option>
                                ))}
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
                            Total <span className="font-semibold text-slate-700">{mejas.total}</span> meja
                        </p>
                    </div>

                    {mejas.data.length === 0 ? (
                        <div className="flex flex-col items-center justify-center px-6 py-16 text-center">
                            <span className="flex h-16 w-16 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-500">
                                <EmptyIcon />
                            </span>
                            <h2 className="mt-4 text-sm font-semibold text-slate-800">
                                {hasFilter ? 'Meja tidak ditemukan' : 'Belum ada data meja'}
                            </h2>
                            <p className="mt-1 max-w-sm text-sm text-slate-500">
                                {hasFilter
                                    ? 'Coba gunakan kata kunci atau venue lain untuk menemukan meja yang dicari.'
                                    : 'Tambahkan meja pertama pada venue Anda untuk mulai mengelola data meja mahjong.'}
                            </p>
                            {!hasFilter && (
                                <button
                                    type="button"
                                    onClick={openCreateModal}
                                    className="mt-5 inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-700"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4" fill="none" viewBox="0 0 24 24" strokeWidth={2.2} stroke="currentColor">
                                        <path strokeLinecap="round" strokeLinejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                    </svg>
                                    Tambah Meja
                                </button>
                            )}
                        </div>
                    ) : (
                        <>
                            <div className="overflow-x-auto">
                                <table className="w-full min-w-[760px] text-left text-sm">
                                    <thead className="bg-emerald-50/60 text-xs uppercase tracking-wide text-emerald-800">
                                        <tr>
                                            <th className="px-5 py-3 font-semibold">No</th>
                                            <th className="px-5 py-3 font-semibold">Kode Meja</th>
                                            <th className="px-5 py-3 font-semibold">Nama Meja</th>
                                            <th className="px-5 py-3 font-semibold">Venue</th>
                                            <th className="px-5 py-3 font-semibold">Dibuat</th>
                                            <th className="px-5 py-3 text-right font-semibold">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-emerald-50">
                                        {mejas.data.map((meja, index) => (
                                            <tr key={meja.id} className="transition hover:bg-emerald-50/40">
                                                <td className="px-5 py-3.5 text-slate-500">
                                                    {(mejas.from ?? 0) + index}
                                                </td>
                                                <td className="px-5 py-3.5">
                                                    <span className="inline-flex rounded-lg bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-100">
                                                        {meja.kode_meja}
                                                    </span>
                                                </td>
                                                <td className="px-5 py-3.5 font-medium text-slate-800">{meja.nama_meja}</td>
                                                <td className="px-5 py-3.5">
                                                    {meja.vanue ? (
                                                        <span className="inline-flex items-center gap-1.5 rounded-full bg-sky-50 px-2.5 py-1 text-xs font-semibold text-sky-700 ring-1 ring-sky-100">
                                                            <svg xmlns="http://www.w3.org/2000/svg" className="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" strokeWidth={1.8} stroke="currentColor">
                                                                <path strokeLinecap="round" strokeLinejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                                                <path strokeLinecap="round" strokeLinejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
                                                            </svg>
                                                            {meja.vanue.nama}
                                                        </span>
                                                    ) : (
                                                        <span className="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-500 ring-1 ring-slate-200">
                                                            Tanpa venue
                                                        </span>
                                                    )}
                                                </td>
                                                <td className="px-5 py-3.5 text-slate-500">{formatDate(meja.created_at)}</td>
                                                <td className="px-5 py-3.5">
                                                    <div className="flex items-center justify-end gap-2">
                                                        <button
                                                            type="button"
                                                            onClick={() => setQrTarget(meja)}
                                                            className="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 transition hover:bg-slate-50"
                                                        >
                                                            <svg xmlns="http://www.w3.org/2000/svg" className="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" strokeWidth={1.9} stroke="currentColor">
                                                                <path strokeLinecap="round" strokeLinejoin="round" d="M3.75 4.5h4.5v4.5h-4.5V4.5Zm12 0h4.5v4.5h-4.5V4.5Zm-12 10.5h4.5v4.5h-4.5V15Zm9.75-3h3v3h-3v-3Zm3.75 3h.75v.75H17.25V15Zm-3 3h3v.75h-3V18Zm3.75.75h.75V21H18v-2.25Zm-4.5-.75h.75V21h-.75v-2.25Z" />
                                                            </svg>
                                                            QR
                                                        </button>
                                                        <button
                                                            type="button"
                                                            onClick={() => openEditModal(meja)}
                                                            className="inline-flex items-center gap-1.5 rounded-lg border border-emerald-100 bg-white px-3 py-1.5 text-xs font-semibold text-emerald-700 transition hover:bg-emerald-50"
                                                        >
                                                            <svg xmlns="http://www.w3.org/2000/svg" className="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" strokeWidth={1.9} stroke="currentColor">
                                                                <path strokeLinecap="round" strokeLinejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125" />
                                                            </svg>
                                                            Edit
                                                        </button>
                                                        <button
                                                            type="button"
                                                            onClick={() => setDeleteTarget(meja)}
                                                            className="inline-flex items-center gap-1.5 rounded-lg border border-rose-100 bg-white px-3 py-1.5 text-xs font-semibold text-rose-600 transition hover:bg-rose-50"
                                                        >
                                                            <svg xmlns="http://www.w3.org/2000/svg" className="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" strokeWidth={1.9} stroke="currentColor">
                                                                <path strokeLinecap="round" strokeLinejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                                            </svg>
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
                                    Menampilkan <span className="font-semibold text-slate-700">{mejas.from ?? 0}</span>–
                                    <span className="font-semibold text-slate-700">{mejas.to ?? 0}</span> dari{' '}
                                    <span className="font-semibold text-slate-700">{mejas.total}</span> meja
                                </p>

                                {mejas.last_page > 1 && (
                                    <div className="flex flex-wrap items-center gap-1.5">
                                        {mejas.links.map((link, index) =>
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
                                    {editingMeja ? 'Edit Meja' : 'Tambah Meja'}
                                </h2>
                                <p className="mt-0.5 text-sm text-slate-500">
                                    {editingMeja
                                        ? `Perbarui data meja ${editingMeja.kode_meja}.`
                                        : 'Lengkapi data meja baru di bawah ini.'}
                                </p>
                            </div>
                            <button
                                type="button"
                                onClick={closeFormModal}
                                disabled={processing}
                                className="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-600 disabled:opacity-60"
                                aria-label="Tutup modal"
                            >
                                <CloseIcon />
                            </button>
                        </div>

                        <div className="max-h-[70vh] overflow-y-auto px-6 py-5">
                            <MejaForm
                                data={data}
                                setData={setData}
                                errors={errors}
                                processing={processing}
                                onSubmit={submitForm}
                                onCancel={closeFormModal}
                                submitLabel={editingMeja ? 'Simpan Perubahan' : 'Simpan Meja'}
                                vanues={vanues}
                                currentAdmin={currentAdmin}
                                kodeInputRef={kodeInputRef}
                            />
                        </div>
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
                                <h2 className="text-base font-semibold text-slate-900">Hapus meja ini?</h2>
                                <p className="mt-1 text-sm leading-relaxed text-slate-500">
                                    Meja <span className="font-semibold text-slate-700">{deleteTarget.nama_meja}</span>{' '}
                                    ({deleteTarget.kode_meja})
                                    {deleteTarget.vanue ? (
                                        <>
                                            {' '}
                                            di venue{' '}
                                            <span className="font-semibold text-slate-700">{deleteTarget.vanue.nama}</span>
                                        </>
                                    ) : null}{' '}
                                    akan dihapus permanen. Tindakan ini tidak dapat dibatalkan.
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

            {qrTarget && (
                <div
                    className="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-slate-900/50 p-4 backdrop-blur-sm"
                    onMouseDown={(event) => {
                        if (event.target === event.currentTarget) {
                            setQrTarget(null);
                        }
                    }}
                >
                    <div className="w-full max-w-sm rounded-2xl bg-white shadow-2xl">
                        <div className="flex items-start justify-between gap-4 border-b border-slate-100 px-6 py-4">
                            <div>
                                <h2 className="text-base font-semibold text-slate-900">QR Code Meja</h2>
                                <p className="mt-0.5 text-sm text-slate-500">
                                    {qrTarget.nama_meja} &middot; {qrTarget.kode_meja}
                                    {qrTarget.vanue ? ` · ${qrTarget.vanue.nama}` : ''}
                                </p>
                            </div>
                            <button
                                type="button"
                                onClick={() => setQrTarget(null)}
                                className="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-600"
                                aria-label="Tutup modal"
                            >
                                <CloseIcon />
                            </button>
                        </div>

                        <div className="px-6 py-6 text-center">
                            <div className="mx-auto inline-flex rounded-2xl border-2 border-emerald-600 bg-white p-4">
                                <img
                                    src={`/admin/mejas/${qrTarget.id}/qr-code/gambar`}
                                    alt={`QR Code ${qrTarget.kode_meja}`}
                                    className="h-48 w-48"
                                />
                            </div>
                            <p className="mt-4 inline-flex rounded-lg bg-emerald-50 px-3 py-1.5 text-sm font-semibold text-emerald-700 ring-1 ring-emerald-100">
                                {qrTarget.kode_meja}
                            </p>
                            <p className="mt-2 text-xs text-slate-500">
                                QR code diambil dari kode meja. Cetak dan tempelkan pada meja.
                            </p>
                        </div>

                        <div className="flex flex-col-reverse gap-3 border-t border-slate-100 px-6 py-4 sm:flex-row sm:justify-end">
                            <button
                                type="button"
                                onClick={() => setQrTarget(null)}
                                className="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-600 transition hover:bg-slate-50"
                            >
                                Tutup
                            </button>
                            <a
                                href={`/admin/mejas/${qrTarget.id}/qr-code`}
                                className="inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4" fill="none" viewBox="0 0 24 24" strokeWidth={1.9} stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                                </svg>
                                Download PDF
                            </a>
                        </div>
                    </div>
                </div>
            )}
        </>
    );
}

Index.layout = (page) => (
    <AdminLayout title="Data Meja" subtitle="Kelola data meja mahjong per venue">
        {page}
    </AdminLayout>
);
