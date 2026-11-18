export default function AturanPointForm({
    data,
    setData,
    errors,
    processing,
    onSubmit,
    onCancel,
    submitLabel = 'Simpan',
    namaAturanInputRef,
}) {
    return (
        <form onSubmit={onSubmit} className="space-y-5">
            <div>
                <label htmlFor="nama_aturan" className="mb-1.5 block text-sm font-medium text-slate-700">
                    Nama Aturan <span className="text-rose-500">*</span>
                </label>
                <input
                    id="nama_aturan"
                    type="text"
                    ref={namaAturanInputRef}
                    value={data.nama_aturan}
                    onChange={(event) => setData('nama_aturan', event.target.value)}
                    placeholder="Contoh: Menang Full Flush"
                    autoComplete="off"
                    required
                    className={`w-full rounded-xl border px-3.5 py-2.5 text-sm text-slate-800 shadow-sm transition placeholder:text-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 ${
                        errors.nama_aturan ? 'border-rose-300 bg-rose-50/40' : 'border-slate-200 bg-white'
                    }`}
                />
                {errors.nama_aturan && <p className="mt-1.5 text-xs font-medium text-rose-600">{errors.nama_aturan}</p>}
            </div>

            <div>
                <label htmlFor="deskripsi" className="mb-1.5 block text-sm font-medium text-slate-700">
                    Deskripsi <span className="text-rose-500">*</span>
                </label>
                <input
                    id="deskripsi"
                    type="text"
                    value={data.deskripsi}
                    onChange={(event) => setData('deskripsi', event.target.value)}
                    placeholder="Contoh: Point dihitung 2x untuk full flush"
                    autoComplete="off"
                    required
                    className={`w-full rounded-xl border px-3.5 py-2.5 text-sm text-slate-800 shadow-sm transition placeholder:text-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 ${
                        errors.deskripsi ? 'border-rose-300 bg-rose-50/40' : 'border-slate-200 bg-white'
                    }`}
                />
                {errors.deskripsi && <p className="mt-1.5 text-xs font-medium text-rose-600">{errors.deskripsi}</p>}
            </div>

            <div className="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <div>
                    <label htmlFor="min_faan" className="mb-1.5 block text-sm font-medium text-slate-700">
                        Minimal Faan <span className="text-rose-500">*</span>
                    </label>
                    <input
                        id="min_faan"
                        type="number"
                        min="0"
                        value={data.min_faan}
                        onChange={(event) => setData('min_faan', event.target.value)}
                        placeholder="Contoh: 1"
                        autoComplete="off"
                        required
                        className={`w-full rounded-xl border px-3.5 py-2.5 text-sm text-slate-800 shadow-sm transition placeholder:text-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 ${
                            errors.min_faan ? 'border-rose-300 bg-rose-50/40' : 'border-slate-200 bg-white'
                        }`}
                    />
                    {errors.min_faan && <p className="mt-1.5 text-xs font-medium text-rose-600">{errors.min_faan}</p>}
                </div>

                <div>
                    <label htmlFor="max_faan" className="mb-1.5 block text-sm font-medium text-slate-700">
                        Maksimal Faan <span className="text-rose-500">*</span>
                    </label>
                    <input
                        id="max_faan"
                        type="number"
                        min="0"
                        value={data.max_faan}
                        onChange={(event) => setData('max_faan', event.target.value)}
                        placeholder="Contoh: 5"
                        autoComplete="off"
                        required
                        className={`w-full rounded-xl border px-3.5 py-2.5 text-sm text-slate-800 shadow-sm transition placeholder:text-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 ${
                            errors.max_faan ? 'border-rose-300 bg-rose-50/40' : 'border-slate-200 bg-white'
                        }`}
                    />
                    {errors.max_faan && <p className="mt-1.5 text-xs font-medium text-rose-600">{errors.max_faan}</p>}
                </div>
            </div>

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
                    <option value="">Pilih status</option>
                    <option value="aktif">Aktif</option>
                    <option value="nonaktif">Nonaktif</option>
                </select>
                {errors.status && <p className="mt-1.5 text-xs font-medium text-rose-600">{errors.status}</p>}
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
