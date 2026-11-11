import { router } from '@inertiajs/react';
import { useCallback, useEffect, useRef, useState } from 'react';

function ScanIcon({ className = 'h-5 w-5' }) {
    return (
        <svg xmlns="http://www.w3.org/2000/svg" className={className} fill="none" viewBox="0 0 24 24" strokeWidth={1.8} stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" d="M7.5 3.75H5.25A1.5 1.5 0 0 0 3.75 5.25V7.5M16.5 3.75h2.25a1.5 1.5 0 0 1 1.5 1.5V7.5M7.5 20.25H5.25a1.5 1.5 0 0 1-1.5-1.5V16.5M16.5 20.25h2.25a1.5 1.5 0 0 0 1.5-1.5V16.5M3.75 12h16.5" />
        </svg>
    );
}

function CloseIcon({ className = 'h-5 w-5' }) {
    return (
        <svg xmlns="http://www.w3.org/2000/svg" className={className} fill="none" viewBox="0 0 24 24" strokeWidth={2} stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" d="M6 18 18 6M6 6l12 12" />
        </svg>
    );
}

function ChevronRightIcon() {
    return (
        <svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4" fill="none" viewBox="0 0 24 24" strokeWidth={2} stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
        </svg>
    );
}

function FoodIcon({ className = 'h-5 w-5' }) {
    return (
        <svg xmlns="http://www.w3.org/2000/svg" className={className} fill="none" viewBox="0 0 24 24" strokeWidth={1.8} stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" d="M3.75 11.25h16.5a8.25 8.25 0 0 1-16.5 0ZM9 7.5c0-1.2 1.25-1.6 1.25-2.75M13.75 7.5c0-1.2 1.25-1.6 1.25-2.75" />
        </svg>
    );
}

function CornerBrackets() {
    return (
        <>
            <span className="absolute left-0 top-0 h-10 w-10 rounded-tl-2xl border-l-4 border-t-4 border-emerald-400" />
            <span className="absolute right-0 top-0 h-10 w-10 rounded-tr-2xl border-r-4 border-t-4 border-emerald-400" />
            <span className="absolute bottom-0 left-0 h-10 w-10 rounded-bl-2xl border-b-4 border-l-4 border-emerald-400" />
            <span className="absolute bottom-0 right-0 h-10 w-10 rounded-br-2xl border-b-4 border-r-4 border-emerald-400" />
        </>
    );
}

/**
 * Tombol scan QR di tengah bottom navigation beserta modal scan-nya.
 */
