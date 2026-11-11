import { Head, Link, router, usePage } from '@inertiajs/react';
import { useEffect, useMemo, useRef, useState } from 'react';
import UserLayout from '../../Layouts/UserLayout';

const GRUP_KARTU = [
    { key: 'w', nama: 'Wan', warna: 'bg-rose-50 text-rose-700 ring-rose-200' },
    { key: 't', nama: 'Tong', warna: 'bg-sky-50 text-sky-700 ring-sky-200' },
    { key: 'b', nama: 'Bambu', warna: 'bg-emerald-50 text-emerald-700 ring-emerald-200' },
    { key: 'z', nama: 'Honor', warna: 'bg-violet-50 text-violet-700 ring-violet-200' },
];

const WARNA_TIPE = {
    chow: 'bg-sky-50 text-sky-700 ring-sky-200',
    pung: 'bg-emerald-50 text-emerald-700 ring-emerald-200',
    kong: 'bg-amber-50 text-amber-700 ring-amber-200',
    pair: 'bg-violet-50 text-violet-700 ring-violet-200',
    special: 'bg-fuchsia-50 text-fuchsia-700 ring-fuchsia-200',
};

function formatTanggal(value) {
    if (!value) {
        return '-';
    }

    return new Intl.DateTimeFormat('id-ID', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(value));
}

function warnaKartu(code) {
    return GRUP_KARTU.find((grup) => grup.key === code?.[0])?.warna ?? 'bg-slate-100 text-slate-600 ring-slate-200';
}

function ScanIcon({ className = 'h-5 w-5' }) {
    return (
        <svg xmlns="http://www.w3.org/2000/svg" className={className} fill="none" viewBox="0 0 24 24" strokeWidth={1.8} stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" d="M6.827 6.175A2.31 2.31 0 0 1 5.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 0 0-1.134-.175 2.31 2.31 0 0 1-1.64-1.055l-.822-1.316a2.192 2.192 0 0 0-1.736-1.039 48.774 48.774 0 0 0-5.232 0 2.192 2.192 0 0 0-1.736 1.039l-.821 1.316Z" />
            <path strokeLinecap="round" strokeLinejoin="round" d="M16.5 12.75a4.5 4.5 0 1 1-9 0 4.5 4.5 0 0 1 9 0Z" />
        </svg>
    );
}

function StarIcon({ className = 'h-5 w-5' }) {
    return (
        <svg xmlns="http://www.w3.org/2000/svg" className={className} fill="none" viewBox="0 0 24 24" strokeWidth={1.8} stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" d="M11.48 3.499a.562.562 0 0 1 1.04 0l2.125 5.111a.563.563 0 0 0 .475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 0 0-.182.557l1.285 5.385a.562.562 0 0 1-.84.61l-4.725-2.885a.562.562 0 0 0-.586 0L6.982 20.54a.562.562 0 0 1-.84-.61l1.285-5.386a.562.562 0 0 0-.182-.557l-4.204-3.602a.562.562 0 0 1 .321-.988l5.518-.442a.563.563 0 0 0 .475-.345L11.48 3.5Z" />
        </svg>
    );
}

function KartuChip({ code, label, onClick, aktif = true }) {
    return (
        <button
            type="button"
            onClick={onClick}
            className={`rounded-lg px-2 py-1 text-[10px] font-bold ring-1 transition ${warnaKartu(code)} ${
                aktif ? 'hover:scale-105' : 'opacity-40'
            }`}
        >
            {label}
        </button>
    );
}

