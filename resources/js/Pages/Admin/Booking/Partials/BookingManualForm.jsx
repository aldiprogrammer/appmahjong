import { useForm } from '@inertiajs/react';
import { useMemo } from 'react';

const HARI = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];

function formatRupiah(value) {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(Number(value) || 0);
}

function formatTime(value) {
    if (!value) {
        return '-';
    }

    return value.length > 5 ? value.slice(0, 5) : value;
}

// Menit dari jam "HH:MM"; nilai tidak valid dianggap 0.
function menitDariJam(jam) {
    const bagian = String(jam ?? '')
        .slice(0, 5)
        .split(':')
        .map((nilai) => Number.parseInt(nilai, 10) || 0);

    return (bagian[0] ?? 0) * 60 + (bagian[1] ?? 0);
}

// Durasi satu slot dalam menit, menangani slot melewati tengah malam.
function durasiSlotMenit(jam) {
    const mulai = menitDariJam(jam?.jam_mulai);
    let selesai = menitDariJam(jam?.jam_selesai);

    if (selesai <= mulai) {
        selesai += 24 * 60;
    }

    return selesai - mulai;
}

function tampilDurasi(menit) {
    const jam = menit / 60;

    return Number.isInteger(jam) ? String(jam) : jam.toFixed(1).replace('.', ',');
}

function jamSudahDibooking(jam, mejaTerpilih, tglMain, bookedSlots) {
    if (!mejaTerpilih || !tglMain) {
        return false;
    }

    const kunciMeja = String(mejaTerpilih.kode_meja || mejaTerpilih.id);

    return bookedSlots.some(
        (booking) =>
            String(booking.id_meja) === kunciMeja &&
            booking.tgl_main === tglMain &&
            formatTime(booking.jam_mulai) === formatTime(jam.jam_mulai) &&
            formatTime(booking.jam_selesai) === formatTime(jam.jam_selesai),
    );
}

