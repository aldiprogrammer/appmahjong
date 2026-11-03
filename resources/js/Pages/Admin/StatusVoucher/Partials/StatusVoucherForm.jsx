export default function StatusVoucherForm({
    data,
    setData,
    errors,
    processing,
    onSubmit,
    onCancel,
    submitLabel = 'Simpan',
    statusVoucherInputRef,
}) {
    return (
        <form onSubmit={onSubmit} className="space-y-5">
            <div>
                <label htmlFor="status_voucher" className="mb-1.5 block text-sm font-medium text-slate-700">
                    Status Voucher <span className="text-rose-500">*</span>
                </label>
                <select
                    id="status_voucher"
                    ref={statusVoucherInputRef}
                    value={data.status_voucher}
                    onChange={(event) => setData('status_voucher', event.target.value)}
                    required
                    className={`w-full rounded-xl border px-3.5 py-2.5 text-sm text-slate-800 shadow-sm transition focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 ${
                        errors.status_voucher ? 'border-rose-300 bg-rose-50/40' : 'border-slate-200 bg-white'
                    }`}
                >
                    <option value="">Pilih status</option>
                    <option value="aktif">Aktif</option>
                    <option value="tidak aktif">Tidak Aktif</option>
                </select>
                {errors.status_voucher && <p className="mt-1.5 text-xs font-medium text-rose-600">{errors.status_voucher}</p>}
            </div>

            <div>
                <label htmlFor="jumah_booking" className="mb-1.5 block text-sm font-medium text-slate-700">
                    Jumlah Booking <span className="text-rose-500">*</span>
                </label>
                <input
                    id="jumah_booking"
                    type="text"
                    value={data.jumah_booking}
                    onChange={(event) => setData('jumah_booking', event.target.value)}
                    placeholder="Contoh: 10"
                    autoComplete="off"
                    required
                    className={`w-full rounded-xl border px-3.5 py-2.5 text-sm text-slate-800 shadow-sm transition placeholder:text-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 ${
                        errors.jumah_booking ? 'border-rose-300 bg-rose-50/40' : 'border-slate-200 bg-white'
                    }`}
                />
                {errors.jumah_booking && <p className="mt-1.5 text-xs font-medium text-rose-600">{errors.jumah_booking}</p>}
            </div>

            <div>
                <label htmlFor="jumah_jam" className="mb-1.5 block text-sm font-medium text-slate-700">
                    Jumlah Jam <span className="text-rose-500">*</span>
                </label>
                <input
                    id="jumah_jam"
                    type="text"
                    value={data.jumah_jam}
                    onChange={(event) => setData('jumah_jam', event.target.value)}
                    placeholder="Contoh: 2"
                    autoComplete="off"
                    required
                    className={`w-full rounded-xl border px-3.5 py-2.5 text-sm text-slate-800 shadow-sm transition placeholder:text-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 ${
                        errors.jumah_jam ? 'border-rose-300 bg-rose-50/40' : 'border-slate-200 bg-white'
                    }`}
                />
                {errors.jumah_jam && <p className="mt-1.5 text-xs font-medium text-rose-600">{errors.jumah_jam}</p>}
            </div>

            <div>
                <label htmlFor="tanggal_mulai" className="mb-1.5 block text-sm font-medium text-slate-700">
                    Tanggal Mulai <span className="text-rose-500">*</span>
                </label>
                <input
                    id="tanggal_mulai"
                    type="date"
                    value={data.tanggal_mulai ?? ''}
                    onChange={(event) => setData('tanggal_mulai', event.target.value)}
                    required
                    className={`w-full rounded-xl border px-3.5 py-2.5 text-sm text-slate-800 shadow-sm transition focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 ${
                        errors.tanggal_mulai ? 'border-rose-300 bg-rose-50/40' : 'border-slate-200 bg-white'
                    }`}
                />
                {errors.tanggal_mulai && <p className="mt-1.5 text-xs font-medium text-rose-600">{errors.tanggal_mulai}</p>}
            </div>

            <div>
                <label htmlFor="tanggal_berakhir" className="mb-1.5 block text-sm font-medium text-slate-700">
                    Tanggal Berakhir <span className="text-rose-500">*</span>
                </label>
                <input
                    id="tanggal_berakhir"
                    type="date"
                    value={data.tanggal_berakhir ?? ''}
                    onChange={(event) => setData('tanggal_berakhir', event.target.value)}
                    required
                    className={`w-full rounded-xl border px-3.5 py-2.5 text-sm text-slate-800 shadow-sm transition focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 ${
                        errors.tanggal_berakhir ? 'border-rose-300 bg-rose-50/40' : 'border-slate-200 bg-white'
                    }`}
                />
                {errors.tanggal_berakhir && <p className="mt-1.5 text-xs font-medium text-rose-600">{errors.tanggal_berakhir}</p>}
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
