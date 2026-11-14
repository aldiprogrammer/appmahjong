export default function MejaForm({
    data,
    setData,
    errors,
    processing,
    onSubmit,
    onCancel,
    submitLabel = 'Simpan',
    isEditing = false,
    vanues = [],
    currentAdmin = null,
    kodeInputRef,
}) {
    const vanueTerkunci = currentAdmin?.role !== 'super_admin' && Boolean(currentAdmin?.vanue);

    return (
        <form onSubmit={onSubmit} className="space-y-5">
            {isEditing ? (
                <div>
                    <label htmlFor="kode_meja" className="mb-1.5 block text-sm font-medium text-slate-700">
                        Kode Meja <span className="text-rose-500">*</span>
                    </label>
                    <input
                        id="kode_meja"
                        ref={kodeInputRef}
                        type="text"
                        value={data.kode_meja}
                        onChange={(event) => setData('kode_meja', event.target.value)}
                        placeholder="Contoh: MJ-01"
                        autoComplete="off"
                        required
                        className={`w-full rounded-xl border px-3.5 py-2.5 text-sm text-slate-800 shadow-sm transition placeholder:text-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 ${
                            errors.kode_meja ? 'border-rose-300 bg-rose-50/40' : 'border-slate-200 bg-white'
                        }`}
                    />
                    {errors.kode_meja && <p className="mt-1.5 text-xs font-medium text-rose-600">{errors.kode_meja}</p>}
                    <p className="mt-1.5 text-xs text-slate-500">
                        Kode unik dipakai pada QR code meja. Pastikan tidak sama dengan meja lain.
                    </p>
                </div>
            ) : (
                <div className="rounded-xl border border-emerald-100 bg-emerald-50/60 px-3.5 py-3">
                    <p className="text-xs font-semibold text-emerald-800">Kode meja dibuat otomatis</p>
                    <p className="mt-0.5 text-[11px] leading-relaxed text-emerald-700/80">
                        Sistem akan membuat kode unik (contoh: MJ-A1B2C3) saat meja disimpan, sehingga tidak mungkin
                        sama dengan meja lain.
                    </p>
                </div>
            )}

            <div>
                <label htmlFor="nama_meja" className="mb-1.5 block text-sm font-medium text-slate-700">
                    Nama Meja <span className="text-rose-500">*</span>
                </label>
                <input
                    id="nama_meja"
                    type="text"
                    value={data.nama_meja}
                    onChange={(event) => setData('nama_meja', event.target.value)}
                    placeholder="Contoh: Meja VIP 1"
                    autoComplete="off"
                    required
                    className={`w-full rounded-xl border px-3.5 py-2.5 text-sm text-slate-800 shadow-sm transition placeholder:text-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 ${
                        errors.nama_meja ? 'border-rose-300 bg-rose-50/40' : 'border-slate-200 bg-white'
                    }`}
                />
                {errors.nama_meja && <p className="mt-1.5 text-xs font-medium text-rose-600">{errors.nama_meja}</p>}
            </div>

            <div>
                <label htmlFor="vanue_id" className="mb-1.5 block text-sm font-medium text-slate-700">
                    Venue <span className="text-rose-500">*</span>
                </label>
                <select
                    id="vanue_id"
                    value={data.vanue_id ?? ''}
                    onChange={(event) => setData('vanue_id', event.target.value)}
                    required
                    disabled={vanueTerkunci}
                    className={`w-full rounded-xl border px-3.5 py-2.5 text-sm shadow-sm transition focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 ${
                        errors.vanue_id ? 'border-rose-300 bg-rose-50/40' : 'border-slate-200 bg-white'
                    } ${vanueTerkunci ? 'cursor-not-allowed bg-slate-50 text-slate-600' : 'text-slate-800'}`}
                >
                    <option value="">Pilih venue...</option>
                    {vanues.map((vanue) => (
                        <option key={vanue.id} value={vanue.id}>
                            {vanue.nama}
                        </option>
                    ))}
                </select>
                {errors.vanue_id && <p className="mt-1.5 text-xs font-medium text-rose-600">{errors.vanue_id}</p>}
                <p className="mt-1.5 text-xs leading-relaxed text-slate-500">
                    {vanueTerkunci
                        ? `Admin ${currentAdmin?.username ?? ''} hanya dapat mengelola meja di venue sendiri.`
                        : 'Setiap meja hanya dimiliki satu venue dan tampil pada halaman venue tersebut.'}
                </p>
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
