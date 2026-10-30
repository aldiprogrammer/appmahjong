const HARI = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];

function formatTime(value) {
    if (!value) {
        return '-';
    }

    return value.slice(0, 5);
}

function formatRupiah(value) {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(Number(value) || 0);
}

function ClockIcon({ className = 'h-4 w-4' }) {
    return (
        <svg xmlns="http://www.w3.org/2000/svg" className={className} fill="none" viewBox="0 0 24 24" strokeWidth={1.9} stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" d="M12 6v6l4 2m6-2a10 10 0 1 1-20 0 10 10 0 0 1 20 0Z" />
        </svg>
    );
}

function CheckIcon({ className = 'h-4 w-4' }) {
    return (
        <svg xmlns="http://www.w3.org/2000/svg" className={className} fill="none" viewBox="0 0 24 24" strokeWidth={2.4} stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" d="M4.5 12.75l6 6 9-13.5" />
        </svg>
    );
}

export default function HargaJamMainForm({
    data,
    setData,
    errors,
    processing,
    onSubmit,
    onCancel,
    submitLabel = 'Simpan',
    jamMains = [],
    jamMainSelectRef,
    multiple = false,
}) {
    const hargaAkhir = Math.max(0, (Number(data.harga) || 0) - (Number(data.diskon) || 0));
    const jamMainIds = (data.jam_main_ids ?? []).map(String);
    const semuaDipilih = jamMains.length > 0 && jamMainIds.length === jamMains.length;
    const jamMainError = errors.jam_main_ids || errors['jam_main_ids.0'];

    const toggleJamMain = (id) => {
        const value = String(id);

        setData(
            'jam_main_ids',
            jamMainIds.includes(value)
                ? jamMainIds.filter((item) => item !== value)
                : [...jamMainIds, value],
        );
    };

    const toggleSemua = () => {
        setData('jam_main_ids', semuaDipilih ? [] : jamMains.map((jamMain) => String(jamMain.id)));
    };

    const hariList = (data.hari_list ?? []).map(String);
    const semuaHariDipilih = hariList.length === HARI.length;
    const hariError = errors.hari_list || errors['hari_list.0'];

    const toggleHari = (hari) => {
        setData(
            'hari_list',
            hariList.includes(hari)
                ? hariList.filter((item) => item !== hari)
                : [...hariList, hari],
        );
    };

    const toggleSemuaHari = () => {
        setData('hari_list', semuaHariDipilih ? [] : [...HARI]);
    };

    return (
        <form onSubmit={onSubmit} className="space-y-5">
            <div>
                <div className="mb-1.5 flex items-center justify-between gap-2">
                    <label htmlFor={multiple ? undefined : 'jam_main_id'} className="block text-sm font-medium text-slate-700">
                        Jam Main <span className="text-rose-500">*</span>
                    </label>
                    {multiple && jamMains.length > 0 && (
                        <button
                            type="button"
                            onClick={toggleSemua}
                            className="text-xs font-semibold text-emerald-700 transition hover:text-emerald-800"
                        >
                            {semuaDipilih ? 'Kosongkan' : 'Pilih semua'}
                        </button>
                    )}
                </div>

                {multiple ? (
                    <>
                        <div className="grid grid-cols-2 gap-2">
                            {jamMains.map((jamMain) => {
                                const active = jamMainIds.includes(String(jamMain.id));

                                return (
                                    <button
                                        key={jamMain.id}
                                        type="button"
                                        onClick={() => toggleJamMain(jamMain.id)}
                                        aria-pressed={active}
                                        className={`flex items-center gap-2 rounded-xl border px-3 py-2.5 text-left transition ${
                                            active
                                                ? 'border-emerald-600 bg-emerald-600 text-white shadow-sm'
                                                : 'border-slate-200 bg-white text-slate-700 hover:border-emerald-300'
                                        }`}
                                    >
                                        <span
                                            className={`flex h-5 w-5 shrink-0 items-center justify-center rounded-full ${
                                                active ? 'bg-white/20 text-white' : 'bg-emerald-50 text-emerald-600'
                                            }`}
                                        >
                                            {active ? <CheckIcon className="h-3 w-3" /> : <ClockIcon className="h-3.5 w-3.5" />}
                                        </span>
                                        <span className="text-xs font-semibold">
                                            {formatTime(jamMain.jam_mulai)} - {formatTime(jamMain.jam_selesai)}
                                        </span>
                                    </button>
                                );
                            })}
                        </div>
                        {jamMainIds.length > 0 && (
                            <p className="mt-2 text-[11px] text-emerald-600">
                                {jamMainIds.length} jam dipilih. Harga yang sama akan disimpan untuk semua jam tersebut.
                            </p>
                        )}
                    </>
                ) : (
                    <select
                        id="jam_main_id"
                        ref={jamMainSelectRef}
                        value={jamMainIds[0] ?? ''}
                        onChange={(event) => setData('jam_main_ids', event.target.value ? [event.target.value] : [])}
                        required
                        className={`w-full rounded-xl border px-3.5 py-2.5 text-sm text-slate-800 shadow-sm transition focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 ${
                            jamMainError ? 'border-rose-300 bg-rose-50/40' : 'border-slate-200 bg-white'
                        }`}
                    >
                        <option value="">Pilih jam main</option>
                        {jamMains.map((jamMain) => (
                            <option key={jamMain.id} value={jamMain.id}>
                                {formatTime(jamMain.jam_mulai)} - {formatTime(jamMain.jam_selesai)}
                            </option>
                        ))}
                    </select>
                )}
                {jamMainError && <p className="mt-1.5 text-xs font-medium text-rose-600">{jamMainError}</p>}
            </div>

            <div>
                <div className="mb-1.5 flex items-center justify-between gap-2">
                    <label htmlFor={multiple ? undefined : 'hari'} className="block text-sm font-medium text-slate-700">
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
                                        className={`rounded-xl border px-2 py-2 text-xs font-semibold transition ${
                                            active
                                                ? 'border-emerald-600 bg-emerald-600 text-white shadow-sm'
                                                : 'border-slate-200 bg-white text-slate-700 hover:border-emerald-300'
                                        }`}
                                    >
                                        {hari}
                                    </button>
                                );
                            })}
                        </div>
                        {hariList.length > 0 && (
                            <p className="mt-2 text-[11px] text-emerald-600">
                                {hariList.length} hari dipilih. Harga yang sama akan disimpan untuk semua hari tersebut.
                            </p>
                        )}
                    </>
                ) : (
                    <select
                        id="hari"
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
                    <label htmlFor="harga" className="mb-1.5 block text-sm font-medium text-slate-700">
                        Harga (Rp) <span className="text-rose-500">*</span>
                    </label>
                    <input
                        id="harga"
                        type="number"
                        min="0"
                        step="1"
                        inputMode="numeric"
                        value={data.harga}
                        onChange={(event) => setData('harga', event.target.value)}
                        placeholder="50000"
                        required
                        className={`w-full rounded-xl border px-3.5 py-2.5 text-sm text-slate-800 shadow-sm transition placeholder:text-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 ${
                            errors.harga ? 'border-rose-300 bg-rose-50/40' : 'border-slate-200 bg-white'
                        }`}
                    />
                    {errors.harga && <p className="mt-1.5 text-xs font-medium text-rose-600">{errors.harga}</p>}
                </div>

                <div>
                    <label htmlFor="diskon" className="mb-1.5 block text-sm font-medium text-slate-700">
                        Diskon (Rp) <span className="text-rose-500">*</span>
                    </label>
                    <input
                        id="diskon"
                        type="number"
                        min="0"
                        step="1"
                        inputMode="numeric"
                        value={data.diskon}
                        onChange={(event) => setData('diskon', event.target.value)}
                        placeholder="0"
                        required
                        className={`w-full rounded-xl border px-3.5 py-2.5 text-sm text-slate-800 shadow-sm transition placeholder:text-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 ${
                            errors.diskon ? 'border-rose-300 bg-rose-50/40' : 'border-slate-200 bg-white'
                        }`}
                    />
                    {errors.diskon && <p className="mt-1.5 text-xs font-medium text-rose-600">{errors.diskon}</p>}
                </div>
            </div>

            <div className="rounded-xl border border-emerald-100 bg-emerald-50 px-4 py-3">
                <div className="flex items-center justify-between">
                    <div>
                        <p className="text-xs font-medium uppercase tracking-wide text-emerald-700">Harga Akhir</p>
                        <p className="text-[11px] text-emerald-600">Dihitung otomatis dari harga dikurangi diskon.</p>
                    </div>
                    <p className="text-lg font-bold text-emerald-800">{formatRupiah(hargaAkhir)}</p>
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
