function ImagePlaceholder() {
    return (
        <svg xmlns="http://www.w3.org/2000/svg" className="h-7 w-7" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909M3.75 21h16.5A1.5 1.5 0 0 0 21.75 19.5V4.5A1.5 1.5 0 0 0 20.25 3H3.75A1.5 1.5 0 0 0 2.25 4.5v15A1.5 1.5 0 0 0 3.75 21Zm10.5-13.5h.008v.008h-.008V7.5Z" />
        </svg>
    );
}

export default function VanueForm({
    data,
    setData,
    errors,
    processing,
    onSubmit,
    onCancel,
    submitLabel = 'Simpan',
    isEditing = false,
    logoPreview,
    onLogoChange,
    namaInputRef,
}) {
    return (
        <form onSubmit={onSubmit} className="space-y-5">
            <div>
                <label htmlFor="nama" className="mb-1.5 block text-sm font-medium text-slate-700">
                    Nama Venue <span className="text-rose-500">*</span>
                </label>
                <input
                    id="nama"
                    ref={namaInputRef}
                    type="text"
                    value={data.nama}
                    onChange={(event) => setData('nama', event.target.value)}
                    placeholder="Contoh: Mahjong Center Jakarta"
                    maxLength={50}
                    autoComplete="off"
                    required
                    className={`w-full rounded-xl border px-3.5 py-2.5 text-sm text-slate-800 shadow-sm transition placeholder:text-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 ${
                        errors.nama ? 'border-rose-300 bg-rose-50/40' : 'border-slate-200 bg-white'
                    }`}
                />
                {errors.nama && <p className="mt-1.5 text-xs font-medium text-rose-600">{errors.nama}</p>}
            </div>

            <div>
                <label htmlFor="alamat" className="mb-1.5 block text-sm font-medium text-slate-700">
                    Alamat <span className="text-rose-500">*</span>
                </label>
                <textarea
                    id="alamat"
                    value={data.alamat}
                    onChange={(event) => setData('alamat', event.target.value)}
                    placeholder="Contoh: Jl. Sudirman No. 10, Jakarta"
                    rows={3}
                    maxLength={255}
                    required
                    className={`w-full rounded-xl border px-3.5 py-2.5 text-sm text-slate-800 shadow-sm transition placeholder:text-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 ${
                        errors.alamat ? 'border-rose-300 bg-rose-50/40' : 'border-slate-200 bg-white'
                    }`}
                />
                {errors.alamat && <p className="mt-1.5 text-xs font-medium text-rose-600">{errors.alamat}</p>}
            </div>

            <div>
                <label htmlFor="logo" className="mb-1.5 block text-sm font-medium text-slate-700">
                    Logo Venue {!isEditing && <span className="text-rose-500">*</span>}
                </label>
                <div className="flex items-center gap-4">
                    <span className="flex h-20 w-20 shrink-0 items-center justify-center overflow-hidden rounded-xl border border-dashed border-slate-200 bg-slate-50 text-slate-300">
                        {logoPreview ? (
                            <img src={logoPreview} alt="Pratinjau logo venue" className="h-full w-full object-cover" />
                        ) : (
                            <ImagePlaceholder />
                        )}
                    </span>
                    <div className="min-w-0 flex-1">
                        <input
                            id="logo"
                            type="file"
                            accept="image/jpeg,image/png,image/webp"
                            onChange={onLogoChange}
                            required={!isEditing}
                            className="block w-full cursor-pointer rounded-xl border border-slate-200 bg-white text-sm text-slate-500 shadow-sm file:mr-3 file:cursor-pointer file:rounded-l-xl file:border-0 file:bg-emerald-50 file:px-4 file:py-2.5 file:text-sm file:font-semibold file:text-emerald-700 hover:file:bg-emerald-100 focus:outline-none focus:ring-2 focus:ring-emerald-500/20"
                        />
                        <p className="mt-1.5 text-xs text-slate-500">
                            JPG, PNG, atau WEBP. Maksimal 5 MB.
                            {isEditing && ' Biarkan kosong jika tidak ingin mengganti logo.'}
                        </p>
                        {errors.logo && <p className="mt-1.5 text-xs font-medium text-rose-600">{errors.logo}</p>}
                    </div>
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
