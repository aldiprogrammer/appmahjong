import { Head, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import AdminLayout from '../../../Layouts/AdminLayout';

function PembayaranIcon({ className = 'h-5 w-5' }) {
    return (
        <svg xmlns="http://www.w3.org/2000/svg" className={className} fill="none" viewBox="0 0 24 24" strokeWidth={1.8} stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" d="M2.25 6h15.5A.75.75 0 0 1 18 6.75v8.25a.75.75 0 0 1-.75.75H2.25a.75.75 0 0 1-.75-.75V6.75a.75.75 0 0 1 .75-.75zm15-2.25A2.25 2.25 0 0 0 13.5 3.75h-3A2.25 2.25 0 0 0 8.25 6v.008h.02A2.25 2.25 0 0 0 11.25 8.25h3a2.25 2.25 0 0 0 2.25-2.25v-8.5zM4.5 9a.75.75 0 0 1-.75.75H1.5a.75.75 0 0 1 0-1.5h2.25a.75.75 0 0 1 .75.75zm16.5 0a.75.75 0 0 0-1.5 0v8.5a2.25 2.25 0 0 0 2.25 2.25h3a2.25 2.25 0 0 0 2.25-2.25v-8.5a2.25 2.25 0 0 0-2.25-2.25z" />
        </svg>
    );
}

function TrashIcon({ className = 'h-4 w-4' }) {
    return (
        <svg xmlns="http://www.w3.org/2000/svg" className={className} fill="none" viewBox="0 0 24 24" strokeWidth={1.9} stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
        </svg>
    );
}

const metodePembayaranTerdaftar = [
    { nilai: 'transfer', label: 'Transfer', deskripsi: 'Nomor rekening bank, atas nama, dan nama pemilik rekening.' },
    { nilai: 'qris', label: 'QRIS', deskripsi: 'Nama pemilik QRIS dan gambar QRIS pembayaran yang diunggah admin.' },
];

export default function Index({ pembayarans = [], vanues = [] }) {
    const { flash } = usePage().props;
    const [pesan, setPesan] = useState(flash?.success ?? null);
    const [jenisPesan, setJenisPesan] = useState('success');
    const [metode, setMetode] = useState('transfer');
    const [idVanue, setIdVanue] = useState(String(vanues[0]?.id ?? ''));
    const [bank, setBank] = useState('');
    const [atasNama, setAtasNama] = useState('');
    const [norek, setNorek] = useState('');
    const [qrisImage, setQrisImage] = useState(null);
    const [previewQris, setPreviewQris] = useState('');

    const tampilkanPesan = (teks, jenis = 'success') => {
        setPesan(teks);
        setJenisPesan(jenis);
    };

    const aturMetode = (nilai) => {
        setMetode(nilai);

        if (nilai !== 'qris') {
            setQrisImage(null);
            setPreviewQris('');
        }
    };

    const pilihQrisImage = (event) => {
        const file = event.target.files?.[0] ?? null;

        setQrisImage(file);

        if (file) {
            const url = URL.createObjectURL(file);

            setPreviewQris(url);
        }
    };

    const hapusQrisPreview = () => {
        if (previewQris) {
            URL.revokeObjectURL(previewQris);
        }

        setQrisImage(null);
        setPreviewQris('');
    };

    const buatPembayaran = (event) => {
        event.preventDefault();

        const data = new FormData();
        data.append('id_venue', idVanue);
        data.append('metode_pembayaran', metode);
        data.append('atas_nama', atasNama);

        if (metode === 'transfer') {
            data.append('bank', bank);
            data.append('norek', norek);
        }

        if (metode === 'qris') {
            if (! qrisImage) {
                tampilkanPesan('Upload gambar QRIS untuk metode QRIS.', 'error');

                return;
            }

            data.append('qris_image', qrisImage);
        }

        router.post('/admin/pembayarans', data, {
            preserveScroll: true,
            onSuccess: () => {
                setMetode('transfer');
                setBank('');
                setAtasNama('');
                setNorek('');
                setQrisImage(null);
                setPreviewQris('');

                tampilkanPesan('Metode pembayaran berhasil ditambahkan.');
            },
        });
    };

    const hapusPembayaran = (pembayaran) => {
        if (! window.confirm(`Hapus metode pembayaran untuk venue ini?`)) {
            return;
        }

        router.delete(`/admin/pembayarans/${pembayaran.id}`, {
            preserveScroll: true,
            onSuccess: () => tampilkanPesan('Metode pembayaran berhasil dihapus.'),
        });
    };

    return (
        <>
            <Head title="Pembayaran" />

            <div className="mx-auto max-w-3xl space-y-6">
                {pesan && (
                    <div className={`flex items-center gap-3 rounded-xl border px-4 py-3 text-sm ${
                        jenisPesan === 'error'
                            ? 'border-rose-200 bg-rose-50 text-rose-800'
                            : 'border-emerald-200 bg-emerald-50 text-emerald-800'
                    }`}>
                        <p className="font-medium">{pesan}</p>
                        <button
                            type="button"
                            onClick={() => setPesan(null)}
                            className="ml-auto inline-flex h-7 w-7 items-center justify-center rounded-lg text-slate-500 transition hover:bg-black/5"
                            aria-label="Tutup notifikasi"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4" fill="none" viewBox="0 0 24 24" strokeWidth={2} stroke="currentColor">
                                <path strokeLinecap="round" strokeLinejoin="round" d="M6 18 18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                )}

                <div>
                    <h1 className="text-xl font-bold text-slate-900">Pembayaran</h1>
                    <p className="mt-1 text-sm text-slate-500">
                        Atur metode pembayaran untuk transaksi booking. Saat ini tersedia metode <span className="font-semibold text-slate-700">Transfer</span> dan{' '}
                        <span className="font-semibold text-slate-700">QRIS</span>; untuk metode QRIS, wajib mengisi atas nama dan mengunggah gambar QRIS-nya.
                    </p>
                </div>

                <div className="rounded-2xl border border-emerald-100 bg-white p-5 shadow-sm">
                    <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p className="text-sm font-bold text-slate-800">Metode Pembayaran</p>
                            <p className="text-xs text-slate-500">Daftar metode pembayaran per venue beserta data rekening atau gambar QRIS.</p>
                        </div>
                        {vanues.length > 1 && (
                            <select
                                value={idVanue}
                                onChange={(event) => setIdVanue(event.target.value)}
                                className="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-800 shadow-sm transition focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 sm:w-56"
                                aria-label="Pilih venue pembayaran"
                            >
                                {vanues.map((vanue) => (
                                    <option key={vanue.id} value={String(vanue.id)}>
                                        {vanue.nama}
                                    </option>
                                ))}
                            </select>
                        )}
                    </div>

                    <form onSubmit={buatPembayaran} className="mt-5 space-y-4">
                        <div className="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label htmlFor="pembayaran_venue" className="mb-1.5 block text-sm font-medium text-slate-700">
                                    Venue
                                </label>
                                <select
                                    id="pembayaran_venue"
                                    value={idVanue}
                                    onChange={(event) => setIdVanue(event.target.value)}
                                    className="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm font-semibold text-slate-800 shadow-sm transition focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20"
                                    required
                                >
                                    {vanues.map((vanue) => (
                                        <option key={vanue.id} value={String(vanue.id)}>
                                            {vanue.nama}
                                        </option>
                                    ))}
                                </select>
                            </div>
                            <div>
                                <label htmlFor="pembayaran_metode" className="mb-1.5 block text-sm font-medium text-slate-700">
                                    Metode Pembayaran
                                </label>
                                <select
                                    id="pembayaran_metode"
                                    value={metode}
                                    onChange={(event) => aturMetode(event.target.value)}
                                    className="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm font-semibold text-slate-800 shadow-sm transition focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20"
                                    required
                                >
                                    {metodePembayaranTerdaftar.map((item) => (
                                        <option key={item.nilai} value={item.nilai}>
                                            {item.label}
                                        </option>
                                    ))}
                                </select>
                            </div>
                        </div>

                        {metode === 'transfer' ? (
                            <div className="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <label htmlFor="pembayaran_bank" className="mb-1.5 block text-sm font-medium text-slate-700">
                                        Bank
                                    </label>
                                    <input
                                        id="pembayaran_bank"
                                        type="text"
                                        value={bank}
                                        onChange={(event) => setBank(event.target.value)}
                                        placeholder="BCA"
                                        className="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm font-semibold text-slate-800 shadow-sm transition focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20"
                                        required
                                    />
                                </div>
                                <div>
                                    <label htmlFor="pembayaran_atas_nama" className="mb-1.5 block text-sm font-medium text-slate-700">
                                        Atas Nama
                                    </label>
                                    <input
                                        id="pembayaran_atas_nama"
                                        type="text"
                                        value={atasNama}
                                        onChange={(event) => setAtasNama(event.target.value)}
                                        placeholder="Mahjong Medan"
                                        className="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm font-semibold text-slate-800 shadow-sm transition focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20"
                                        required
                                    />
                                </div>
                                <div>
                                    <label htmlFor="pembayaran_norek" className="mb-1.5 block text-sm font-medium text-slate-700">
                                        Nomor Rekening
                                    </label>
                                    <input
                                        id="pembayaran_norek"
                                        type="text"
                                        value={norek}
                                        onChange={(event) => setNorek(event.target.value)}
                                        placeholder="1234567890"
                                        className="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm font-semibold text-slate-800 shadow-sm transition focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 sm:col-span-2"
                                        required
                                    />
                                </div>
                            </div>
                        ) : (
                            <>
                                <div>
                                    <label htmlFor="pembayaran_atas_nama_qris" className="mb-1.5 block text-sm font-medium text-slate-700">
                                        Atas Nama <span className="text-rose-500">*</span>
                                    </label>
                                    <input
                                        id="pembayaran_atas_nama_qris"
                                        type="text"
                                        value={atasNama}
                                        onChange={(event) => setAtasNama(event.target.value)}
                                        placeholder="Nama pemilik QRIS / merchant"
                                        className="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm font-semibold text-slate-800 shadow-sm transition focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20"
                                        required
                                    />
                                    <p className="mt-1.5 text-xs text-slate-500">Nama ini akan ditampilkan ke pengguna di halaman pembayaran.</p>
                                </div>

                                <div>
                                    <label htmlFor="pembayaran_qris_image" className="mb-1.5 block text-sm font-medium text-slate-700">
                                        Gambar QRIS
                                    </label>
                                    {previewQris ? (
                                        <div className="overflow-hidden rounded-xl border border-emerald-100 bg-emerald-50 p-3">
                                            <img src={previewQris} alt="Preview QRIS" className="mx-auto h-36 w-36 rounded-lg object-contain" />
                                            <button
                                                type="button"
                                                onClick={hapusQrisPreview}
                                                className="mt-2 inline-flex items-center gap-1.5 rounded-lg border border-rose-100 bg-white px-2.5 py-1.5 text-xs font-semibold text-rose-600 transition hover:bg-rose-50"
                                            >
                                                Hapus gambar QRIS
                                            </button>
                                        </div>
                                    ) : (
                                        <input
                                            id="pembayaran_qris_image"
                                            type="file"
                                            accept="image/jpeg,image/png,image/webp"
                                            onChange={pilihQrisImage}
                                            className="block w-full cursor-pointer rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm font-semibold text-slate-500 shadow-sm transition focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20"
                                        />
                                    )}
                                    <p className="mt-1.5 text-xs text-slate-500">Upload gambar QRIS pembayaran. Wajib diisi untuk metode QRIS.</p>
                                </div>
                            </>
                        )}

                        <div className="flex justify-end">
                            <button
                                type="submit"
                                className="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-5 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700"
                                disabled={metode === 'qris' && ! qrisImage}
                            >
                                Simpan
                            </button>
                        </div>
                    </form>
                </div>

                <div className="rounded-2xl border border-emerald-100 bg-white p-5 shadow-sm">
                    <p className="text-sm font-bold text-slate-800">Daftar Metode Pembayaran</p>
                    <p className="mt-1 text-xs text-slate-500">Semua data metode pembayaran yang tersimpan di tabel pembayaran.</p>

                    <div className="mt-4 space-y-2">
                        {pembayarans.length === 0 ? (
                            <p className="rounded-xl border border-dashed border-slate-200 bg-slate-50 px-4 py-6 text-center text-xs text-slate-500">
                                Belum ada metode pembayaran yang didaftarkan.
                            </p>
                        ) : (
                            pembayarans.map((pembayaran) => (
                                <div key={pembayaran.id} className="flex items-center gap-3 rounded-xl border border-slate-100 bg-slate-50 px-4 py-3">
                                    <span className="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600">
                                        <PembayaranIcon className="h-4 w-4" />
                                    </span>
                                    <div className="min-w-0 flex-1">
                                        <p className="truncate text-sm font-bold text-slate-800">
                                            {pembayaran.metode_pembayaran === 'qris' ? 'QRIS' : 'Transfer'}
                                            <span className="ml-2 text-[10px] font-semibold uppercase tracking-wide text-emerald-700">
                                                {pembayaran.vanue?.nama ?? 'Venue'}
                                            </span>
                                        </p>
                                        <p className="truncate text-[11px] text-slate-500">
                                            {pembayaran.metode_pembayaran === 'qris'
                                                ? `Atas Nama: ${pembayaran.atas_nama ?? '-'}${pembayaran.qris_image ? ` · QRIS: ${pembayaran.qris_image}` : ' · QRIS belum diunggah'}`
                                                : `Bank: ${pembayaran.bank ?? '-'} · No. Rek: ${pembayaran.norek ?? '-'} · Atas Nama: ${pembayaran.atas_nama ?? '-'}`}
                                        </p>
                                    </div>
                                    <button
                                        type="button"
                                        onClick={() => hapusPembayaran(pembayaran)}
                                        className="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg border border-rose-100 bg-white text-rose-600 transition hover:bg-rose-50"
                                        aria-label={`Hapus metode pembayaran ${pembayaran.metode_pembayaran}`}
                                    >
                                        <TrashIcon />
                                    </button>
                                </div>
                            ))
                        )}
                    </div>
                </div>

                <div className="rounded-2xl border border-slate-100 bg-slate-50 p-5 text-xs leading-relaxed text-slate-500">
                    <p className="font-semibold text-slate-700">Catatan</p>
                    <ul className="mt-2 list-disc space-y-1 pl-4">
                        <li>Metode pembayaran yang tersedia: <span className="font-semibold">Transfer</span> dan <span className="font-semibold">QRIS</span>.</li>
                        <li>Untuk metode <span className="font-semibold">QRIS</span>, wajib mengisi atas nama pemilik QRIS dan mengunggah gambar QRIS sebelum menyimpan.</li>
                        <li>Metode <span className="font-semibold">Transfer</span> diisi dengan data bank, nama pemilik rekening, dan nomor rekening.</li>
                    </ul>
                </div>
            </div>
        </>
    );
}

Index.layout = (page) => (
    <AdminLayout title="Pembayaran" subtitle="Pengaturan metode pembayaran">
        {page}
    </AdminLayout>
);
