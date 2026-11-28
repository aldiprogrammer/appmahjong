export default function AturanMahjongForm({
    data,
    setData,
    errors,
    processing,
    onSubmit,
    onCancel,
    submitLabel = 'Simpan',
    kodeInputRef,
    editing = false,
    menyiapkanKode = false,
}) {
    return (
        <form onSubmit={onSubmit} className="space-y-5">
            <div>
                <label htmlFor="kode" className="mb-1.5 block text-sm font-medium text-slate-700">
                    Kode Aturan
                </label>
                <input
                    id="kode"
                    type="text"
                    ref={kodeInputRef}
                    value={data.kode}
                    readOnly
                    placeholder={menyiapkanKode ? 'Membuat kode...' : ''}
                    autoComplete="off"
                    className="w-full cursor-not-allowed rounded-xl border border-slate-200 bg-slate-100 px-3.5 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition placeholder:text-slate-400 focus:outline-none"
                />
                <p className="mt-1.5 text-xs text-slate-500">
                    {editing
                        ? 'Kode tidak dapat diubah karena sudah terdaftar.'
                        : 'Kode dibuat otomatis oleh sistem.'}
                </p>
                {errors.kode && <p className="mt-1.5 text-xs font-medium text-rose-600">{errors.kode}</p>}
            </div>

            <div>
                <label htmlFor="nama_aturan" className="mb-1.5 block text-sm font-medium text-slate-700">
                    Nama Aturan <span className="text-rose-500">*</span>
                </label>
                <input
                    id="nama_aturan"
                    type="text"
                    value={data.nama_aturan}
                    onChange={(event) => setData('nama_aturan', event.target.value)}
                    placeholder="Contoh: Susunan khusus"
                    autoComplete="off"
                    required
                    className={`w-full rounded-xl border px-3.5 py-2.5 text-sm text-slate-800 shadow-sm transition placeholder:text-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 ${
                        errors.nama_aturan ? 'border-rose-300 bg-rose-50/40' : 'border-slate-200 bg-white'
                    }`}
                />
                {errors.nama_aturan && (
                    <p className="mt-1.5 text-xs font-medium text-rose-600">{errors.nama_aturan}</p>
                )}
            </div>

            <label className="flex items-start gap-3 rounded-xl border border-slate-200 bg-slate-50/60 px-3.5 py-3">
                <input
                    type="checkbox"
                    checked={Boolean(data.is_active)}
                    onChange={(event) => setData('is_active', event.target.checked)}
                    className="mt-0.5 h-4 w-4 accent-emerald-600"
                />
                <span>
                    <span className="block text-sm font-medium text-slate-800">Jadikan aturan aktif</span>
                    <span className="mt-0.5 block text-xs text-slate-500">
                        Hanya satu aturan yang aktif. Aturan ini yang dipakai untuk menghitung point user.
                    </span>
                    {errors.is_active && (
                        <span className="mt-1 block text-xs font-medium text-rose-600">{errors.is_active}</span>
                    )}
                </span>
            </label>

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
                    disabled={processing || menyiapkanKode}
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
