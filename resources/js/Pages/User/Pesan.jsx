import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { useEffect, useMemo, useState } from 'react';
import UserLayout from '../../Layouts/UserLayout';
import PilihVenue from '../../Components/PilihVenue';

function formatRupiah(value) {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(Number(value) || 0);
}

function SearchIcon() {
    return (
        <svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4" fill="none" viewBox="0 0 24 24" strokeWidth={1.8} stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" d="m21 21-4.35-4.35M17 11a6 6 0 1 1-12 0 6 6 0 0 1 12 0Z" />
        </svg>
    );
}

function PlusIcon() {
    return (
        <svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4" fill="none" viewBox="0 0 24 24" strokeWidth={2.4} stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
        </svg>
    );
}

function GridIcon({ className = 'h-4 w-4' }) {
    return (
        <svg xmlns="http://www.w3.org/2000/svg" className={className} fill="none" viewBox="0 0 24 24" strokeWidth={1.9} stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 6A2.25 2.25 0 0 1 15.75 3.75H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 8.25 20.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z" />
        </svg>
    );
}

function FoodIcon({ className = 'h-4 w-4' }) {
    return (
        <svg xmlns="http://www.w3.org/2000/svg" className={className} fill="none" viewBox="0 0 24 24" strokeWidth={1.9} stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" d="M3.75 11.25h16.5a8.25 8.25 0 0 1-16.5 0ZM9 7.5c0-1.2 1.25-1.6 1.25-2.75M13.75 7.5c0-1.2 1.25-1.6 1.25-2.75" />
        </svg>
    );
}

function DrinkIcon({ className = 'h-4 w-4' }) {
    return (
        <svg xmlns="http://www.w3.org/2000/svg" className={className} fill="none" viewBox="0 0 24 24" strokeWidth={1.9} stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" d="M7.5 4.5h9l-1.1 14.1a1.5 1.5 0 0 1-1.5 1.4h-3.8a1.5 1.5 0 0 1-1.5-1.4L7.5 4.5ZM14.5 4.5 17 2M7.5 9h9" />
        </svg>
    );
}

function SnackIcon({ className = 'h-4 w-4' }) {
    return (
        <svg xmlns="http://www.w3.org/2000/svg" className={className} fill="none" viewBox="0 0 24 24" strokeWidth={1.9} stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" d="M6.75 9h10.5l-1.2 10.2a1.5 1.5 0 0 1-1.5 1.3H9.45a1.5 1.5 0 0 1-1.5-1.3L6.75 9ZM8 9a4 4 0 0 1 8 0M9.5 12.75v3.5M12 12.75v3.5M14.5 12.75v3.5" />
        </svg>
    );
}

function ImagePlaceholderIcon({ className = 'h-8 w-8' }) {
    return (
        <svg xmlns="http://www.w3.org/2000/svg" className={className} fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909M3.75 21h16.5A1.5 1.5 0 0 0 21.75 19.5V4.5A1.5 1.5 0 0 0 20.25 3H3.75A1.5 1.5 0 0 0 2.25 4.5v15A1.5 1.5 0 0 0 3.75 21Zm10.5-13.5h.008v.008h-.008V7.5Z" />
        </svg>
    );
}

function ClearIcon() {
    return (
        <svg xmlns="http://www.w3.org/2000/svg" className="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" strokeWidth={2.4} stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" d="M6 18 18 6M6 6l12 12" />
        </svg>
    );
}

function CartIcon({ className = 'h-5 w-5' }) {
    return (
        <svg xmlns="http://www.w3.org/2000/svg" className={className} fill="none" viewBox="0 0 24 24" strokeWidth={1.8} stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z" />
        </svg>
    );
}

function MinusIcon() {
    return (
        <svg xmlns="http://www.w3.org/2000/svg" className="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" strokeWidth={2.6} stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" d="M5 12h14" />
        </svg>
    );
}

function RiwayatIcon({ className = 'h-3.5 w-3.5' }) {
    return (
        <svg xmlns="http://www.w3.org/2000/svg" className={className} fill="none" viewBox="0 0 24 24" strokeWidth={1.9} stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" d="M12 6v6l4 2m6-2a10 10 0 1 1-20 0 10 10 0 0 1 20 0Z" />
        </svg>
    );
}

function TrashIcon() {
    return (
        <svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4" fill="none" viewBox="0 0 24 24" strokeWidth={1.8} stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
        </svg>
    );
}

function iconKategori(nama = '') {
    const label = nama.toLowerCase();

    if (label.includes('minum')) {
        return DrinkIcon;
    }

    if (label.includes('snack') || label.includes('cemilan') || label.includes('camilan')) {
        return SnackIcon;
    }

    if (label.includes('makan')) {
        return FoodIcon;
    }

    return GridIcon;
}

