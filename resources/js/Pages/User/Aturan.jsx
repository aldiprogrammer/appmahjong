import { Head, Link } from '@inertiajs/react';
import UserLayout from '../../Layouts/UserLayout';

const ubinAngka = [
    {
        nama: 'Karakter',
        hanzi: '',
        keterangan: 'Angka 1 sampai 9 ditulis dengan aksara Hanzi (一-九) di atas aksara 萬.',
        badge: 'bg-rose-50 text-rose-600 ring-rose-100',
    },
    {
        nama: 'Lingkaran',
        hanzi: '',
        keterangan: 'Angka 1 sampai 9 dalam bentuk bulatan berwarna.',
        badge: 'bg-sky-50 text-sky-600 ring-sky-100',
    },
    {
        nama: 'Bambu',
        hanzi: '',
        keterangan: 'Angka 1 sampai 9 dengan gambar batang bambu.',
        badge: 'bg-emerald-50 text-emerald-600 ring-emerald-100',
    },
];

const ubinNaga = [
    { karakter: '中', nama: 'Naga Merah', tone: 'text-rose-600', catatan: 'Sering disebut Hong Chung.' },
    { karakter: '發', nama: 'Naga Hijau', tone: 'text-emerald-600', catatan: 'Sering disebut Hatsu.' },
    { karakter: '白', nama: 'Naga Putih', tone: 'text-slate-700', catatan: 'Ubin kosong dengan bingkai biru (Haku).' },
];

const ubinAngin = [
    { karakter: '東', nama: 'Timur', tone: 'text-sky-700' },
    { karakter: '南', nama: 'Selatan', tone: 'text-sky-700' },
    { karakter: '西', nama: 'Barat', tone: 'text-sky-700' },
    { karakter: '北', nama: 'Utara', tone: 'text-sky-700' },
];

const istilahDasar = [
    {
        nama: 'Pung',
        arti: 'Tiga ubin yang sama persis.',
        contoh: [
            { karakter: '中', tone: 'text-rose-600' },
            { karakter: '中', tone: 'text-rose-600' },
            { karakter: '中', tone: 'text-rose-600' },
        ],
    },
    {
        nama: 'Chi',
        arti: 'Tiga ubin berurutan dari jenis yang sama.',
        contoh: [
            { karakter: '三', sub: '萬' },
            { karakter: '四', sub: '萬' },
            { karakter: '五', sub: '萬' },
        ],
    },
    {
        nama: 'Kong',
        arti: 'Empat ubin yang sama persis.',
        contoh: [
            { karakter: '發', tone: 'text-emerald-600' },
            { karakter: '發', tone: 'text-emerald-600' },
            { karakter: '發', tone: 'text-emerald-600' },
            { karakter: '發', tone: 'text-emerald-600' },
        ],
    },
    {
        nama: 'Pair',
        arti: 'Dua ubin sama sebagai pasangan penutup.',
        contoh: [
            { karakter: '五', sub: '萬' },
            { karakter: '五', sub: '萬' },
        ],
    },
];

function Ubin({ karakter, sub, tone = 'text-slate-800', className = '' }) {
    return (
        <span
            className={`flex flex-col items-center justify-center rounded-lg border-b-4 border-emerald-950/20 bg-gradient-to-b from-white to-emerald-50 shadow-md ${className}`}
        >
            <span className={`font-black leading-none ${tone}`}>{karakter}</span>
            {sub && <span className="mt-0.5 text-[8px] font-semibold leading-none text-slate-400">{sub}</span>}
        </span>
    );
}

const polaTitik = {
    1: { baris: 3, kolom: 3, posisi: [[2, 2]] },
    2: { baris: 3, kolom: 3, posisi: [[1, 2], [3, 2]] },
    3: { baris: 3, kolom: 3, posisi: [[1, 1], [2, 2], [3, 3]] },
    4: { baris: 3, kolom: 3, posisi: [[1, 1], [1, 3], [3, 1], [3, 3]] },
    5: { baris: 3, kolom: 3, posisi: [[1, 1], [1, 3], [2, 2], [3, 1], [3, 3]] },
    6: { baris: 3, kolom: 3, posisi: [[1, 1], [2, 1], [3, 1], [1, 3], [2, 3], [3, 3]] },
    7: { baris: 3, kolom: 3, posisi: [[1, 2], [2, 1], [2, 2], [2, 3], [3, 1], [3, 2], [3, 3]] },
    8: { baris: 2, kolom: 4, posisi: [[1, 1], [1, 2], [1, 3], [1, 4], [2, 1], [2, 2], [2, 3], [2, 4]] },
    9: { baris: 3, kolom: 3, posisi: [[1, 1], [1, 2], [1, 3], [2, 1], [2, 2], [2, 3], [3, 1], [3, 2], [3, 3]] },
};

