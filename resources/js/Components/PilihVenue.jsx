import { useState } from 'react';

function MapPinIcon({ className = 'h-3.5 w-3.5' }) {
    return (
        <svg xmlns="http://www.w3.org/2000/svg" className={className} fill="none" viewBox="0 0 24 24" strokeWidth={1.9} stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
            <path strokeLinecap="round" strokeLinejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
        </svg>
    );
}

function ChevronIcon({ className = 'h-4 w-4' }) {
    return (
        <svg xmlns="http://www.w3.org/2000/svg" className={className} fill="none" viewBox="0 0 24 24" strokeWidth={2.4} stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
        </svg>
    );
}

function StoreIcon({ className = 'h-4 w-4' }) {
    return (
        <svg xmlns="http://www.w3.org/2000/svg" className={className} fill="none" viewBox="0 0 24 24" strokeWidth={1.9} stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" d="M13.5 21v-7.5a.75.75 0 0 1 .75-.75h3a.75.75 0 0 1 .75.75V21m-4.5 0H2.36m11.14 0H18m-4.5 0h4.5M12 21.75V21m0 0h-2.25m0 0h-3m-2.39 0H3a.75.75 0 0 1-.75-.75v-8.25a.75.75 0 0 1 .75-.75h.75m14.5 0H21a.75.75 0 0 1 .75.75v8.25a.75.75 0 0 1-.75.75h-1.5m-14.5 0v-8.5c0-.4.19-.78.5-1.03l6-4.5a1.5 1.5 0 0 1 1.8 0l6 4.5c.31.25.5.63.5 1.03v8.5M3.75 8.25V6a1.5 1.5 0 0 1 1.5-1.5h13.5A1.5 1.5 0 0 1 20.25 6v2.25" />
        </svg>
    );
}

const fasilitasMeta = [
    { kata: ['wifi', 'wi-fi', 'internet'], label: 'Wi-Fi', warna: 'bg-sky-50 text-sky-700 ring-sky-100', ikon: <path strokeLinecap="round" strokeLinejoin="round" d="M8.288 15.038a5.25 5.25 0 0 1 7.424 0M5.106 11.856c3.807-3.808 9.98-3.808 13.788 0M1.924 8.674c5.565-5.565 14.587-5.565 20.152 0M12.53 18.22l-.53.53-.53-.53a.75.75 0 0 1 1.06 0Z" /> },
    { kata: ['parkir', 'parking'], label: 'Parkir', warna: 'bg-violet-50 text-violet-700 ring-violet-100', ikon: <path strokeLinecap="round" strokeLinejoin="round" d="M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25a2.25 2.25 0 0 1 2.25-2.25h.872a6.753 6.753 0 0 1 6.478-4.5H12M6.75 15.75h.008v.008H6.75v-.008ZM13.5 5.25V3.375a.75.75 0 0 0-.75-.75h-.75a.75.75 0 0 0-.75.75v1.875m4.5 0h1.5a2.25 2.25 0 0 1 2.25 2.25v4.5m0 3.375a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h.008v.008h-.008v-.008Zm-13.5 0h.008v.008h-.008v-.008Z" /> },
    { kata: ['ac', 'pendingin', 'kipas'], label: 'AC', warna: 'bg-cyan-50 text-cyan-700 ring-cyan-100', ikon: <path strokeLinecap="round" strokeLinejoin="round" d="M12 3v2.25m6.364.386-1.591 1.591M21 12h-2.25m-.386 6.364-1.591-1.591M12 18.75V21m-4.773-4.227-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z" /> },
    { kata: ['toilet', 'km', 'kamar mandi', 'wc'], label: 'Toilet', warna: 'bg-teal-50 text-teal-700 ring-teal-100', ikon: <path strokeLinecap="round" strokeLinejoin="round" d="M3.75 12h16.5m-16.5 0v-1.5A2.25 2.25 0 0 1 6 8.25h1.5A2.25 2.25 0 0 1 9.75 10.5V12m-6 0v6.75a2.25 2.25 0 0 0 2.25 2.25h1.5a2.25 2.25 0 0 0 2.25-2.25V12M14.25 8.625h4.5m-4.5 3h4.5m-4.5 3h4.5" /> },
    { kata: ['makan', 'resto', 'restoran', 'kafe', 'cafe', 'menu', 'food'], label: 'Makan', warna: 'bg-orange-50 text-orange-700 ring-orange-100', ikon: <path strokeLinecap="round" strokeLinejoin="round" d="M15.75 10.5V6a3.75 3.75 0 1 1 7.5 0v4.5m-7.5 0v7.5a2.25 2.25 0 0 1-2.25 2.25h-7.5A2.25 2.25 0 0 1 3.75 18v-7.5m12 0H3.75m3-3.75V3m4.5 3.75V3" /> },
    { kata: ['rokok', 'smoking', 'merokok'], label: 'Smoking Area', warna: 'bg-stone-50 text-stone-700 ring-stone-200', ikon: <path strokeLinecap="round" strokeLinejoin="round" d="M9.75 6.75V4.5m2.25 2.25V3m2.25 3.75V4.5M4.5 13.5h15a.75.75 0 0 0 .75-.75v-1.5a.75.75 0 0 0-.75-.75h-15a.75.75 0 0 0-.75.75v1.5a.75.75 0 0 0 .75.75Zm.75 0v4.5m13.5-4.5v4.5M9 18v1.5m3-1.5v1.5m3-1.5v1.5" /> },
    { kata: ['sholat', 'salat', 'musholla', 'musala', 'prayer'], label: 'Musholla', warna: 'bg-emerald-50 text-emerald-700 ring-emerald-100', ikon: <path strokeLinecap="round" strokeLinejoin="round" d="m10.5 3.75 1.5 3.75 3.75 1.5-3.75 1.5-1.5 3.75-1.5-3.75L5.25 9l3.75-1.5 1.5-3.75Zm6 9.75 1.5 3.75 3.75 1.5-3.75 1.5-1.5 3.75-1.5-3.75-3.75-1.5 3.75-1.5 1.5-3.75Z" /> },
    { kata: ['games', 'game', 'playstation', 'ps'], label: 'Games', warna: 'bg-fuchsia-50 text-fuchsia-700 ring-fuchsia-100', ikon: <path strokeLinecap="round" strokeLinejoin="round" d="M14.25 6.087c0-.355.186-.676.401-.959.221-.29.349-.634.349-1.003 0-1.036-1.007-1.875-2.25-1.875s-2.25.84-2.25 1.875c0 .369.128.713.349 1.003.215.283.401.604.401.959v0a.64.64 0 0 1-.658.643 48.39 48.39 0 0 1-3.213-.492.75.75 0 0 0-.654 1.02 47.653 47.653 0 0 0 1.52 3.34.75.75 0 0 1-.816.997 48.583 48.583 0 0 1-1.905-.311.75.75 0 0 0-.917.789 48.83 48.83 0 0 0 3.436 8.387.75.75 0 0 0 1.38-.204c.212-.678.502-1.352.897-1.985a48.03 48.03 0 0 1 5.68-2.985.75.75 0 0 0 .71-.901 49.337 49.337 0 0 1-.563-2.432.75.75 0 0 1 .825-.85 48.65 48.65 0 0 1 2.323.328.75.75 0 0 0 .836-.788 49.152 49.152 0 0 0-2.72-9.065.75.75 0 0 0-.916-.527c-.797.18-1.59.343-2.386.484a.75.75 0 0 1-.768-.657v0Z" /> },
];

