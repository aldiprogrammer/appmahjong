import { Head, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import AdminLayout from '../../../Layouts/AdminLayout';
import {
    hapusPrinterTersimpan,
    kirimKePrinter,
    mintaPrinterBluetooth,
    printerBisaDipakai,
    bacaPrinterTersimpan,
    printerTersimpan,
    simpanPrinterTersimpan,
} from '../../../printer';

function PrinterIcon({ className = 'h-5 w-5' }) {
    return (
        <svg xmlns="http://www.w3.org/2000/svg" className={className} fill="none" viewBox="0 0 24 24" strokeWidth={1.8} stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" d="M6.75 7.5V4.5a1.5 1.5 0 0 1 1.5-1.5h7.5a1.5 1.5 0 0 1 1.5 1.5v3M6.75 16.5H4.5a1.5 1.5 0 0 1-1.5-1.5v-4.5a1.5 1.5 0 0 1 1.5-1.5h15a1.5 1.5 0 0 1 1.5 1.5V15a1.5 1.5 0 0 1-1.5 1.5h-2.25M6.75 13.5h10.5a.75.75 0 0 1 .75.75v4.5a.75.75 0 0 1-.75.75H6.75a.75.75 0 0 1-.75-.75v-4.5a.75.75 0 0 1 .75-.75Z" />
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

export default function Index({ printers = [], vanues = [] }) {
    const { flash } = usePage().props;
    const [printerUtama, setPrinterUtama] = useState(() => bacaPrinterTersimpan());
    const [mencari, setMencari] = useState(false);
    const [menguji, setMenguji] = useState(false);
    const [pesan, setPesan] = useState(flash?.success ?? null);
    const [jenisPesan, setJenisPesan] = useState('success');
    const [vanueId, setVanueId] = useState(String(vanues[0]?.id ?? ''));

    const tampilkanPesan = (teks, jenis = 'success') => {
        setPesan(teks);
        setJenisPesan(jenis);
    };

    const daftarkanPrinter = (printer) => {
        router.post('/admin/printers', {
            vanue_id: vanueId,
            nama: printer.name ?? '',
            device_id: printer.id ?? '',
        }, {
            preserveScroll: true,
        });
    };

    const vanueTerpilih = vanues.find((vanue) => String(vanue.id) === String(vanueId));

    const cariPrinter = async () => {
        if (mencari) {
            return;
        }

        if (!printerBisaDipakai()) {
            tampilkanPesan('Browser ini tidak mendukung Bluetooth. Gunakan Chrome atau Edge (Android/desktop).', 'error');

            return;
        }

        setMencari(true);
        setPesan(null);

        try {
            const printer = await mintaPrinterBluetooth();

            simpanPrinterTersimpan(printer);
            setPrinterUtama({ id: printer.id ?? null, name: printer.name ?? null });
            daftarkanPrinter(printer);
            tampilkanPesan(`Printer "${printer.name ?? 'tanpa nama'}" berhasil disimpan sebagai printer utama.`);
        } catch (error) {
            const pesanError = error?.name === 'NotFoundError'
                ? 'Tidak ada printer dipilih.'
                : error?.name === 'SecurityError'
                    ? 'Akses Bluetooth diblokir browser. Izinkan akses Bluetooth untuk situs ini.'
                    : error?.message || 'Gagal mencari printer. Coba lagi.';

            tampilkanPesan(pesanError, 'error');
        } finally {
            setMencari(false);
        }
    };

    const ujiCetak = async () => {
        if (menguji) {
            return;
        }

        if (!printerBisaDipakai()) {
            tampilkanPesan('Browser ini tidak mendukung Bluetooth. Gunakan Chrome atau Edge (Android/desktop).', 'error');

            return;
        }

        setMenguji(true);
        setPesan(null);

        try {
            const printer = (await printerTersimpan()) ?? (await mintaPrinterBluetooth());
            const encoder = new TextEncoder();

            await kirimKePrinter(
                printer,
                encoder.encode('\x1b@\x1ba\x01Uji koneksi berhasil\n\x1ba\x00\n\n\x1dV\x00'),
            );
            simpanPrinterTersimpan(printer);
            setPrinterUtama({ id: printer.id ?? null, name: printer.name ?? null });
            tampilkanPesan(`Koneksi ke "${printer.name ?? 'printer'}" berhasil.`);
        } catch (error) {
            tampilkanPesan(error?.message || 'Gagal terhubung ke printer. Pastikan printer menyala dan Bluetooth aktif.', 'error');
        } finally {
            setMenguji(false);
        }
    };

    const lepasPrinter = () => {
        hapusPrinterTersimpan();
        setPrinterUtama(null);
        tampilkanPesan('Printer utama dilepas. Kasir akan meminta pilihan printer saat cetak berikutnya.');
    };

    return (
        <>
            <Head title="Printer" />

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
                    <h1 className="text-xl font-bold text-slate-900">Printer Struk</h1>
                    <p className="mt-1 text-sm text-slate-500">
                        Cari printer thermal Bluetooth yang terhubung, simpan sebagai printer utama, dan halaman Kasir akan
                        langsung memakainya tanpa perlu memilih printer setiap kali mencetak struk.
                    </p>
                </div>

                <div className="rounded-2xl border border-emerald-100 bg-white p-5 shadow-sm">
                    <div className="flex items-start gap-4">
                        <span className="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-600 ring-1 ring-emerald-100">
                            <PrinterIcon className="h-6 w-6" />
                        </span>

                        <div className="min-w-0 flex-1">
                            <p className="text-xs font-semibold uppercase tracking-wide text-slate-400">Printer utama tersimpan</p>
                            {printerUtama ? (
                                <p className="mt-1 truncate text-base font-bold text-slate-900">
                                    {printerUtama.name ?? 'Printer tanpa nama'}
                                </p>
                            ) : (
                                <p className="mt-1 text-base font-semibold text-slate-400">Belum ada printer disimpan</p>
                            )}
                            <p className="mt-1 text-xs text-slate-500">
                                Tersimpan di perangkat kasir ini. Pastikan printer menyala dan Bluetooth aktif saat mencetak.
                            </p>
                        </div>
                    </div>

                    <div className="mt-5 flex flex-wrap gap-2">
                        <button
                            type="button"
                            onClick={cariPrinter}
                            disabled={mencari}
                            className="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700 disabled:opacity-60"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4" fill="none" viewBox="0 0 24 24" strokeWidth={1.8} stroke="currentColor">
                                <path strokeLinecap="round" strokeLinejoin="round" d="m21 21-4.35-4.35M17 11a6 6 0 1 1-12 0 6 6 0 0 1 12 0Z" />
                            </svg>
                            {mencari ? 'Mencari printer...' : 'Cari & Simpan Printer'}
                        </button>

                        <button
                            type="button"
                            onClick={ujiCetak}
                            disabled={menguji}
                            className="inline-flex items-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-sm font-semibold text-emerald-700 transition hover:bg-emerald-100 disabled:opacity-60"
                        >
                            {menguji ? 'Menguji koneksi...' : 'Uji Koneksi'}
                        </button>

                        {printerUtama && (
                            <button
                                type="button"
                                onClick={lepasPrinter}
                                className="inline-flex items-center gap-2 rounded-xl border border-rose-100 bg-white px-4 py-2.5 text-sm font-semibold text-rose-600 transition hover:bg-rose-50"
                            >
                                Lepas Printer
                            </button>
                        )}
                    </div>
                </div>

                <div className="rounded-2xl border border-emerald-100 bg-white p-5 shadow-sm">
                    <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p className="text-sm font-bold text-slate-800">Printer Terdaftar</p>
                            <p className="text-xs text-slate-500">
                                {vanueTerpilih?.nama
                                    ? `Daftar printer untuk venue ${vanueTerpilih.nama}.`
                                    : 'Daftar printer per venue.'}
                            </p>
                        </div>
                        {vanues.length > 1 && (
                            <select
                                value={vanueId}
                                onChange={(event) => setVanueId(event.target.value)}
                                className="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-800 shadow-sm transition focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 sm:w-56"
                                aria-label="Pilih venue printer"
                            >
                                {vanues.map((vanue) => (
                                    <option key={vanue.id} value={String(vanue.id)}>
                                        {vanue.nama}
                                    </option>
                                ))}
                            </select>
                        )}
                    </div>

                    <div className="mt-4 space-y-2">
                        {printers.length === 0 ? (
                            <p className="rounded-xl border border-dashed border-slate-200 bg-slate-50 px-4 py-6 text-center text-xs text-slate-500">
                                Belum ada printer yang didaftarkan untuk venue ini.
                            </p>
                        ) : (
                            printers.map((printer) => (
                                <div
                                    key={printer.id}
                                    className="flex items-center gap-3 rounded-xl border border-slate-100 bg-slate-50 px-4 py-3"
                                >
                                    <span className="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600">
                                        <PrinterIcon className="h-4 w-4" />
                                    </span>
                                    <div className="min-w-0 flex-1">
                                        <p className="truncate text-sm font-bold text-slate-800">
                                            {printer.nama || 'Printer tanpa nama'}
                                        </p>
                                        <p className="truncate text-[11px] text-slate-500">
                                            Venue: {printer.vanue?.nama ?? '-'}
                                        </p>
                                    </div>
                                    <button
                                        type="button"
                                        onClick={() => {
                                            router.delete(`/admin/printers/${printer.id}`, {
                                                preserveScroll: true,
                                                onSuccess: () => tampilkanPesan('Printer berhasil dihapus dari venue.'),
                                            });
                                        }}
                                        className="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg border border-rose-100 bg-white text-rose-600 transition hover:bg-rose-50"
                                        aria-label={`Hapus printer ${printer.nama ?? ''}`}
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
                        <li>Pencarian printer memakai Bluetooth browser (Web Bluetooth), hanya tersedia di Chrome/Edge Android &amp; desktop.</li>
                        <li>Saat pertama kali memilih, izinkan akses Bluetooth agar browser mengingat perangkat ini.</li>
                        <li>Setelah tersimpan, tombol Cetak Struk di halaman Kasir otomatis memakai printer ini.</li>
                        <li>Jika printer diganti, cukup cari &amp; simpan printer baru di halaman ini.</li>
                    </ul>
                </div>
            </div>
        </>
    );
}

Index.layout = (page) => (
    <AdminLayout title="Printer" subtitle="Pengaturan printer struk Bluetooth">
        {page}
    </AdminLayout>
);