export default function BookingManualForm({ mejas, jamMains, hargas, bookedSlots, onClose }) {
    const today = new Date().toISOString().slice(0, 10);

    const { data, setData, post, processing, errors } = useForm({
        tgl_main: today,
        meja_id: '',
        jam_main_ids: [],
        nama_pengguna: '',
        no_wa: '',
        sistem_pembayaran: 'tunai',
        diskon_tipe: 'persen',
        diskon_nilai: 0,
    });

    const mejaTerpilih = mejas.find((meja) => String(meja.id) === String(data.meja_id)) ?? null;
    const vanueTerpilih = mejaTerpilih?.vanue_id ?? null;
    const hariTerpilih = HARI[new Date(`${data.tgl_main}T00:00:00`).getDay()] ?? '';

    const jamTampil = useMemo(
        () =>
            jamMains.filter(
                (jam) => !jamSudahDibooking(jam, mejaTerpilih, data.tgl_main, bookedSlots),
            ),
        [jamMains, mejaTerpilih, data.tgl_main, bookedSlots],
    );

    const toggleJam = (jam) => {
        const id = String(jam.id);

        setData(
            'jam_main_ids',
            data.jam_main_ids.includes(id)
                ? data.jam_main_ids.filter((idTerpilih) => idTerpilih !== id)
                : [...data.jam_main_ids, id],
        );
    };

    const jamTerpilihObjek = jamMains.filter((jam) => data.jam_main_ids.includes(String(jam.id)));

    const rincian = jamTerpilihObjek.map((jam) => {
        const harga = hargas.find(
            (item) =>
                String(item.jam_main_id) === String(jam.id) &&
                item.hari === hariTerpilih &&
                String(item.vanue_id ?? '') === String(vanueTerpilih ?? ''),
        );

        return {
            jam,
            tersedia: Boolean(harga),
            hargaAsli: Number(harga?.harga) || 0,
            diskon: Number(harga?.diskon) || 0,
            total: Number(harga?.harga_akhir) || 0,
        };
    });

    const totalDurasiMenit = jamTerpilihObjek.reduce((jumlah, jam) => jumlah + durasiSlotMenit(jam), 0);

    const hargaAsli = rincian.reduce((jumlah, item) => jumlah + item.hargaAsli, 0);
    const diskonPerJam = rincian.reduce((jumlah, item) => jumlah + item.diskon, 0);
    const totalSebelumDiskon = rincian.reduce((jumlah, item) => jumlah + item.total, 0);

    const nilaiDiskonInput = Number(data.diskon_nilai) || 0;
    const diskonManual =
        data.diskon_tipe === 'persen'
            ? Math.round((totalSebelumDiskon * Math.min(nilaiDiskonInput, 100)) / 100)
            : Math.min(nilaiDiskonInput, totalSebelumDiskon);
    const totalDibayar = Math.max(0, totalSebelumDiskon - diskonManual);
    const hargaTidakTersedia = rincian.some((item) => !item.tersedia);

    const submit = (event) => {
        event.preventDefault();

        post('/admin/bookings', {
            preserveScroll: true,
            onSuccess: () => {
                setData('jam_main_ids', []);
                setData('no_wa', '');
                setData('diskon_nilai', 0);
                onClose();
            },
        });
    };

    const opsiPembayaran = [
        { value: 'tunai', label: 'Tunai', hint: 'Langsung lunas (bayar di tempat)' },
        { value: 'transfer', label: 'Transfer', hint: 'Menunggu unggah bukti & verifikasi' },
        { value: 'qris', label: 'QRIS', hint: 'Menunggu unggah bukti & verifikasi' },
    ];

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-slate-900/50 p-4 backdrop-blur-sm">
            <div className="my-8 w-full max-w-2xl rounded-2xl bg-white p-6 shadow-2xl">
                <div className="flex items-start justify-between gap-4">
                    <div>
                        <h2 className="text-base font-semibold text-slate-900">Tambah Booking Manual</h2>
                        <p className="mt-1 text-sm text-slate-500">
                            Harga otomatis dari Harga Jam Main, diskon diisi manual. Booking langsung disetujui.
                        </p>
                    </div>
                    <button
                        type="button"
                        onClick={onClose}
                        aria-label="Tutup form"
                        className="inline-flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-600"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5" fill="none" viewBox="0 0 24 24" strokeWidth={2} stroke="currentColor">
                            <path strokeLinecap="round" strokeLinejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form onSubmit={submit} className="mt-5 space-y-4">
                    <div className="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label htmlFor="manual_tgl_main" className="mb-1.5 block text-sm font-medium text-slate-700">
                                Tanggal Main <span className="text-rose-500">*</span>
                            </label>
                            <input
                                id="manual_tgl_main"
                                type="date"
                                min={today}
                                value={data.tgl_main}
                                onChange={(event) => {
                                    setData('tgl_main', event.target.value);
                                    setData('jam_main_ids', []);
                                }}
                                required
                                className={`w-full rounded-xl border px-3.5 py-2.5 text-sm text-slate-800 shadow-sm transition focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 ${
                                    errors.tgl_main ? 'border-rose-300 bg-rose-50/40' : 'border-slate-200 bg-white'
                                }`}
                            />
                            {errors.tgl_main && <p className="mt-1.5 text-xs font-medium text-rose-600">{errors.tgl_main}</p>}
                        </div>

                        <div>
                            <label htmlFor="manual_meja_id" className="mb-1.5 block text-sm font-medium text-slate-700">
                                Meja <span className="text-rose-500">*</span>
                            </label>
                            <select
                                id="manual_meja_id"
                                value={data.meja_id}
                                onChange={(event) => {
                                    setData('meja_id', event.target.value);
                                    setData('jam_main_ids', []);
                                }}
                                required
                                className={`w-full rounded-xl border px-3.5 py-2.5 text-sm text-slate-800 shadow-sm transition focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 ${
                                    errors.meja_id ? 'border-rose-300 bg-rose-50/40' : 'border-slate-200 bg-white'
                                }`}
                            >
                                <option value="">Pilih meja</option>
                                {mejas.map((meja) => (
                                    <option key={meja.id} value={meja.id}>
                                        {meja.nama_meja || meja.kode_meja}
                                        {meja.vanue ? ` — ${meja.vanue.nama}` : ''}
                                    </option>
                                ))}
                            </select>
                            {errors.meja_id && <p className="mt-1.5 text-xs font-medium text-rose-600">{errors.meja_id}</p>}
                        </div>
                    </div>

                    <div>
                        <label className="mb-1.5 block text-sm font-medium text-slate-700">
                            Jam Main <span className="text-rose-500">*</span>
                        </label>
                        {jamTampil.length === 0 ? (
                            <p className="rounded-xl border border-dashed border-slate-200 bg-slate-50 px-4 py-3 text-xs text-slate-500">
                                {mejaTerpilih
                                    ? 'Semua slot sudah terbooking pada tanggal ini.'
                                    : 'Pilih meja dan tanggal terlebih dahulu.'}
                            </p>
                        ) : (
                            <div className="flex flex-wrap gap-2">
                                {jamTampil.map((jam) => {
                                    const aktif = data.jam_main_ids.includes(String(jam.id));

                                    return (
                                        <button
                                            key={jam.id}
                                            type="button"
                                            onClick={() => toggleJam(jam)}
                                            aria-pressed={aktif}
                                            className={`rounded-xl border px-3 py-2 text-xs font-semibold shadow-sm transition ${
                                                aktif
                                                    ? 'border-emerald-600 bg-emerald-600 text-white'
                                                    : 'border-slate-200 bg-white text-slate-700 hover:border-emerald-300 hover:bg-emerald-50'
                                            }`}
                                        >
                                            {formatTime(jam.jam_mulai)} - {formatTime(jam.jam_selesai)}
                                        </button>
                                    );
                                })}
                            </div>
                        )}
                        {errors.jam_main_ids && (
                            <p className="mt-1.5 text-xs font-medium text-rose-600">{errors.jam_main_ids}</p>
                        )}
                    </div>

                    <div className="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label htmlFor="manual_nama_pengguna" className="mb-1.5 block text-sm font-medium text-slate-700">
                                Nama Pengguna <span className="text-rose-500">*</span>
                            </label>
                            <input
                                id="manual_nama_pengguna"
                                type="text"
                                value={data.nama_pengguna}
                                onChange={(event) => setData('nama_pengguna', event.target.value)}
                                placeholder="Contoh: Budi Santoso"
                                maxLength={100}
                                required
                                className={`w-full rounded-xl border px-3.5 py-2.5 text-sm text-slate-800 shadow-sm transition placeholder:text-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 ${
                                    errors.nama_pengguna ? 'border-rose-300 bg-rose-50/40' : 'border-slate-200 bg-white'
                                }`}
                            />
                            <p className="mt-1.5 text-[11px] leading-relaxed text-slate-500">
                                Nama tamu disimpan di booking saja, tanpa membuat akun pengguna.
                            </p>
                            {errors.nama_pengguna && (
                                <p className="mt-1.5 text-xs font-medium text-rose-600">{errors.nama_pengguna}</p>
                            )}
                        </div>

                        <div>
                            <label htmlFor="manual_no_wa" className="mb-1.5 block text-sm font-medium text-slate-700">
                                No. WhatsApp <span className="text-slate-400 font-normal">(opsional)</span>
                            </label>
                            <input
                                id="manual_no_wa"
                                type="text"
                                inputMode="tel"
                                value={data.no_wa}
                                onChange={(event) => setData('no_wa', event.target.value)}
                                placeholder="Contoh: 081234567890"
                                maxLength={20}
                                className={`w-full rounded-xl border px-3.5 py-2.5 text-sm text-slate-800 shadow-sm transition placeholder:text-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 ${
                                    errors.no_wa ? 'border-rose-300 bg-rose-50/40' : 'border-slate-200 bg-white'
                                }`}
                            />
                            {errors.no_wa && <p className="mt-1.5 text-xs font-medium text-rose-600">{errors.no_wa}</p>}
                        </div>
                    </div>

                    <div>
                        <span className="mb-1.5 block text-sm font-medium text-slate-700">
                            Sistem Pembayaran <span className="text-rose-500">*</span>
                        </span>
                        <div className="space-y-2">
                            {opsiPembayaran.map((opsi) => {
                                const aktif = data.sistem_pembayaran === opsi.value;

                                return (
                                    <label
                                        key={opsi.value}
                                        className={`flex cursor-pointer items-start gap-2.5 rounded-xl border px-3 py-2 transition ${
                                            aktif
                                                ? 'border-emerald-600 bg-emerald-50'
                                                : 'border-slate-200 bg-white hover:border-emerald-300'
                                        }`}
                                    >
                                        <input
                                            type="radio"
                                            name="sistem_pembayaran"
                                            value={opsi.value}
                                            checked={aktif}
                                            onChange={(event) => setData('sistem_pembayaran', event.target.value)}
                                            className="mt-0.5 h-4 w-4 shrink-0 cursor-pointer accent-emerald-600"
                                        />
                                        <span>
                                            <span className="block text-xs font-bold text-slate-800">{opsi.label}</span>
                                            <span className="block text-[10px] text-slate-500">{opsi.hint}</span>
                                        </span>
                                    </label>
                                );
                            })}
                        </div>
                        {errors.sistem_pembayaran && (
                            <p className="mt-1.5 text-xs font-medium text-rose-600">{errors.sistem_pembayaran}</p>
                        )}
                    </div>

                    <div className="rounded-xl border border-slate-200 bg-slate-50/60 p-4">
                        <span className="mb-1.5 block text-sm font-medium text-slate-700">Diskon</span>
                        <div className="grid gap-3 sm:grid-cols-[auto_1fr] sm:items-end">
                            <div className="flex gap-2">
                                {[
                                    { value: 'persen', label: 'Persen (%)' },
                                    { value: 'nominal', label: 'Nominal (Rp)' },
                                ].map((opsi) => {
                                    const aktif = data.diskon_tipe === opsi.value;

                                    return (
                                        <label
                                            key={opsi.value}
                                            className={`flex cursor-pointer items-center gap-2 rounded-xl border px-3 py-2.5 text-xs font-semibold transition ${
                                                aktif
                                                    ? 'border-emerald-600 bg-emerald-50 text-emerald-700'
                                                    : 'border-slate-200 bg-white text-slate-600 hover:border-emerald-300'
                                            }`}
                                        >
                                            <input
                                                type="radio"
                                                name="diskon_tipe"
                                                value={opsi.value}
                                                checked={aktif}
                                                onChange={(event) => setData('diskon_tipe', event.target.value)}
                                                className="h-4 w-4 cursor-pointer accent-emerald-600"
                                            />
                                            {opsi.label}
                                        </label>
                                    );
                                })}
                            </div>

                            <div>
                                <label htmlFor="manual_diskon_nilai" className="mb-1.5 block text-xs font-medium text-slate-600">
                                    {data.diskon_tipe === 'persen' ? 'Besaran diskon' : 'Nominal diskon'}
                                </label>
                                <input
                                    id="manual_diskon_nilai"
                                    type="number"
                                    inputMode="numeric"
                                    min={0}
                                    max={data.diskon_tipe === 'persen' ? 100 : undefined}
                                    step={1}
                                    value={data.diskon_nilai}
                                    onChange={(event) => setData('diskon_nilai', event.target.value)}
                                    placeholder={data.diskon_tipe === 'persen' ? 'Contoh: 10' : 'Contoh: 25000'}
                                    className={`w-full rounded-xl border px-3.5 py-2.5 text-sm text-slate-800 shadow-sm transition placeholder:text-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 ${
                                        errors.diskon_nilai ? 'border-rose-300 bg-rose-50/40' : 'border-slate-200 bg-white'
                                    }`}
                                />
                            </div>
                        </div>
                        {errors.diskon_tipe && (
                            <p className="mt-1.5 text-xs font-medium text-rose-600">{errors.diskon_tipe}</p>
                        )}
                        {errors.diskon_nilai && (
                            <p className="mt-1.5 text-xs font-medium text-rose-600">{errors.diskon_nilai}</p>
                        )}
                    </div>

                    <div className="rounded-xl border border-emerald-100 bg-emerald-50/60 p-4">
                        <div className="flex items-center justify-between text-xs text-slate-600">
                            <span>Harga ({data.jam_main_ids.length} sesi)</span>
                            <span className="font-semibold text-slate-800">{formatRupiah(hargaAsli)}</span>
                        </div>
                        {diskonPerJam > 0 && (
                            <div className="mt-1.5 flex items-center justify-between text-xs text-rose-600">
                                <span>Diskon per jam</span>
                                <span className="font-semibold">-{formatRupiah(diskonPerJam)}</span>
                            </div>
                        )}
                        {diskonManual > 0 && (
                            <div className="mt-1.5 flex items-center justify-between text-xs text-rose-600">
                                <span>
                                    Diskon manual (
                                    {data.diskon_tipe === 'persen' ? `${nilaiDiskonInput}%` : 'nominal'})
                                </span>
                                <span className="font-semibold">-{formatRupiah(diskonManual)}</span>
                            </div>
                        )}
                        <div className="mt-2 flex items-center justify-between border-t border-emerald-100 pt-2">
                            <span className="text-xs font-bold text-slate-800">Total bayar</span>
                            <span className="text-sm font-bold text-emerald-700">{formatRupiah(totalDibayar)}</span>
                        </div>
                        {data.jam_main_ids.length > 0 && totalDurasiMenit > 0 && (
                            <p className="mt-1.5 text-[11px] leading-relaxed text-slate-500">
                                Total {tampilDurasi(totalDurasiMenit)} jam. Total tidak mengikuti diskon paket.
                            </p>
                        )}
                        {hargaTidakTersedia && (
                            <p className="mt-2 rounded-lg bg-rose-50 px-3 py-2 text-[11px] font-medium text-rose-600">
                                Harga untuk hari, venue, dan jam yang dipilih belum tersedia.
                            </p>
                        )}
                    </div>

                    <div className="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                        <button
                            type="button"
                            onClick={onClose}
                            disabled={processing}
                            className="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-600 transition hover:bg-slate-50 disabled:opacity-60"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            disabled={processing || data.jam_main_ids.length === 0}
                            className="inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-60"
                        >
                            {processing ? 'Menyimpan...' : 'Simpan Booking'}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    );
}