const fasilitasDefault = {
    warna: 'bg-emerald-50 text-emerald-700 ring-emerald-100',
    ikon: <path strokeLinecap="round" strokeLinejoin="round" d="m4.5 12.75 6 6 9-13.5" />,
};

function BadgeFasilitas({ fasilitas }) {
    const kata = fasilitas.toLowerCase();
    const meta = fasilitasMeta.find((item) => item.kata.some((k) => kata.includes(k))) ?? fasilitasDefault;

    return (
        <span className={`inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[9px] font-bold ring-1 ${meta.warna}`}>
            <svg xmlns="http://www.w3.org/2000/svg" className="h-2.5 w-2.5 shrink-0" fill="none" viewBox="0 0 24 24" strokeWidth={2.2} stroke="currentColor">
                {meta.ikon}
            </svg>
            {fasilitas}
        </span>
    );
}

export function jarakKmDari(lat1, lon1, lat2, lon2) {
    const R = 6371;
    const rad = (derajat) => (derajat * Math.PI) / 180;
    const dLat = rad(lat2 - lat1);
    const dLon = rad(lon2 - lon1);
    const a =
        Math.sin(dLat / 2) ** 2 +
        Math.cos(rad(lat1)) * Math.cos(rad(lat2)) * Math.sin(dLon / 2) ** 2;

    return 2 * R * Math.asin(Math.min(1, Math.sqrt(a)));
}