export default function Pesan({ pengguna, kategoriMenus = [], menus = [], mejaAwal = null, vanues = [], mejas = [] }) {
    const { flash } = usePage().props;
    const [vanueId, setVanueId] = useState(mejaAwal?.vanue_id ?? '');
    const [kategori, setKategori] = useState('semua');
    const [pencarian, setPencarian] = useState('');
    const [keranjang, setKeranjang] = useState([]);
    const [keranjangTerbuka, setKeranjangTerbuka] = useState(false);
    const [menuVarian, setMenuVarian] = useState(null);
    const [suhuPilihan, setSuhuPilihan] = useState('Dingin');
    const [jumlahPilihan, setJumlahPilihan] = useState(1);
    const [pesananBerhasil, setPesananBerhasil] = useState(null);

    const { data, setData, post, processing, errors, reset, clearErrors } = useForm({
        atas_nama: pengguna
            ? `${pengguna.nama_depan ?? ''} ${pengguna.nama_balakang ?? ''}`.trim()
            : '',
        nomor_meja: mejaAwal?.kode_meja ?? '',
        tipe_pesanan: 'dine_in',
        catatan: '',
        items: [],
    });

    useEffect(() => {
        if (flash?.pesanan_sukses) {
            setPesananBerhasil(flash.pesanan_sukses);
        }
    }, [flash?.pesanan_sukses]);

    useEffect(() => {
        setData(
            'items',
            keranjang.map((item) => ({
                id: item.id,
                jumlah: item.jumlah,
                suhu: item.suhu ?? null,
            })),
        );
    }, [keranjang]);

    const vanueTerpilih = vanues.find((vanue) => String(vanue.id) === String(vanueId));
    const menuVenue = vanueId
        ? menus.filter((menu) => String(menu.vanue_id) === String(vanueId))
        : [];
    const kategoriVenue = vanueId
        ? kategoriMenus.filter((kategori) => String(kategori.vanue_id) === String(vanueId))
        : [];

    const menuTampil = menuVenue.filter((menu) => {
        const cocokKategori = kategori === 'semua' || String(menu.kategori_id) === String(kategori);
        const cocokPencarian = (menu.nama ?? '').toLowerCase().includes(pencarian.trim().toLowerCase());

        return cocokKategori && cocokPencarian;
    });

    const jumlahItem = useMemo(
        () => keranjang.reduce((total, item) => total + item.jumlah, 0),
        [keranjang],
    );

    const totalHarga = useMemo(
        () => keranjang.reduce((total, item) => total + item.harga * item.jumlah, 0),
        [keranjang],
    );

    const punyaVarianSuhu = (menu) => menu.harga_dingin != null && menu.harga_panas != null;

    const hargaUntukSuhu = (menu, suhu) => (suhu === 'Panas' ? menu.harga_panas : menu.harga_dingin);

    const jumlahUntukMenu = (menu) =>
        keranjang
            .filter((item) => String(item.id) === String(menu.id))
            .reduce((total, item) => total + item.jumlah, 0);

    const tambahKeKeranjang = (menu, suhu = null, jumlah = 1) => {
        const punyaVarian = punyaVarianSuhu(menu);
        const kunci = punyaVarian ? `${menu.id}-${suhu}` : `${menu.id}`;
        const harga = punyaVarian ? hargaUntukSuhu(menu, suhu) : menu.harga;

        setKeranjang((laman) => {
            const ada = laman.find((item) => item.kunci === kunci);

            if (ada) {
                return laman.map((item) =>
                    item.kunci === kunci ? { ...item, jumlah: item.jumlah + jumlah } : item,
                );
            }

            return [
                ...laman,
                {
                    kunci,
                    id: menu.id,
                    nama: menu.nama,
                    gambar_url: menu.gambar_url,
                    harga,
                    suhu: punyaVarian ? suhu : null,
                    jumlah,
                },
            ];
        });
    };

    const bukaPilihVarian = (menu) => {
        setMenuVarian(menu);
        setSuhuPilihan('Dingin');
        setJumlahPilihan(1);
    };

    const simpanPilihanVarian = () => {
        if (!menuVarian) {
            return;
        }

        tambahKeKeranjang(menuVarian, suhuPilihan, jumlahPilihan);
        setMenuVarian(null);
    };

    const kurangiJumlah = (kunci) => {
        setKeranjang((laman) =>
            laman
                .map((item) => (item.kunci === kunci ? { ...item, jumlah: item.jumlah - 1 } : item))
                .filter((item) => item.jumlah > 0),
        );
    };

    const tambahJumlah = (kunci) => {
        setKeranjang((laman) =>
            laman.map((item) => (item.kunci === kunci ? { ...item, jumlah: item.jumlah + 1 } : item)),
        );
    };

    const hapusItem = (kunci) => {
        setKeranjang((laman) => laman.filter((item) => item.kunci !== kunci));
    };

    const bukaKeranjang = () => {
        clearErrors();
        setKeranjangTerbuka(true);
    };

    const kirimPesanan = (event) => {
        event.preventDefault();

        post('/pesan', {
            preserveScroll: true,
            onSuccess: () => {
                setKeranjang([]);
                setKeranjangTerbuka(false);
                reset();
            },
        });
    };

    return (
        <>
            <Head title="Pesan Makanan" />

            <header className="relative overflow-hidden rounded-b-[2rem] bg-gradient-to-br from-emerald-600 via-emerald-700 to-emerald-900 px-5 pb-16 pt-6 text-white">
                <div className="pointer-events-none absolute -right-12 -top-12 h-44 w-44 rounded-full bg-white/10" />
                <div className="pointer-events-none absolute -bottom-20 -left-12 h-48 w-48 rounded-full bg-emerald-300/20" />

                <div className="relative">
                    <div className="flex items-start justify-between gap-3">
                        <span className="inline-flex items-center gap-1.5 rounded-full bg-white/15 px-2.5 py-1 text-[10px] font-bold uppercase tracking-widest ring-1 ring-white/20">
                            <span className="h-1.5 w-1.5 rounded-full bg-lime-300" />
                            Pesanan Makanan
                        </span>
                        <Link
                            href="/riwayat/pesanan"
                            className="inline-flex shrink-0 items-center gap-1.5 rounded-full bg-white/15 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide text-white ring-1 ring-white/20 transition hover:bg-white/25"
                        >
                            <RiwayatIcon />
                            Riwayat Pesanan
                        </Link>
                    </div>
                    <h1 className="mt-3 text-xl font-bold leading-snug">
                        {vanueTerpilih ? `Menu di ${vanueTerpilih.nama}` : 'Mau Ngemil Apa Hari Ini?'}
                    </h1>
                    <p className="mt-1 max-w-[16rem] text-xs leading-relaxed text-emerald-100">
                        {vanueTerpilih
                            ? 'Pilih makanan, minuman, atau snack favoritmu dan nikmati langsung di meja.'
                            : 'Pilih venue tempat kamu bermain untuk melihat menu yang tersedia.'}
                    </p>
                </div>
            </header>

            <main className="relative -mt-9 space-y-4 px-5 pb-8">
                {!vanueId && !mejaAwal ? (
                    <PilihVenue
                        vanues={vanues}
                        mejas={mejas}
                        onPilih={(vanue) => {
                            setKategori('semua');
                            setVanueId(String(vanue.id));
                        }}
                    />
                ) : (
                <>
                <div className="rounded-2xl border border-emerald-100 bg-white p-3 shadow-lg shadow-emerald-900/5">
                    <div className="flex items-center gap-2 rounded-xl bg-emerald-50/50 px-3.5 py-2.5 ring-1 ring-inset ring-emerald-100/70 focus-within:ring-emerald-300">
                        <span className="text-emerald-500">
                            <SearchIcon />
                        </span>
                        <input
                            type="text"
                            value={pencarian}
                            onChange={(event) => setPencarian(event.target.value)}
                            placeholder="Cari menu favoritmu..."
                            className="w-full bg-transparent text-sm text-slate-700 placeholder:text-slate-400 focus:outline-none"
                        />
                        {pencarian && (
                            <button
                                type="button"
                                onClick={() => setPencarian('')}
                                aria-label="Hapus pencarian"
                                className="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-slate-200 text-slate-500 transition hover:bg-slate-300"
                            >
                                <ClearIcon />
                            </button>
                        )}
                    </div>

                    <div className="mt-2.5 flex gap-2 overflow-x-auto pb-0.5 [-ms-overflow-style:none] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                        {!mejaAwal && (
                            <button
                                type="button"
                                onClick={() => {
                                    setKategori('semua');
                                    setVanueId('');
                                }}
                                className="flex shrink-0 items-center gap-1.5 rounded-xl bg-white px-3 py-2 text-[11px] font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-200 transition hover:bg-emerald-50 active:scale-95"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" className="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" strokeWidth={2} stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" d="M10.5 19.5 3 12l7.5-7.5M3 12h18" />
                                </svg>
                                Ganti venue
                            </button>
                        )}
                        {[{ id: 'semua', kategori: 'Semua' }, ...kategoriVenue].map((item) => {
                            const value = item.id === 'semua' ? 'semua' : String(item.id);
                            const Icon = item.id === 'semua' ? GridIcon : iconKategori(item.kategori);
                            const aktif = kategori === value;

                            return (
                                <button
                                    key={value}
                                    type="button"
                                    onClick={() => setKategori(value)}
                                    aria-pressed={aktif}
                                    className={`flex shrink-0 items-center gap-1.5 rounded-xl px-3 py-2 text-[11px] font-semibold transition-all duration-200 active:scale-95 ${aktif
                                            ? 'bg-gradient-to-br from-emerald-600 to-emerald-700 text-white shadow-md shadow-emerald-600/25'
                                            : 'bg-emerald-50/70 text-emerald-700 ring-1 ring-inset ring-emerald-100 hover:bg-emerald-100'
                                        }`}
                                >
                                    <Icon className="h-3.5 w-3.5" />
                                    {item.kategori}
                                </button>
                            );
                        })}
                    </div>
                </div>

                <section>
                    <div className="mb-2.5 flex items-end justify-between">
                        <h2 className="text-sm font-bold text-slate-800">Menu Pilihan</h2>
                        <span className="text-[11px] text-slate-500">{menuTampil.length} menu</span>
                    </div>

                    {menuTampil.length === 0 ? (
                        <div className="rounded-2xl border border-dashed border-emerald-200 bg-white px-4 py-10 text-center text-sm text-slate-500">
                            {menuVenue.length === 0
                                ? 'Belum ada menu tersedia di venue ini.'
                                : 'Menu tidak ditemukan. Coba kata kunci lain.'}
                        </div>
                    ) : (
                        <div className="grid grid-cols-2 gap-3">
                            {menuTampil.map((menu) => (
                                <div
                                    key={menu.id}
                                    className="group flex flex-col overflow-hidden rounded-2xl border border-emerald-100/80 bg-white shadow-sm transition duration-300 hover:-translate-y-0.5 hover:border-emerald-200 hover:shadow-lg hover:shadow-emerald-900/10"
                                >
                                    <div className="relative flex aspect-[5/4] items-center justify-center overflow-hidden bg-gradient-to-br from-emerald-100 via-emerald-50 to-teal-100">
                                        {menu.gambar_url ? (
                                            <img
                                                src={menu.gambar_url}
                                                alt={menu.nama}
                                                loading="lazy"
                                                className="h-full w-full object-cover transition duration-300 group-hover:scale-105"
                                            />
                                        ) : (
                                            <span className="text-emerald-300">
                                                <ImagePlaceholderIcon />
                                            </span>
                                        )}

                                        {menu.kategori && (
                                            <span className="absolute left-2 top-2 rounded-full bg-white/90 px-2 py-0.5 text-[9px] font-bold uppercase tracking-wide text-emerald-700 shadow-sm backdrop-blur">
                                                {menu.kategori}
                                            </span>
                                        )}
                                    </div>

                                    <div className="flex flex-1 flex-col p-3">
                                        <p className="truncate text-xs font-bold text-slate-800">{menu.nama}</p>

                                        <div className="mt-auto border-t border-slate-100 pt-2.5">
                                            {punyaVarianSuhu(menu) ? (
                                                <div className="flex items-end justify-between gap-2">
                                                    <div className="min-w-0">
                                                        <p className="text-[9px] font-bold uppercase tracking-wide text-slate-400">
                                                            {Number(menu.harga_dingin) === Number(menu.harga_panas)
                                                                ? 'Harga'
                                                                : 'Dingin'}
                                                        </p>
                                                        <p className="text-xs font-bold text-emerald-700">
                                                            {formatRupiah(menu.harga_dingin)}
                                                        </p>
                                                    </div>
                                                    <button
                                                        type="button"
                                                        onClick={() => bukaPilihVarian(menu)}
                                                        aria-label={`Pilih Dingin atau Panas untuk ${menu.nama}`}
                                                        className="relative flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-emerald-600 text-white shadow-sm shadow-emerald-600/30 transition hover:bg-emerald-700 active:scale-95"
                                                    >
                                                        <PlusIcon />
                                                        {jumlahUntukMenu(menu) > 0 && (
                                                            <span className="absolute -right-1.5 -top-1.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-lime-400 px-1 text-[9px] font-bold text-emerald-900 ring-2 ring-white">
                                                                {jumlahUntukMenu(menu)}
                                                            </span>
                                                        )}
                                                    </button>
                                                </div>
                                            ) : (
                                                <div className="flex items-center justify-between gap-2">
                                                    <span className="text-xs font-bold text-emerald-700">{formatRupiah(menu.harga)}</span>
                                                    <button
                                                        type="button"
                                                        onClick={() => tambahKeKeranjang(menu)}
                                                        aria-label={`Tambahkan ${menu.nama} ke keranjang`}
                                                        className="relative flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-emerald-600 text-white shadow-sm shadow-emerald-600/30 transition hover:bg-emerald-700 active:scale-95"
                                                    >
                                                        <PlusIcon />
                                                        {jumlahUntukMenu(menu) > 0 && (
                                                            <span className="absolute -right-1.5 -top-1.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-lime-400 px-1 text-[9px] font-bold text-emerald-900 ring-2 ring-white">
                                                                {jumlahUntukMenu(menu)}
                                                            </span>
                                                        )}
                                                    </button>
                                                </div>
                                            )}
                                        </div>
                                    </div>
                                </div>
                            ))}
                        </div>
                    )}
                </section>

                {/* <div className="rounded-2xl border border-emerald-100 bg-emerald-50/70 p-4">
                    <p className="text-xs font-bold text-emerald-900">Cara pesan</p>
                    <ol className="mt-1.5 space-y-1 text-[11px] leading-relaxed text-emerald-800">
                        <li>1. Pilih menu favoritmu dari daftar di atas.</li>
                        <li>2. Sampaikan pesanan ke petugas meja saat kamu mulai bermain.</li>
                        <li>3. Pembayaran dapat dilakukan bersama tagihan meja.</li>
                    </ol>
                </div> */}

                {!pengguna && (
                    <Link
                        href="/login"
                        className="flex items-center justify-center rounded-xl bg-emerald-600 px-4 py-3 text-xs font-bold text-white shadow-md shadow-emerald-500/30 transition hover:bg-emerald-700"
                    >
                        Masuk untuk mulai pesan
                    </Link>
                )}
                </>
                )}
            </main>

            {jumlahItem > 0 && (
                <button
                    type="button"
                    onClick={bukaKeranjang}
                    className="fixed bottom-24 right-4 z-30 flex items-center gap-3 rounded-2xl bg-emerald-600 px-4 py-3 text-white shadow-xl shadow-emerald-900/30 transition hover:bg-emerald-700 active:scale-95"
                >
                    <span className="relative">
                        <CartIcon className="h-5 w-5" />
                        <span className="absolute -right-2 -top-2 flex h-4 min-w-4 items-center justify-center rounded-full bg-lime-400 px-1 text-[9px] font-bold text-emerald-900">
                            {jumlahItem}
                        </span>
                    </span>
                    <span className="text-left leading-tight">
                        <span className="block text-[10px] font-medium text-emerald-100">Keranjang</span>
                        <span className="block text-xs font-bold">{formatRupiah(totalHarga)}</span>
                    </span>
                </button>
            )}

            {keranjangTerbuka && (
                <div
                    className="fixed inset-0 z-50 flex items-end justify-center bg-slate-900/50 p-0 backdrop-blur-sm sm:items-center sm:p-4"
                    onMouseDown={(event) => {
                        if (event.target === event.currentTarget && !processing) {
                            setKeranjangTerbuka(false);
                        }
                    }}
                >
                    <div className="max-h-[92vh] w-full overflow-y-auto rounded-t-3xl bg-white shadow-2xl sm:max-w-lg sm:rounded-2xl">
                        <div className="sticky top-0 z-10 flex items-start justify-between gap-4 border-b border-slate-100 bg-white px-5 py-4">
                            <div>
                                <h2 className="text-base font-bold text-slate-900">Keranjang Pesanan</h2>
                                <p className="mt-0.5 text-xs text-slate-500">
                                    {jumlahItem} item dipilih dari menu.
                                </p>
                            </div>
                            <button
                                type="button"
                                onClick={() => setKeranjangTerbuka(false)}
                                disabled={processing}
                                aria-label="Tutup keranjang"
                                className="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-600 disabled:opacity-60"
                            >
                                <ClearIcon />
                            </button>
                        </div>

                        {keranjang.length === 0 ? (
                            <div className="px-5 py-12 text-center text-sm text-slate-500">
                                Keranjang masih kosong. Pilih menu terlebih dahulu.
                            </div>
                        ) : (
                            <form onSubmit={kirimPesanan} className="space-y-4 px-5 py-4">
                                <ul className="space-y-2.5">
                                    {keranjang.map((item) => (
                                        <li key={item.kunci} className="flex items-center gap-3 rounded-xl border border-emerald-50 bg-emerald-50/40 p-2.5">
                                            <span className="flex h-12 w-12 shrink-0 items-center justify-center overflow-hidden rounded-lg bg-emerald-100 text-emerald-400">
                                                {item.gambar_url ? (
                                                    <img src={item.gambar_url} alt={item.nama} className="h-full w-full object-cover" />
                                                ) : (
                                                    <ImagePlaceholderIcon className="h-5 w-5" />
                                                )}
                                            </span>
                                            <div className="min-w-0 flex-1">
                                                <p className="truncate text-xs font-bold text-slate-800">
                                                    {item.nama}
                                                    {item.suhu && (
                                                        <span className="ml-1.5 rounded-full bg-sky-100 px-1.5 py-0.5 text-[9px] font-bold uppercase tracking-wide text-sky-700">
                                                            {item.suhu}
                                                        </span>
                                                    )}
                                                </p>
                                                <p className="text-[11px] font-semibold text-emerald-700">{formatRupiah(item.harga)}</p>
                                            </div>
                                            <div className="flex items-center gap-1.5">
                                                <button
                                                    type="button"
                                                    onClick={() => kurangiJumlah(item.kunci)}
                                                    aria-label={`Kurangi ${item.nama}`}
                                                    className="flex h-6 w-6 items-center justify-center rounded-full bg-white text-emerald-700 ring-1 ring-emerald-200 transition hover:bg-emerald-100"
                                                >
                                                    <MinusIcon />
                                                </button>
                                                <span className="w-5 text-center text-xs font-bold text-slate-800">{item.jumlah}</span>
                                                <button
                                                    type="button"
                                                    onClick={() => tambahJumlah(item.kunci)}
                                                    aria-label={`Tambah ${item.nama}`}
                                                    className="flex h-6 w-6 items-center justify-center rounded-full bg-emerald-600 text-white transition hover:bg-emerald-700"
                                                >
                                                    <PlusIcon />
                                                </button>
                                                <button
                                                    type="button"
                                                    onClick={() => hapusItem(item.kunci)}
                                                    aria-label={`Hapus ${item.nama}`}
                                                    className="ml-1 flex h-6 w-6 items-center justify-center rounded-full text-rose-500 transition hover:bg-rose-50"
                                                >
                                                    <TrashIcon />
                                                </button>
                                            </div>
                                        </li>
                                    ))}
                                </ul>

                                <div>
                                    <span className="mb-1.5 block text-xs font-semibold text-slate-700">
                                        Tipe Pesanan <span className="text-rose-500">*</span>
                                    </span>
                                    <div className="grid grid-cols-2 gap-2">
                                        {[
                                            { value: 'dine_in', label: 'Dine In', desc: 'Makan di tempat' },
                                            { value: 'take_away', label: 'Take Away', desc: 'Dibungkus' },
                                        ].map((tipe) => {
                                            const terpilih = data.tipe_pesanan === tipe.value;

                                            return (
                                                <button
                                                    key={tipe.value}
                                                    type="button"
                                                    onClick={() => setData('tipe_pesanan', tipe.value)}
                                                    aria-pressed={terpilih}
                                                    className={`rounded-xl border px-3 py-3 text-left transition active:scale-95 ${
                                                        terpilih
                                                            ? 'border-emerald-500 bg-emerald-50 ring-2 ring-emerald-500/30'
                                                            : 'border-slate-200 bg-white hover:border-emerald-300 hover:bg-emerald-50/60'
                                                    }`}
                                                >
                                                    <span className={`block text-xs font-bold ${terpilih ? 'text-emerald-700' : 'text-slate-700'}`}>
                                                        {tipe.label}
                                                    </span>
                                                    <span className="mt-0.5 block text-[10px] text-slate-500">{tipe.desc}</span>
                                                </button>
                                            );
                                        })}
                                    </div>
                                    {errors.tipe_pesanan && (
                                        <p className="mt-1.5 text-xs font-medium text-rose-600">{errors.tipe_pesanan}</p>
                                    )}
                                </div>

                                <div>
                                    <label htmlFor="atas_nama" className="mb-1.5 block text-xs font-semibold text-slate-700">
                                        Atas Nama <span className="text-rose-500">*</span>
                                    </label>
                                    <input
                                        id="atas_nama"
                                        type="text"
                                        value={data.atas_nama}
                                        onChange={(event) => setData('atas_nama', event.target.value)}
                                        placeholder="Contoh: Budi"
                                        maxLength={100}
                                        required
                                        className={`w-full rounded-xl border px-3.5 py-2.5 text-sm text-slate-800 shadow-sm transition placeholder:text-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 ${errors.atas_nama ? 'border-rose-300 bg-rose-50/40' : 'border-slate-200 bg-white'
                                            }`}
                                    />
                                    <p className="mt-1.5 text-[11px] text-slate-500">
                                        Nama ini akan dipanggil kasir saat pesanan siap.
                                    </p>
                                    {errors.atas_nama && <p className="mt-1.5 text-xs font-medium text-rose-600">{errors.atas_nama}</p>}
                                </div>

                                <div>
                                    <label htmlFor="nomor_meja" className="mb-1.5 block text-xs font-semibold text-slate-700">
                                        Nomor Meja <span className="text-rose-500">*</span>
                                    </label>
                                    <input
                                        id="nomor_meja"
                                        type="text"
                                        value={data.nomor_meja}
                                        onChange={(event) => setData('nomor_meja', event.target.value)}
                                        placeholder="Contoh: 12 atau A3"
                                        maxLength={20}
                                        required
                                        className={`w-full rounded-xl border px-3.5 py-2.5 text-sm text-slate-800 shadow-sm transition placeholder:text-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 ${errors.nomor_meja ? 'border-rose-300 bg-rose-50/40' : 'border-slate-200 bg-white'
                                            }`}
                                    />
                                    {errors.nomor_meja && <p className="mt-1.5 text-xs font-medium text-rose-600">{errors.nomor_meja}</p>}
                                    {mejaAwal && data.nomor_meja === mejaAwal.kode_meja && (
                                        <p className="mt-1.5 inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-1 text-[11px] font-semibold text-emerald-700">
                                            <span className="h-1.5 w-1.5 rounded-full bg-emerald-500" />
                                            Terisi otomatis dari QR {mejaAwal.nama_meja}
                                        </p>
                                    )}
                                </div>

                                <div>
                                    <label htmlFor="catatan" className="mb-1.5 block text-xs font-semibold text-slate-700">
                                        Catatan (opsional)
                                    </label>
                                    <textarea
                                        id="catatan"
                                        value={data.catatan}
                                        onChange={(event) => setData('catatan', event.target.value)}
                                        rows={2}
                                        maxLength={255}
                                        placeholder="Contoh: tanpa sambal, es teh tanpa gula..."
                                        className={`w-full resize-none rounded-xl border px-3.5 py-2.5 text-sm text-slate-800 shadow-sm transition placeholder:text-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 ${errors.catatan ? 'border-rose-300 bg-rose-50/40' : 'border-slate-200 bg-white'
                                            }`}
                                    />
                                    {errors.catatan && <p className="mt-1.5 text-xs font-medium text-rose-600">{errors.catatan}</p>}
                                </div>

                                {errors.items && <p className="text-xs font-medium text-rose-600">{errors.items}</p>}

                                <div className="flex items-center justify-between rounded-xl bg-emerald-50 px-4 py-3">
                                    <span className="text-xs font-semibold text-emerald-800">Total Pembayaran</span>
                                    <span className="text-sm font-bold text-emerald-700">{formatRupiah(totalHarga)}</span>
                                </div>

                                <div className="flex flex-col-reverse gap-2 pt-1 sm:flex-row sm:justify-end">
                                    <button
                                        type="button"
                                        onClick={() => setKeranjangTerbuka(false)}
                                        disabled={processing}
                                        className="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-600 transition hover:bg-slate-50 disabled:opacity-60"
                                    >
                                        Lanjut Pilih Menu
                                    </button>
                                    <button
                                        type="submit"
                                        disabled={processing}
                                        className="inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-600 px-5 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-60"
                                    >
                                        {processing && (
                                            <svg className="h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" />
                                                <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8V0C5.373 0 0 5.373 0 12h4Z" />
                                            </svg>
                                        )}
                                        {processing ? 'Mengirim...' : 'Kirim ke Kasir'}
                                    </button>
                                </div>
                            </form>
                        )}
                    </div>
                </div>
            )}

            {menuVarian && (
                <div
                    className="fixed inset-0 z-50 flex items-end justify-center bg-slate-900/50 backdrop-blur-sm sm:items-center sm:p-4"
                    onMouseDown={(event) => {
                        if (event.target === event.currentTarget) {
                            setMenuVarian(null);
                        }
                    }}
                >
                    <div className="w-full rounded-t-3xl bg-white shadow-2xl sm:max-w-sm sm:rounded-2xl">
                        <div className="flex items-start justify-between gap-4 border-b border-slate-100 px-5 py-4">
                            <div className="min-w-0">
                                <h2 className="truncate text-base font-bold text-slate-900">{menuVarian.nama}</h2>
                                <p className="mt-0.5 text-xs text-slate-500">Pilih minuman dingin atau panas.</p>
                            </div>
                            <button
                                type="button"
                                onClick={() => setMenuVarian(null)}
                                aria-label="Tutup pilihan suhu"
                                className="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-600"
                            >
                                <ClearIcon />
                            </button>
                        </div>

                        <div className="space-y-4 px-5 py-4">
                            <div className="grid grid-cols-2 gap-3">
                                {['Dingin', 'Panas'].map((suhu) => {
                                    const harga = hargaUntukSuhu(menuVarian, suhu);
                                    const terpilih = suhuPilihan === suhu;
                                    const jumlahDiKeranjang = keranjang.find(
                                        (item) => item.kunci === `${menuVarian.id}-${suhu}`,
                                    )?.jumlah ?? 0;

                                    return (
                                        <button
                                            key={suhu}
                                            type="button"
                                            onClick={() => setSuhuPilihan(suhu)}
                                            aria-pressed={terpilih}
                                            className={`flex flex-col items-center gap-1 rounded-2xl border px-3 py-4 text-center transition active:scale-95 ${
                                                terpilih
                                                    ? 'border-emerald-500 bg-emerald-50 ring-2 ring-emerald-500/30'
                                                    : 'border-emerald-100 bg-white hover:border-emerald-300 hover:bg-emerald-50/60'
                                            }`}
                                        >
                                            <span className="text-sm font-bold text-slate-800">{suhu}</span>
                                            <span className="text-xs font-semibold text-emerald-700">{formatRupiah(harga)}</span>
                                            {jumlahDiKeranjang > 0 && (
                                                <span className="mt-0.5 text-[9px] font-semibold text-slate-500">
                                                    {jumlahDiKeranjang} di keranjang
                                                </span>
                                            )}
                                        </button>
                                    );
                                })}
                            </div>

                            <div className="flex items-center justify-between rounded-xl border border-slate-200 px-3.5 py-2.5">
                                <div className="min-w-0">
                                    <p className="text-xs font-semibold text-slate-700">Jumlah</p>
                                    <p className="text-[11px] font-semibold text-emerald-700">
                                        {formatRupiah(hargaUntukSuhu(menuVarian, suhuPilihan) * jumlahPilihan)}
                                    </p>
                                </div>
                                <div className="flex items-center gap-2">
                                    <button
                                        type="button"
                                        onClick={() => setJumlahPilihan((jumlah) => Math.max(1, jumlah - 1))}
                                        disabled={jumlahPilihan <= 1}
                                        aria-label="Kurangi jumlah"
                                        className="flex h-7 w-7 items-center justify-center rounded-full bg-white text-emerald-700 ring-1 ring-emerald-200 transition hover:bg-emerald-100 disabled:cursor-not-allowed disabled:opacity-40"
                                    >
                                        <MinusIcon />
                                    </button>
                                    <span className="w-5 text-center text-sm font-bold text-slate-800">{jumlahPilihan}</span>
                                    <button
                                        type="button"
                                        onClick={() => setJumlahPilihan((jumlah) => Math.min(99, jumlah + 1))}
                                        aria-label="Tambah jumlah"
                                        className="flex h-7 w-7 items-center justify-center rounded-full bg-emerald-600 text-white transition hover:bg-emerald-700"
                                    >
                                        <PlusIcon />
                                    </button>
                                </div>
                            </div>

                            <button
                                type="button"
                                onClick={simpanPilihanVarian}
                                className="w-full rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-emerald-700 active:scale-95"
                            >
                                Tambah ke Keranjang
                            </button>
                        </div>
                    </div>
                </div>
            )}

            {pesananBerhasil && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4 backdrop-blur-sm">
                    <div className="w-full max-w-sm overflow-hidden rounded-3xl bg-white p-6 text-center shadow-2xl">
                        <span className="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-emerald-100 text-emerald-600">
                            <svg xmlns="http://www.w3.org/2000/svg" className="h-8 w-8" fill="none" viewBox="0 0 24 24" strokeWidth={2.4} stroke="currentColor">
                                <path strokeLinecap="round" strokeLinejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                            </svg>
                        </span>

                        <h2 className="mt-4 text-lg font-bold text-slate-900">Pesanan Berhasil Dikirim!</h2>
                        <p className="mt-1.5 text-xs leading-relaxed text-slate-500">
                            Pesananmu sudah diteruskan ke kasir. Mohon tunggu pesanan disiapkan, ya.
                        </p>

                        <div className="mt-4 space-y-1.5 rounded-2xl bg-emerald-50 p-3.5 text-left text-xs text-slate-600">
                            <div className="flex items-center justify-between gap-3">
                                <span>Kode Pesanan</span>
                                <span className="font-mono font-bold text-slate-900">{pesananBerhasil.kode_pesanan}</span>
                            </div>
                            <div className="flex items-center justify-between gap-3">
                                <span>Nomor Meja</span>
                                <span className="font-bold text-slate-900">{pesananBerhasil.nomor_meja}</span>
                            </div>
                            <div className="flex items-center justify-between gap-3">
                                <span>Tipe Pesanan</span>
                                <span className="font-bold text-slate-900">{pesananBerhasil.tipe_pesanan}</span>
                            </div>
                            <div className="flex items-center justify-between gap-3 border-t border-emerald-200/70 pt-1.5">
                                <span>Total</span>
                                <span className="font-bold text-emerald-700">{formatRupiah(pesananBerhasil.total)}</span>
                            </div>
                        </div>

                        <div className="mt-5 space-y-2">
                            <Link
                                href="/riwayat/pesanan"
                                className="block rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-bold text-white shadow-md shadow-emerald-500/30 transition hover:bg-emerald-700 active:scale-95"
                            >
                                Lihat Riwayat Pesanan
                            </Link>
                            <button
                                type="button"
                                onClick={() => setPesananBerhasil(null)}
                                className="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-bold text-slate-600 transition hover:bg-slate-50 active:scale-95"
                            >
                                Pesan Lagi
                            </button>
                        </div>
                    </div>
                </div>
            )}
        </>
    );
}

Pesan.layout = (page) => <UserLayout>{page}</UserLayout>;
