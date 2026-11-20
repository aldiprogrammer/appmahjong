export default function SuplayerFoodForm({
    data,
    setData,
    errors,
    processing,
    onSubmit,
    onCancel,
    submitLabel = 'Simpan',
    kodeInputRef,
    editing = false,
    vanues = [],
    vanueTerkunci = false,
    isVenueScoped = false,
    namaVanueAktif = null,
}) {
    const inputClass = (field) =>
        `w-full rounded-xl border px-3.5 py-2.5 text-sm text-slate-800 shadow-sm transition placeholder:text-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 ${
            errors[field] ? 'border-rose-300 bg-rose-50/40' : 'border-slate-200 bg-white'
        }`;

    return (
        <form onSubmit={onSubmit} className="space-y-5">
            <div>
                <label htmlFor="kode_suplayer" className="mb-1.5 block text-sm font-medium text-slate-700">
                    Kode Suplayer
                </label>
                <input
                    id="kode_suplayer"
                    ref={kodeInputRef}
                    type="text"
                    value={data.kode_suplayer}
                    onChange={(event) => setData('kode_suplayer', event.target.value)}
                    placeholder="Membuat kode..."
                    readOnly={true}
                    autoComplete="off"
                    className={`w-full cursor-not-allowed rounded-xl border border-slate-200 bg-slate-100 px-3.5 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition focus:outline-none`}
                />
                <p className="mt-1.5 text-xs text-slate-500">
                    {editing
                        ? 'Kode tidak dapat diubah karena sudah terdaftar.'
                        : 'Kode dibuat otomatis oleh sistem dari kombinasi huruf dan angka.'}
                </p>
                {errors.kode_suplayer && <p className="mt-1.5 text-xs font-medium text-rose-600">{errors.kode_suplayer}</p>}
            </div>

            <div>
                <label htmlFor="vanue_id" className="mb-1.5 block text-sm font-medium text-slate-700">
                    Venue
                </label>
                <select
                    id="vanue_id"
                    value={data.vanue_id ?? ''}
                    onChange={(event) => setData('vanue_id', event.target.value)}
                    disabled={vanueTerkunci}
                    className={`w-full rounded-xl border px-3.5 py-2.5 text-sm shadow-sm transition focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 ${
                        errors.vanue_id ? 'border-rose-300 bg-rose-50/40' : 'border-slate-200 bg-white'
                    } ${vanueTerkunci ? 'cursor-not-allowed bg-slate-50 text-slate-600' : 'text-slate-800'}`}
                >
                    <option value="">Tanpa venue</option>
                    {vanues.map((vanue) => (
                        <option key={vanue.id} value={vanue.id}>
                            {vanue.nama}
                        </option>
                    ))}
                </select>
                {errors.vanue_id && <p className="mt-1.5 text-xs font-medium text-rose-600">{errors.vanue_id}</p>}
                <p className="mt-1.5 text-xs leading-relaxed text-slate-500">
                    {editing
                        ? 'Venue suplayer tidak dapat diubah setelah dibuat.'
                        : isVenueScoped
                            ? `Suplayer ini otomatis menjadi milik venue ${namaVanueAktif ?? ''}.`
                            : 'Suplayer hanya dapat dipilih untuk menu pada venue yang sama.'}
                </p>
            </div>

            <div>
                <label htmlFor="nama_suplayer" className="mb-1.5 block text-sm font-medium text-slate-700">
                    Nama Suplayer <span className="text-rose-500">*</span>
                </label>
                <input
                    id="nama_suplayer"
                    type="text"
                    value={data.nama_suplayer}
                    onChange={(event) => setData('nama_suplayer', event.target.value)}
                    placeholder="Contoh: PT Berkah Makanan"
                    autoComplete="off"
                    required
                    className={inputClass('nama_suplayer')}
                />
                {errors.nama_suplayer && <p className="mt-1.5 text-xs font-medium text-rose-600">{errors.nama_suplayer}</p>}
            </div>

            <div>
                <label htmlFor="alamat" className="mb-1.5 block text-sm font-medium text-slate-700">
                    Alamat <span className="text-rose-500">*</span>
                </label>
                <input
                    id="alamat"
                    type="text"
                    value={data.alamat}
                    onChange={(event) => setData('alamat', event.target.value)}
                    placeholder="Alamat lengkap suplayer"
                    autoComplete="off"
                    required
                    className={inputClass('alamat')}
                />
                {errors.alamat && <p className="mt-1.5 text-xs font-medium text-rose-600">{errors.alamat}</p>}
            </div>

            <div className="grid gap-4 sm:grid-cols-2">
                <div>
                    <label htmlFor="nowa" className="mb-1.5 block text-sm font-medium text-slate-700">
                        No. WhatsApp <span className="text-rose-500">*</span>
                    </label>
                    <input
                        id="nowa"
                        type="text"
                        value={data.nowa}
                        onChange={(event) => setData('nowa', event.target.value)}
                        placeholder="08xxxxxxxxxx"
                        autoComplete="off"
                        required
                        className={inputClass('nowa')}
                    />
                    {errors.nowa && <p className="mt-1.5 text-xs font-medium text-rose-600">{errors.nowa}</p>}
                </div>

                <div>
                    <label htmlFor="email" className="mb-1.5 block text-sm font-medium text-slate-700">
                        Email <span className="text-rose-500">*</span>
                    </label>
                    <input
                        id="email"
                        type="email"
                        value={data.email}
                        onChange={(event) => setData('email', event.target.value)}
                        placeholder="email@contoh.com"
                        autoComplete="off"
                        required
                        className={inputClass('email')}
                    />
                    {errors.email && <p className="mt-1.5 text-xs font-medium text-rose-600">{errors.email}</p>}
                </div>
            </div>

            <div className="grid gap-4 sm:grid-cols-2">
                <div>
                    <label htmlFor="password" className="mb-1.5 block text-sm font-medium text-slate-700">
                        Password <span className="text-rose-500">*</span>
                    </label>
                    <input
                        id="password"
                        type="text"
                        value={data.password}
                        onChange={(event) => setData('password', event.target.value)}
                        placeholder="Password suplayer"
                        autoComplete="new-password"
                        required
                        className={inputClass('password')}
                    />
                    {errors.password && <p className="mt-1.5 text-xs font-medium text-rose-600">{errors.password}</p>}
                </div>

                <div>
                    <label htmlFor="tangggl_daftar" className="mb-1.5 block text-sm font-medium text-slate-700">
                        Tanggal Daftar <span className="text-rose-500">*</span>
                    </label>
                    <input
                        id="tangggl_daftar"
                        type="date"
                        value={data.tangggl_daftar}
                        onChange={(event) => setData('tangggl_daftar', event.target.value)}
                        required
                        className={inputClass('tangggl_daftar')}
                    />
                    {errors.tangggl_daftar && <p className="mt-1.5 text-xs font-medium text-rose-600">{errors.tangggl_daftar}</p>}
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
                    {submitLabel}
                </button>
            </div>
        </form>
    );
}