function VenueCard({ vanue, jumlahMeja, jarakKm, terdekat, onPilih }) {
    const inisial = (vanue.nama || 'V').trim().charAt(0).toUpperCase();
    const fasilitas = vanue.fasilitas ?? [];

    return (
        <button
            type="button"
            onClick={() => onPilih(vanue)}
            className="group relative flex w-full items-center gap-3.5 overflow-hidden rounded-2xl border border-slate-100 bg-white p-3.5 text-left shadow-sm transition duration-200 hover:-translate-y-0.5 hover:border-emerald-200 hover:shadow-lg hover:shadow-emerald-900/10 active:scale-[0.98] active:shadow-sm"
        >
            <span className="pointer-events-none absolute inset-y-0 right-0 w-24 bg-gradient-to-l from-emerald-50/70 to-transparent opacity-0 transition group-hover:opacity-100" />
            <span className="relative flex h-14 w-14 shrink-0 items-center justify-center overflow-hidden rounded-2xl bg-gradient-to-br from-emerald-500 to-emerald-700 text-lg font-black text-white shadow-md shadow-emerald-900/20">
                {vanue.logo_url ? (
                    <img src={vanue.logo_url} alt={vanue.nama} className="h-full w-full object-cover" loading="lazy" />
                ) : (
                    inisial
                )}
            </span>
            <span className="relative min-w-0 flex-1">
                <span className="flex flex-wrap items-center gap-1.5">
                    <span className="truncate text-sm font-bold text-slate-800">{vanue.nama}</span>
                    {terdekat && (
                        <span className="inline-flex items-center gap-0.5 rounded-full bg-amber-50 px-1.5 py-0.5 text-[9px] font-bold uppercase tracking-wide text-amber-700 ring-1 ring-amber-100">
                            <svg xmlns="http://www.w3.org/2000/svg" className="h-2.5 w-2.5" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M3.478 2.405a.75.75 0 0 0-.926.94l2.432 7.905H13.5a.75.75 0 0 1 0 1.5H4.984l-2.432 7.905a.75.75 0 0 0 .926.94 60.519 60.519 0 0 0 18.445-8.986.75.75 0 0 0 0-1.218A60.517 60.517 0 0 0 3.478 2.405Z" />
                            </svg>
                            Terdekat
                        </span>
                    )}
                </span>
                {vanue.alamat && (
                    <span className="mt-0.5 flex items-start gap-1 text-[11px] leading-snug text-slate-500">
                        <MapPinIcon className="mt-px h-3 w-3 shrink-0 text-emerald-600" />
                        <span className="line-clamp-2">{vanue.alamat}</span>
                    </span>
                )}
                {fasilitas.length > 0 && (
                    <span className="mt-1.5 flex flex-wrap gap-1">
                        {fasilitas.slice(0, 4).map((fasilitas) => (
                            <BadgeFasilitas key={fasilitas} fasilitas={fasilitas} />
                        ))}
                        {fasilitas.length > 4 && (
                            <span className="rounded-full bg-slate-100 px-2 py-0.5 text-[9px] font-bold text-slate-500">
                                +{fasilitas.length - 4} lainnya
                            </span>
                        )}
                    </span>
                )}
                <span className="mt-1.5 flex flex-wrap items-center gap-1">
                    {jarakKm !== null && (
                        <span className="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2 py-0.5 text-[9px] font-bold text-emerald-700">
                            <svg xmlns="http://www.w3.org/2000/svg" className="h-2.5 w-2.5" fill="none" viewBox="0 0 24 24" strokeWidth={2.2} stroke="currentColor">
                                <path strokeLinecap="round" strokeLinejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 12 5.25c2.986 0 5.863.708 8.731 1.875L18 12m0 0 1.5 8.875M6 12l-1.5 8.875M12 5.25V2.25m0 3V5.25" />
                            </svg>
                            {jarakKm < 1 ? `${Math.round(jarakKm * 1000)} m` : `${jarakKm.toFixed(1)} km`}
                        </span>
                    )}
                    {jumlahMeja !== null && (
                        <span className="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2 py-0.5 text-[9px] font-bold uppercase tracking-wide text-emerald-700">
                            {jumlahMeja} meja
                        </span>
                    )}
                </span>
            </span>
            <span className="relative flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-emerald-50 text-emerald-600 transition group-hover:bg-emerald-600 group-hover:text-white">
                <ChevronIcon />
            </span>
        </button>
    );
}

