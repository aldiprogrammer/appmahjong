import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import AdminLayout from '../../../Layouts/AdminLayout';
import SuplayerFoodForm from './Partials/SuplayerFoodForm';

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
            <path strokeLinecap="round" strokeLinejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 1 1-3 0m3 0a1.5 1.5 0 1 0-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 1 0-3 0m-3.75 0H7.5m5.25-6h6.75m-6.75 0a1.5 1.5 0 1 1-3 0m3 0a1.5 1.5 0 1 0-3 0m-3.75 0h3.75" />
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

export default function Index({ suplayerFoods, vanues = [], filters }) {
    const { flash, auth } = usePage().props;
    const currentAdmin = auth?.admin ?? null;
    const isVenueScoped = Boolean(currentAdmin?.vanue) && currentAdmin?.role !== 'super_admin';
    const [search, setSearch] = useState(filters?.search ?? '');
    const [successMessage, setSuccessMessage] = useState(flash?.success ?? null);
    const [deleteTarget, setDeleteTarget] = useState(null);
    const [deleting, setDeleting] = useState(false);
    const [formOpen, setFormOpen] = useState(false);
    const [editingSuplayer, setEditingSuplayer] = useState(null);
    const kodeInputRef = useRef(null);
    const vanueTerkunci = isVenueScoped || Boolean(editingSuplayer);

    const { data, setData, post, put, processing, errors, reset, clearErrors } = useForm({
        vanue_id: currentAdmin?.vanue?.id ? String(currentAdmin.vanue.id) : '',
        kode_suplayer: '',
        nama_suplayer: '',
        alamat: '',
        nowa: '',
        email: '',
        password: '',
        tangggl_daftar: '',
    });

    useEffect(() => {
        setSuccessMessage(flash?.success ?? null);
    }, [flash?.success]);

    useEffect(() => {
        if (!formOpen) {
            return;
        }

        const timer = setTimeout(() => kodeInputRef.current?.focus(), 80);

        return () => clearTimeout(timer);
    }, [formOpen]);

    useEffect(() => {
        if (!formOpen && !deleteTarget) {
            return;
        }

        const handleEscape = (event) => {
            if (event.key !== 'Escape' || processing || deleting) {
                return;
            }

            setFormOpen(false);
            setEditingSuplayer(null);
            setDeleteTarget(null);
        };

        window.addEventListener('keydown', handleEscape);

        return () => window.removeEventListener('keydown', handleEscape);
    }, [formOpen, deleteTarget, processing, deleting]);

    const submitSearch = (event) => {
        event.preventDefault();

        router.get('/admin/suplayer-foods', { search }, { preserveState: true, replace: true });
    };

    const resetSearch = () => {
        setSearch('');
        router.get('/admin/suplayer-foods', {}, { preserveState: true, replace: true });
    };

    const openCreateModal = () => {
        reset();
        clearErrors();
        setEditingSuplayer(null);
        setFormOpen(true);

        fetch('/admin/suplayer-foods/kode-baru')
            .then((response) => response.json())
            .then((hasil) => setData('kode_suplayer', hasil.kode_suplayer ?? ''))
            .catch(() => setData('kode_suplayer', ''));
    };

    const openEditModal = (suplayer) => {
        clearErrors();
        setData({
            vanue_id: suplayer.vanue?.id ? String(suplayer.vanue.id) : '',
            kode_suplayer: suplayer.kode_suplayer,
            nama_suplayer: suplayer.nama_suplayer,
            alamat: suplayer.alamat,
            nowa: suplayer.nowa,
            email: suplayer.email,
            password: suplayer.password,
            tangggl_daftar: suplayer.tangggl_daftar ?? '',
        });
        setEditingSuplayer(suplayer);
        setFormOpen(true);
    };

    const closeFormModal = () => {
        if (processing) {
            return;
        }

        setFormOpen(false);
        setEditingSuplayer(null);
        reset();
        clearErrors();
    };

    const submitForm = (event) => {
        event.preventDefault();

        const options = {
            preserveScroll: true,
            onSuccess: () => {
                setFormOpen(false);
                setEditingSuplayer(null);
                reset();
            },
        };

        if (editingSuplayer) {
            put(`/admin/suplayer-foods/${editingSuplayer.id}`, options);

            return;
        }

        post('/admin/suplayer-foods', options);
    };

    const confirmDelete = () => {
        if (!deleteTarget) {
            return;
        }

        setDeleting(true);

        router.delete(`/admin/suplayer-foods/${deleteTarget.id}`, {
            preserveScroll: true,
            onSuccess: () => setDeleteTarget(null),
            onFinish: () => setDeleting(false),
        });
    };

    return (
        <>
            <Head title="Suplayer Food" />

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
                            <svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4" strokeWidth={2} stroke="currentColor" fill="none" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" d="M6 18 18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                )}

                <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 className="text-xl font-bold text-slate-900">Suplayer Food</h1>
                        <p className="text-sm text-slate-500">Kelola data suplayer pemasok makanan dan minuman.</p>
                    </div>
                    <button
                        type="button"
                        onClick={openCreateModal}
                        className="inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4" fill="none" viewBox="0 0 24 24" strokeWidth={2.2} stroke="currentColor">
                            <path strokeLinecap="round" strokeLinejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>
                        Tambah Suplayer
                    </button>
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
                                    placeholder="Cari suplayer food..."
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
                            Total <span className="font-semibold text-slate-700">{suplayerFoods.total}</span> suplayer
                        </p>
                    </div>

                    {suplayerFoods.data.length === 0 ? (
                        <div className="flex flex-col items-center justify-center px-6 py-16 text-center">
                            <span className="flex h-16 w-16 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-500">
                                <EmptyIcon />
                            </span>
                            <h2 className="mt-4 text-sm font-semibold text-slate-800">
                                {filters?.search ? 'Suplayer food tidak ditemukan' : 'Belum ada data suplayer food'}
                            </h2>
                            <p className="mt-1 max-w-sm text-sm text-slate-500">
                                {filters?.search
                                    ? 'Coba gunakan kata kunci lain untuk menemukan suplayer food yang dicari.'
                                    : 'Tambahkan suplayer food pertama untuk mulai mengelola data pemasok.'}
                            </p>
                            {!filters?.search && (
                                <button
                                    type="button"
                                    onClick={openCreateModal}
                                    className="mt-5 inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-700"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4" fill="none" viewBox="0 0 24 24" strokeWidth={2.2} stroke="currentColor">
                                        <path strokeLinecap="round" strokeLinejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                    </svg>
                                    Tambah Suplayer
                                </button>
                            )}
                        </div>
                    ) : (
                        <>
                            <div className="overflow-x-auto">
                                <table className="w-full min-w-[980px] text-left text-sm">
                                    <thead className="bg-emerald-50/60 text-xs uppercase tracking-wide text-emerald-800">
                                        <tr>
                                            <th className="px-5 py-3 font-semibold">No</th>
                                            <th className="px-5 py-3 font-semibold">Kode</th>
                                            <th className="px-5 py-3 font-semibold">Nama Suplayer</th>
                                            <th className="px-5 py-3 font-semibold">Venue</th>
                                            <th className="px-5 py-3 font-semibold">Alamat</th>
                                            <th className="px-5 py-3 font-semibold">No. WhatsApp</th>
                                            <th className="px-5 py-3 font-semibold">Email</th>
                                            <th className="px-5 py-3 font-semibold">Tgl Daftar</th>
                                            <th className="px-5 py-3 text-right font-semibold">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-emerald-50">
                                        {suplayerFoods.data.map((suplayer, index) => (
                                            <tr key={suplayer.id} className="transition hover:bg-emerald-50/40">
                                                <td className="px-5 py-3.5 text-slate-500">
                                                    {(suplayerFoods.from ?? 0) + index}
                                                </td>
                                                <td className="px-5 py-3.5">
                                                    <span className="inline-flex rounded-lg bg-emerald-50 px-2.5 py-1 text-xs font-semibold uppercase text-emerald-700 ring-1 ring-emerald-100">
                                                        {suplayer.kode_suplayer}
                                                    </span>
                                                </td>
                                                <td className="px-5 py-3.5 font-medium text-slate-800">{suplayer.nama_suplayer}</td>
                                                <td className="px-5 py-3.5">
                                                    {suplayer.vanue ? (
                                                        <span className="inline-flex items-center rounded-full bg-sky-50 px-2.5 py-1 text-xs font-semibold text-sky-700 ring-1 ring-sky-100">
                                                            {suplayer.vanue.nama}
                                                        </span>
                                                    ) : (
                                                        <span className="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-500 ring-1 ring-slate-200">
                                                            Tanpa venue
                                                        </span>
                                                    )}
                                                </td>
                                                <td className="max-w-[220px] truncate px-5 py-3.5 text-slate-500">{suplayer.alamat}</td>
                                                <td className="px-5 py-3.5 text-slate-500">{suplayer.nowa}</td>
                                                <td className="px-5 py-3.5 text-slate-500">{suplayer.email}</td>
                                                <td className="px-5 py-3.5 text-slate-500">{formatDate(suplayer.tangggl_daftar)}</td>
                                                <td className="px-5 py-3.5">
                                                    <div className="flex items-center justify-end gap-2">
                                                        <button
                                                            type="button"
                                                            onClick={() => openEditModal(suplayer)}
                                                            className="inline-flex items-center gap-1.5 rounded-lg border border-emerald-100 bg-white px-3 py-1.5 text-xs font-semibold text-emerald-700 transition hover:bg-emerald-50"
                                                        >
                                                            <svg xmlns="http://www.w3.org/2000/svg" className="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" strokeWidth={1.9} stroke="currentColor">
                                                                <path strokeLinecap="round" strokeLinejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125" />
                                                            </svg>
                                                            Edit
                                                        </button>
                                                        <button
                                                            type="button"
                                                            onClick={() => setDeleteTarget(suplayer)}
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
                                    Menampilkan <span className="font-semibold text-slate-700">{suplayerFoods.from ?? 0}</span>–
                                    <span className="font-semibold text-slate-700">{suplayerFoods.to ?? 0}</span> dari{' '}
                                    <span className="font-semibold text-slate-700">{suplayerFoods.total}</span> suplayer
                                </p>

                                {suplayerFoods.last_page > 1 && (
                                    <div className="flex flex-wrap items-center gap-1.5">
                                        {suplayerFoods.links.map((link, index) =>
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
                                    {editingSuplayer ? 'Edit Suplayer Food' : 'Tambah Suplayer Food'}
                                </h2>
                                <p className="mt-0.5 text-sm text-slate-500">
                                    {editingSuplayer
                                        ? 'Perbarui data suplayer food.'
                                        : 'Lengkapi data suplayer food baru di bawah ini.'}
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
                            <SuplayerFoodForm
                                data={data}
                                setData={setData}
                                errors={errors}
                                processing={processing}
                                onSubmit={submitForm}
                                onCancel={closeFormModal}
                                submitLabel={editingSuplayer ? 'Simpan Perubahan' : 'Simpan Suplayer'}
                                kodeInputRef={kodeInputRef}
                                editing={Boolean(editingSuplayer)}
                                vanues={vanues}
                                vanueTerkunci={vanueTerkunci}
                                isVenueScoped={isVenueScoped}
                                namaVanueAktif={currentAdmin?.vanue?.nama}
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
                                <h2 className="text-base font-semibold text-slate-900">Hapus suplayer food ini?</h2>
                                <p className="mt-1 text-sm leading-relaxed text-slate-500">
                                    Suplayer <span className="font-semibold text-slate-700">{deleteTarget.nama_suplayer}</span>{' '}
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
        </>
    );
}

Index.layout = (page) => (
    <AdminLayout title="Suplayer Food" subtitle="Kelola data suplayer pemasok makanan dan minuman">
        {page}
    </AdminLayout>
);