export default function Point({
    pengguna,
    points = 0,
    pendingScan = null,
    riwayat = [],
    kartu = [],
    pointPerHand = 10,
    scoringModes = [],
    hkMinFaan = 3,
    hkMaxFaan = 13,
}) {
    const { flash, errors } = usePage().props;
    const fileRef = useRef(null);
    const [preview, setPreview] = useState(null);
    const [mengunggah, setMengunggah] = useState(false);
    const [tiles, setTiles] = useState(pendingScan?.tiles ?? []);
    const [mode, setMode] = useState(pendingScan?.scoring_mode ?? 'chicken');
    const [seatWind, setSeatWind] = useState(pendingScan?.seat_wind ?? '');
    const [roundWind, setRoundWind] = useState(pendingScan?.round_wind ?? '');
    const [selfDraw, setSelfDraw] = useState(Boolean(pendingScan?.is_self_draw));
    const [concealed, setConcealed] = useState(Boolean(pendingScan?.is_concealed));
    const [konfirmasiTerbuka, setKonfirmasiTerbuka] = useState(false);

    useEffect(() => {
        setTiles(pendingScan?.tiles ?? []);
        setMode(pendingScan?.scoring_mode ?? 'chicken');
        setSeatWind(pendingScan?.seat_wind ?? '');
        setRoundWind(pendingScan?.round_wind ?? '');
        setSelfDraw(Boolean(pendingScan?.is_self_draw));
        setConcealed(Boolean(pendingScan?.is_concealed));
    }, [
        pendingScan?.id,
        pendingScan?.tiles,
        pendingScan?.scoring_mode,
        pendingScan?.seat_wind,
        pendingScan?.round_wind,
        pendingScan?.is_self_draw,
        pendingScan?.is_concealed,
    ]);

    useEffect(() => () => {
        if (preview) {
            URL.revokeObjectURL(preview);
        }
    }, [preview]);

    const labelKartu = useMemo(
        () => Object.fromEntries(kartu.map((item) => [item.code, item.label])),
        [kartu],
    );

    const grupKartu = useMemo(
        () => GRUP_KARTU.map((grup) => ({
            ...grup,
            items: kartu.filter((item) => item.code.startsWith(grup.key)),
        })),
        [kartu],
    );

    const dataOpsi = (override = {}) => ({
        seat_wind: override.seatWind ?? seatWind ?? '',
        round_wind: override.roundWind ?? roundWind ?? '',
        self_draw: (override.selfDraw ?? selfDraw) ? 1 : 0,
        concealed: (override.concealed ?? concealed) ? 1 : 0,
    });

    const pilihFoto = (event) => {
        const file = event.target.files?.[0];

        if (!file) {
            return;
        }

        setPreview(URL.createObjectURL(file));
        setMengunggah(true);

        const formData = new FormData();
        formData.append('image', file);
        formData.append('mode', mode);

        Object.entries(dataOpsi()).forEach(([kunci, nilai]) => formData.append(kunci, nilai));

        router.post('/point/scan', formData, {
            forceFormData: true,
            preserveScroll: true,
            onFinish: () => {
                setMengunggah(false);
                event.target.value = '';
            },
        });
    };

    const tambahKartu = (code) => {
        setTiles((sebelumnya) => [...sebelumnya, code]);
    };

    const hapusKartu = (index) => {
        setTiles((sebelumnya) => sebelumnya.filter((_, i) => i !== index));
    };

    const hitungUlang = () => {
        if (!pendingScan) {
            return;
        }

        router.post(`/point/scan/${pendingScan.id}/kartu`, { tiles, mode, ...dataOpsi() }, { preserveScroll: true });
    };

    const gantiMode = (modeBaru) => {
        if (modeBaru === mode) {
            return;
        }

        setMode(modeBaru);

        if (pendingScan) {
            router.post(
                `/point/scan/${pendingScan.id}/kartu`,
                { tiles, mode: modeBaru, ...dataOpsi() },
                { preserveScroll: true },
            );
        }
    };

    const ubahOpsi = (override) => {
        if ('seatWind' in override) {
            setSeatWind(override.seatWind);
        }

        if ('roundWind' in override) {
            setRoundWind(override.roundWind);
        }

        if ('selfDraw' in override) {
            setSelfDraw(override.selfDraw);
        }

        if ('concealed' in override) {
            setConcealed(override.concealed);
        }

        if (!pendingScan) {
            return;
        }

        router.post(
            `/point/scan/${pendingScan.id}/kartu`,
            { tiles, mode, ...dataOpsi(override) },
            { preserveScroll: true },
        );
    };

    const klaimPoint = () => {
        if (!pendingScan) {
            return;
        }

        router.post(`/point/scan/${pendingScan.id}/konfirmasi`, {}, {
            preserveScroll: true,
            onSuccess: () => setKonfirmasiTerbuka(false),
        });
    };

    const batalkanScan = () => {
        if (!pendingScan) {
            return;
        }

        router.delete(`/point/scan/${pendingScan.id}`, { preserveScroll: true });
    };

    return (
        <>
            <Head title="Point Mahjong" />

            <header className="relative overflow-hidden rounded-b-[2rem] bg-gradient-to-br from-emerald-600 to-emerald-800 px-5 pb-14 pt-6 text-white">
                <div className="pointer-events-none absolute -right-10 -top-12 h-40 w-40 rounded-full bg-white/10" />
                <div className="pointer-events-none absolute -bottom-16 -left-10 h-40 w-40 rounded-full bg-white/5" />
                <div className="relative">
                    <Link href="/profil" className="inline-flex items-center gap-1.5 text-xs font-semibold text-emerald-100 transition hover:text-white">
                        <svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4" fill="none" viewBox="0 0 24 24" strokeWidth={2} stroke="currentColor">
                            <path strokeLinecap="round" strokeLinejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
                        </svg>
                        Profil
                    </Link>

                    <div className="mt-4 flex items-end justify-between gap-3">
                        <div>
                            <p className="text-xs font-medium uppercase tracking-wide text-emerald-100">Total Point Mahjong</p>
                            <p className="mt-1 text-4xl font-black leading-none">{points}</p>
                            <p className="mt-1.5 text-[11px] text-emerald-100">Point terkumpul dari winning hand</p>
                        </div>
                        <span className="flex h-16 w-16 items-center justify-center rounded-2xl bg-white/15 ring-4 ring-white/10">
                            <StarIcon className="h-8 w-8" />
                        </span>
                    </div>
                </div>
            </header>

            <div className="relative -mt-8 space-y-4 px-5 pb-8">
                {flash?.success && (
                    <div className="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-xs font-medium leading-relaxed text-emerald-700">
                        {flash.success}
                    </div>
                )}

                {flash?.error && (
                    <div className="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-xs font-medium leading-relaxed text-rose-700">
                        {flash.error}
                    </div>
                )}

                {errors?.image && (
                    <div className="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-xs font-medium leading-relaxed text-rose-700">
                        {errors.image}
                    </div>
                )}

                <section className="overflow-hidden rounded-2xl border border-emerald-100 bg-white shadow-sm">
                    <div className="flex items-start gap-3 px-4 pt-4">
                        <span className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600">
                            <ScanIcon />
                        </span>
                        <div>
                            <h2 className="text-sm font-bold text-slate-800">Scan Susunan Mahjong</h2>
                            <p className="mt-1 text-xs leading-relaxed text-slate-500">
                                {mode === 'hk'
                                    ? `Foto susunan kartu. AI (Gemini) membaca kartu lalu menghitung faan HK klasik (${hkMinFaan}-${hkMaxFaan} faan). Faan menjadi point.`
                                    : `Foto susunan kartu. AI (Gemini) mendeteksi kartu dan memvalidasi chicken hand (4 meld + 1 pair atau 7 pasang). Setiap chicken hand yang benar bernilai ${pointPerHand} point.`}
                            </p>
                        </div>
                    </div>

                    <div className="px-4 pt-4">
                        <p className="text-[10px] font-semibold uppercase tracking-wide text-slate-400">Pilih sistem penilaian</p>
                        <div className="mt-2 grid grid-cols-2 gap-2">
                            {scoringModes.map((item) => (
                                <button
                                    key={item.value}
                                    type="button"
                                    onClick={() => gantiMode(item.value)}
                                    className={`rounded-xl border px-3 py-2.5 text-left transition ${
                                        mode === item.value
                                            ? 'border-emerald-500 bg-emerald-50 ring-2 ring-emerald-200'
                                            : 'border-slate-200 bg-white hover:border-emerald-200'
                                    }`}
                                >
                                    <span className={`block text-xs font-bold ${mode === item.value ? 'text-emerald-700' : 'text-slate-700'}`}>
                                        {item.label}
                                    </span>
                                    <span className="mt-0.5 block text-[10px] leading-snug text-slate-500">{item.description}</span>
                                </button>
                            ))}
                        </div>
                    </div>

                    <div className="px-4 pb-4 pt-3">
                        <input
                            ref={fileRef}
                            type="file"
                            accept="image/*"
                            capture="environment"
                            onChange={pilihFoto}
                            className="hidden"
                        />
                        <button
                            type="button"
                            onClick={() => fileRef.current?.click()}
                            disabled={mengunggah}
                            className="flex w-full items-center justify-center gap-2 rounded-xl bg-emerald-600 px-4 py-3 text-xs font-bold text-white shadow-sm transition hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-60"
                        >
                            <ScanIcon className="h-4 w-4" />
                            {mengunggah ? 'Memindai...' : pendingScan ? 'Scan Ulang' : 'Buka Kamera / Pilih Foto'}
                        </button>
                        <p className="mt-2 text-center text-[10px] leading-relaxed text-slate-400">
                            Pastikan seluruh kartu terlihat jelas, tidak terpotong, dan pencahayaan cukup.
                        </p>
                    </div>
                </section>

                {pendingScan && (
                    <section className="overflow-hidden rounded-2xl border border-emerald-100 bg-white shadow-sm">
                        <div className="flex items-center justify-between gap-2 border-b border-emerald-50 px-4 py-3">
                            <div>
                                <h2 className="text-sm font-bold text-slate-800">Hasil Scan</h2>
                                <p className="mt-0.5 text-[10px] text-slate-400">
                                    {pendingScan.scoring_mode === 'hk' ? 'HK Klasik 3-13 faan' : 'Chicken Hand 10 point'} · Periksa dan setujui sebelum point masuk.
                                </p>
                            </div>
                            <span className={`rounded-full px-2.5 py-1 text-[10px] font-bold ring-1 ${
                                pendingScan.is_winning_hand
                                    ? 'bg-emerald-50 text-emerald-700 ring-emerald-200'
                                    : 'bg-amber-50 text-amber-700 ring-amber-200'
                            }`}>
                                {pendingScan.is_winning_hand ? 'Winning Hand' : 'Belum Winning'}
                            </span>
                        </div>

                        <div className="space-y-4 px-4 py-4">
                            {(preview || pendingScan.image_url) && (
                                <img
                                    src={preview || pendingScan.image_url}
                                    alt="Foto susunan mahjong"
                                    className="h-40 w-full rounded-xl border border-emerald-100 object-cover"
                                />
                            )}

                            <div>
                                <p className="text-[10px] font-semibold uppercase tracking-wide text-slate-400">
                                    Kartu terdeteksi ({tiles.length})
                                </p>
                                {tiles.length > 0 ? (
                                    <div className="mt-2 flex flex-wrap gap-1.5">
                                        {tiles.map((code, index) => (
                                            <span
                                                key={`${code}-${index}`}
                                                className={`inline-flex items-center gap-1 rounded-lg px-2 py-1 text-[10px] font-bold ring-1 ${warnaKartu(code)}`}
                                            >
                                                {labelKartu[code] ?? code}
                                                <button
                                                    type="button"
                                                    onClick={() => hapusKartu(index)}
                                                    className="rounded-full text-current/70 transition hover:text-rose-600"
                                                    aria-label={`Hapus ${labelKartu[code] ?? code}`}
                                                >
                                                    <svg xmlns="http://www.w3.org/2000/svg" className="h-3 w-3" fill="none" viewBox="0 0 24 24" strokeWidth={2.5} stroke="currentColor">
                                                        <path strokeLinecap="round" strokeLinejoin="round" d="M6 18 18 6M6 6l12 12" />
                                                    </svg>
                                                </button>
                                            </span>
                                        ))}
                                    </div>
                                ) : (
                                    <p className="mt-2 text-xs text-slate-500">
                                        Belum ada kartu terdeteksi. Tambahkan kartu secara manual di bawah.
                                    </p>
                                )}
                            </div>

                            {pendingScan.combinations.length > 0 && (
                                <div>
                                    <p className="text-[10px] font-semibold uppercase tracking-wide text-slate-400">Kombinasi</p>
                                    <div className="mt-2 space-y-1.5">
                                        {pendingScan.combinations.map((kombinasi, index) => (
                                            <div
                                                key={`${kombinasi.type}-${index}`}
                                                className="flex items-center justify-between gap-2 rounded-xl border border-slate-100 bg-slate-50/60 px-3 py-2"
                                            >
                                                <span className={`rounded-full px-2 py-0.5 text-[9px] font-bold uppercase tracking-wide ring-1 ${WARNA_TIPE[kombinasi.type] ?? 'bg-slate-100 text-slate-600 ring-slate-200'}`}>
                                                    {kombinasi.type}
                                                </span>
                                                <span className="flex-1 text-right text-xs font-semibold text-slate-700">{kombinasi.label}</span>
                                            </div>
                                        ))}
                                    </div>
                                </div>
                            )}

                            {pendingScan.unmatched?.length > 0 && (
                                <div className="rounded-xl border border-amber-100 bg-amber-50/60 px-3 py-2">
                                    <p className="text-[10px] font-semibold uppercase tracking-wide text-amber-600">Kartu belum cocok</p>
                                    <div className="mt-1.5 flex flex-wrap gap-1">
                                        {pendingScan.unmatched.map((code, index) => (
                                            <span key={`${code}-u-${index}`} className={`rounded px-1.5 py-0.5 text-[9px] font-bold ring-1 ${warnaKartu(code)}`}>
                                                {labelKartu[code] ?? code}
                                            </span>
                                        ))}
                                    </div>
                                </div>
                            )}

                            {pendingScan.scoring_mode === 'hk' && (
                                <div className="rounded-xl border border-fuchsia-100 bg-fuchsia-50/40 p-3">
                                    <p className="text-[10px] font-semibold uppercase tracking-wide text-fuchsia-700">Penanda manual HK</p>
                                    <p className="mt-0.5 text-[10px] text-slate-500">Tandai kondisi permainan agar faan dihitung tepat.</p>

                                    <div className="mt-2.5 grid grid-cols-2 gap-2">
                                        <label className="block">
                                            <span className="text-[10px] font-semibold text-slate-500">Angin kursi</span>
                                            <select
                                                value={seatWind}
                                                onChange={(event) => ubahOpsi({ seatWind: event.target.value })}
                                                className="mt-1 w-full rounded-lg border border-fuchsia-200 bg-white px-2 py-1.5 text-[11px] font-semibold text-slate-700"
                                            >
                                                <option value="">Tidak dipilih</option>
                                                <option value="z1">Timur</option>
                                                <option value="z2">Selatan</option>
                                                <option value="z3">Barat</option>
                                                <option value="z4">Utara</option>
                                            </select>
                                        </label>
                                        <label className="block">
                                            <span className="text-[10px] font-semibold text-slate-500">Angin meja</span>
                                            <select
                                                value={roundWind}
                                                onChange={(event) => ubahOpsi({ roundWind: event.target.value })}
                                                className="mt-1 w-full rounded-lg border border-fuchsia-200 bg-white px-2 py-1.5 text-[11px] font-semibold text-slate-700"
                                            >
                                                <option value="">Tidak dipilih</option>
                                                <option value="z1">Timur</option>
                                                <option value="z2">Selatan</option>
                                                <option value="z3">Barat</option>
                                                <option value="z4">Utara</option>
                                            </select>
                                        </label>
                                    </div>

                                    <label className="mt-2.5 flex items-center gap-2 rounded-lg bg-white px-2.5 py-2 ring-1 ring-fuchsia-100">
                                        <input
                                            type="checkbox"
                                            checked={selfDraw}
                                            onChange={(event) => ubahOpsi({ selfDraw: event.target.checked })}
                                            className="h-4 w-4 accent-fuchsia-600"
                                        />
                                        <span className="flex-1 text-[11px] font-semibold text-slate-700">
                                            Menang sendiri (自摸)
                                        </span>
                                        <span className="text-[10px] font-bold text-fuchsia-700">+1 faan</span>
                                    </label>

                                    <label className="mt-1.5 flex items-center gap-2 rounded-lg bg-white px-2.5 py-2 ring-1 ring-fuchsia-100">
                                        <input
                                            type="checkbox"
                                            checked={concealed}
                                            onChange={(event) => ubahOpsi({ concealed: event.target.checked })}
                                            className="h-4 w-4 accent-fuchsia-600"
                                        />
                                        <span className="flex-1 text-[11px] font-semibold text-slate-700">
                                            Kartu tertutup (門前清)
                                        </span>
                                        <span className="text-[10px] font-bold text-fuchsia-700">+1 faan</span>
                                    </label>

                                    <p className="mt-1.5 text-[10px] leading-relaxed text-slate-400">
                                        Pung angin dihitung jika cocok dengan angin kursi/meja. Semua meld pung + kartu
                                        tertutup menjadi 四暗刻 (13 faan).
                                    </p>
                                </div>
                            )}

                            {pendingScan.scoring_mode === 'hk' && pendingScan.faan_details?.length > 0 && (
                                <div>
                                    <p className="text-[10px] font-semibold uppercase tracking-wide text-slate-400">Rincian Faan</p>
                                    <div className="mt-2 space-y-1.5">
                                        {pendingScan.faan_details.map((detail, index) => (
                                            <div
                                                key={`${detail.label}-${index}`}
                                                className="flex items-center justify-between gap-2 rounded-xl border border-fuchsia-100 bg-fuchsia-50/50 px-3 py-2"
                                            >
                                                <span className="text-xs font-semibold text-slate-700">{detail.label}</span>
                                                <span className="rounded-full bg-white px-2 py-0.5 text-[10px] font-bold text-fuchsia-700 ring-1 ring-fuchsia-200">
                                                    +{detail.faan} faan
                                                </span>
                                            </div>
                                        ))}
                                        <div className="flex items-center justify-between rounded-xl bg-fuchsia-600 px-3 py-2 text-white">
                                            <span className="text-xs font-bold">Total Faan</span>
                                            <span className="text-sm font-black">{pendingScan.faan} faan</span>
                                        </div>
                                    </div>
                                </div>
                            )}

                            <div className={`flex items-center justify-between rounded-xl px-3 py-2.5 ring-1 ${
                                pendingScan.is_winning_hand
                                    ? 'bg-emerald-50 text-emerald-700 ring-emerald-200'
                                    : 'bg-slate-50 text-slate-600 ring-slate-200'
                            }`}>
                                <span className="text-xs font-semibold">{pendingScan.message}</span>
                                <span className="text-sm font-black">
                                    {pendingScan.scoring_mode === 'hk'
                                        ? `${pendingScan.points} point`
                                        : `+${pendingScan.points}`}
                                </span>
                            </div>

                            <div className="rounded-xl border border-dashed border-emerald-200 bg-emerald-50/40 p-3">
                                <p className="text-[10px] font-semibold uppercase tracking-wide text-emerald-700">Tambah / koreksi kartu</p>
                                <p className="mt-0.5 text-[10px] text-slate-500">Ketuk kartu untuk menambahkan jika hasil deteksi kurang tepat.</p>

                                <div className="mt-2.5 space-y-2.5">
                                    {grupKartu.map((grup) => (
                                        <div key={grup.key}>
                                            <p className="text-[9px] font-bold uppercase tracking-wide text-slate-400">{grup.nama}</p>
                                            <div className="mt-1 flex flex-wrap gap-1">
                                                {grup.items.map((item) => (
                                                    <KartuChip
                                                        key={item.code}
                                                        code={item.code}
                                                        label={item.label}
                                                        onClick={() => tambahKartu(item.code)}
                                                    />
                                                ))}
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </div>

                            <div className="flex gap-2">
                                <button
                                    type="button"
                                    onClick={batalkanScan}
                                    className="flex-1 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-bold text-slate-600 transition hover:bg-slate-50"
                                >
                                    Batal
                                </button>
                                <button
                                    type="button"
                                    onClick={hitungUlang}
                                    className="flex-1 rounded-xl border border-emerald-200 bg-white px-4 py-2.5 text-xs font-bold text-emerald-700 transition hover:bg-emerald-50"
                                >
                                    Hitung Ulang
                                </button>
                                <button
                                    type="button"
                                    onClick={() => setKonfirmasiTerbuka(true)}
                                    disabled={!pendingScan.is_winning_hand}
                                    className="flex-[1.2] rounded-xl bg-emerald-600 px-4 py-2.5 text-xs font-bold text-white shadow-sm transition hover:bg-emerald-700 disabled:cursor-not-allowed disabled:bg-slate-300"
                                >
                                    Konfirmasi
                                </button>
                            </div>
                        </div>
                    </section>
                )}

                <section className="rounded-2xl border border-emerald-100 bg-white p-4 shadow-sm">
                    <div className="flex items-center justify-between">
                        <h2 className="text-sm font-bold text-slate-800">Riwayat Point</h2>
                        <span className="rounded-full bg-emerald-50 px-2.5 py-1 text-[10px] font-bold text-emerald-700 ring-1 ring-emerald-100">
                            {points} point
                        </span>
                    </div>

                    {riwayat.length === 0 ? (
                        <p className="mt-3 text-xs leading-relaxed text-slate-500">
                            Belum ada winning hand yang dikonfirmasi. Scan susunan mahjong pertamamu untuk mulai mengumpulkan point.
                        </p>
                    ) : (
                        <div className="mt-3 space-y-2">
                            {riwayat.map((item) => (
                                <div key={item.id} className="flex items-center gap-3 rounded-xl border border-slate-100 bg-slate-50/60 px-3 py-2.5">
                                    {item.image_url ? (
                                        <img src={item.image_url} alt="Scan mahjong" className="h-10 w-10 shrink-0 rounded-lg object-cover" />
                                    ) : (
                                        <span className="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-emerald-100 text-emerald-600">
                                            <StarIcon className="h-5 w-5" />
                                        </span>
                                    )}
                                    <div className="min-w-0 flex-1">
                                        <p className="truncate text-xs font-semibold text-slate-700">
                                            {item.combinations?.map((kombinasi) => kombinasi.type).join(' · ') || 'Winning hand'}
                                        </p>
                                        <p className="text-[10px] text-slate-400">
                                            {item.scoring_mode === 'hk' ? `HK Klasik · ${item.faan ?? item.points} faan` : 'Chicken Hand'}
                                            {' · '}
                                            {formatTanggal(item.confirmed_at)}
                                        </p>
                                    </div>
                                    <span className="text-sm font-black text-emerald-700">+{item.points}</span>
                                </div>
                            ))}
                        </div>
                    )}
                </section>
            </div>

            {konfirmasiTerbuka && pendingScan && (
                <div className="fixed inset-0 z-50 flex items-end justify-center bg-slate-900/50 p-4 backdrop-blur-sm sm:items-center">
                    <div className="w-full max-w-md rounded-2xl bg-white p-5 shadow-2xl">
                        <div className="flex items-start gap-3">
                            <span className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-emerald-50 text-emerald-600">
                                <StarIcon className="h-5 w-5" />
                            </span>
                            <div>
                                <h2 className="text-sm font-bold text-slate-900">Konfirmasi hasil scan?</h2>
                                <p className="mt-1 text-xs leading-relaxed text-slate-500">
                                    {pendingScan.scoring_mode === 'hk' ? (
                                        <>
                                            Hasil HK klasik sebesar{' '}
                                            <span className="font-bold text-emerald-700">{pendingScan.faan} faan</span>{' '}
                                            akan menambah <span className="font-bold text-emerald-700">{pendingScan.points} point</span>{' '}
                                            ke akunmu setelah kamu menyetujui susunan ini.
                                        </>
                                    ) : (
                                        <>
                                            Point sebesar{' '}
                                            <span className="font-bold text-emerald-700">{pendingScan.points} point</span> akan
                                            ditambahkan ke akunmu setelah kamu menyetujui susunan ini.
                                        </>
                                    )}
                                </p>
                            </div>
                        </div>

                        <div className="mt-5 flex gap-2">
                            <button
                                type="button"
                                onClick={() => setKonfirmasiTerbuka(false)}
                                className="flex-1 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-bold text-slate-600 transition hover:bg-slate-50"
                            >
                                Periksa Lagi
                            </button>
                            <button
                                type="button"
                                onClick={klaimPoint}
                                className="flex-1 rounded-xl bg-emerald-600 px-4 py-2.5 text-xs font-bold text-white transition hover:bg-emerald-700"
                            >
                                Ya, Klaim Point
                            </button>
                        </div>
                    </div>
                </div>
            )}
        </>
    );
}

Point.layout = (page) => <UserLayout>{page}</UserLayout>;