export default function ScanMejaButton() {
    const [scanOpen, setScanOpen] = useState(false);
    const [kodeManual, setKodeManual] = useState('');
    const [mencari, setMencari] = useState(false);
    const [galatPindai, setGalatPindai] = useState('');
    const [kameraSiap, setKameraSiap] = useState(false);
    const [dukungKamera, setDukungKamera] = useState(false);
    const [mejaTerpilih, setMejaTerpilih] = useState(null);

    const videoRef = useRef(null);
    const streamRef = useRef(null);
    const frameRef = useRef(null);
    const berhentiRef = useRef(true);
    const sedangCariRef = useRef(false);
    const kodeTerakhirRef = useRef({ kode: '', waktu: 0 });

    const hentikanKamera = useCallback(() => {
        berhentiRef.current = true;

        if (frameRef.current) {
            cancelAnimationFrame(frameRef.current);
            frameRef.current = null;
        }

        if (streamRef.current) {
            streamRef.current.getTracks().forEach((track) => track.stop());
            streamRef.current = null;
        }

        if (videoRef.current) {
            videoRef.current.srcObject = null;
        }

        setKameraSiap(false);
    }, []);

    const cariKode = useCallback(
        async (kode) => {
            const bersih = (kode ?? '').trim();

            if (bersih === '') {
                setGalatPindai('Masukkan kode meja terlebih dahulu.');
                return;
            }

            if (sedangCariRef.current) {
                return;
            }

            sedangCariRef.current = true;
            setMencari(true);
            setGalatPindai('');

            try {
                const response = await fetch(`/meja/cari?kode=${encodeURIComponent(bersih)}`, {
                    headers: { Accept: 'application/json' },
                });
                const hasil = await response.json();

                if (hasil.found) {
                    hentikanKamera();
                    setMejaTerpilih(hasil);
                    setKodeManual('');
                } else {
                    setGalatPindai(hasil.message || 'Kode meja tidak ditemukan.');
                }
            } catch {
                setGalatPindai('Gagal memeriksa kode meja. Periksa koneksi lalu coba lagi.');
            } finally {
                sedangCariRef.current = false;
                setMencari(false);
            }
        },
        [hentikanKamera],
    );

    const mulaiKamera = useCallback(async () => {
        const didukung =
            typeof navigator !== 'undefined' &&
            Boolean(navigator.mediaDevices?.getUserMedia) &&
            typeof window !== 'undefined' &&
            typeof window.BarcodeDetector !== 'undefined';

        if (!didukung) {
            setDukungKamera(false);
            return;
        }

        setDukungKamera(true);
        setGalatPindai('');
        berhentiRef.current = false;

        try {
            const stream = await navigator.mediaDevices.getUserMedia({
                video: { facingMode: 'environment' },
            });

            if (berhentiRef.current) {
                stream.getTracks().forEach((track) => track.stop());
                return;
            }

            streamRef.current = stream;

            if (videoRef.current) {
                videoRef.current.srcObject = stream;
                await videoRef.current.play().catch(() => {});
            }

            setKameraSiap(true);

            const detector = new window.BarcodeDetector({ formats: ['qr_code'] });

            const deteksi = async () => {
                if (berhentiRef.current) {
                    return;
                }

                if (videoRef.current && videoRef.current.readyState >= 2) {
                    try {
                        const kodeTerdeteksi = await detector.detect(videoRef.current);

                        if (kodeTerdeteksi.length > 0) {
                            const nilai = (kodeTerdeteksi[0].rawValue ?? '').trim();
                            const terakhir = kodeTerakhirRef.current;

                            if (nilai !== '' && (nilai !== terakhir.kode || Date.now() - terakhir.waktu > 3000)) {
                                kodeTerakhirRef.current = { kode: nilai, waktu: Date.now() };
                                await cariKode(nilai);
                            }
                        }
                    } catch {
                        // Abaikan frame yang gagal diproses, lanjut ke frame berikutnya.
                    }
                }

                if (!berhentiRef.current) {
                    frameRef.current = requestAnimationFrame(deteksi);
                }
            };

            frameRef.current = requestAnimationFrame(deteksi);
        } catch {
            setDukungKamera(false);
            setGalatPindai('Kamera tidak bisa diakses. Masukkan kode meja secara manual.');
        }
    }, [cariKode]);

    useEffect(() => {
        if (!scanOpen || mejaTerpilih) {
            hentikanKamera();
            return;
        }

        mulaiKamera();

        return () => hentikanKamera();
    }, [scanOpen, mejaTerpilih, mulaiKamera, hentikanKamera]);

    const bukaScan = () => {
        setMejaTerpilih(null);
        setKodeManual('');
        setGalatPindai('');
        setDukungKamera(false);
        setScanOpen(true);
    };

    const tutupScan = () => {
        setScanOpen(false);
        setMejaTerpilih(null);
        setKodeManual('');
        setGalatPindai('');
    };

    const pindaiLagi = () => {
        setMejaTerpilih(null);
        setGalatPindai('');
        setKodeManual('');
        kodeTerakhirRef.current = { kode: '', waktu: 0 };
    };

    const kirimKodeManual = (event) => {
        event.preventDefault();
        cariKode(kodeManual);
    };

    return (
        <>
            <button
                type="button"
                onClick={bukaScan}
                aria-label="Scan QR Code Meja"
                className="group relative -mt-6 flex flex-col items-center gap-1 active:scale-95"
            >
                <span className="flex h-14 w-14 items-center justify-center rounded-full bg-gradient-to-br from-emerald-500 to-emerald-700 text-white shadow-xl shadow-emerald-600/45 ring-4 ring-[#f6faf7] transition duration-200 group-hover:scale-105">
                    <ScanIcon className="h-6 w-6" />
                </span>
                <span className="text-[10px] font-semibold leading-none tracking-wide text-emerald-700">
                    Scan
                </span>
            </button>

            {scanOpen && (
                <div className="fixed inset-0 z-[60] overflow-hidden bg-black">
                    {mejaTerpilih ? (
                        <div className="absolute inset-0 flex items-end justify-center bg-slate-900/70 backdrop-blur-sm sm:items-center sm:p-4">
                            <div className="max-h-[92vh] w-full max-w-md overflow-y-auto rounded-t-3xl bg-white p-5 shadow-2xl sm:rounded-3xl">
                                <div className="mb-4 flex items-start justify-between gap-3">
                                    <div>
                                        <h2 className="text-base font-bold text-slate-900">Meja Ditemukan</h2>
                                        <p className="mt-0.5 text-xs text-slate-500">
                                            Pilih tindakan yang ingin kamu lakukan.
                                        </p>
                                    </div>
                                    <button
                                        type="button"
                                        onClick={tutupScan}
                                        aria-label="Tutup"
                                        className="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-slate-100 text-slate-500 transition hover:bg-slate-200"
                                    >
                                        <CloseIcon />
                                    </button>
                                </div>

                                <div className="space-y-4">
                                    <div className="rounded-2xl border border-emerald-100 bg-emerald-50 p-4 text-center">
                                        <span className="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-emerald-600 text-white">
                                            <svg xmlns="http://www.w3.org/2000/svg" className="h-6 w-6" fill="none" viewBox="0 0 24 24" strokeWidth={2.4} stroke="currentColor">
                                                <path strokeLinecap="round" strokeLinejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                            </svg>
                                        </span>
                                        <p className="mt-2 text-sm font-bold text-emerald-900">
                                            {mejaTerpilih.meja?.nama_meja}
                                        </p>
                                        <p className="mt-0.5 inline-flex rounded-full bg-white px-2.5 py-0.5 text-xs font-bold text-emerald-700 ring-1 ring-emerald-200">
                                            {mejaTerpilih.meja?.kode_meja}
                                        </p>
                                    </div>

                                    <div className="space-y-2.5">
                                        <button
                                            type="button"
                                            onClick={() => router.visit(mejaTerpilih.pesan_url)}
                                            className="flex w-full items-center gap-3 rounded-2xl bg-gradient-to-r from-amber-400 to-orange-500 px-4 py-3.5 text-left text-white shadow-lg shadow-orange-500/30 transition active:scale-[0.98]"
                                        >
                                            <span className="flex h-10 w-10 items-center justify-center rounded-xl bg-white/20">
                                                <FoodIcon className="h-5 w-5" />
                                            </span>
                                            <span className="flex-1">
                                                <span className="block text-sm font-bold">Pesan Makanan</span>
                                                <span className="mt-0.5 block text-[11px] text-white/85">
                                                    Nomor meja terisi otomatis
                                                </span>
                                            </span>
                                            <ChevronRightIcon />
                                        </button>

                                        <button
                                            type="button"
                                            onClick={() => router.visit(mejaTerpilih.point_url)}
                                            className="flex w-full items-center gap-3 rounded-2xl bg-gradient-to-r from-emerald-500 to-emerald-700 px-4 py-3.5 text-left text-white shadow-lg shadow-emerald-500/30 transition active:scale-[0.98]"
                                        >
                                            <span className="flex h-10 w-10 items-center justify-center rounded-xl bg-white/20">
                                                <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5" fill="none" viewBox="0 0 24 24" strokeWidth={1.8} stroke="currentColor">
                                                    <path strokeLinecap="round" strokeLinejoin="round" d="M12 6v12m3-9.75c0-1.243-1.343-2.25-3-2.25s-3 1.007-3 2.25S10.343 10.5 12 10.5s3 1.007 3 2.25-1.343 2.25-3 2.25-3-1.007-3-2.25" />
                                                </svg>
                                            </span>
                                            <span className="flex-1">
                                                <span className="block text-sm font-bold">Tambah Point</span>
                                                <span className="mt-0.5 block text-[11px] text-white/85">
                                                    Scan kartu mahjong untuk point
                                                </span>
                                            </span>
                                            <ChevronRightIcon />
                                        </button>
                                    </div>

                                    <button
                                        type="button"
                                        onClick={pindaiLagi}
                                        className="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-bold text-slate-600 transition hover:bg-slate-50"
                                    >
                                        Scan Ulang
                                    </button>
                                </div>
                            </div>
                        </div>
                    ) : (
                        <>
                            <video
                                ref={videoRef}
                                playsInline
                                muted
                                autoPlay
                                className={`absolute inset-0 h-full w-full object-cover ${kameraSiap ? 'opacity-100' : 'opacity-0'}`}
                            />

                            {!kameraSiap && (
                                <div className="absolute inset-0 flex flex-col items-center justify-center gap-3 bg-slate-950 px-8 text-center">
                                    <ScanIcon className="h-14 w-14 text-slate-600" />
                                    <p className="text-sm font-semibold text-white">Kamera tidak tersedia</p>
                                    <p className="max-w-xs text-xs leading-relaxed text-slate-400">
                                        {dukungKamera
                                            ? 'Menyiapkan kamera...'
                                            : 'Izinkan akses kamera atau masukkan kode meja secara manual di bawah.'}
                                    </p>
                                </div>
                            )}

                            <div className="pointer-events-none absolute inset-0 bg-gradient-to-b from-black/70 via-transparent to-black/80" />

                            <div className="absolute inset-x-0 top-0 flex items-center justify-between gap-3 px-4 pt-[max(1rem,env(safe-area-inset-top))]">
                                <button
                                    type="button"
                                    onClick={tutupScan}
                                    aria-label="Tutup"
                                    className="flex h-10 w-10 items-center justify-center rounded-full bg-white/15 text-white ring-1 ring-white/25 backdrop-blur-md transition hover:bg-white/25"
                                >
                                    <CloseIcon />
                                </button>
                                <div className="text-center">
                                    <p className="text-sm font-bold text-white">Scan QR Meja</p>
                                    <p className="text-[11px] text-white/70">Arahkan kamera ke QR code di meja</p>
                                </div>
                                <span className="h-10 w-10" />
                            </div>

                            <div className="pointer-events-none absolute inset-0 flex items-center justify-center px-8">
                                <div className="relative h-64 w-64 max-h-[45vh] max-w-[70vw]">
                                    <CornerBrackets />
                                    <span
                                        className={`absolute inset-x-3 top-1/2 h-0.5 -translate-y-1/2 bg-emerald-400 shadow-[0_0_14px_3px_rgba(52,211,153,0.75)] ${kameraSiap ? 'animate-pulse' : ''}`}
                                    />
                                </div>
                            </div>

                            <div className="absolute inset-x-0 bottom-0 space-y-3 px-4 pb-[max(1rem,env(safe-area-inset-bottom))] pt-4">
                                {galatPindai && (
                                    <p className="rounded-xl bg-rose-500/15 px-3 py-2 text-xs font-medium text-rose-200 ring-1 ring-rose-400/40 backdrop-blur-md">
                                        {galatPindai}
                                    </p>
                                )}

                                <div className="rounded-2xl bg-black/45 p-3 ring-1 ring-white/10 backdrop-blur-md">
                                    <p className="mb-2 text-center text-[11px] font-semibold uppercase tracking-wide text-white/60">
                                        atau masukkan kode manual
                                    </p>
                                    <form onSubmit={kirimKodeManual} className="flex items-center gap-2">
                                        <input
                                            type="text"
                                            value={kodeManual}
                                            onChange={(event) => setKodeManual(event.target.value)}
                                            placeholder="Contoh: MJ-01"
                                            maxLength={30}
                                            autoCapitalize="characters"
                                            className="min-w-0 flex-1 rounded-xl border border-white/15 bg-white/10 px-3.5 py-2.5 text-sm font-semibold uppercase tracking-wide text-white transition placeholder:font-normal placeholder:normal-case placeholder:tracking-normal placeholder:text-white/40 focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-400/30"
                                        />
                                        <button
                                            type="submit"
                                            disabled={mencari}
                                            className="shrink-0 rounded-xl bg-emerald-500 px-4 py-2.5 text-sm font-bold text-white shadow-md shadow-emerald-500/30 transition hover:bg-emerald-600 disabled:cursor-not-allowed disabled:opacity-60"
                                        >
                                            {mencari ? 'Memeriksa...' : 'Cek'}
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </>
                    )}
                </div>
            )}
        </>
    );
}