const polaBambu = {
    2: { baris: 2, kolom: 3, posisi: [[1, 2], [2, 2]] },
    3: { baris: 2, kolom: 3, posisi: [[1, 2], [2, 1], [2, 3]] },
    4: { baris: 2, kolom: 2, posisi: [[1, 1], [1, 2], [2, 1], [2, 2]] },
    5: { baris: 3, kolom: 3, posisi: [[1, 1], [1, 3], [2, 2], [3, 1], [3, 3]] },
    6: { baris: 2, kolom: 3, posisi: [[1, 1], [1, 2], [1, 3], [2, 1], [2, 2], [2, 3]] },
    7: { baris: 3, kolom: 3, posisi: [[1, 2], [2, 1], [2, 2], [2, 3], [3, 1], [3, 2], [3, 3]] },
    8: { baris: 2, kolom: 4, posisi: [[1, 1], [1, 2], [1, 3], [1, 4], [2, 1], [2, 2], [2, 3], [2, 4]] },
    9: { baris: 3, kolom: 3, posisi: [[1, 1], [1, 2], [1, 3], [2, 1], [2, 2], [2, 3], [3, 1], [3, 2], [3, 3]] },
};

const angkaHanzi = ['', '一', '二', '三', '四', '五', '六', '七', '八', '九'];

const warnaTitik = ['#1d4ed8', '#15803d', '#dc2626'];

function BurungBambu() {
    return (
        <svg viewBox="0 0 32 32" className="h-7 w-7" fill="none">
            <ellipse cx="16" cy="19" rx="8" ry="5.5" fill="#15803d" />
            <circle cx="21.5" cy="12" r="4" fill="#15803d" />
            <path d="M25.5 11.5 31 13.5l-5.5 2z" fill="#dc2626" />
            <circle cx="22.6" cy="11" r="0.9" fill="#ffffff" />
            <path d="M9 17c-3 .8-5 3-5.8 6 4-.2 7-1.4 9-3.4" fill="#16a34a" />
            <path d="M13 24.5 11 29M18.5 24.5 20.5 29" stroke="#dc2626" strokeWidth="1.4" strokeLinecap="round" />
        </svg>
    );
}

function WajahAngka({ jenis, angka }) {
    if (jenis === 'Lingkaran') {
        const pola = polaTitik[angka];

        return (
            <span
                className="grid h-full w-full place-items-center p-0.5"
                style={{
                    gridTemplateColumns: `repeat(${pola.kolom}, minmax(0, 1fr))`,
                    gridTemplateRows: `repeat(${pola.baris}, minmax(0, 1fr))`,
                }}
            >
                {pola.posisi.map(([baris, kolom], index) => (
                    <span
                        key={`${baris}-${kolom}`}
                        className="flex items-center justify-center"
                        style={{ gridArea: `${baris} / ${kolom}` }}
                    >
                        <span
                            className={`rounded-full ${angka === 1 ? 'h-3 w-3' : 'h-1.5 w-1.5'}`}
                            style={{ backgroundColor: warnaTitik[index % warnaTitik.length] }}
                        />
                    </span>
                ))}
            </span>
        );
    }

    if (jenis === 'Bambu') {
        if (angka === 1) {
            return <BurungBambu />;
        }

        const pola = polaBambu[angka];

        return (
            <span
                className="grid h-full w-full place-items-center p-0.5"
                style={{
                    gridTemplateColumns: `repeat(${pola.kolom}, minmax(0, 1fr))`,
                    gridTemplateRows: `repeat(${pola.baris}, minmax(0, 1fr))`,
                }}
            >
                {pola.posisi.map(([baris, kolom], index) => (
                    <span
                        key={`${baris}-${kolom}`}
                        className="flex items-center justify-center"
                        style={{ gridArea: `${baris} / ${kolom}` }}
                    >
                        <span className="relative h-4 w-[3px] rounded-full bg-gradient-to-b from-emerald-500 via-emerald-600 to-emerald-700 shadow-sm">
                            <span
                                className={`absolute inset-x-0 top-1/2 h-[1px] -translate-y-1/2 ${index % 3 === 1 ? 'bg-rose-300' : 'bg-emerald-200'
                                    }`}
                            />
                        </span>
                    </span>
                ))}
            </span>
        );
    }

    return (
        <span className="flex flex-col items-center leading-none" style={{ fontFamily: 'serif' }}>
            <span className="text-[15px] font-black text-rose-600">{angkaHanzi[angka]}</span>
            <span className="mt-0.5 text-[8px] font-bold text-rose-500">萬</span>
        </span>
    );
}

