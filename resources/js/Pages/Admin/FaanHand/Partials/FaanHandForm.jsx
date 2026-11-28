export default function FaanHandForm({
    data,
    setData,
    errors,
    processing,
    onSubmit,
    onCancel,
    submitLabel = 'Simpan',
    aturanMahjongs = [],
    aturanSelectRef,
    kategoriFaans = [],
}) {
    return (
        <form onSubmit={onSubmit} className="space-y-5">
            <div>
                <label htmlFor="id_aturanmahjong" className="mb-1.5 block text-sm font-medium text-slate-700">
                    Aturan Mahjong <span className="text-rose-500">*</span>
                </label>
                <select
                    id="id_aturanmahjong"
                    ref={aturanSelectRef}
                    value={data.id_aturanmahjong}
                    onChange={(event) => setData('id_aturanmahjong', event.target.value)}
                    required
                    className={`w-full rounded-xl border px-3.5 py-2.5 text-sm text-slate-800 shadow-sm transition focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 ${
                        errors.id_aturanmahjong ? 'border-rose-300 bg-rose-50/40' : 'border-slate-200 bg-white'
                    }`}
                >
                    <option value="">Pilih aturan mahjong</option>
                    {aturanMahjongs.map((aturan) => (
                        <option key={aturan.id} value={aturan.id}>
                            {aturan.kode} — {aturan.nama_aturan}
                        </option>
                    ))}
                </select>
                {aturanMahjongs.length === 0 && (
                    <p className="mt-1.5 text-xs text-amber-600">
                        Belum ada Aturan Mahjong. Tambahkan terlebih dahulu di menu Aturan Mahjong.
                    </p>
                )}
                {errors.id_aturanmahjong && (
                    <p className="mt-1.5 text-xs font-medium text-rose-600">{errors.id_aturanmahjong}</p>
                )}
            </div>

            <div>
                <label htmlFor="id_kategorifaan" className="mb-1.5 block text-sm font-medium text-slate-700">
                    Kategori Faan <span className="text-rose-500">*</span>
                </label>
                <select
                    id="id_kategorifaan"
                    value={data.id_kategorifaan}
                    onChange={(event) => setData('id_kategorifaan', event.target.value)}
                    required
                    className={`w-full rounded-xl border px-3.5 py-2.5 text-sm text-slate-800 shadow-sm transition focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 ${
                        errors.id_kategorifaan ? 'border-rose-300 bg-rose-50/40' : 'border-slate-200 bg-white'
                    }`}
                >
                    <option value="">Pilih kategori faan</option>
                    {kategoriFaans.map((kategori) => (
                        <option key={kategori.id} value={kategori.id}>
                            {kategori.nama}
                        </option>
                    ))}
                </select>
                {kategoriFaans.length === 0 && (
                    <p className="mt-1.5 text-xs text-amber-600">
                        Belum ada Kategori Faan. Tambahkan terlebih dahulu di menu Kategori Faan.
                    </p>
                )}
                {errors.id_kategorifaan && (
                    <p className="mt-1.5 text-xs font-medium text-rose-600">{errors.id_kategorifaan}</p>
                )}
            </div>

            <div className="grid gap-5 sm:grid-cols-2">
                <div>
                    <label htmlFor="nama_faan" className="mb-1.5 block text-sm font-medium text-slate-700">
                        Nama Faan <span className="text-rose-500">*</span>
                    </label>
                    <input
                        id="nama_faan"
                        type="text"
                        value={data.nama_faan}
                        onChange={(event) => setData('nama_faan', event.target.value)}
                        placeholder="Contoh: 清一色"
                        autoComplete="off"
                        required
                        className={`w-full rounded-xl border px-3.5 py-2.5 text-sm text-slate-800 shadow-sm transition placeholder:text-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 ${
                            errors.nama_faan ? 'border-rose-300 bg-rose-50/40' : 'border-slate-200 bg-white'
                        }`}
                    />
                    {errors.nama_faan && (
                        <p className="mt-1.5 text-xs font-medium text-rose-600">{errors.nama_faan}</p>
                    )}
                </div>

                <div>
                    <label htmlFor="inggris" className="mb-1.5 block text-sm font-medium text-slate-700">
                        Nama Inggris <span className="text-rose-500">*</span>
                    </label>
                    <input
                        id="inggris"
                        type="text"
                        value={data.inggris}
                        onChange={(event) => setData('inggris', event.target.value)}
                        placeholder="Contoh: Full Flush"
                        autoComplete="off"
                        required
                        className={`w-full rounded-xl border px-3.5 py-2.5 text-sm text-slate-800 shadow-sm transition placeholder:text-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 ${
                            errors.inggris ? 'border-rose-300 bg-rose-50/40' : 'border-slate-200 bg-white'
                        }`}
                    />
                    {errors.inggris && (
                        <p className="mt-1.5 text-xs font-medium text-rose-600">{errors.inggris}</p>
                    )}
                </div>

                <div>
                    <label htmlFor="faan_asli" className="mb-1.5 block text-sm font-medium text-slate-700">
                        Faan Asli <span className="text-rose-500">*</span>
                    </label>
                    <input
                        id="faan_asli"
                        type="number"
                        min={0}
                        value={data.faan_asli}
                        onChange={(event) => setData('faan_asli', event.target.value)}
                        placeholder="Contoh: 24"
                        required
                        className={`w-full rounded-xl border px-3.5 py-2.5 text-sm text-slate-800 shadow-sm transition placeholder:text-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 ${
                            errors.faan_asli ? 'border-rose-300 bg-rose-50/40' : 'border-slate-200 bg-white'
                        }`}
                    />
                    {errors.faan_asli && (
                        <p className="mt-1.5 text-xs font-medium text-rose-600">{errors.faan_asli}</p>
                    )}
                </div>

                <div>
                    <label htmlFor="faan" className="mb-1.5 block text-sm font-medium text-slate-700">
                        Faan <span className="text-rose-500">*</span>
                    </label>
                    <input
                        id="faan"
                        type="number"
                        min={0}
                        value={data.faan}
                        onChange={(event) => setData('faan', event.target.value)}
                        placeholder="Contoh: 8"
                        required
                        className={`w-full rounded-xl border px-3.5 py-2.5 text-sm text-slate-800 shadow-sm transition placeholder:text-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 ${
                            errors.faan ? 'border-rose-300 bg-rose-50/40' : 'border-slate-200 bg-white'
                        }`}
                    />
                    {errors.faan && (
                        <p className="mt-1.5 text-xs font-medium text-rose-600">{errors.faan}</p>
                    )}
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