export default function PilihVenue({ vanues, mejas, onPilih }) {
    const hitungMeja = (vanue) =>
        mejas === undefined ? null : mejas.filter((meja) => String(meja.vanue_id) === String(vanue.id)).length;

    const [posisi, setPosisi] = useState(null);
    const [lokasiStatus, setLokasiStatus] = useState('idle');

    const vanueTerurut = posisi === null
        ? vanues
        : [...vanues].sort((a, b) => {
            const jarakA = a.latitude !== null && a.longitude !== null
                ? jarakKmDari(posisi.lat, posisi.lng, Number(a.latitude), Number(a.longitude))
                : Number.POSITIVE_INFINITY;
            const jarakB = b.latitude !== null && b.longitude !== null
                ? jarakKmDari(posisi.lat, posisi.lng, Number(b.latitude), Number(b.longitude))
                : Number.POSITIVE_INFINITY;

            return jarakA - jarakB;
        });

    const jarakVanue = (vanue) => {
        if (posisi === null || vanue.latitude === null || vanue.longitude === null) {
            return null;
        }

        return jarakKmDari(posisi.lat, posisi.lng, Number(vanue.latitude), Number(vanue.longitude));
    };

    const cariTerdekat = () => {
        if (!('geolocation' in navigator)) {
            setLokasiStatus('gagal');

            return;
        }

        setLokasiStatus('memuat');
        navigator.geolocation.getCurrentPosition(
            (position) => {
                setPosisi({
                    lat: position.coords.latitude,
                    lng: position.coords.longitude,
                });
                setLokasiStatus('siap');
            },
            () => setLokasiStatus('gagal'),
            { enableHighAccuracy: false, timeout: 10000, maximumAge: 300000 },
        );
    };

    return (
        <div className="space-y-4">
            <div className="flex items-center justify-between gap-3 rounded-2xl border border-emerald-100 bg-white px-4 py-3 shadow-sm">
                <div className="flex items-center gap-2.5">
                    <span className="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600">
                        <StoreIcon />
                    </span>
                    <div>
                        <p className="text-sm font-bold leading-tight text-slate-800">Pilih Venue</p>
                        <p className="text-[11px] text-slate-500">Mulai dengan memilih tempat bermain</p>
                    </div>
                </div>
                {mejas !== undefined && (
                    <span className="rounded-full bg-emerald-50 px-2.5 py-1 text-[10px] font-bold text-emerald-700">
                        {vanues.length} venue
                    </span>
                )}
            </div>

            {vanues.some((vanue) => vanue.latitude !== null && vanue.longitude !== null) && (
                <div className="flex flex-wrap items-center gap-2 rounded-2xl border border-emerald-100 bg-gradient-to-r from-emerald-50 to-white px-4 py-3 shadow-sm">
                    <button
                        type="button"
                        onClick={cariTerdekat}
                        disabled={lokasiStatus === 'memuat'}
                        className="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-3.5 py-2 text-xs font-bold text-white shadow-sm shadow-emerald-600/30 transition hover:bg-emerald-700 active:scale-95 disabled:cursor-not-allowed disabled:opacity-60"
                    >
                        {lokasiStatus === 'memuat' ? (
                            <svg className="h-3.5 w-3.5 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" />
                                <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8V0C5.373 0 0 5.373 0 12h4Z" />
                            </svg>
                        ) : (
                            <svg xmlns="http://www.w3.org/2000/svg" className="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" strokeWidth={1.9} stroke="currentColor">
                                <path strokeLinecap="round" strokeLinejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                <path strokeLinecap="round" strokeLinejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
                            </svg>
                        )}
                        {posisi ? 'Perbarui lokasi saya' : 'Cari venue terdekat'}
                    </button>
                    <p className="text-[10px] leading-tight text-slate-500">
                        {posisi
                            ? `${vanueTerurut.filter((vanue) => vanue.latitude !== null).length} venue diurutkan dari yang terdekat dengan lokasi Anda.`
                            : 'Gunakan lokasi Anda untuk mengurutkan venue dari yang terdekat.'}
                    </p>
                </div>
            )}

            {lokasiStatus === 'gagal' && (
                <div role="alert" className="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-2.5 text-xs text-amber-800">
                    Lokasi tidak dapat diakses. Izinkan akses lokasi di browser Anda, lalu coba lagi.
                </div>
            )}

            {vanues.length === 0 ? (
                <div className="rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-4 py-10 text-center">
                    <span className="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-emerald-50 text-emerald-500">
                        <StoreIcon className="h-5 w-5" />
                    </span>
                    <p className="mt-3 text-sm font-semibold text-slate-700">Belum ada venue</p>
                    <p className="mt-1 text-xs text-slate-500">Venue akan muncul di sini setelah ditambahkan admin.</p>
                </div>
            ) : (
                <div className="space-y-2.5">
                    {vanueTerurut.map((vanue) => (
                        <VenueCard
                            key={vanue.id}
                            vanue={vanue}
                            jumlahMeja={hitungMeja(vanue)}
                            jarakKm={jarakVanue(vanue)}
                            terdekat={posisi !== null && vanueTerurut[0]?.id === vanue.id && vanue.latitude !== null}
                            onPilih={onPilih}
                        />
                    ))}
                </div>
            )}
        </div>
    );
}
