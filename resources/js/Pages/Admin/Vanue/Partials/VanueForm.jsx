import { useState } from 'react';

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
    const [fasilitasBaru, setFasilitasBaru] = useState('');

    const tambahFasilitas = (event) => {
        event.preventDefault();

        const nilai = fasilitasBaru.trim();

        if (! nilai || data.fasilitas.includes(nilai) || data.fasilitas.length >= 15) {
            return;
        }

        setData('fasilitas', [...data.fasilitas, nilai]);
        setFasilitasBaru('');
    };

    const hapusFasilitas = (fasilitas) => {
        setData('fasilitas', data.fasilitas.filter((item) => item !== fasilitas));
    };

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
                <label htmlFor="fasilitas" className="mb-1.5 block text-sm font-medium text-slate-700">
                    Fasilitas <span className="text-xs font-normal text-slate-400">(opsional, maksimal 15)</span>
                </label>
                <div className="flex gap-2">
                    <input
                        id="fasilitas"
                        type="text"
                        value={fasilitasBaru}
                        onChange={(event) => setFasilitasBaru(event.target.value)}
                        onKeyDown={(event) => {
                            if (event.key === 'Enter') {
                                tambahFasilitas(event);
                            }
                        }}
                        placeholder="Contoh: Parkir luas, AC, Wi-Fi..."
                        maxLength={50}
                        autoComplete="off"
                        className="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-800 shadow-sm transition placeholder:text-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20"
                    />
                    <button
                        type="button"
                        onClick={tambahFasilitas}
                        disabled={fasilitasBaru.trim() === '' || data.fasilitas.length >= 15}
                        className="inline-flex shrink-0 items-center gap-1.5 rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4" fill="none" viewBox="0 0 24 24" strokeWidth={2.4} stroke="currentColor">
                            <path strokeLinecap="round" strokeLinejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>
                        Tambah
                    </button>
                </div>

                {data.fasilitas.length > 0 && (
                    <div className="mt-2.5 flex flex-wrap gap-2">
                        {data.fasilitas.map((fasilitas) => (
                            <span
                                key={fasilitas}
                                className="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-100"
                            >
                                {fasilitas}
                                <button
                                    type="button"
                                    onClick={() => hapusFasilitas(fasilitas)}
                                    className="inline-flex h-4 w-4 items-center justify-center rounded-full text-emerald-500 transition hover:bg-emerald-200 hover:text-emerald-800"
                                    aria-label={`Hapus fasilitas ${fasilitas}`}
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" className="h-3 w-3" fill="none" viewBox="0 0 24 24" strokeWidth={2.6} stroke="currentColor">
                                        <path strokeLinecap="round" strokeLinejoin="round" d="M6 18 18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </span>
                        ))}
                    </div>
                )}
                {errors.fasilitas && <p className="mt-1.5 text-xs font-medium text-rose-600">{errors.fasilitas}</p>}
            </div>

            <div>
                <p className="mb-1.5 block text-sm font-medium text-slate-700">
                    Koordinat <span className="text-xs font-normal text-slate-400">(opsional, untuk pencarian venue terdekat)</span>
                </p>
                <div className="grid gap-3 sm:grid-cols-2">
                    <div>
                        <label htmlFor="latitude" className="mb-1 block text-xs font-medium text-slate-500">
                            Latitude
                        </label>
                        <input
                            id="latitude"
                            type="number"
                            step="any"
                            value={data.latitude}
                            onChange={(event) => setData('latitude', event.target.value)}
                            placeholder="-6.2088"
                            className={`w-full rounded-xl border px-3.5 py-2.5 text-sm text-slate-800 shadow-sm transition placeholder:text-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 ${
                                errors.latitude ? 'border-rose-300 bg-rose-50/40' : 'border-slate-200 bg-white'
                            }`}
                        />
                        {errors.latitude && <p className="mt-1 text-xs font-medium text-rose-600">{errors.latitude}</p>}
                    </div>
                    <div>
                        <label htmlFor="longitude" className="mb-1 block text-xs font-medium text-slate-500">
                            Longitude
                        </label>
                        <input
                            id="longitude"
                            type="number"
                            step="any"
                            value={data.longitude}
                            onChange={(event) => setData('longitude', event.target.value)}
                            placeholder="106.8456"
                            className={`w-full rounded-xl border px-3.5 py-2.5 text-sm text-slate-800 shadow-sm transition placeholder:text-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 ${
                                errors.longitude ? 'border-rose-300 bg-rose-50/40' : 'border-slate-200 bg-white'
                            }`}
                        />
                        {errors.longitude && <p className="mt-1 text-xs font-medium text-rose-600">{errors.longitude}</p>}
                    </div>
                </div>
                <p className="mt-1.5 text-xs text-slate-500">
                    Ambil dari Google Maps: klik kanan lokasi venue, salin angka pada kolom "Latitude, Longitude".
                </p>
            </div>

            <div>
                <label className="mb-1.5 block text-sm font-medium text-slate-700">
                    Status Venue
                </label>
                <div className="grid gap-2 sm:grid-cols-2">
                    {[
                        { value: 'aktif', label: 'Aktif', deskripsi: 'Venue tampil di halaman user dan bisa dibooking' },
                        { value: 'nonaktif', label: 'Nonaktif', deskripsi: 'Venue disembunyikan dari halaman user' },
                    ].map((opsi) => {
                        const aktif = data.status === opsi.value;

                        return (
                            <button
                                key={opsi.value}
                                type="button"
                                onClick={() => setData('status', opsi.value)}
                                aria-pressed={aktif}
                                className={`flex items-start gap-2 rounded-xl border px-3 py-2.5 text-left transition ${aktif
                                    ? 'border-emerald-600 bg-emerald-600 text-white shadow-sm'
                                    : 'border-slate-200 bg-white text-slate-700 hover:border-emerald-300'
                                    }`}
                            >
                                <span
                                    className={`mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full ${aktif ? 'bg-white/20 text-white' : 'bg-emerald-50 text-emerald-600'
                                        }`}
                                >
                                    {aktif ? (
                                        <svg xmlns="http://www.w3.org/2000/svg" className="h-3 w-3" fill="none" viewBox="0 0 24 24" strokeWidth={2.6} stroke="currentColor">
                                            <path strokeLinecap="round" strokeLinejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                                        </svg>
                                    ) : (
                                        <span className="h-1.5 w-1.5 rounded-full bg-emerald-500" />
                                    )}
                                </span>
                                <span className="min-w-0 flex-1">
                                    <span className="block text-xs font-semibold">{opsi.label}</span>
                                    <span className={`mt-0.5 block text-[10px] leading-snug ${aktif ? 'text-emerald-50' : 'text-slate-500'}`}>
                                        {opsi.deskripsi}
                                    </span>
                                </span>
                            </button>
                        );
                    })}
                </div>
                {errors.status && <p className="mt-1.5 text-xs font-medium text-rose-600">{errors.status}</p>}
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
