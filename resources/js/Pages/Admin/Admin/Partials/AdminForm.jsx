export default function AdminForm({
    data,
    setData,
    errors,
    processing,
    onSubmit,
    onCancel,
    submitLabel = 'Simpan',
    isEditing = false,
    roles = {},
    vanues = [],
    adminMenus = {},
    currentAdmin = null,
    usernameInputRef,
}) {
    const inputClass = (field) =>
        `w-full rounded-xl border px-3.5 py-2.5 text-sm text-slate-800 shadow-sm transition focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 ${
            errors[field] ? 'border-rose-300 bg-rose-50/40' : 'border-slate-200 bg-white'
        }`;

    const vanueWajib = data.role !== 'super_admin';
    const vanueTerkunci = currentAdmin?.role !== 'super_admin' && Boolean(currentAdmin?.vanue);
    const hakAkses = Array.isArray(data.hak_akses) ? data.hak_akses : [];
    const errorHakAkses = Object.entries(errors).find(([key]) => key.startsWith('hak_akses'))?.[1];

    const toggleMenu = (key) => {
        setData(
            'hak_akses',
            hakAkses.includes(key) ? hakAkses.filter((menu) => menu !== key) : [...hakAkses, key],
        );
    };

    return (
        <form onSubmit={onSubmit} className="space-y-5">
            <div>
                <label htmlFor="username" className="mb-1.5 block text-sm font-medium text-slate-700">
                    Username <span className="text-rose-500">*</span>
                </label>
                <input
                    id="username"
                    ref={usernameInputRef}
                    type="text"
                    value={data.username}
                    onChange={(event) => setData('username', event.target.value)}
                    placeholder="contoh: admin_mahjong"
                    required
                    autoComplete="username"
                    className={inputClass('username')}
                />
                {errors.username && <p className="mt-1.5 text-xs font-medium text-rose-600">{errors.username}</p>}
            </div>

            <div>
                <label htmlFor="role" className="mb-1.5 block text-sm font-medium text-slate-700">
                    Role <span className="text-rose-500">*</span>
                </label>
                <select
                    id="role"
                    value={data.role}
                    onChange={(event) => setData('role', event.target.value)}
                    required
                    className={inputClass('role')}
                >
                    {Object.entries(roles).map(([value, label]) => (
                        <option key={value} value={value}>
                            {label}
                        </option>
                    ))}
                </select>
                {errors.role && <p className="mt-1.5 text-xs font-medium text-rose-600">{errors.role}</p>}
            </div>

            <div>
                <label htmlFor="vanue_id" className="mb-1.5 block text-sm font-medium text-slate-700">
                    Venue {vanueWajib && <span className="text-rose-500">*</span>}
                </label>
                <select
                    id="vanue_id"
                    value={data.vanue_id ?? ''}
                    onChange={(event) => setData('vanue_id', event.target.value)}
                    required={vanueWajib}
                    disabled={vanueTerkunci}
                    className={`${inputClass('vanue_id')} ${
                        vanueTerkunci ? 'cursor-not-allowed bg-slate-50 text-slate-600' : ''
                    }`}
                >
                    <option value="">{vanueWajib ? 'Pilih venue...' : 'Tanpa venue (semua venue)'}</option>
                    {vanues.map((vanue) => (
                        <option key={vanue.id} value={vanue.id}>
                            {vanue.nama}
                        </option>
                    ))}
                </select>
                {errors.vanue_id && <p className="mt-1.5 text-xs font-medium text-rose-600">{errors.vanue_id}</p>}
                <p className="mt-1.5 text-xs leading-relaxed text-slate-500">
                    {vanueTerkunci
                        ? `Admin ${currentAdmin?.username ?? ''} hanya dapat mengelola admin di venue sendiri.`
                        : 'Setiap admin menempati satu venue. Role Super Admin boleh dikosongkan agar dapat mengakses semua venue.'}
                </p>
            </div>

            <div>
                <span className="mb-1.5 block text-sm font-medium text-slate-700">
                    Hak Akses Menu <span className="text-rose-500">*</span>
                </span>
                <div className="grid grid-cols-2 gap-2">
                    {Object.entries(adminMenus).map(([key, label]) => {
                        const selaluAktif = key === 'dashboard';
                        const dicentang = selaluAktif || hakAkses.includes(key);

                        return (
                            <label
                                key={key}
                                className={`flex items-center gap-2 rounded-xl border px-3 py-2.5 text-sm transition ${
                                    dicentang
                                        ? 'border-emerald-500 bg-emerald-50/60 text-emerald-800'
                                        : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300'
                                } ${selaluAktif ? 'cursor-not-allowed opacity-80' : 'cursor-pointer'}`}
                            >
                                <input
                                    type="checkbox"
                                    checked={dicentang}
                                    disabled={selaluAktif}
                                    onChange={() => toggleMenu(key)}
                                    className="h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500"
                                />
                                <span className="truncate font-medium">{label}</span>
                            </label>
                        );
                    })}
                </div>
                {errorHakAkses && <p className="mt-1.5 text-xs font-medium text-rose-600">{errorHakAkses}</p>}
                <p className="mt-2 text-xs leading-relaxed text-slate-500">
                    Menu yang tidak dicentang tidak akan tampil di sidebar dan tidak dapat dibuka oleh admin ini.
                    Dashboard selalu aktif. Jika semua menu dicentang, admin mendapat akses penuh termasuk menu baru
                    berikutnya.
                </p>
            </div>

            <div className="grid gap-4 sm:grid-cols-2">
                <div>
                    <label htmlFor="password" className="mb-1.5 block text-sm font-medium text-slate-700">
                        Password {isEditing ? '' : <span className="text-rose-500">*</span>}
                    </label>
                    <input
                        id="password"
                        type="password"
                        value={data.password}
                        onChange={(event) => setData('password', event.target.value)}
                        placeholder={isEditing ? 'Kosongkan jika tetap' : 'Minimal 8 karakter'}
                        required={!isEditing}
                        autoComplete="new-password"
                        className={inputClass('password')}
                    />
                    {errors.password && <p className="mt-1.5 text-xs font-medium text-rose-600">{errors.password}</p>}
                </div>

                <div>
                    <label htmlFor="password_confirmation" className="mb-1.5 block text-sm font-medium text-slate-700">
                        Konfirmasi Password {isEditing ? '' : <span className="text-rose-500">*</span>}
                    </label>
                    <input
                        id="password_confirmation"
                        type="password"
                        value={data.password_confirmation}
                        onChange={(event) => setData('password_confirmation', event.target.value)}
                        placeholder="Ulangi password"
                        required={!isEditing}
                        autoComplete="new-password"
                        className={inputClass('password_confirmation')}
                    />
                    {errors.password_confirmation && (
                        <p className="mt-1.5 text-xs font-medium text-rose-600">{errors.password_confirmation}</p>
                    )}
                </div>
            </div>

            <p className="rounded-xl bg-emerald-50 px-3.5 py-2.5 text-xs leading-relaxed text-emerald-700 ring-1 ring-emerald-100">
                Password disimpan dalam bentuk terenkripsi (hash). {isEditing ? 'Biarkan kosong bila tidak ingin mengganti password.' : 'Gunakan minimal 8 karakter.'}
            </p>

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
