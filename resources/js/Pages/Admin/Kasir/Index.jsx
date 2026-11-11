import { Head, Link, router, usePage } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import AdminLayout from '../../../Layouts/AdminLayout';

function formatRupiah(value) {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(Number(value) || 0);
}

function formatWaktu(value) {
    if (!value) {
        return '-';
    }

    return new Intl.DateTimeFormat('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    }).format(new Date(value));
}

const LEBAR_STRUK = 32;

const UUID_LAYANAN_PRINTER = [
    '000018f0-0000-1000-8000-00805f9b34fb',
    '0000ff00-0000-1000-8000-00805f9b34fb',
    '0000ffe0-0000-1000-8000-00805f9b34fb',
    '0000fff0-0000-1000-8000-00805f9b34fb',
    '0000fee7-0000-1000-8000-00805f9b34fb',
    '49535343-fe7d-4ae5-8fa9-9fafd205e455',
    'e7810a71-73ae-499d-8c15-faa9aef0c3f2',
];

function formatAngka(value) {
    return new Intl.NumberFormat('id-ID').format(Number(value) || 0);
}

function bungkusTeks(teks, lebar) {
    const kata = String(teks ?? '').trim().split(/\s+/).filter(Boolean);
    const baris = [];
    let aktif = '';

    kata.forEach((kataItem) => {
        if (aktif === '') {
            aktif = kataItem;

            return;
        }

        if (`${aktif} ${kataItem}`.length <= lebar) {
            aktif = `${aktif} ${kataItem}`;

            return;
        }

        baris.push(aktif);
        aktif = kataItem;
    });

    if (aktif !== '') {
        baris.push(aktif);
    }

    return baris.length > 0 ? baris : [''];
}

function barisKiriKanan(kiri, kanan, lebar = LEBAR_STRUK) {
    const teksKiri = String(kiri ?? '');
    const teksKanan = String(kanan ?? '');
    const ruang = Math.max(lebar - teksKanan.length, 0);

    if (teksKiri.length > ruang) {
        return `${teksKiri}\n${teksKanan.padStart(lebar)}`;
    }

    return teksKiri.padEnd(ruang) + teksKanan;
}

function buatStrukEscPos(pesanan) {
    const encoder = new TextEncoder();
    const bagian = [];

    const tambah = (...bytes) => bagian.push(new Uint8Array(bytes));
    const tulis = (teks = '') => bagian.push(encoder.encode(`${teks}\n`));
    const garis = () => tulis('-'.repeat(LEBAR_STRUK));

    tambah(0x1b, 0x40);
    tambah(0x1b, 0x61, 0x01);
    tambah(0x1d, 0x21, 0x11);
    tambah(0x1b, 0x45, 0x01);
    tulis('MAHJONG');
    tambah(0x1b, 0x45, 0x00);
    tambah(0x1d, 0x21, 0x00);
    tulis('Struk Pembayaran');
    tambah(0x1b, 0x61, 0x00);
    garis();

    tulis(barisKiriKanan('Kode', pesanan.kode_pesanan));
    tulis(barisKiriKanan('Meja', pesanan.nomor_meja));
    tulis(barisKiriKanan('Tipe', pesanan.tipe_pesanan === 'take_away' ? 'Take Away' : 'Dine In'));

    if (pesanan.nama_pemesan) {
        tulis(barisKiriKanan('Nama', pesanan.nama_pemesan));
    }

    tulis(barisKiriKanan('Waktu', formatWaktu(pesanan.created_at)));
    tulis(barisKiriKanan('Status', 'Selesai'));
    garis();

    (pesanan.details ?? []).forEach((detail) => {
        const nama = detail.suhu ? `${detail.nama_menu} (${detail.suhu})` : detail.nama_menu;
        const baris = bungkusTeks(`${detail.jumlah}x ${nama}`, LEBAR_STRUK - 8);

        baris[baris.length - 1] = barisKiriKanan(baris[baris.length - 1], formatAngka(detail.subtotal));

        baris.forEach(tulis);
    });

    garis();
    tambah(0x1b, 0x45, 0x01);
    tulis(barisKiriKanan('TOTAL', formatAngka(pesanan.total)));
    tambah(0x1b, 0x45, 0x00);
    garis();

    if (pesanan.catatan) {
        tulis('Catatan:');
        bungkusTeks(pesanan.catatan, LEBAR_STRUK).forEach(tulis);
        garis();
    }

    tambah(0x1b, 0x61, 0x01);
    tulis('Terima kasih');
    tulis('atas kunjungan Anda');
    tambah(0x1b, 0x61, 0x00);
    tulis('');
    tulis('');
    tambah(0x1b, 0x64, 0x03);
    tambah(0x1d, 0x56, 0x00);

    const total = bagian.reduce((jumlah, item) => jumlah + item.length, 0);
    const hasil = new Uint8Array(total);
    let offset = 0;

    bagian.forEach((item) => {
        hasil.set(item, offset);
        offset += item.length;
    });

    return hasil;
}

function printerBisaDipakai() {
    return typeof navigator !== 'undefined' && 'bluetooth' in navigator;
}

async function mintaPrinterBluetooth() {
    return navigator.bluetooth.requestDevice({
        acceptAllDevices: true,
        optionalServices: UUID_LAYANAN_PRINTER,
    });
}

async function cariKarakteristikTulis(server) {
    const cariDiLayanan = async (layanan) => {
        const karakteristik = await layanan.getCharacteristics();

        return karakteristik.find((item) => item.properties.writeWithoutResponse)
            ?? karakteristik.find((item) => item.properties.write)
            ?? null;
    };

    for (const uuid of UUID_LAYANAN_PRINTER) {
        try {
            const layanan = await server.getPrimaryService(uuid);
            const karakteristik = await cariDiLayanan(layanan);

            if (karakteristik) {
                return karakteristik;
            }
        } catch {
            // Layanan tidak tersedia di printer ini, coba UUID berikutnya.
        }
    }

    try {
        const semuaLayanan = await server.getPrimaryServices();

        for (const layanan of semuaLayanan) {
            const karakteristik = await cariDiLayanan(layanan);

            if (karakteristik) {
                return karakteristik;
            }
        }
    } catch {
        // Biarkan ditangani sebagai karakteristik tidak ditemukan.
    }

    return null;
}

async function kirimKePrinter(device, data) {
    const server = await device.gatt.connect();

    try {
        const karakteristik = await cariKarakteristikTulis(server);

        if (!karakteristik) {
            throw new Error('Printer tidak memiliki karakteristik tulis. Pastikan perangkat adalah printer thermal Bluetooth.');
        }

        const ukuranPotongan = 20;

        for (let posisi = 0; posisi < data.length; posisi += ukuranPotongan) {
            const potongan = data.slice(posisi, posisi + ukuranPotongan);

            if (karakteristik.properties.writeWithoutResponse) {
                await karakteristik.writeValueWithoutResponse(potongan);
            } else {
                await karakteristik.writeValue(potongan);
            }

            await new Promise((selesai) => setTimeout(selesai, 20));
        }
    } finally {
        if (device.gatt.connected) {
            device.gatt.disconnect();
        }
    }
}

const statusStyle = {
    baru: {
        badge: 'bg-amber-50 text-amber-700 ring-amber-200',
        dot: 'bg-amber-500',
        label: 'Baru',
    },
    diproses: {
        badge: 'bg-sky-50 text-sky-700 ring-sky-200',
        dot: 'bg-sky-500',
        label: 'Diproses',
    },
    selesai: {
        badge: 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        dot: 'bg-emerald-500',
        label: 'Selesai',
    },
    dibatalkan: {
        badge: 'bg-rose-50 text-rose-700 ring-rose-200',
        dot: 'bg-rose-500',
        label: 'Dibatalkan',
    },
};

function CloseIcon() {
    return (
        <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5" fill="none" viewBox="0 0 24 24" strokeWidth={2} stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" d="M6 18 18 6M6 6l12 12" />
        </svg>
    );
}

function EmptyIcon() {
    return (
        <svg xmlns="http://www.w3.org/2000/svg" className="h-10 w-10" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" d="M2.25 8.25h19.5M4.5 8.25v10.125A1.875 1.875 0 0 0 6.375 20.25h11.25a1.875 1.875 0 0 0 1.875-1.875V8.25M9 12.75h6M6.75 3.75h10.5a1.5 1.5 0 0 1 1.5 1.5v3H5.25v-3a1.5 1.5 0 0 1 1.5-1.5Z" />
        </svg>
    );
}

function PrinterIcon({ className = 'h-4 w-4' }) {
    return (
        <svg xmlns="http://www.w3.org/2000/svg" className={className} fill="none" viewBox="0 0 24 24" strokeWidth={1.8} stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" d="M6.75 7.5V4.5a1.5 1.5 0 0 1 1.5-1.5h7.5a1.5 1.5 0 0 1 1.5 1.5v3M6.75 16.5H4.5a1.5 1.5 0 0 1-1.5-1.5v-4.5a1.5 1.5 0 0 1 1.5-1.5h15a1.5 1.5 0 0 1 1.5 1.5V15a1.5 1.5 0 0 1-1.5 1.5h-2.25M6.75 13.5h10.5a.75.75 0 0 1 .75.75v4.5a.75.75 0 0 1-.75.75H6.75a.75.75 0 0 1-.75-.75v-4.5a.75.75 0 0 1 .75-.75Z" />
        </svg>
    );
}

function BellIcon({ className = 'h-5 w-5' }) {
    return (
        <svg xmlns="http://www.w3.org/2000/svg" className={className} fill="none" viewBox="0 0 24 24" strokeWidth={1.8} stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
        </svg>
    );
}

function BellOffIcon({ className = 'h-5 w-5' }) {
    return (
        <svg xmlns="http://www.w3.org/2000/svg" className={className} fill="none" viewBox="0 0 24 24" strokeWidth={1.8} stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" d="M9.143 17.082a24.248 24.248 0 0 0 3.714 0m3.714 0a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9a6 6 0 0 0-10.863-3.553M6 9v.75a8.967 8.967 0 0 1-2.312 6.022 24.25 24.25 0 0 0 5.455 1.31M3 3l18 18" />
        </svg>
    );
}

export default function Index({ pesanans, ringkasan, filters }) {
    const { flash } = usePage().props;
    const [search, setSearch] = useState(filters?.search ?? '');
    const [statusFilter, setStatusFilter] = useState(filters?.status ?? '');
    const [successMessage, setSuccessMessage] = useState(flash?.success ?? null);
    const [errorMessage, setErrorMessage] = useState(flash?.error ?? null);
    const [processingId, setProcessingId] = useState(null);
    const [mencetakId, setMencetakId] = useState(null);
    const [notifikasiBaru, setNotifikasiBaru] = useState([]);
    const [suaraAktif, setSuaraAktif] = useState(() => {
        if (typeof window === 'undefined') {
            return true;
        }

        return window.localStorage.getItem('kasir-suara-aktif') !== '0';
    });
    const idBaruRef = useRef(null);
    const audioRef = useRef(null);
    const printerRef = useRef(null);
    const suaraAktifRef = useRef(suaraAktif);
    const notifikasiTimerRef = useRef(null);
    const suaraTtsTimerRef = useRef(null);

    useEffect(() => {
        suaraAktifRef.current = suaraAktif;
    }, [suaraAktif]);

    useEffect(() => {
        setSuccessMessage(flash?.success ?? null);
        setErrorMessage(flash?.error ?? null);
    }, [flash?.success, flash?.error]);

    const ambilAudioContext = () => {
        if (typeof window === 'undefined') {
            return null;
        }

        const AudioContext = window.AudioContext || window.webkitAudioContext;

        if (!AudioContext) {
            return null;
        }

        if (!audioRef.current) {
            audioRef.current = new AudioContext();
        }

        if (audioRef.current.state === 'suspended') {
            audioRef.current.resume();
        }

        return audioRef.current;
    };

    const mainkanBel = () => {
        const audio = ambilAudioContext();

        if (!audio) {
            return;
        }

        const mulai = audio.currentTime;

        [
            { frekuensi: 880, delay: 0 },
            { frekuensi: 1174.66, delay: 0.22 },
        ].forEach(({ frekuensi, delay }) => {
            const oscilator = audio.createOscillator();
            const gain = audio.createGain();

            oscilator.type = 'sine';
            oscilator.frequency.setValueAtTime(frekuensi, mulai + delay);

            gain.gain.setValueAtTime(0.0001, mulai + delay);
            gain.gain.exponentialRampToValueAtTime(0.4, mulai + delay + 0.02);
            gain.gain.exponentialRampToValueAtTime(0.0001, mulai + delay + 1.2);

            oscilator.connect(gain);
            gain.connect(audio.destination);
            oscilator.start(mulai + delay);
            oscilator.stop(mulai + delay + 1.3);
        });
    };

    const ucapkanPesananBaru = (pesananMasuk) => {
        if (typeof window === 'undefined' || !window.speechSynthesis) {
            return;
        }

        const jumlah = pesananMasuk.length;
        const pesananPertama = pesananMasuk[0];
        let teks = 'Pesanan baru masuk';

        if (jumlah > 1) {
            teks = `Ada ${jumlah} pesanan baru masuk`;
        } else if (pesananPertama?.nomor_meja) {
            teks = `Pesanan baru masuk, meja ${pesananPertama.nomor_meja}`;
        }

        const suaraTersedia = window.speechSynthesis.getVoices();
        const suaraIndonesia = suaraTersedia.find((suara) => /^id/i.test(suara.lang))
            ?? suaraTersedia.find((suara) => /indonesia/i.test(suara.name));

        const ucapan = new SpeechSynthesisUtterance(teks);
        ucapan.lang = 'id-ID';
        ucapan.rate = 0.95;
        ucapan.pitch = 1;
        ucapan.volume = 1;

        if (suaraIndonesia) {
            ucapan.voice = suaraIndonesia;
        }

        window.speechSynthesis.cancel();
        window.speechSynthesis.speak(ucapan);
    };

    useEffect(() => {
        const bukaKunciAudio = () => {
            ambilAudioContext();

            if (typeof window !== 'undefined' && window.speechSynthesis) {
                // Memicu pemuatan daftar suara di beberapa browser.
                window.speechSynthesis.getVoices();
            }
        };

        window.addEventListener('pointerdown', bukaKunciAudio, { once: true });
        window.addEventListener('keydown', bukaKunciAudio, { once: true });

        return () => {
            window.removeEventListener('pointerdown', bukaKunciAudio);
            window.removeEventListener('keydown', bukaKunciAudio);
        };
    }, []);

    useEffect(() => {
        let aktif = true;

        const muatUlangDaftar = () => {
            if (document.visibilityState !== 'visible') {
                return;
            }

            router.reload({
                only: ['pesanans', 'ringkasan'],
                preserveState: true,
                preserveScroll: true,
            });
        };

        const cekPesananBaru = async () => {
            if (document.visibilityState !== 'visible') {
                return;
            }

            try {
                const response = await fetch('/admin/kasir/notifikasi', {
                    headers: { Accept: 'application/json' },
                    cache: 'no-store',
                });

                if (!response.ok) {
                    return;
                }

                const data = await response.json();
                const daftar = Array.isArray(data.pesanan) ? data.pesanan : [];
                const ids = new Set(daftar.map((pesanan) => String(pesanan.id)));

                if (idBaruRef.current === null) {
                    idBaruRef.current = ids;

                    return;
                }

                const pesananMasuk = daftar.filter(
                    (pesanan) => !idBaruRef.current.has(String(pesanan.id)),
                );

                idBaruRef.current = ids;

                if (!aktif || pesananMasuk.length === 0) {
                    return;
                }

                setNotifikasiBaru(pesananMasuk);

                if (suaraAktifRef.current) {
                    mainkanBel();

                    clearTimeout(suaraTtsTimerRef.current);
                    suaraTtsTimerRef.current = setTimeout(() => {
                        if (aktif) {
                            ucapkanPesananBaru(pesananMasuk);
                        }
                    }, 900);
                }

                muatUlangDaftar();

                clearTimeout(notifikasiTimerRef.current);
                notifikasiTimerRef.current = setTimeout(() => {
                    if (aktif) {
                        setNotifikasiBaru([]);
                    }
                }, 25000);
            } catch {
                // Abaikan gangguan jaringan; pengecekan berikutnya tetap berjalan.
            }
        };

        cekPesananBaru();

        const intervalCek = setInterval(cekPesananBaru, 5000);
        const intervalDaftar = setInterval(muatUlangDaftar, 60000);

        const saatTerlihat = () => {
            if (document.visibilityState === 'visible') {
                cekPesananBaru();
                muatUlangDaftar();
            }
        };

        document.addEventListener('visibilitychange', saatTerlihat);

        return () => {
            aktif = false;
            clearInterval(intervalCek);
            clearInterval(intervalDaftar);
            clearTimeout(notifikasiTimerRef.current);
            clearTimeout(suaraTtsTimerRef.current);

            if (typeof window !== 'undefined' && window.speechSynthesis) {
                window.speechSynthesis.cancel();
            }

            document.removeEventListener('visibilitychange', saatTerlihat);
        };
    }, []);

    useEffect(() => {
        document.title = notifikasiBaru.length > 0
            ? `(${notifikasiBaru.length}) Pesanan Baru · Kasir`
            : 'Kasir';
    }, [notifikasiBaru]);

    const toggleSuara = () => {
        setSuaraAktif((aktif) => {
            const berikutnya = !aktif;

            window.localStorage.setItem('kasir-suara-aktif', berikutnya ? '1' : '0');

            if (berikutnya) {
                mainkanBel();
            }

            return berikutnya;
        });
    };

    const submitFilter = (event) => {
        event.preventDefault();

        router.get(
            '/admin/kasir',
            { search, status: statusFilter },
            { preserveState: true, replace: true },
        );
    };

    const resetFilter = () => {
        setSearch('');
        setStatusFilter('');
        router.get('/admin/kasir', {}, { preserveState: true, replace: true });
    };

    const pilihStatus = (status) => {
        setStatusFilter(status);
        router.get(
            '/admin/kasir',
            { search, status },
            { preserveState: true, replace: true },
        );
    };

    const ubahStatus = (pesanan, status) => {
        setProcessingId(pesanan.id);

        router.patch(
            `/admin/kasir/${pesanan.id}/status`,
            { status },
            {
                preserveScroll: true,
                onFinish: () => setProcessingId(null),
            },
        );
    };

    const cetakStruk = async (pesanan) => {
        if (mencetakId !== null) {
            return;
        }

        if (!printerBisaDipakai()) {
            setSuccessMessage(null);
            setErrorMessage('Browser ini tidak mendukung Bluetooth. Gunakan Chrome atau Edge (Android/desktop) untuk mencetak struk.');

            return;
        }

        setMencetakId(pesanan.id);
        setSuccessMessage(null);
        setErrorMessage(null);

        try {
            const data = buatStrukEscPos(pesanan);
            let printer = printerRef.current;

            if (!printer) {
                printer = await mintaPrinterBluetooth();
                printerRef.current = printer;
            }

            try {
                await kirimKePrinter(printer, data);
            } catch {
                // Printer tersimpan gagal terhubung: minta kasir memilih ulang.
                printerRef.current = null;
                printer = await mintaPrinterBluetooth();
                printerRef.current = printer;
                await kirimKePrinter(printer, data);
            }

            setSuccessMessage(
                `Struk ${pesanan.kode_pesanan} berhasil dicetak${printer.name ? ` ke ${printer.name}` : ''}.`,
            );
        } catch (error) {
            printerRef.current = null;

            const pesan = error?.name === 'NotFoundError'
                ? 'Printer tidak dipilih. Klik Cetak Struk lalu pilih printer thermal Bluetooth kamu.'
                : error?.name === 'SecurityError'
                    ? 'Akses Bluetooth diblokir browser. Izinkan akses Bluetooth untuk situs ini.'
                    : error?.name === 'NetworkError'
                        ? 'Gagal terhubung ke printer. Pastikan printer menyala dan Bluetooth aktif.'
                        : error?.message || 'Gagal mencetak struk. Coba lagi.';

            setErrorMessage(pesan);
        } finally {
            setMencetakId(null);
        }
    };

    const kartuRingkasan = [
        { key: 'baru', label: 'Pesanan Baru', className: 'border-amber-100 bg-amber-50/60 text-amber-800' },
        { key: 'diproses', label: 'Diproses', className: 'border-sky-100 bg-sky-50/60 text-sky-800' },
        { key: 'selesai', label: 'Selesai', className: 'border-emerald-100 bg-emerald-50/60 text-emerald-800' },
        { key: 'dibatalkan', label: 'Dibatalkan', className: 'border-rose-100 bg-rose-50/60 text-rose-800' },
    ];

    return (
        <>
            <Head title="Kasir" />

            <div className="mx-auto max-w-7xl space-y-6">
                {successMessage && (
                    <div className="flex items-center gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                        <span className="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-emerald-600 text-white">
                            <svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4" fill="none" viewBox="0 0 24 24" strokeWidth={2.2} stroke="currentColor">
                                <path strokeLinecap="round" strokeLinejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                            </svg>
                        </span>
                        <p className="font-medium">{successMessage}</p>
                        <button
                            type="button"
                            onClick={() => setSuccessMessage(null)}
                            className="ml-auto inline-flex h-7 w-7 items-center justify-center rounded-lg text-emerald-600 transition hover:bg-emerald-100"
                            aria-label="Tutup notifikasi"
                        >
                            <CloseIcon />
                        </button>
                    </div>
                )}

                {errorMessage && (
                    <div className="flex items-center gap-3 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                        <span className="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-rose-600 text-white">
                            <svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4" fill="none" viewBox="0 0 24 24" strokeWidth={2.2} stroke="currentColor">
                                <path strokeLinecap="round" strokeLinejoin="round" d="M12 9v3.75m0 3h.008v.008H12v-.008ZM12 3.75 2.25 20.25h19.5L12 3.75Z" />
                            </svg>
                        </span>
                        <p className="font-medium">{errorMessage}</p>
                        <button
                            type="button"
                            onClick={() => setErrorMessage(null)}
                            className="ml-auto inline-flex h-7 w-7 items-center justify-center rounded-lg text-rose-600 transition hover:bg-rose-100"
                            aria-label="Tutup notifikasi"
                        >
                            <CloseIcon />
                        </button>
                    </div>
                )}

                {notifikasiBaru.length > 0 && (
                    <div className="flex items-start gap-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900 shadow-sm">
                        <span className="flex h-8 w-8 shrink-0 animate-bounce items-center justify-center rounded-full bg-amber-500 text-white">
                            <BellIcon className="h-4 w-4" />
                        </span>
                        <div className="min-w-0 flex-1">
                            <p className="font-bold">
                                {notifikasiBaru.length === 1
                                    ? 'Pesanan baru masuk!'
                                    : `${notifikasiBaru.length} pesanan baru masuk!`}
                            </p>
                            <ul className="mt-1 space-y-0.5 text-xs text-amber-800">
                                {notifikasiBaru.slice(0, 3).map((pesanan) => (
                                    <li key={pesanan.id} className="truncate">
                                        <span className="font-semibold">{pesanan.kode_pesanan}</span>
                                        {' · '}Meja {pesanan.nomor_meja}
                                        {pesanan.tipe_pesanan === 'take_away' ? ' · Take Away' : ' · Dine In'}
                                        {pesanan.nama_pemesan ? ` · ${pesanan.nama_pemesan}` : ''}
                                    </li>
                                ))}
                            </ul>
                        </div>
                        <button
                            type="button"
                            onClick={() => setNotifikasiBaru([])}
                            className="inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-lg text-amber-700 transition hover:bg-amber-100"
                            aria-label="Tutup notifikasi pesanan baru"
                        >
                            <CloseIcon />
                        </button>
                    </div>
                )}

                <div className="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h1 className="text-xl font-bold text-slate-900">Kasir</h1>
                        <p className="text-sm text-slate-500">Pesanan makanan dan minuman dari pelanggan masuk ke sini secara otomatis. Pesanan selesai bisa langsung dicetak struknya ke printer thermal Bluetooth.</p>
                    </div>

                    <button
                        type="button"
                        onClick={toggleSuara}
                        aria-pressed={suaraAktif}
                        className={`inline-flex items-center gap-2 rounded-xl border px-3.5 py-2 text-xs font-semibold shadow-sm transition ${
                            suaraAktif
                                ? 'border-emerald-200 bg-emerald-50 text-emerald-700 hover:bg-emerald-100'
                                : 'border-slate-200 bg-white text-slate-500 hover:bg-slate-50'
                        }`}
                    >
                        {suaraAktif ? <BellIcon className="h-4 w-4" /> : <BellOffIcon className="h-4 w-4" />}
                        Suara {suaraAktif ? 'aktif' : 'mati'}
                    </button>
                </div>

                <div className="grid grid-cols-2 gap-3 lg:grid-cols-4">
                    {kartuRingkasan.map((kartu) => (
                        <button
                            key={kartu.key}
                            type="button"
                            onClick={() => pilihStatus(statusFilter === kartu.key ? '' : kartu.key)}
                            className={`rounded-2xl border px-4 py-3.5 text-left shadow-sm transition hover:-translate-y-0.5 hover:shadow-md ${kartu.className} ${
                                statusFilter === kartu.key ? 'ring-2 ring-emerald-500/40' : ''
                            }`}
                        >
                            <p className="text-[11px] font-semibold uppercase tracking-wide opacity-80">{kartu.label}</p>
                            <p className="mt-1 text-2xl font-bold">{ringkasan?.[kartu.key] ?? 0}</p>
                        </button>
                    ))}
                </div>

                <div className="overflow-hidden rounded-2xl border border-emerald-100 bg-white shadow-sm">
                    <div className="flex flex-col gap-3 border-b border-emerald-100 px-4 py-4 sm:px-5">
                        <form onSubmit={submitFilter} className="flex flex-col gap-2 sm:flex-row sm:items-center">
                            <div className="relative flex-1">
                                <span className="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                                    <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5" fill="none" viewBox="0 0 24 24" strokeWidth={1.8} stroke="currentColor">
                                        <path strokeLinecap="round" strokeLinejoin="round" d="m21 21-4.35-4.35M17 11a6 6 0 1 1-12 0 6 6 0 0 1 12 0Z" />
                                    </svg>
                                </span>
                                <input
                                    type="text"
                                    value={search}
                                    onChange={(event) => setSearch(event.target.value)}
                                    placeholder="Cari kode pesanan, nomor meja, atau nama..."
                                    className="w-full rounded-xl border border-slate-200 bg-white py-2.5 pl-10 pr-3 text-sm text-slate-800 shadow-sm transition placeholder:text-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20"
                                />
                            </div>

                            <select
                                value={statusFilter}
                                onChange={(event) => setStatusFilter(event.target.value)}
                                className="rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-800 shadow-sm transition focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 sm:w-44"
                            >
                                <option value="">Semua status</option>
                                <option value="baru">Baru</option>
                                <option value="diproses">Diproses</option>
                                <option value="selesai">Selesai</option>
                                <option value="dibatalkan">Dibatalkan</option>
                            </select>

                            <button
                                type="submit"
                                className="inline-flex items-center justify-center rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-sm font-semibold text-emerald-700 transition hover:bg-emerald-100"
                            >
                                Cari
                            </button>
                            {(filters?.search || filters?.status) && (
                                <button
                                    type="button"
                                    onClick={resetFilter}
                                    className="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm font-medium text-slate-500 transition hover:bg-slate-50"
                                >
                                    Reset
                                </button>
                            )}

                            <button
                                type="button"
                                onClick={() => router.reload({ only: ['pesanans', 'ringkasan'] })}
                                className="inline-flex items-center justify-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm font-medium text-slate-600 transition hover:bg-slate-50"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4" fill="none" viewBox="0 0 24 24" strokeWidth={1.8} stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" d="M16.023 9.348h4.992V4.356M3.05 12.75a8.25 8.25 0 0 1 14.108-5.865l3.857 3.486M2.25 20.25v-4.992m18.7-3.008a8.25 8.25 0 0 1-14.108 5.865L2.985 14.63" />
                                </svg>
                                Segarkan
                            </button>

                            <p className="text-xs text-slate-500 sm:ml-auto">
                                Total <span className="font-semibold text-slate-700">{pesanans.total}</span> pesanan
                            </p>
                        </form>
                    </div>

                    {pesanans.data.length === 0 ? (
                        <div className="flex flex-col items-center justify-center px-6 py-16 text-center">
                            <span className="flex h-16 w-16 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-500">
                                <EmptyIcon />
                            </span>
                            <h2 className="mt-4 text-sm font-semibold text-slate-800">
                                {filters?.search || filters?.status ? 'Pesanan tidak ditemukan' : 'Belum ada pesanan masuk'}
                            </h2>
                            <p className="mt-1 max-w-sm text-sm text-slate-500">
                                {filters?.search || filters?.status
                                    ? 'Coba ubah kata kunci atau filter status pesanan.'
                                    : 'Pesanan baru dari halaman Pesan pelanggan akan otomatis muncul di sini.'}
                            </p>
                        </div>
                    ) : (
                        <div className="space-y-3 bg-slate-50/40 p-4 sm:p-5">
                            {pesanans.data.map((pesanan) => {
                                const style = statusStyle[pesanan.status] ?? statusStyle.baru;
                                const sedangDiproses = processingId === pesanan.id;
                                const masukBaru = notifikasiBaru.some(
                                    (item) => String(item.id) === String(pesanan.id),
                                );

                                return (
                                    <article
                                        key={pesanan.id}
                                        className={`overflow-hidden rounded-2xl border bg-white shadow-sm transition ${
                                            masukBaru
                                                ? 'border-amber-300 ring-2 ring-amber-200/70'
                                                : 'border-emerald-100'
                                        }`}
                                    >
                                        <div className="flex flex-col gap-3 border-b border-emerald-50 px-4 py-3.5 sm:flex-row sm:items-start sm:justify-between sm:px-5">
                                            <div className="min-w-0">
                                                <div className="flex flex-wrap items-center gap-2">
                                                    <span className="font-mono text-sm font-bold text-slate-900">{pesanan.kode_pesanan}</span>
                                                    <span className={`inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-[11px] font-semibold ring-1 ${
                                                        pesanan.tipe_pesanan === 'take_away'
                                                            ? 'bg-rose-50 text-rose-700 ring-rose-200'
                                                            : 'bg-emerald-50 text-emerald-700 ring-emerald-200'
                                                    }`}>
                                                        {pesanan.tipe_pesanan === 'take_away' ? 'Take Away' : 'Dine In'}
                                                    </span>
                                                    <span className={`inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-[11px] font-semibold ring-1 ${style.badge}`}>
                                                        <span className={`h-1.5 w-1.5 rounded-full ${style.dot}`} />
                                                        {style.label}
                                                    </span>
                                                </div>
                                                <p className="mt-1 text-xs text-slate-500">
                                                    <span className="font-semibold text-slate-700">Meja {pesanan.nomor_meja}</span>
                                                    {pesanan.nama_pemesan ? ` · ${pesanan.nama_pemesan}` : ' · Tanpa akun'}
                                                    {' · '}
                                                    {formatWaktu(pesanan.created_at)}
                                                </p>
                                            </div>

                                            <div className="flex flex-wrap items-center gap-2">
                                                {pesanan.status === 'baru' && (
                                                    <>
                                                        <button
                                                            type="button"
                                                            onClick={() => ubahStatus(pesanan, 'diproses')}
                                                            disabled={sedangDiproses}
                                                            className="inline-flex items-center justify-center rounded-lg bg-sky-600 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-sky-700 disabled:opacity-60"
                                                        >
                                                            Proses
                                                        </button>
                                                        <button
                                                            type="button"
                                                            onClick={() => ubahStatus(pesanan, 'dibatalkan')}
                                                            disabled={sedangDiproses}
                                                            className="inline-flex items-center justify-center rounded-lg border border-rose-100 bg-white px-3 py-1.5 text-xs font-semibold text-rose-600 transition hover:bg-rose-50 disabled:opacity-60"
                                                        >
                                                            Batalkan
                                                        </button>
                                                    </>
                                                )}

                                                {pesanan.status === 'diproses' && (
                                                    <>
                                                        <button
                                                            type="button"
                                                            onClick={() => ubahStatus(pesanan, 'selesai')}
                                                            disabled={sedangDiproses}
                                                            className="inline-flex items-center justify-center rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-emerald-700 disabled:opacity-60"
                                                        >
                                                            Selesaikan
                                                        </button>
                                                        <button
                                                            type="button"
                                                            onClick={() => ubahStatus(pesanan, 'dibatalkan')}
                                                            disabled={sedangDiproses}
                                                            className="inline-flex items-center justify-center rounded-lg border border-rose-100 bg-white px-3 py-1.5 text-xs font-semibold text-rose-600 transition hover:bg-rose-50 disabled:opacity-60"
                                                        >
                                                            Batalkan
                                                        </button>
                                                    </>
                                                )}

                                                {pesanan.status === 'selesai' && (
                                                    <button
                                                        type="button"
                                                        onClick={() => cetakStruk(pesanan)}
                                                        disabled={mencetakId === pesanan.id}
                                                        title="Cetak struk pembayaran ke printer thermal Bluetooth"
                                                        className="inline-flex items-center justify-center gap-1.5 rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm transition hover:bg-emerald-700 disabled:cursor-wait disabled:opacity-60"
                                                    >
                                                        <PrinterIcon />
                                                        {mencetakId === pesanan.id ? 'Mencetak...' : 'Cetak Struk'}
                                                    </button>
                                                )}
                                            </div>
                                        </div>

                                        <div className="px-4 py-3 sm:px-5">
                                            <ul className="divide-y divide-slate-100 text-sm">
                                                {pesanan.details.map((detail) => (
                                                    <li key={detail.id} className="flex items-center justify-between gap-3 py-1.5">
                                                        <span className="flex min-w-0 flex-wrap items-center gap-1.5 text-slate-700">
                                                            <span>
                                                                <span className="font-semibold">{detail.jumlah}×</span> {detail.nama_menu}
                                                            </span>
                                                            {detail.suhu && (
                                                                <span className="rounded-full bg-sky-50 px-1.5 py-0.5 text-[9px] font-bold uppercase tracking-wide text-sky-700 ring-1 ring-sky-100">
                                                                    {detail.suhu}
                                                                </span>
                                                            )}
                                                            {detail.dibuat_di && (
                                                                <span
                                                                    className={`rounded-full px-1.5 py-0.5 text-[9px] font-bold uppercase tracking-wide ring-1 ${
                                                                        detail.dibuat_di === 'Bar'
                                                                            ? 'bg-indigo-50 text-indigo-700 ring-indigo-100'
                                                                            : 'bg-amber-50 text-amber-700 ring-amber-100'
                                                                    }`}
                                                                >
                                                                    {detail.dibuat_di}
                                                                </span>
                                                            )}
                                                        </span>
                                                        <span className="shrink-0 text-xs font-medium text-slate-500">
                                                            {formatRupiah(detail.subtotal)}
                                                        </span>
                                                    </li>
                                                ))}
                                            </ul>

                                            {pesanan.catatan && (
                                                <p className="mt-2 rounded-xl bg-amber-50 px-3 py-2 text-xs text-amber-800">
                                                    <span className="font-semibold">Catatan:</span> {pesanan.catatan}
                                                </p>
                                            )}

                                            <div className="mt-3 flex items-center justify-between border-t border-slate-100 pt-2.5">
                                                <span className="text-xs text-slate-500">Total pesanan</span>
                                                <span className="text-sm font-bold text-emerald-700">{formatRupiah(pesanan.total)}</span>
                                            </div>
                                        </div>
                                    </article>
                                );
                            })}
                        </div>
                    )}

                    {pesanans.data.length > 0 && (
                        <div className="flex flex-col gap-3 border-t border-emerald-100 px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-5">
                            <p className="text-xs text-slate-500">
                                Menampilkan <span className="font-semibold text-slate-700">{pesanans.from ?? 0}</span>–
                                <span className="font-semibold text-slate-700">{pesanans.to ?? 0}</span> dari{' '}
                                <span className="font-semibold text-slate-700">{pesanans.total}</span> pesanan
                            </p>

                            {pesanans.last_page > 1 && (
                                <div className="flex flex-wrap items-center gap-1.5">
                                    {pesanans.links.map((link, index) =>
                                        link.url ? (
                                            <Link
                                                key={index}
                                                href={link.url}
                                                preserveScroll
                                                className={`inline-flex min-w-8 items-center justify-center rounded-lg border px-2.5 py-1.5 text-xs font-semibold transition ${
                                                    link.active
                                                        ? 'border-emerald-600 bg-emerald-600 text-white'
                                                        : 'border-slate-200 bg-white text-slate-600 hover:bg-emerald-50 hover:text-emerald-700'
                                                }`}
                                                dangerouslySetInnerHTML={{ __html: link.label }}
                                            />
                                        ) : (
                                            <span
                                                key={index}
                                                className="inline-flex min-w-8 cursor-not-allowed items-center justify-center rounded-lg border border-slate-100 bg-slate-50 px-2.5 py-1.5 text-xs font-semibold text-slate-300"
                                                dangerouslySetInnerHTML={{ __html: link.label }}
                                            />
                                        ),
                                    )}
                                </div>
                            )}
                        </div>
                    )}
                </div>
            </div>
        </>
    );
}

Index.layout = (page) => (
    <AdminLayout title="Kasir" subtitle="Pesanan pelanggan dan cetak struk Bluetooth">
        {page}
    </AdminLayout>
);
