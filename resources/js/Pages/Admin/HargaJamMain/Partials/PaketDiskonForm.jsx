const HARI = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];

function formatRupiah(value) {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(Number(value) || 0);
}

function CheckIcon({ className = 'h-4 w-4' }) {
    return (
        <svg xmlns="http://www.w3.org/2000/svg" className={className} fill="none" viewBox="0 0 24 24" strokeWidth={2.4} stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" d="M4.5 12.75l6 6 9-13.5" />
        </svg>
    );
}

export default function PaketDiskonForm({
    data,
    setData,
    errors,
    processing,
    onSubmit,
    onCancel,
    submitLabel = 'Simpan',
    multiple = false,
    vanues = [],
    isVenueScoped = false,
    vanueTerkunci = false,
}) {
    const hariList = (data.hari_list ?? []).map(String);
    const semuaHariDipilih = hariList.length === HARI.length;
    const hariError = errors.hari_list || errors['hari_list.0'];
    const minimalJamError = errors.minimal_jam;
    const maksimalJamError = errors.maksimal_jam;
    const diskonError = errors.diskon_persen;
    const hargaPaketError = errors.harga_paket;

    const toggleHari = (hari) => {
        setData(
            'hari_list',
            hariList.includes(hari) ? hariList.filter((item) => item !== hari) : [...hariList, hari],
        );
    };

    const toggleSemuaHari = () => {
        setData('hari_list', semuaHariDipilih ? [] : [...HARI]);
    };

    const minimalJam = Number(data.minimal_jam) || 0;
    const maksimalJam = Number(data.maksimal_jam) || 0;
    const diskonPersen = Number(data.diskon_persen) || 0;
    const hargaPaketDiisi = data.harga_paket !== '' && data.harga_paket !== null && data.harga_paket !== undefined;
    const hargaPaket = Number(data.harga_paket) || 0;

    return (
        <form onSubmit={onSubmit} className="space-y-5">
            <div>
                <label htmlFor="paket_vanue_id" className="mb-1.5 block text-sm font-medium text-slate-700">
                    Venue
                </label>
                <select
                    id="paket_vanue_id"
                    value={data.vanue_id ?? ''}
                    onChange={(event) => setData('vanue_id', event.target.value)}
                    disabled={vanueTerkunci}
                    className={`w-full rounded-xl border px-3.5 py-2.5 text-sm shadow-sm transition focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 ${
                        errors.vanue_id ? 'border-rose-300 bg-rose-50/40' : 'border-slate-200 bg-white'
                    } ${vanueTerkunci ? 'cursor-not-allowed bg-slate-50 text-slate-600' : 'text-slate-800'}`}
                >
                    <option value="">Semua venue</option>
                    {vanues.map((vanue) => (
                        <option key={vanue.id} value={vanue.id}>
                            {vanue.nama}
                        </option>
                    ))}
                </select>
                {errors.vanue_id && <p className="mt-1.5 text-xs font-medium text-rose-600">{errors.vanue_id}</p>}
                <p className="mt-1.5 text-xs leading-relaxed text-slate-500">
                    {vanueTerkunci
                        ? 'Venue paket tidak dapat diubah setelah dibuat.'
                        : isVenueScoped
                            ? 'Paket ini otomatis menjadi milik venue Anda.'
                            : 'Paket hanya berlaku untuk meja pada venue yang sama.'}
                </p>
            </div>

            <div>
                <div className="mb-1.5 flex items-center justify-between gap-2">
                    <label htmlFor={multiple ? undefined : 'paket_hari'} className="block text-sm font-medium text-slate-700">
                        Hari <span className="text-rose-500">*</span>
                    </label>
                    {multiple && (
                        <button
                            type="button"
                            onClick={toggleSemuaHari}
                            className="text-xs font-semibold text-emerald-700 transition hover:text-emerald-800"
                        >
                            {semuaHariDipilih ? 'Kosongkan' : 'Pilih semua'}
                        </button>
                    )}
                </div>

                {multiple ? (
                    <>
                        <div className="grid grid-cols-3 gap-2">
                            {HARI.map((hari) => {
                                const active = hariList.includes(hari);

                                return (
                                    <button
                                        key={hari}
                                        type="button"
                                        onClick={() => toggleHari(hari)}
                                        aria-pressed={active}
                                        className={`flex items-center justify-center gap-1.5 rounded-xl border px-2 py-2 text-xs font-semibold transition ${
                                            active
                                                ? 'border-emerald-600 bg-emerald-600 text-white shadow-sm'
                                                : 'border-slate-200 bg-white text-slate-700 hover:border-emerald-300'
                                        }`}
                                    >
                                        {active && <CheckIcon className="h-3 w-3" />}
                                        {hari}
                                    </button>
                                );
                            })}
                        </div>
                        {hariList.length > 0 && (
                            <p className="mt-2 text-[11px] text-emerald-600">
                                {hariList.length} hari dipilih. Paket yang sama dibuat untuk setiap hari tersebut.
                            </p>
                        )}
                    </>
                ) : (
                    <select
                        id="paket_hari"
                        value={hariList[0] ?? 'Senin'}
                        onChange={(event) => setData('hari_list', [event.target.value])}
                        required
                        className={`w-full rounded-xl border px-3.5 py-2.5 text-sm text-slate-800 shadow-sm transition focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 ${
                            hariError ? 'border-rose-300 bg-rose-50/40' : 'border-slate-200 bg-white'
                        }`}
                    >
                        {HARI.map((hari) => (
                            <option key={hari} value={hari}>
                                {hari}
                            </option>
                        ))}
                    </select>
                )}
                {hariError && <p className="mt-1.5 text-xs font-medium text-rose-600">{hariError}</p>}
            </div>

            <div className="grid gap-4 sm:grid-cols-2">
                <div>
                    <label htmlFor="paket_minimal_jam" className="mb-1.5 block text-sm font-medium text-slate-700">
                        Minimal Jam <span className="text-rose-500">*</span>
                    </label>
                    <input
                        id="paket_minimal_jam"
                        type="number"
                        min="1"
                        max="168"
                        step="1"
                        inputMode="numeric"
                        value={data.minimal_jam}
                        onChange={(event) => setData('minimal_jam', event.target.value)}
                        placeholder="3"
                        required
                        className={`w-full rounded-xl border px-3.5 py-2.5 text-sm text-slate-800 shadow-sm transition placeholder:text-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 ${
                            minimalJamError ? 'border-rose-300 bg-rose-50/40' : 'border-slate-200 bg-white'
                        }`}
                    />
                    {minimalJamError && <p className="mt-1.5 text-xs font-medium text-rose-600">{minimalJamError}</p>}
                </div>

                <div>
                    <label htmlFor="paket_maksimal_jam" className="mb-1.5 block text-sm font-medium text-slate-700">
                        Maksimal Jam
                    </label>
                    <input
                        id="paket_maksimal_jam"
                        type="number"
                        min={minimalJam || 1}
                        max="168"
                        step="1"
                        inputMode="numeric"
                        value={data.maksimal_jam ?? ''}
                        onChange={(event) => setData('maksimal_jam', event.target.value)}
                        placeholder="Tanpa batas"
                        className={`w-full rounded-xl border px-3.5 py-2.5 text-sm text-slate-800 shadow-sm transition placeholder:text-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 ${
                            maksimalJamError ? 'border-rose-300 bg-rose-50/40' : 'border-slate-200 bg-white'
                        }`}
                    />
                    {maksimalJamError && <p className="mt-1.5 text-xs font-medium text-rose-600">{maksimalJamError}</p>}
                </div>

                <div>
                    <label htmlFor="paket_diskon_persen" className="mb-1.5 block text-sm font-medium text-slate-700">
                        Diskon (%)
                    </label>
                    <input
                        id="paket_diskon_persen"
                        type="number"
                        min="0"
                        max="100"
                        step="0.5"
                        inputMode="decimal"
                        value={data.diskon_persen ?? ''}
                        onChange={(event) => setData('diskon_persen', event.target.value)}
                        placeholder="5"
                        className={`w-full rounded-xl border px-3.5 py-2.5 text-sm text-slate-800 shadow-sm transition placeholder:text-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 ${
                            diskonError ? 'border-rose-300 bg-rose-50/40' : 'border-slate-200 bg-white'
                        }`}
                    />
                    {diskonError && <p className="mt-1.5 text-xs font-medium text-rose-600">{diskonError}</p>}
                </div>

                <div>
                    <label htmlFor="paket_harga_paket" className="mb-1.5 block text-sm font-medium text-slate-700">
                        Harga Paket (Rp)
                    </label>
                    <input
                        id="paket_harga_paket"
                        type="number"
                        min="0"
                        step="0.01"
                        inputMode="decimal"
                        value={data.harga_paket ?? ''}
                        onChange={(event) => setData('harga_paket', event.target.value)}
                        placeholder="30000"
                        className={`w-full rounded-xl border px-3.5 py-2.5 text-sm text-slate-800 shadow-sm transition placeholder:text-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 ${
                            hargaPaketError ? 'border-rose-300 bg-rose-50/40' : 'border-slate-200 bg-white'
                        }`}
                    />
                    {hargaPaketError && <p className="mt-1.5 text-xs font-medium text-rose-600">{hargaPaketError}</p>}
                </div>
            </div>
            <p className="text-xs leading-relaxed text-slate-500">
                Isi Diskon (%) atau Harga Paket (minimal salah satu). Jika keduanya diisi, harga paket yang dipakai.
                Maksimal jam kosong berarti tanpa batas durasi.
            </p>

            <div>
                <label className="mb-1.5 block text-sm font-medium text-slate-700">
                    Rentang Jam <span className="text-rose-500">*</span>
                </label>
                <div className="grid grid-cols-2 gap-4">
                    <div>
                        <input
                            id="paket_jam_mulai"
                            type="time"
                            value={data.jam_mulai ?? ''}
                            onChange={(event) => setData('jam_mulai', event.target.value)}
                            required
                            aria-label="Jam mulai rentang paket"
                            className={`w-full rounded-xl border px-3.5 py-2.5 text-sm text-slate-800 shadow-sm transition focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 ${
                                errors.jam_mulai ? 'border-rose-300 bg-rose-50/40' : 'border-slate-200 bg-white'
                            }`}
                        />
                        {errors.jam_mulai && <p className="mt-1.5 text-xs font-medium text-rose-600">{errors.jam_mulai}</p>}
                    </div>
                    <div>
                        <input
                            id="paket_jam_selesai"
                            type="time"
                            value={data.jam_selesai ?? ''}
                            onChange={(event) => setData('jam_selesai', event.target.value)}
                            required
                            aria-label="Jam selesai rentang paket"
                            className={`w-full rounded-xl border px-3.5 py-2.5 text-sm text-slate-800 shadow-sm transition focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 ${
                                errors.jam_selesai ? 'border-rose-300 bg-rose-50/40' : 'border-slate-200 bg-white'
                            }`}
                        />
                        {errors.jam_selesai && <p className="mt-1.5 text-xs font-medium text-rose-600">{errors.jam_selesai}</p>}
                    </div>
                </div>
                <p className="mt-1.5 text-xs leading-relaxed text-slate-500">
                    Sesi yang terkena paket harus berada di dalam rentang ini. Bila booking lebih panjang dari
                    maksimal jam, sesi sejumlah maksimal jam tetap memakai paket dan sisanya memakai harga normal.
                    Isi <span className="font-semibold text-slate-600">00:00 - 00:00</span> untuk berlaku seharian penuh.
                </p>
            </div>

            <div className="rounded-xl border border-emerald-100 bg-emerald-50 px-4 py-3">
                <div className="flex items-center justify-between gap-3">
                    <div>
                        <p className="text-xs font-medium uppercase tracking-wide text-emerald-700">Syarat Paket</p>
                        <p className="text-[11px] text-emerald-600">
                            Booking {maksimalJam ? `${minimalJam || 0} - ${maksimalJam} jam` : `minimal ${minimalJam || 0} jam`},
                            sesi paket antara {data.jam_mulai || '-'} - {data.jam_selesai || '-'} pada hari terpilih.
                            {maksimalJam && ' Sesi lebihnya memakai harga normal.'}
                        </p>
                    </div>
                    <p className="text-lg font-bold text-emerald-800">
                        {hargaPaketDiisi ? formatRupiah(hargaPaket) : `${diskonPersen || 0}%`}
                    </p>
                </div>
            </div>

            <div className="flex flex-col-reverse gap-3 border-t border-slate-100 pt-5 sm:flex-row sm:justify-end">
                <button
                    type="button"
                    onClick={onCancel}
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
                    {processing ? 'Menyimpan...' : submitLabel}
                </button>
            </div>
        </form>
    );
}