function UbinAngkaKeping({ jenis, angka, className = '' }) {
    return (
        <span
            className={`flex items-center justify-center rounded-lg border-b-4 border-emerald-950/20 bg-gradient-to-b from-white to-emerald-50 p-0.5 shadow-md ${className}`}
        >
            <WajahAngka jenis={jenis} angka={angka} />
        </span>
    );
}

function UbinKecil({ karakter, sub, tone = 'text-slate-800' }) {
    return (
        <span className="flex h-10 w-7 flex-col items-center justify-center rounded-md border-b-[3px] border-emerald-950/20 bg-gradient-to-b from-white to-emerald-50 shadow-sm">
            <span className={`text-sm font-black leading-none ${tone}`}>{karakter}</span>
            {sub && <span className="mt-0.5 text-[6px] font-bold leading-none text-rose-500">{sub}</span>}
        </span>
    );
}

function Bagian({ nomor, judul, deskripsi, children }) {
    return (
        <section className="overflow-hidden rounded-2xl border border-emerald-100 bg-white shadow-sm">
            <div className="flex items-center gap-3 border-b border-emerald-50 bg-gradient-to-r from-emerald-50/80 to-white px-4 py-3">
                <span className="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-emerald-600 text-xs font-black text-white shadow-sm shadow-emerald-600/30">
                    {nomor}
                </span>
                <div className="min-w-0">
                    <p className="text-sm font-bold text-slate-800">{judul}</p>
                    <p className="truncate text-[10px] text-slate-500">{deskripsi}</p>
                </div>
            </div>

            <div className="px-4 py-4">{children}</div>
        </section>
    );
}

