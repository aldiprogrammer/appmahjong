import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
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

function formatRupiah(value) {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(Number(value) || 0);
}

function CloseIcon() {
    return (
        <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5" fill="none" viewBox="0 0 24 24" strokeWidth={2} stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" d="M6 18 18 6M6 6l12 12" />
        </svg>
    );
}

function EmptyIcon() {
    return (
        <svg xmlns="http://www.w3.org/2000/svg" className="h-10 w-10" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" d="M4.5 4.5v6a2.25 2.25 0 0 0 2.25 2.25h.75v6.75M7.5 4.5v6m-3-6v6m6.75-6h3.75A2.25 2.25 0 0 1 17.25 6.75v0A2.25 2.25 0 0 1 15 9H11.25V4.5Zm0 4.5v10.5" />
        </svg>
    );
}

export default function Index({ menus, kategoriMenus, vanues = [], filters }) {
    const { flash, auth } = usePage().props;
    const currentAdmin = auth?.admin ?? null;
    const isVenueScoped = Boolean(currentAdmin?.vanue) && currentAdmin?.role !== 'super_admin';
    const [search, setSearch] = useState(filters?.search ?? '');
    const [kategoriFilter, setKategoriFilter] = useState(filters?.kategori ?? '');
    const [successMessage, setSuccessMessage] = useState(flash?.success ?? null);
    const [errorMessage, setErrorMessage] = useState(flash?.error ?? null);
    const [formOpen, setFormOpen] = useState(false);
    const [editingMenu, setEditingMenu] = useState(null);
    const [deleteTarget, setDeleteTarget] = useState(null);
    const [deleting, setDeleting] = useState(false);
    const [togglingId, setTogglingId] = useState(null);
    const [gambarPreview, setGambarPreview] = useState(null);
    const namaInputRef = useRef(null);
    const vanueTerkunci = isVenueScoped || Boolean(editingMenu);

    const { data, setData, post, processing, errors, reset, clearErrors, transform } = useForm({
        vanue_id: currentAdmin?.vanue?.id ? String(currentAdmin.vanue.id) : '',
        id_kategori: '',
        nama: '',
        gambar: null,
        hapus_gambar: false,
        dibuat_di: 'Dapur',
        harga: '',
        harga_dingin: '',
        harga_panas: '',
        status: 'aktif',
    });

    const kategoriTerpilih = kategoriMenus.find(
        (kategori) => String(kategori.id) === String(data.id_kategori),
    );
    const isMinuman = /minum/i.test(kategoriTerpilih?.kategori ?? '');

    // Kategori yang ditawarkan pada form mengikuti venue yang dipilih agar selalu senada.
    const kategoriTersedia = kategoriMenus.filter(
        (kategori) => String(kategori.vanue_id ?? '') === String(data.vanue_id ?? ''),
    );

    useEffect(() => {
        return () => {
            if (gambarPreview?.startsWith('blob:')) {
                URL.revokeObjectURL(gambarPreview);
            }
        };
    }, [gambarPreview]);

    useEffect(() => {
        setSuccessMessage(flash?.success ?? null);
        setErrorMessage(flash?.error ?? null);
    }, [flash?.success, flash?.error]);

    useEffect(() => {
        if (!formOpen) {
            return;
        }

        const timer = setTimeout(() => namaInputRef.current?.focus(), 80);

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
            setEditingMenu(null);
            setDeleteTarget(null);
        };

        window.addEventListener('keydown', handleEscape);

        return () => window.removeEventListener('keydown', handleEscape);
    }, [formOpen, deleteTarget, processing, deleting]);

    const submitFilter = (event) => {
        event.preventDefault();

        router.get(
            '/admin/menus',
            { search, kategori: kategoriFilter },
            { preserveState: true, replace: true },
        );
    };

    const resetFilter = () => {
        setSearch('');
        setKategoriFilter('');
        router.get('/admin/menus', {}, { preserveState: true, replace: true });
    };

    const openCreateModal = () => {
        reset();
        clearErrors();
        setEditingMenu(null);
        setGambarPreview(null);

        if (kategoriFilter) {
            const kategori = kategoriTersedia.find(
                (item) => String(item.id) === String(kategoriFilter),
            );

            setData((current) => ({
                ...current,
                id_kategori: kategori ? String(kategoriFilter) : '',
                dibuat_di: /minum/i.test(kategori?.kategori ?? '') ? 'Bar' : 'Dapur',
            }));
        }

        setFormOpen(true);
    };

    const openEditModal = (menu) => {
        clearErrors();
        setData({
            vanue_id: menu.vanue?.id ? String(menu.vanue.id) : '',
            id_kategori: String(menu.id_kategori),
            nama: menu.nama,
            gambar: null,
            hapus_gambar: false,
            dibuat_di: menu.dibuat_di ?? 'Dapur',
            harga: menu.harga,
            harga_dingin: menu.harga_dingin ?? '',
            harga_panas: menu.harga_panas ?? '',
            status: menu.status,
        });
        setGambarPreview(menu.gambar_url ?? null);
        setEditingMenu(menu);
        setFormOpen(true);
    };

    const ubahKategori = (value) => {
        const kategori = kategoriTersedia.find((item) => String(item.id) === String(value));
        const minuman = /minum/i.test(kategori?.kategori ?? '');

        setData((current) => ({
            ...current,
            id_kategori: value,
            dibuat_di: minuman ? 'Bar' : 'Dapur',
            harga: minuman ? '' : current.harga,
            harga_dingin: minuman ? current.harga_dingin : '',
            harga_panas: minuman ? current.harga_panas : '',
        }));
    };

    const ubahVanue = (value) => {
        setData((current) => {
            const kategoriVenue = kategoriMenus.filter(
                (kategori) => String(kategori.vanue_id ?? '') === String(value ?? ''),
            );
            const kategoriMasihAda = kategoriVenue.some(
                (kategori) => String(kategori.id) === String(current.id_kategori),
            );

            return {
                ...current,
                vanue_id: value,
                id_kategori: kategoriMasihAda ? current.id_kategori : '',
            };
        });
    };

    const closeFormModal = () => {
        if (processing) {
            return;
        }

        setFormOpen(false);
        setEditingMenu(null);
        setGambarPreview(null);
        reset();
        clearErrors();
    };

    const handleGambarChange = (event) => {
        const file = event.target.files?.[0] ?? null;

        setData((current) => ({
            ...current,
            gambar: file,
            hapus_gambar: false,
        }));

        setGambarPreview(file ? URL.createObjectURL(file) : (editingMenu?.gambar_url ?? null));
    };

    const handleHapusGambar = () => {
        setData((current) => ({
            ...current,
            gambar: null,
            hapus_gambar: true,
        }));
        setGambarPreview(null);
    };

    const submitForm = (event) => {
        event.preventDefault();

        const options = {
            preserveScroll: true,
            forceFormData: true,
            onSuccess: () => {
                setFormOpen(false);
                setEditingMenu(null);
                setGambarPreview(null);
                reset();
            },
        };

        if (editingMenu) {
            // PHP tidak mem-parse body multipart pada request PUT/PATCH, sehingga
            // gunakan POST dengan method spoofing agar update + upload gambar tetap terbaca.
            transform((values) => ({ ...values, _method: 'put' }));
            post(`/admin/menus/${editingMenu.id}`, options);

            return;
        }

        transform((values) => values);
        post('/admin/menus', options);
    };

    const toggleStatus = (menu) => {
        setTogglingId(menu.id);

        router.patch(`/admin/menus/${menu.id}/status`, {}, {
            preserveScroll: true,
            onFinish: () => setTogglingId(null),
        });
    };

    const confirmDelete = () => {
        if (!deleteTarget) {
            return;
        }

        setDeleting(true);

        router.delete(`/admin/menus/${deleteTarget.id}`, {
            preserveScroll: true,
            onSuccess: () => setDeleteTarget(null),
            onFinish: () => setDeleting(false),
        });
    };

    return (
        <>
            <Head title="Menu" />

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
                            <CloseIcon />
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
                            <CloseIcon />
                        </button>
                    </div>
                )}

                <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 className="text-xl font-bold text-slate-900">Menu</h1>
                        <p className="text-sm text-slate-500">Kelola daftar makanan dan minuman beserta harga dan statusnya.</p>
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
                    <button
                        type="button"
                        onClick={openCreateModal}
                        className="inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4" fill="none" viewBox="0 0 24 24" strokeWidth={2.2} stroke="currentColor">
                            <path strokeLinecap="round" strokeLinejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>
                        Tambah Menu
                    </button>
                </div>

                <div className="overflow-hidden rounded-2xl border border-emerald-100 bg-white shadow-sm">
                    <div className="flex flex-col gap-3 border-b border-emerald-100 px-4 py-4 sm:px-5">
                        <form onSubmit={submitFilter} className="flex flex-col gap-2 sm:flex-row sm:items-center">
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
                                    placeholder="Cari nama menu atau kategori..."
                                    className="w-full rounded-xl border border-slate-200 bg-white py-2.5 pl-10 pr-3 text-sm text-slate-800 shadow-sm transition placeholder:text-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20"
                                />
                            </div>

                            <select
                                value={kategoriFilter}
                                onChange={(event) => setKategoriFilter(event.target.value)}
                                className="rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-800 shadow-sm transition focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 sm:w-52"
                            >
                                <option value="">Semua kategori</option>
                                {kategoriMenus.map((kategori) => (
                                    <option key={kategori.id} value={kategori.id}>
                                        {kategori.kategori}
                                    </option>
                                ))}
                            </select>

                            <button
                                type="submit"
                                className="inline-flex items-center justify-center rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-sm font-semibold text-emerald-700 transition hover:bg-emerald-100"
                            >
                                Cari
                            </button>
                            {(filters?.search || filters?.kategori) && (
                                <button
                                    type="button"
                                    onClick={resetFilter}
                                    className="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm font-medium text-slate-500 transition hover:bg-slate-50"
                                >
                                    Reset
                                </button>
                            )}

                            <p className="text-xs text-slate-500 sm:ml-auto">
                                Total <span className="font-semibold text-slate-700">{menus.total}</span> menu
                            </p>
                        </form>
                    </div>

                    {menus.data.length === 0 ? (
                        <div className="flex flex-col items-center justify-center px-6 py-16 text-center">
                            <span className="flex h-16 w-16 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-500">
                                <EmptyIcon />
                            </span>
                            <h2 className="mt-4 text-sm font-semibold text-slate-800">
                                {filters?.search || filters?.kategori ? 'Menu tidak ditemukan' : 'Belum ada menu'}
                            </h2>
                            <p className="mt-1 max-w-sm text-sm text-slate-500">
                                {filters?.search || filters?.kategori
                                    ? 'Coba ubah kata kunci atau filter kategori untuk menemukan menu.'
                                    : 'Tambahkan menu pertama untuk mulai mengelola daftar makanan dan minuman.'}
                            </p>
                            {!filters?.search && !filters?.kategori && (
                                <button
                                    type="button"
                                    onClick={openCreateModal}
                                    className="mt-5 inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-700"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4" fill="none" viewBox="0 0 24 24" strokeWidth={2.2} stroke="currentColor">
                                        <path strokeLinecap="round" strokeLinejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                    </svg>
                                    Tambah Menu
                                </button>
                            )}
                        </div>
                    ) : (
                        <>
                            <div className="overflow-x-auto">
                                <table className="w-full min-w-[1080px] text-left text-sm">
                                    <thead className="bg-emerald-50/60 text-xs uppercase tracking-wide text-emerald-800">
                                        <tr>
                                            <th className="px-5 py-3 font-semibold">No</th>
                                            <th className="px-5 py-3 font-semibold">Gambar</th>
                                            <th className="px-5 py-3 font-semibold">Nama Menu</th>
                                            <th className="px-5 py-3 font-semibold">Kategori</th>
                                            <th className="px-5 py-3 font-semibold">Venue</th>
                                            <th className="px-5 py-3 font-semibold">Dibuat di</th>
                                            <th className="px-5 py-3 font-semibold">Harga</th>
                                            <th className="px-5 py-3 font-semibold">Status</th>
                                            <th className="px-5 py-3 font-semibold">Dibuat</th>
                                            <th className="px-5 py-3 text-right font-semibold">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-emerald-50">
                                        {menus.data.map((menu, index) => (
                                            <tr key={menu.id} className="transition hover:bg-emerald-50/40">
                                                <td className="px-5 py-3.5 text-slate-500">
                                                    {(menus.from ?? 0) + index}
                                                </td>
                                                <td className="px-5 py-3.5">
                                                    {menu.gambar_url ? (
                                                        <img
                                                            src={menu.gambar_url}
                                                            alt={menu.nama}
                                                            className="h-12 w-12 rounded-xl border border-emerald-100 object-cover"
                                                        />
                                                    ) : (
                                                        <span className="flex h-12 w-12 items-center justify-center rounded-xl border border-dashed border-slate-200 bg-slate-50 text-slate-300">
                                                            <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5" fill="none" viewBox="0 0 24 24" strokeWidth={1.6} stroke="currentColor">
                                                                <path strokeLinecap="round" strokeLinejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909M3.75 21h16.5A1.5 1.5 0 0 0 21.75 19.5V4.5A1.5 1.5 0 0 0 20.25 3H3.75A1.5 1.5 0 0 0 2.25 4.5v15A1.5 1.5 0 0 0 3.75 21Zm10.5-13.5h.008v.008h-.008V7.5Z" />
                                                            </svg>
                                                        </span>
                                                    )}
                                                </td>
                                                <td className="px-5 py-3.5">
                                                    <span className="font-semibold text-slate-800">{menu.nama}</span>
                                                </td>
                                                <td className="px-5 py-3.5">
                                                    <span className="inline-flex rounded-lg bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-100">
                                                        {menu.kategori?.kategori ?? '-'}
                                                    </span>
                                                </td>
                                                <td className="px-5 py-3.5">
                                                    {menu.vanue ? (
                                                        <span className="inline-flex items-center gap-1.5 rounded-full bg-sky-50 px-2.5 py-1 text-xs font-semibold text-sky-700 ring-1 ring-sky-100">
                                                            <svg xmlns="http://www.w3.org/2000/svg" className="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" strokeWidth={1.8} stroke="currentColor">
                                                                <path strokeLinecap="round" strokeLinejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                                                <path strokeLinecap="round" strokeLinejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
                                                            </svg>
                                                            {menu.vanue.nama}
                                                        </span>
                                                    ) : (
                                                        <span className="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-500 ring-1 ring-slate-200">
                                                            Tanpa venue
                                                        </span>
                                                    )}
                                                </td>
                                                <td className="px-5 py-3.5">
                                                    <span
                                                        className={`inline-flex rounded-lg px-2.5 py-1 text-xs font-semibold ring-1 ${
                                                            menu.dibuat_di === 'Bar'
                                                                ? 'bg-sky-50 text-sky-700 ring-sky-100'
                                                                : 'bg-amber-50 text-amber-700 ring-amber-100'
                                                        }`}
                                                    >
                                                        {menu.dibuat_di ?? 'Dapur'}
                                                    </span>
                                                </td>
                                                <td className="px-5 py-3.5 font-medium text-slate-800">
                                                    {menu.harga_dingin !== null && menu.harga_panas !== null ? (
                                                        <div className="space-y-0.5 text-xs">
                                                            <p>
                                                                <span className="text-slate-400">Dingin</span>{' '}
                                                                <span className="font-semibold">{formatRupiah(menu.harga_dingin)}</span>
                                                            </p>
                                                            <p>
                                                                <span className="text-slate-400">Panas</span>{' '}
                                                                <span className="font-semibold">{formatRupiah(menu.harga_panas)}</span>
                                                            </p>
                                                        </div>
                                                    ) : (
                                                        formatRupiah(menu.harga)
                                                    )}
                                                </td>
                                                <td className="px-5 py-3.5">
                                                    <span
                                                        className={`inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ${
                                                            menu.status === 'aktif'
                                                                ? 'bg-emerald-50 text-emerald-700 ring-emerald-100'
                                                                : 'bg-slate-100 text-slate-600 ring-slate-200'
                                                        }`}
                                                    >
                                                        <span
                                                            className={`h-1.5 w-1.5 rounded-full ${
                                                                menu.status === 'aktif' ? 'bg-emerald-500' : 'bg-slate-400'
                                                            }`}
                                                        />
                                                        {menu.status === 'aktif' ? 'Aktif' : 'Nonaktif'}
                                                    </span>
                                                </td>
                                                <td className="px-5 py-3.5 text-slate-500">{formatDate(menu.created_at)}</td>
                                                <td className="px-5 py-3.5">
                                                    <div className="flex items-center justify-end gap-2">
                                                        <button
                                                            type="button"
                                                            onClick={() => toggleStatus(menu)}
                                                            disabled={togglingId === menu.id}
                                                            className={`inline-flex items-center gap-1.5 rounded-lg border px-3 py-1.5 text-xs font-semibold transition disabled:cursor-not-allowed disabled:opacity-60 ${
                                                                menu.status === 'aktif'
                                                                    ? 'border-amber-100 bg-white text-amber-600 hover:bg-amber-50'
                                                                    : 'border-emerald-100 bg-white text-emerald-700 hover:bg-emerald-50'
                                                            }`}
                                                        >
                                                            {togglingId === menu.id ? (
                                                                <svg className="h-3.5 w-3.5 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                                    <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" />
                                                                    <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8V0C5.373 0 0 5.373 0 12h4Z" />
                                                                </svg>
                                                            ) : (
                                                                <svg xmlns="http://www.w3.org/2000/svg" className="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" strokeWidth={1.9} stroke="currentColor">
                                                                    <path strokeLinecap="round" strokeLinejoin="round" d="M5.636 5.636a9 9 0 1 0 12.728 0M12 3v9" />
                                                                </svg>
                                                            )}
                                                            {menu.status === 'aktif' ? 'Nonaktifkan' : 'Aktifkan'}
                                                        </button>
                                                        <button
                                                            type="button"
                                                            onClick={() => openEditModal(menu)}
                                                            className="inline-flex items-center gap-1.5 rounded-lg border border-emerald-100 bg-white px-3 py-1.5 text-xs font-semibold text-emerald-700 transition hover:bg-emerald-50"
                                                        >
                                                            <svg xmlns="http://www.w3.org/2000/svg" className="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" strokeWidth={1.9} stroke="currentColor">
                                                                <path strokeLinecap="round" strokeLinejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125" />
                                                            </svg>
                                                            Edit
                                                        </button>
                                                        <button
                                                            type="button"
                                                            onClick={() => setDeleteTarget(menu)}
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
                                    Menampilkan <span className="font-semibold text-slate-700">{menus.from ?? 0}</span>–
                                    <span className="font-semibold text-slate-700">{menus.to ?? 0}</span> dari{' '}
                                    <span className="font-semibold text-slate-700">{menus.total}</span> menu
                                </p>

                                {menus.last_page > 1 && (
                                    <div className="flex flex-wrap items-center gap-1.5">
                                        {menus.links.map((link, index) =>
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
                                    {editingMenu ? 'Edit Menu' : 'Tambah Menu'}
                                </h2>
                                <p className="mt-0.5 text-sm text-slate-500">
                                    {editingMenu
                                        ? 'Perbarui data menu, harga, dan statusnya.'
                                        : 'Lengkapi data menu baru di bawah ini.'}
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

                        <form onSubmit={submitForm} className="space-y-5 px-6 py-5">
                            <div>
                                <label htmlFor="vanue_id" className="mb-1.5 block text-sm font-medium text-slate-700">
                                    Venue
                                </label>
                                <select
                                    id="vanue_id"
                                    value={data.vanue_id ?? ''}
                                    onChange={(event) => ubahVanue(event.target.value)}
                                    disabled={vanueTerkunci}
                                    className={`w-full rounded-xl border px-3.5 py-2.5 text-sm shadow-sm transition focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 ${
                                        errors.vanue_id ? 'border-rose-300 bg-rose-50/40' : 'border-slate-200 bg-white'
                                    } ${vanueTerkunci ? 'cursor-not-allowed bg-slate-50 text-slate-600' : 'text-slate-800'}`}
                                >
                                    <option value="">Tanpa venue</option>
                                    {vanues.map((vanue) => (
                                        <option key={vanue.id} value={vanue.id}>
                                            {vanue.nama}
                                        </option>
                                    ))}
                                </select>
                                {errors.vanue_id && <p className="mt-1.5 text-xs font-medium text-rose-600">{errors.vanue_id}</p>}
                                <p className="mt-1.5 text-xs leading-relaxed text-slate-500">
                                    {editingMenu
                                        ? 'Venue menu tidak dapat diubah setelah dibuat.'
                                        : isVenueScoped
                                            ? `Menu ini otomatis menjadi milik venue ${currentAdmin?.vanue?.nama}.`
                                            : 'Pilihan kategori menyesuaikan venue yang dipilih.'}
                                </p>
                            </div>

                            <div>
                                <label htmlFor="id_kategori" className="mb-1.5 block text-sm font-medium text-slate-700">
                                    Kategori Menu <span className="text-rose-500">*</span>
                                </label>
                                <select
                                    id="id_kategori"
                                    value={data.id_kategori}
                                    onChange={(event) => ubahKategori(event.target.value)}
                                    required
                                    className={`w-full rounded-xl border px-3.5 py-2.5 text-sm text-slate-800 shadow-sm transition focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 ${
                                        errors.id_kategori ? 'border-rose-300 bg-rose-50/40' : 'border-slate-200 bg-white'
                                    }`}
                                >
                                    <option value="">Pilih kategori menu</option>
                                    {kategoriTersedia.map((kategori) => (
                                        <option key={kategori.id} value={kategori.id}>
                                            {kategori.kategori}
                                        </option>
                                    ))}
                                </select>
                                {errors.id_kategori && <p className="mt-1.5 text-xs font-medium text-rose-600">{errors.id_kategori}</p>}
                                {kategoriTersedia.length === 0 && (
                                    <p className="mt-1.5 text-xs text-amber-600">
                                        Belum ada kategori pada venue ini. Tambahkan kategori menu terlebih dahulu.
                                    </p>
                                )}
                            </div>

                            <div>
                                <label htmlFor="dibuat_di" className="mb-1.5 block text-sm font-medium text-slate-700">
                                    Dibuat di <span className="text-rose-500">*</span>
                                </label>
                                <select
                                    id="dibuat_di"
                                    value={data.dibuat_di}
                                    onChange={(event) => setData('dibuat_di', event.target.value)}
                                    required
                                    className={`w-full rounded-xl border px-3.5 py-2.5 text-sm text-slate-800 shadow-sm transition focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 ${
                                        errors.dibuat_di ? 'border-rose-300 bg-rose-50/40' : 'border-slate-200 bg-white'
                                    }`}
                                >
                                    <option value="Dapur">Dapur</option>
                                    <option value="Bar">Bar</option>
                                </select>
                                <p className="mt-1.5 text-xs text-slate-500">
                                    Pesanan akan diteruskan ke bagian ini.
                                </p>
                                {errors.dibuat_di && <p className="mt-1.5 text-xs font-medium text-rose-600">{errors.dibuat_di}</p>}
                            </div>

                            <div>
                                <label htmlFor="nama" className="mb-1.5 block text-sm font-medium text-slate-700">
                                    Nama Menu <span className="text-rose-500">*</span>
                                </label>
                                <input
                                    id="nama"
                                    ref={namaInputRef}
                                    type="text"
                                    value={data.nama}
                                    onChange={(event) => setData('nama', event.target.value)}
                                    placeholder="Contoh: Nasi Goreng Spesial"
                                    maxLength={50}
                                    autoComplete="off"
                                    required
                                    className={`w-full rounded-xl border px-3.5 py-2.5 text-sm text-slate-800 shadow-sm transition placeholder:text-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 ${
                                        errors.nama ? 'border-rose-300 bg-rose-50/40' : 'border-slate-200 bg-white'
                                    }`}
                                />
                                {errors.nama && <p className="mt-1.5 text-xs font-medium text-rose-600">{errors.nama}</p>}
                            </div>

                            <div>
                                <label htmlFor="gambar" className="mb-1.5 block text-sm font-medium text-slate-700">
                                    Gambar Menu
                                </label>
                                <div className="flex items-center gap-4">
                                    <span className="flex h-20 w-20 shrink-0 items-center justify-center overflow-hidden rounded-xl border border-dashed border-slate-200 bg-slate-50 text-slate-300">
                                        {gambarPreview ? (
                                            <img src={gambarPreview} alt="Pratinjau gambar menu" className="h-full w-full object-cover" />
                                        ) : (
                                            <svg xmlns="http://www.w3.org/2000/svg" className="h-7 w-7" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor">
                                                <path strokeLinecap="round" strokeLinejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909M3.75 21h16.5A1.5 1.5 0 0 0 21.75 19.5V4.5A1.5 1.5 0 0 0 20.25 3H3.75A1.5 1.5 0 0 0 2.25 4.5v15A1.5 1.5 0 0 0 3.75 21Zm10.5-13.5h.008v.008h-.008V7.5Z" />
                                            </svg>
                                        )}
                                    </span>
                                    <div className="min-w-0 flex-1">
                                        <input
                                            id="gambar"
                                            type="file"
                                            accept="image/jpeg,image/png,image/webp"
                                            onChange={handleGambarChange}
                                            className="block w-full cursor-pointer rounded-xl border border-slate-200 bg-white text-sm text-slate-500 shadow-sm file:mr-3 file:cursor-pointer file:rounded-l-xl file:border-0 file:bg-emerald-50 file:px-4 file:py-2.5 file:text-sm file:font-semibold file:text-emerald-700 hover:file:bg-emerald-100 focus:outline-none focus:ring-2 focus:ring-emerald-500/20"
                                        />
                                        <p className="mt-1.5 text-xs text-slate-500">JPG, PNG, atau WEBP. Maksimal 5 MB.</p>
                                        {gambarPreview && (
                                            <button
                                                type="button"
                                                onClick={handleHapusGambar}
                                                className="mt-1.5 inline-flex items-center gap-1 text-xs font-semibold text-rose-600 transition hover:text-rose-700"
                                            >
                                                Hapus gambar
                                            </button>
                                        )}
                                        {errors.gambar && <p className="mt-1.5 text-xs font-medium text-rose-600">{errors.gambar}</p>}
                                    </div>
                                </div>
                            </div>

                            <div className="grid gap-4 sm:grid-cols-2">
                                {isMinuman ? (
                                    <>
                                        <div>
                                            <label htmlFor="harga_dingin" className="mb-1.5 block text-sm font-medium text-slate-700">
                                                Harga Dingin <span className="text-rose-500">*</span>
                                            </label>
                                            <input
                                                id="harga_dingin"
                                                type="number"
                                                min="0"
                                                step="1"
                                                value={data.harga_dingin}
                                                onChange={(event) => setData('harga_dingin', event.target.value)}
                                                placeholder="Contoh: 8000"
                                                autoComplete="off"
                                                required
                                                className={`w-full rounded-xl border px-3.5 py-2.5 text-sm text-slate-800 shadow-sm transition placeholder:text-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 ${
                                                    errors.harga_dingin ? 'border-rose-300 bg-rose-50/40' : 'border-slate-200 bg-white'
                                                }`}
                                            />
                                            {errors.harga_dingin && <p className="mt-1.5 text-xs font-medium text-rose-600">{errors.harga_dingin}</p>}
                                        </div>

                                        <div>
                                            <label htmlFor="harga_panas" className="mb-1.5 block text-sm font-medium text-slate-700">
                                                Harga Panas <span className="text-rose-500">*</span>
                                            </label>
                                            <input
                                                id="harga_panas"
                                                type="number"
                                                min="0"
                                                step="1"
                                                value={data.harga_panas}
                                                onChange={(event) => setData('harga_panas', event.target.value)}
                                                placeholder="Contoh: 10000"
                                                autoComplete="off"
                                                required
                                                className={`w-full rounded-xl border px-3.5 py-2.5 text-sm text-slate-800 shadow-sm transition placeholder:text-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 ${
                                                    errors.harga_panas ? 'border-rose-300 bg-rose-50/40' : 'border-slate-200 bg-white'
                                                }`}
                                            />
                                            {errors.harga_panas && <p className="mt-1.5 text-xs font-medium text-rose-600">{errors.harga_panas}</p>}
                                        </div>
                                    </>
                                ) : (
                                    <div>
                                        <label htmlFor="harga" className="mb-1.5 block text-sm font-medium text-slate-700">
                                            Harga <span className="text-rose-500">*</span>
                                        </label>
                                        <input
                                            id="harga"
                                            type="number"
                                            min="0"
                                            step="1"
                                            value={data.harga}
                                            onChange={(event) => setData('harga', event.target.value)}
                                            placeholder="Contoh: 25000"
                                            autoComplete="off"
                                            required
                                            className={`w-full rounded-xl border px-3.5 py-2.5 text-sm text-slate-800 shadow-sm transition placeholder:text-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 ${
                                                errors.harga ? 'border-rose-300 bg-rose-50/40' : 'border-slate-200 bg-white'
                                            }`}
                                        />
                                        {errors.harga && <p className="mt-1.5 text-xs font-medium text-rose-600">{errors.harga}</p>}
                                    </div>
                                )}

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
                                        <option value="aktif">Aktif</option>
                                        <option value="nonaktif">Nonaktif</option>
                                    </select>
                                    {errors.status && <p className="mt-1.5 text-xs font-medium text-rose-600">{errors.status}</p>}
                                </div>
                            </div>

                            {isMinuman && (
                                <p className="rounded-xl bg-sky-50 px-3.5 py-2.5 text-xs text-sky-700">
                                    Kategori minuman otomatis memiliki pilihan <span className="font-semibold">Dingin</span> dan{' '}
                                    <span className="font-semibold">Panas</span> dengan harga masing-masing.
                                </p>
                            )}

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
                                    {processing && (
                                        <svg className="h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" />
                                            <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8V0C5.373 0 0 5.373 0 12h4Z" />
                                        </svg>
                                    )}
                                    {processing ? 'Menyimpan...' : editingMenu ? 'Simpan Perubahan' : 'Simpan Menu'}
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
                                <h2 className="text-base font-semibold text-slate-900">Hapus menu ini?</h2>
                                <p className="mt-1 text-sm leading-relaxed text-slate-500">
                                    Menu <span className="font-semibold text-slate-700">{deleteTarget.nama}</span> akan dihapus permanen.
                                    Tindakan ini tidak dapat dibatalkan.
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
    <AdminLayout title="Menu" subtitle="Kelola daftar makanan dan minuman">
        {page}
    </AdminLayout>
);

