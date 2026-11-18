export default function MahjongFaanRuleForm({
    data,
    setData,
    errors,
    processing,
    onSubmit,
    onCancel,
    submitLabel = 'Simpan',
    aturanPoints = [],
    kodeInputRef,
}) {
    return (
        <form onSubmit={onSubmit} className="space-y-5">
            <div>
                <label htmlFor="id_aturanpoint" className="mb-1.5 block text-sm font-medium text-slate-700">
                    Aturan Point <span className="text-rose-500">*</span>
                </label>
                <select
                    id="id_aturanpoint"
                    value={data.id_aturanpoint}
                    onChange={(event) => setData('id_aturanpoint', event.target.value)}
                    required
                    className={`w-full rounded-xl border px-3.5 py-2.5 text-sm text-slate-800 shadow-sm transition focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 ${
                        errors.id_aturanpoint ? 'border-rose-300 bg-rose-50/40' : 'border-slate-200 bg-white'
                    }`}
                >
                    <option value="">Pilih aturan point</option>
                    {aturanPoints.map((aturanPoint) => (
                        <option key={aturanPoint.id} value={aturanPoint.id}>
                            {aturanPoint.nama_aturan}
                        </option>
                    ))}
                </select>
                {errors.id_aturanpoint && (
                    <p className="mt-1.5 text-xs font-medium text-rose-600">{errors.id_aturanpoint}</p>
                )}
            </div>

            <div>
                <label htmlFor="kode" className="mb-1.5 block text-sm font-medium text-slate-700">
                    Kode <span className="text-rose-500">*</span>
                </label>
                <input
                    id="kode"
                    type="text"
                    ref={kodeInputRef}
                    value={data.kode}
                    onChange={(event) => setData('kode', event.target.value)}
                    placeholder="Contoh: HK001"
                    autoComplete="off"
                    required
                    className={`w-full rounded-xl border px-3.5 py-2.5 text-sm text-slate-800 shadow-sm transition placeholder:text-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 ${
                        errors.kode ? 'border-rose-300 bg-rose-50/40' : 'border-slate-200 bg-white'
                    }`}
                />
                {errors.kode && <p className="mt-1.5 text-xs font-medium text-rose-600">{errors.kode}</p>}
            </div>

            <div>
                <label htmlFor="nama" className="mb-1.5 block text-sm font-medium text-slate-700">
                    Nama <span className="text-rose-500">*</span>
                </label>
                <input
                    id="nama"
                    type="text"
                    value={data.nama}
                    onChange={(event) => setData('nama', event.target.value)}
                    placeholder="Contoh: 清一色 (Full Flush)"
                    autoComplete="off"
                    required
                    className={`w-full rounded-xl border px-3.5 py-2.5 text-sm text-slate-800 shadow-sm transition placeholder:text-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 ${
                        errors.nama ? 'border-rose-300 bg-rose-50/40' : 'border-slate-200 bg-white'
                    }`}
                />
                {errors.nama && <p className="mt-1.5 text-xs font-medium text-rose-600">{errors.nama}</p>}
            </div>

            <div>
                <label htmlFor="faan_value" className="mb-1.5 block text-sm font-medium text-slate-700">
                    Nilai Faan <span className="text-rose-500">*</span>
                </label>
                <input
                    id="faan_value"
                    type="number"
                    min={0}
                    value={data.faan_value}
                    onChange={(event) => setData('faan_value', event.target.value)}
                    placeholder="Contoh: 10"
                    required
                    className={`w-full rounded-xl border px-3.5 py-2.5 text-sm text-slate-800 shadow-sm transition placeholder:text-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 ${
                        errors.faan_value ? 'border-rose-300 bg-rose-50/40' : 'border-slate-200 bg-white'
                    }`}
                />
                {errors.faan_value && <p className="mt-1.5 text-xs font-medium text-rose-600">{errors.faan_value}</p>}
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