export default function Aturan() {
    return (
        <>
            <Head title="Aturan" />

            <header className="relative overflow-hidden rounded-b-[2rem] bg-gradient-to-br from-emerald-600 via-emerald-700 to-emerald-900 px-5 pb-16 pt-6 text-white">
                <div className="pointer-events-none absolute -right-12 -top-12 h-44 w-44 rounded-full bg-white/10" />
                <div className="pointer-events-none absolute -bottom-20 -left-12 h-48 w-48 rounded-full bg-emerald-300/20" />

                <div className="relative">
                    <span className="inline-flex items-center gap-1.5 rounded-full bg-white/15 px-2.5 py-1 text-[10px] font-bold uppercase tracking-widest ring-1 ring-white/20">
                        <span className="h-1.5 w-1.5 rounded-full bg-lime-300" />
                        Panduan Mahjong
                    </span>
                    <h1 className="mt-3 text-xl font-bold leading-snug">Kenali Ubin Mahjong</h1>
                    <p className="mt-1 max-w-[17rem] text-xs leading-relaxed text-emerald-100">
                        Pengenalan singkat dan jenis-jenis ubin mahjong sebelum kamu mulai bermain.
                    </p>

                    <div className="mt-4 flex items-center gap-2">
                        <Ubin karakter="中" tone="text-rose-600" className="-rotate-6 h-11 w-8 text-lg" />
                        <Ubin karakter="發" tone="text-emerald-600" className="h-11 w-8 text-lg" />
                        <Ubin karakter="東" tone="text-sky-700" className="rotate-6 h-11 w-8 text-lg" />
                    </div>
                </div>
            </header>

            <main className="relative -mt-9 space-y-3 px-5 pb-8">
                <section className="rounded-2xl border border-emerald-100 bg-white p-4 shadow-lg shadow-emerald-900/5">
                    <div className="flex items-center gap-2">
                        <span className="flex h-8 w-8 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600">
                            <svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4" fill="none" viewBox="0 0 24 24" strokeWidth={1.8} stroke="currentColor">
                                <path strokeLinecap="round" strokeLinejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25" />
                            </svg>
                        </span>
                        <p className="text-xs font-bold text-slate-800">Pengenalan Mahjong</p>
                    </div>

                    <p className="mt-2.5 text-[11px] leading-relaxed text-slate-600">
                        Mahjong adalah permainan strategi untuk <span className="font-semibold text-slate-800">4 pemain</span>.
                        Setiap pemain berusaha menyusun <span className="font-semibold text-slate-800">4 set</span> dan{' '}
                        <span className="font-semibold text-slate-800">1 pasangan</span> dari ubin yang dimiliki. Pemain
                        bergiliran mengambil dan membuang ubin sampai ada yang menang.
                    </p>

                    <div className="mt-3 grid grid-cols-3 gap-2">
                        <div className="rounded-xl bg-emerald-50/70 px-2 py-2.5 text-center ring-1 ring-emerald-100">
                            <p className="text-base font-black text-emerald-700">144</p>
                            <p className="text-[9px] font-medium text-slate-500">Total ubin</p>
                        </div>
                        <div className="rounded-xl bg-emerald-50/70 px-2 py-2.5 text-center ring-1 ring-emerald-100">
                            <p className="text-base font-black text-emerald-700">4</p>
                            <p className="text-[9px] font-medium text-slate-500">Pemain</p>
                        </div>
                        <div className="rounded-xl bg-emerald-50/70 px-2 py-2.5 text-center ring-1 ring-emerald-100">
                            <p className="text-sm font-black leading-tight text-emerald-700">4 set + 1 pair</p>
                            <p className="text-[9px] font-medium text-slate-500">Target menang</p>
                        </div>
                    </div>
                </section>

                <Bagian nomor="1" judul="Ubin Angka" deskripsi="3 jenis, angka 1-9, total 108 ubin">
                    <div className="space-y-3.5">
                        {ubinAngka.map((jenis) => (
                            <div key={jenis.nama}>
                                <div className="mb-1.5 flex items-center justify-between">
                                    <p className="text-[11px] font-bold text-slate-700">
                                        {jenis.nama} <span className="font-normal text-slate-400">{jenis.hanzi}</span>
                                    </p>
                                    <span className={`rounded-full px-2 py-0.5 text-[9px] font-bold ring-1 ${jenis.badge}`}>
                                        1 - 9
                                    </span>
                                </div>

                                <div className="space-y-1.5">
                                    <div className="flex justify-center gap-1.5">
                                        {Array.from({ length: 5 }, (_, index) => (
                                            <UbinAngkaKeping
                                                key={index}
                                                jenis={jenis.nama}
                                                angka={index + 1}
                                                className="aspect-[3/4] w-10"
                                            />
                                        ))}
                                    </div>
                                    <div className="flex justify-center gap-1.5">
                                        {Array.from({ length: 4 }, (_, index) => (
                                            <UbinAngkaKeping
                                                key={index + 5}
                                                jenis={jenis.nama}
                                                angka={index + 6}
                                                className="aspect-[3/4] w-10"
                                            />
                                        ))}
                                    </div>
                                </div>

                                <p className="mt-1.5 text-[10px] leading-relaxed text-slate-500">{jenis.keterangan}</p>
                            </div>
                        ))}
                    </div>

                    <p className="mt-3 rounded-xl bg-emerald-50/70 px-3 py-2 text-[10px] leading-relaxed text-emerald-800">
                        Setiap angka memiliki 4 keping, sehingga total ubin angka adalah 3 jenis x 9 angka x 4 keping ={' '}
                        <span className="font-bold">108 ubin</span>.
                    </p>
                </Bagian>

                <Bagian nomor="2" judul="Ubin Naga" deskripsi="3 jenis naga, total 12 ubin">
                    <div className="grid grid-cols-3 gap-2">
                        {ubinNaga.map((naga) => (
                            <div key={naga.karakter} className="flex flex-col items-center rounded-xl bg-emerald-50/40 p-2.5 ring-1 ring-emerald-100/70">
                                {naga.karakter === '白' ? (
                                    <span className="flex h-14 w-11 items-center justify-center rounded-lg border-b-4 border-emerald-950/20 bg-gradient-to-b from-white to-emerald-50 shadow-md">
                                        <span className="h-8 w-6 rounded-[3px] border-2 border-sky-700/80" />
                                    </span>
                                ) : (
                                    <Ubin karakter={naga.karakter} tone={naga.tone} className="h-14 w-11 text-xl" />
                                )}
                                <p className="mt-1.5 text-[10px] font-bold text-slate-700">{naga.nama}</p>
                                <p className="mt-0.5 text-center text-[9px] leading-snug text-slate-500">{naga.catatan}</p>
                            </div>
                        ))}
                    </div>

                    <p className="mt-3 text-[10px] leading-relaxed text-slate-500">
                        Ubin naga tidak memiliki urutan. Masing-masing berjumlah 4 keping sehingga totalnya{' '}
                        <span className="font-semibold text-slate-700">12 ubin</span>. Naga termasuk ubin spesial yang
                        nilainya lebih tinggi.
                    </p>
                </Bagian>

                <Bagian nomor="3" judul="Ubin Angin" deskripsi="4 arah mata angin, total 16 ubin">
                    <div className="grid grid-cols-4 gap-2">
                        {ubinAngin.map((angin) => (
                            <div key={angin.karakter} className="flex flex-col items-center rounded-xl bg-emerald-50/40 p-2 ring-1 ring-emerald-100/70">
                                <Ubin karakter={angin.karakter} tone={angin.tone} className="h-14 w-11 text-xl" />
                                <p className="mt-1.5 text-[10px] font-bold text-slate-700">{angin.nama}</p>
                            </div>
                        ))}
                    </div>

                    <p className="mt-3 text-[10px] leading-relaxed text-slate-500">
                        Ubin angin melambangkan arah duduk pemain: Timur, Selatan, Barat, dan Utara. Masing-masing
                        berjumlah 4 keping sehingga totalnya <span className="font-semibold text-slate-700">16 ubin</span>.
                    </p>
                </Bagian>

                <Bagian nomor="4" judul="Ubin Bunga dan Musim" deskripsi="8 ubin bonus, total 144 ubin">
                    <div className="flex flex-wrap gap-2">
                        {['梅', '蘭', '菊', '竹'].map((karakter) => (
                            <Ubin key={karakter} karakter={karakter} sub="Bunga" tone="text-fuchsia-600" className="h-14 w-11 text-lg" />
                        ))}
                        {['春', '夏', '秋', '冬'].map((karakter) => (
                            <Ubin key={karakter} karakter={karakter} sub="Musim" tone="text-amber-600" className="h-14 w-11 text-lg" />
                        ))}
                    </div>

                    <p className="mt-3 text-[10px] leading-relaxed text-slate-500">
                        4 ubin bunga dan 4 ubin musim berfungsi sebagai ubin bonus. Jika kamu mengambilnya, ubin diganti
                        dari ujung tumpukan dan tidak dihitung dalam set. Dengan 8 ubin ini, total satu set mahjong
                        menjadi <span className="font-semibold text-slate-700">144 ubin</span>.
                    </p>
                </Bagian>

                <Bagian nomor="5" judul="Istilah Dasar" deskripsi="Kombinasi yang perlu kamu kenali">
                    <div className="space-y-2">
                        {istilahDasar.map((istilah) => (
                            <div
                                key={istilah.nama}
                                className="flex items-center justify-between gap-3 rounded-xl border border-emerald-100 bg-white px-3 py-2.5"
                            >
                                <div className="min-w-0">
                                    <p className="text-xs font-bold text-emerald-700">{istilah.nama}</p>
                                    <p className="mt-0.5 text-[10px] leading-relaxed text-slate-500">{istilah.arti}</p>
                                </div>

                                <div className="flex shrink-0 gap-1">
                                    {istilah.contoh.map((keping, index) => (
                                        <UbinKecil
                                            key={index}
                                            karakter={keping.karakter}
                                            sub={keping.sub}
                                            tone={keping.tone}
                                        />
                                    ))}
                                </div>
                            </div>
                        ))}
                    </div>

                    <div className="mt-3 flex items-center gap-2 rounded-xl bg-emerald-50/70 px-3 py-2.5">
                        <span className="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-emerald-600 text-white">
                            <svg xmlns="http://www.w3.org/2000/svg" className="h-3 w-3" fill="none" viewBox="0 0 24 24" strokeWidth={2.6} stroke="currentColor">
                                <path strokeLinecap="round" strokeLinejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                            </svg>
                        </span>
                        <p className="text-[10px] leading-relaxed text-emerald-800">
                            Sudah kenal ubinnya? Saatnya pilih meja favoritmu dan mulai bermain.
                        </p>
                    </div>
                </Bagian>

                <Link
                    href="/booking"
                    className="flex items-center justify-center rounded-xl bg-emerald-600 px-4 py-3 text-xs font-bold text-white shadow-md shadow-emerald-500/30 transition hover:bg-emerald-700"
                >
                    Mulai Booking Meja
                </Link>
            </main>
        </>
    );
}

Aturan.layout = (page) => <UserLayout>{page}</UserLayout>;
