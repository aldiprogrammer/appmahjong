import { useEffect, useState } from 'react';
import { Head, Link } from '@inertiajs/react';

function MahjongTile({ label, tone, className = '' }) {
    return (
        <span
            className={`flex h-16 w-12 items-center justify-center rounded-xl border-b-4 border-emerald-950/25 bg-gradient-to-b from-white to-emerald-50 shadow-xl ${className}`}
        >
            <span className={`text-2xl font-black leading-none ${tone}`}>{label}</span>
        </span>
    );
}

function scrollToSection(event, id) {
    event.preventDefault();
    document.getElementById(id)?.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

const menuSections = [
    { label: 'Home', id: 'home' },
    { label: 'Tentang', id: 'tentang' },
    { label: 'Tutorial', id: 'tutorial' },
    { label: 'Lokasi', id: 'lokasi' },
];

// ID video YouTube untuk section Tutorial — ganti di sini saja, ambil dari
// URL video YouTube, contoh: https://youtu.be/<ID> atau watch?v=<ID>.
const videoIdTutorial = 'SOsLu3EY9B4';

/**
 * Embed YouTube gaya click-to-load: thumbnail dimuat dulu, iframe YouTube
 * hanya dibuat setelah tombol putar diklik supaya landing page tetap ringan.
 */
function VideoTutorialYoutube({ videoId }) {
    const [putarVideo, setPutarVideo] = useState(false);
    const [sumberPoster, setSumberPoster] = useState(`https://i.ytimg.com/vi/${videoId}/maxresdefault.jpg`);

    const posterTersedia = sumberPoster !== null;

    const gagalMuatPoster = () => {
        if (sumberPoster?.includes('maxresdefault')) {
            setSumberPoster(`https://i.ytimg.com/vi/${videoId}/hqdefault.jpg`);

            return;
        }

        setSumberPoster(null);
    };

    return (
        <div
            className={`relative aspect-video overflow-hidden shadow-2xl shadow-emerald-950/50 ring-1 ring-white/15 rounded-[2rem] ${posterTersedia ? '' : 'bg-gradient-to-br from-emerald-800 to-emerald-950'}`}
        >
            {putarVideo ? (
                <iframe
                    src={`https://www.youtube-nocookie.com/embed/${videoId}?autoplay=1&rel=0`}
                    title="Video tutorial mahjong"
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                    allowFullScreen
                    className="absolute inset-0 h-full w-full"
                />
            ) : (
                <button
                    type="button"
                    onClick={() => setPutarVideo(true)}
                    aria-label="Putar video tutorial mahjong"
                    className="group absolute inset-0 h-full w-full"
                >
                    {posterTersedia && (
                        <img
                            src={sumberPoster}
                            alt="Poster video tutorial mahjong"
                            onError={gagalMuatPoster}
                            className="absolute inset-0 h-full w-full object-cover transition duration-500 group-hover:scale-105"
                        />
                    )}
                    <span
                        className={`absolute inset-0 ${posterTersedia
                            ? 'bg-gradient-to-t from-emerald-950/70 via-emerald-950/10 to-transparent'
                            : 'opacity-0 group-hover:opacity-100 bg-emerald-50/5 transition'} transition`}
                    />
                    <span className="absolute inset-0 flex items-center justify-center">
                        <span className="relative flex h-16 w-16 items-center justify-center rounded-full bg-white/95 shadow-2xl transition duration-300 group-hover:scale-110">
                            <span className="absolute inset-0 animate-ping rounded-full bg-white/25" />
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" className="ml-1 h-7 w-7 fill-emerald-700" aria-hidden="true">
                                <path d="M8 5.14v13.72L19 12 8 5.14Z" />
                            </svg>
                        </span>
                    </span>
                    <span className="absolute left-4 top-4 inline-flex items-center gap-1.5 rounded-full bg-emerald-950/80 px-3 py-1.5 text-[10px] font-black uppercase tracking-wide text-lime-300 backdrop-blur">
                        <svg xmlns="http://www.w3.org/2000/svg" className="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" strokeWidth={2} stroke="currentColor" aria-hidden="true">
                            <path strokeLinecap="round" strokeLinejoin="round" d="m15.75 10.5 4.72-4.72a.75.75 0 0 1 1.28.53v11.38a.75.75 0 0 1-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 0 0 2.25-2.25v-9a2.25 2.25 0 0 0-2.25-2.25h-9A2.25 2.25 0 0 0 2.25 7.5v9a2.25 2.25 0 0 0 2.25 2.25Z" />
                        </svg>
                        Video Tutorial
                    </span>
                </button>
            )}
        </div>
    );
}

export default function Landing({ pengguna, mejas, hargas }) {
    const [menuOpen, setMenuOpen] = useState(false);

    useEffect(() => {
        document.documentElement.style.scrollBehavior = 'smooth';

        return () => {
            document.documentElement.style.scrollBehavior = '';
        };
    }, []);

    const handleAnchor = (event, id) => {
        event.preventDefault();
        setMenuOpen(false);
        scrollToSection(event, id);
    };

    return (
        <>
            <Head title="Landing" />

            <div className="min-h-screen bg-[#f6faf7]">
                {/* Navbar */}
                <header className="fixed inset-x-0 top-0 z-50 border-b border-emerald-900/10 bg-emerald-950/90 shadow-lg shadow-emerald-950/20 backdrop-blur-md">
                    <nav className="mx-auto flex max-w-6xl items-center justify-between px-4 py-3 sm:px-6">
                        <a
                            href="#home"
                            onClick={(event) => handleAnchor(event, 'home')}
                            className="flex items-center gap-2.5 text-white"
                        >
                            <span className="flex h-10 w-8 items-center justify-center rounded-lg border-b-2 border-emerald-950/30 bg-gradient-to-b from-white to-emerald-50 text-lg font-black text-rose-600 shadow-md">
                                中
                            </span>
                            <span className="text-base font-black tracking-tight sm:text-lg">Mahjong Medan</span>
                        </a>

                        <ul className="hidden items-center gap-1 md:flex">
                            {menuSections.map((item) => (
                                <li key={item.id}>
                                    <a
                                        href={`#${item.id}`}
                                        onClick={(event) => handleAnchor(event, item.id)}
                                        className="rounded-full px-4 py-2 text-sm font-semibold text-emerald-100 transition hover:bg-white/10 hover:text-white"
                                    >
                                        {item.label}
                                    </a>
                                </li>
                            ))}
                        </ul>

                        <div className="flex items-center gap-2">
                            {pengguna ? (
                                <Link
                                    href="/home"
                                    className="hidden rounded-full bg-white px-4 py-2 text-xs font-bold text-emerald-800 shadow transition hover:bg-emerald-50 md:inline-flex"
                                >
                                    Buka Aplikasi
                                </Link>
                            ) : (
                                <>
                                    <Link
                                        href="/login"
                                        className="hidden rounded-full px-3 py-2 text-xs font-bold text-emerald-100 transition hover:text-white md:inline-flex"
                                    >
                                        Masuk
                                    </Link>
                                    <Link
                                        href="/booking"
                                        className="hidden rounded-full bg-lime-300 px-4 py-2 text-xs font-black text-emerald-950 shadow transition hover:bg-lime-200 md:inline-flex"
                                    >
                                        Booking Sekarang
                                    </Link>
                                </>
                            )}

                            {/* Tombol menu mobile */}
                            <button
                                type="button"
                                onClick={() => setMenuOpen((open) => !open)}
                                aria-label="Buka menu"
                                aria-expanded={menuOpen}
                                className="flex h-10 w-10 items-center justify-center rounded-xl bg-white/10 text-white ring-1 ring-white/20 transition hover:bg-white/20 md:hidden"
                            >
                                {menuOpen ? (
                                    <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5" fill="none" viewBox="0 0 24 24" strokeWidth={2} stroke="currentColor">
                                        <path strokeLinecap="round" strokeLinejoin="round" d="M6 18 18 6M6 6l12 12" />
                                    </svg>
                                ) : (
                                    <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5" fill="none" viewBox="0 0 24 24" strokeWidth={2} stroke="currentColor">
                                        <path strokeLinecap="round" strokeLinejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                                    </svg>
                                )}
                            </button>
                        </div>
                    </nav>

                    {menuOpen && (
                        <ul className="border-t border-emerald-900/20 bg-emerald-950/95 px-4 pb-4 pt-2 backdrop-blur-md md:hidden">
                            {menuSections.map((item) => (
                                <li key={item.id}>
                                    <a
                                        href={`#${item.id}`}
                                        onClick={(event) => handleAnchor(event, item.id)}
                                        className="block rounded-xl px-4 py-2.5 text-sm font-semibold text-emerald-100 transition hover:bg-white/10 hover:text-white"
                                    >
                                        {item.label}
                                    </a>
                                </li>
                            ))}
                            <li className="mt-2 border-t border-emerald-900/20 pt-2">
                                {pengguna ? (
                                    <Link
                                        href="/home"
                                        className="block rounded-xl bg-white px-4 py-2.5 text-center text-sm font-bold text-emerald-800"
                                    >
                                        Buka Aplikasi
                                    </Link>
                                ) : (
                                    <div className="flex gap-2">
                                        <Link
                                            href="/login"
                                            className="flex-1 rounded-xl bg-white/10 px-4 py-2.5 text-center text-sm font-bold text-white ring-1 ring-white/20"
                                        >
                                            Masuk
                                        </Link>
                                        <Link
                                            href="/booking"
                                            className="flex-1 rounded-xl bg-lime-300 px-4 py-2.5 text-center text-sm font-black text-emerald-950"
                                        >
                                            Booking
                                        </Link>
                                    </div>
                                )}
                            </li>
                        </ul>
                    )}
                </header>

                {/* Home / Hero — full-screen bleed, foto meja mahjong transparan penuh */}
                <section id="home" className="relative flex min-h-[100svh] items-center overflow-hidden text-white">
                    {/* Latar foto meja mahjong penuh layar */}
                    <img
                        src="/images/landing/hero.webp"
                        alt=""
                        aria-hidden="true"
                        className="pointer-events-none absolute inset-0 h-full w-full scale-100 object-cover animate-[hero-kenburns_24s_ease-in-out_infinite_alternate]"
                    />

                    {/* Overlay gradasi transparan agar teks tetap terbaca */}
                    <div className="pointer-events-none absolute inset-0 bg-gradient-to-b from-emerald-950/85 via-emerald-950/55 to-emerald-950/90" />
                    <div className="pointer-events-none absolute inset-0 bg-[radial-gradient(ellipse_at_center,transparent_0%,rgba(6,78,59,0.35)_55%,rgba(2,44,34,0.75)_100%)]" />
                    <div
                        className="pointer-events-none absolute -left-20 top-1/3 h-72 w-72 rounded-full bg-lime-300/15 blur-3xl animate-[hero-glow_8s_ease-in-out_infinite]"
                    />
                    <div
                        className="pointer-events-none absolute -right-20 bottom-10 h-80 w-80 rounded-full bg-emerald-400/20 blur-3xl animate-[hero-glow_10s_ease-in-out_1.5s_infinite]"
                    />

                    {/* Ubin mahjong melayang dekoratif */}
                    <div className="pointer-events-none absolute right-[8%] top-28 hidden lg:block" style={{ '--hero-rot': '12deg' }}>
                        <MahjongTile label="中" tone="text-rose-600" className="animate-[hero-float_7s_ease-in-out_infinite] opacity-90 drop-shadow-2xl" />
                    </div>
                    <div className="pointer-events-none absolute right-[16%] bottom-40 hidden lg:block" style={{ '--hero-rot': '-10deg' }}>
                        <MahjongTile label="發" tone="text-emerald-600" className="animate-[hero-float_9s_ease-in-out_1s_infinite] opacity-90 drop-shadow-2xl" />
                    </div>
                    <div className="pointer-events-none absolute left-[6%] bottom-24 hidden lg:block" style={{ '--hero-rot': '6deg' }}>
                        <MahjongTile label="東" tone="text-sky-600" className="animate-[hero-float_8s_ease-in-out_0.5s_infinite] opacity-90 drop-shadow-2xl" />
                    </div>

                    <div className="relative mx-auto w-full max-w-6xl px-4 pb-28 pt-36 sm:px-6">
                        <div className="mx-auto max-w-3xl text-center">
                            <span className="inline-flex items-center gap-2 rounded-full border border-white/25 bg-white/10 px-4 py-2 text-[11px] font-bold uppercase tracking-[0.2em] text-lime-200 backdrop-blur-md">
                                <span className="h-1.5 w-1.5 rounded-full bg-lime-300 animate-pulse" />
                                Booking Mudah, Main Nyaman
                            </span>

                            <h1 className="mt-6 text-5xl font-black leading-[1.05] tracking-tight drop-shadow-[0_4px_24px_rgba(2,44,34,0.8)] sm:text-6xl lg:text-7xl">
                                Serunya Main
                                <br />
                                <span className="bg-gradient-to-r from-lime-300 via-lime-200 to-emerald-300 bg-clip-text text-transparent">
                                    Mahjong Bareng
                                </span>
                            </h1>

                            <p className="mx-auto mt-5 max-w-xl text-sm leading-relaxed text-emerald-50/90 drop-shadow sm:text-base">
                                Tempat nongkrong dan bermain mahjong paling asyik. Pilih meja favorit,
                                tentukan jamnya, dan booking langsung dari HP-mu tanpa ribet.
                            </p>

                            <div className="mt-9 flex flex-wrap items-center justify-center gap-3">
                                <Link
                                    href="/booking"
                                    className="group relative inline-flex items-center gap-2 rounded-full bg-lime-300 px-8 py-3.5 text-sm font-black text-emerald-950 shadow-[0_10px_40px_rgba(163,230,53,0.35)] transition hover:-translate-y-0.5 hover:bg-lime-200 hover:shadow-[0_14px_48px_rgba(163,230,53,0.5)]"
                                >
                                    Booking Sekarang
                                    <svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4 transition group-hover:translate-x-0.5" fill="none" viewBox="0 0 24 24" strokeWidth={2.5} stroke="currentColor">
                                        <path strokeLinecap="round" strokeLinejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                                    </svg>
                                </Link>
                                <a
                                    href="#tutorial"
                                    onClick={(event) => handleAnchor(event, 'tutorial')}
                                    className="inline-flex items-center gap-2 rounded-full border border-white/30 bg-white/10 px-8 py-3.5 text-sm font-bold text-white backdrop-blur-md transition hover:bg-white/20"
                                >
                                    Belajar Main Dulu
                                </a>
                            </div>

                            <dl className="mx-auto mt-11 grid max-w-lg grid-cols-3 gap-3 sm:gap-4">
                                {[
                                    { value: `${mejas.length}+`, label: 'Meja tersedia' },
                                    { value: '24', label: 'Jam / hari' },
                                    { value: '2-4', label: 'Pemain per meja' },
                                ].map((stat) => (
                                    <div
                                        key={stat.label}
                                        className="rounded-2xl border border-white/15 bg-white/10 px-3 py-4 shadow-lg shadow-emerald-950/30 backdrop-blur-md transition hover:bg-white/15"
                                    >
                                        <dt className="text-xl font-black text-lime-300 sm:text-2xl">{stat.value}</dt>
                                        <dd className="mt-1 text-[11px] font-medium text-emerald-100">{stat.label}</dd>
                                    </div>
                                ))}
                            </dl>
                        </div>
                    </div>

                    {/* Petunjuk scroll ke bawah */}
                    <a
                        href="#tentang"
                        onClick={(event) => handleAnchor(event, 'tentang')}
                        aria-label="Gulir ke bawah"
                        className="absolute bottom-6 left-1/2 hidden -translate-x-1/2 flex-col items-center gap-1 text-emerald-100/70 transition hover:text-white sm:flex"
                    >
                        <span className="text-[10px] font-bold uppercase tracking-[0.25em]">Scroll</span>
                        <svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4 animate-bounce" fill="none" viewBox="0 0 24 24" strokeWidth={2} stroke="currentColor">
                            <path strokeLinecap="round" strokeLinejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                        </svg>
                    </a>
                </section>

                {/* Tentang */}
                <section id="tentang" className="scroll-mt-24 bg-[#f6faf7] py-20">
                    <div className="mx-auto max-w-6xl px-4 sm:px-6">
                        <div className="grid items-center gap-10 lg:grid-cols-2">
                            <div className="relative order-2 lg:order-1">
                                <div className="overflow-hidden rounded-[2rem] border border-emerald-100 shadow-xl shadow-emerald-900/10">
                                    <img
                                        src="/images/landing/meja.webp"
                                        alt="Meja mahjong premium dengan permukaan felt hijau"
                                        className="aspect-[4/3] w-full object-cover"
                                    />
                                </div>
                                <div className="absolute -bottom-6 -right-4 flex items-center gap-3 rounded-2xl border border-emerald-100 bg-white px-4 py-3 shadow-lg shadow-emerald-900/10 sm:-right-6">
                                    <span className="flex h-10 w-8 items-center justify-center rounded-lg border-b-4 border-emerald-950/25 bg-gradient-to-b from-white to-emerald-50 text-xl font-black text-rose-600 shadow">
                                        中
                                    </span>
                                    <div>
                                        <p className="text-sm font-black text-slate-800">Meja Premium</p>
                                        <p className="text-[11px] font-medium text-slate-500">Mahjong Medan</p>
                                    </div>
                                </div>
                            </div>

                            <div className="order-1 lg:order-2">
                                <p className="text-xs font-black uppercase tracking-[0.2em] text-emerald-600">Tentang Kami</p>
                                <h2 className="mt-3 text-3xl font-black leading-tight text-slate-900 sm:text-4xl">
                                    Tempat Main Mahjong Paling <span className="text-emerald-600">Nyaman</span>
                                </h2>
                                <p className="mt-4 text-sm leading-relaxed text-slate-600 sm:text-base">
                                    Mahjong Medan adalah tempat berkumpulnya para penggemar mahjong. Dengan meja
                                    premium bersurface felt hijau yang lembut, ruangan yang sejuk, dan suasana yang
                                    santai, setiap sesi main jadi lebih seru, baik buat pemula maupun pemain profesional.
                                </p>

                                <ul className="mt-7 grid gap-3 sm:grid-cols-2">
                                    {[
                                        { tile: '', tone: 'text-emerald-600', bg: 'bg-emerald-50', title: 'Meja Premium', desc: 'Felt hijau lembut dan ubin gading yang nyaman di tangan.' },
                                        { tile: '', tone: 'text-sky-600', bg: 'bg-sky-50', title: 'Harga Bersahabat', desc: 'Tarif per jam jelas dan terjangkau setiap hari.' },
                                        { tile: '', tone: 'text-rose-600', bg: 'bg-rose-50', title: 'Suasana Santai', desc: 'Ruangan sejuk, bersih, cocok buat nongkrong lama.' },
                                        { tile: '', tone: 'text-emerald-700', bg: 'bg-lime-50', title: 'Booking Online', desc: 'Pilih meja dan jam main langsung dari HP-mu.' },
                                    ].map((item) => (
                                        <li
                                            key={item.title}
                                            className="rounded-2xl border border-emerald-100 bg-white p-4 shadow-sm transition hover:border-emerald-300 hover:shadow-md"
                                        >
                                            <span
                                                className={`flex h-9 w-9 items-center justify-center rounded-lg text-base font-black ${item.bg} ${item.tone}`}
                                            >
                                                {item.tile}
                                            </span>
                                            <p className="mt-2.5 text-sm font-bold text-slate-800">{item.title}</p>
                                            <p className="mt-1 text-xs leading-relaxed text-slate-500">{item.desc}</p>
                                        </li>
                                    ))}
                                </ul>
                            </div>
                        </div>
                    </div>
                </section>

                {/* Tutorial */}
                <section id="tutorial" className="scroll-mt-24 bg-gradient-to-b from-emerald-950 via-emerald-900 to-emerald-950 py-20 text-white">
                    <div className="mx-auto max-w-6xl px-4 sm:px-6">
                        <div className="text-center">
                            <p className="text-xs font-black uppercase tracking-[0.2em] text-lime-300">Tutorial</p>
                            <h2 className="mt-3 text-3xl font-black leading-tight sm:text-4xl">
                                Belum Pernah Main? <span className="text-lime-300">Gampang Banget.</span>
                            </h2>
                            <p className="mx-auto mt-3 max-w-xl text-sm leading-relaxed text-emerald-100">
                                Ikuti 4 langkah simple berikut, dan kamu siap duduk di meja bersama teman-temanmu.
                            </p>
                        </div>

                        <div className="mt-12 grid items-center gap-10 lg:grid-cols-2">
                            <VideoTutorialYoutube videoId={videoIdTutorial} />

                            <ol className="space-y-4">
                                {[
                                    {
                                        title: 'Kenali ubin-nya',
                                        desc: 'Ada ubin karakter (中 發 東 南 西 北), bambu, lingkaran, hingga angka. Semua ubin terlihat serupa, bedanya di bagian muka.',
                                    },
                                    {
                                        title: 'Susun dinding ubin',
                                        desc: 'Tiap pemain menyusun 2 lapis ubin membentuk dinding persegi di tengah meja, ini jadi  gudang ubin anda selama permainan.',
                                    },
                                    {
                                        title: 'Ambil & buang ubin',
                                        desc: 'Bergiliran searah jarum jam, tarik satu ubin dari dinding ubin anda, coba bentuk kombinasi, lalu buang satu ubin yang tak terpakai.',
                                    },
                                    {
                                        title: 'Raih kombinasi menang',
                                        desc: 'Kumpulkan 4 set (triple atau urutan) plus 1 pasang. Jika cocok, maka anda dinyatakan menang dan nikmati serunya ronde berikutnya!',
                                    },
                                ].map((step, index) => (
                                    <li
                                        key={step.title}
                                        className="flex gap-4 rounded-2xl bg-white/5 p-4 ring-1 ring-white/10 transition hover:bg-white/10"
                                    >
                                        <span className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-lime-300 text-base font-black text-emerald-950">
                                            {index + 1}
                                        </span>
                                        <div>
                                            <p className="text-sm font-bold">{step.title}</p>
                                            <p className="mt-1 text-xs leading-relaxed text-emerald-100 sm:text-sm">{step.desc}</p>
                                        </div>
                                    </li>
                                ))}
                            </ol>
                        </div>

                        {/* <div className="mt-12 rounded-3xl bg-white/5 p-6 ring-1 ring-white/10 sm:p-8">
                            <h3 className="text-center text-lg font-black">Contoh Harga Sewa Per Jam</h3>
                            {hargas.length === 0 ? (
                                <p className="mt-4 text-center text-xs text-emerald-200">
                                    Daftar harga belum tersedia. Hubungi kami untuk info terbaru.
                                </p>
                            ) : (
                                <div className="mt-6 flex flex-wrap justify-center gap-3">
                                    {hargas.map((harga) => (
                                        <div
                                            key={`${harga.id}-${harga.hari}`}
                                            className="rounded-2xl bg-white/10 px-4 py-3 text-center ring-1 ring-white/15"
                                        >
                                            <p className="text-[10px] font-bold uppercase tracking-wide text-lime-300">{harga.hari}</p>
                                            <p className="mt-1 text-sm font-black">
                                                Rp{new Intl.NumberFormat('id-ID').format(Number(harga.harga) || 0)}
                                                <span className="text-[10px] font-medium text-emerald-200"> /jam</span>
                                            </p>
                                        </div>
                                    ))}
                                </div>
                            )}
                            <p className="mt-4 text-center text-[11px] text-emerald-200">
                                *Harga berdasarkan jadwal terbaru — bisa berbeda di hari libur.
                            </p>
                        </div> */}
                    </div>
                </section>

                {/* Meja favorit */}
                {mejas.length > 0 && (
                    <section className="bg-[#f6faf7] py-20">
                        <div className="mx-auto max-w-6xl px-4 sm:px-6">
                            <div className="flex items-end justify-between">
                                <div>
                                    <p className="text-xs font-black uppercase tracking-[0.2em] text-emerald-600">Meja Kami</p>
                                    <h2 className="mt-3 text-3xl font-black leading-tight text-slate-900">
                                        Pilihan Meja Favorit
                                    </h2>
                                </div>
                                <Link href="/booking" className="text-sm font-bold text-emerald-700 transition hover:text-emerald-800">
                                    Lihat semua →
                                </Link>
                            </div>

                            <div className="mt-8 grid grid-cols-2 gap-4 lg:grid-cols-4">
                                {mejas.map((meja) => (
                                    <Link
                                        key={meja.id}
                                        href="/booking"
                                        className="group overflow-hidden rounded-2xl border border-emerald-100 bg-white shadow-sm transition duration-300 hover:-translate-y-1 hover:border-emerald-300 hover:shadow-lg hover:shadow-emerald-900/10"
                                    >
                                        <div className="relative aspect-[4/3] overflow-hidden bg-emerald-900">
                                            <img
                                                src="/images/meja-mahjong.svg"
                                                alt={`Meja ${meja.nama_meja}`}
                                                loading="lazy"
                                                className="h-full w-full object-cover transition duration-500 group-hover:scale-105"
                                            />
                                            <span className="absolute left-2 top-2 rounded-full bg-white/90 px-2 py-0.5 text-[10px] font-bold text-emerald-700 shadow-sm backdrop-blur">
                                                {meja.kode_meja || 'MEJA'}
                                            </span>
                                            <div className="absolute inset-x-2 bottom-2">
                                                <p className="truncate text-sm font-bold text-white drop-shadow">{meja.nama_meja}</p>
                                            </div>
                                        </div>
                                        <div className="flex items-center justify-between px-3 py-2.5">
                                            <span className="text-[11px] font-medium text-slate-500">Meja premium</span>
                                            <span className="flex h-6 w-6 items-center justify-center rounded-full bg-emerald-50 text-emerald-600 transition group-hover:bg-emerald-600 group-hover:text-white">
                                                <svg xmlns="http://www.w3.org/2000/svg" className="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" strokeWidth={2.4} stroke="currentColor">
                                                    <path strokeLinecap="round" strokeLinejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                                                </svg>
                                            </span>
                                        </div>
                                    </Link>
                                ))}
                            </div>
                        </div>
                    </section>
                )}

                {/* Lokasi */}
                <section id="lokasi" className="scroll-mt-24 border-t border-emerald-100 bg-white py-20">
                    <div className="mx-auto max-w-6xl px-4 sm:px-6">
                        <div className="grid gap-10 lg:grid-cols-2">
                            <div>
                                <p className="text-xs font-black uppercase tracking-[0.2em] text-emerald-600">Lokasi</p>
                                <h2 className="mt-3 text-3xl font-black leading-tight text-slate-900 sm:text-4xl">
                                    Mampir Sekarang, <span className="text-emerald-600">Main Bareng</span>
                                </h2>
                                <p className="mt-4 text-sm leading-relaxed text-slate-600">
                                    Lokasi kami strategis dan mudah dijangkau. Datang langsung, atau booking
                                    dulu biar meja favoritmu nggak kehabisan.
                                </p>

                                <ul className="mt-8 space-y-4">
                                    <li className="flex gap-4 rounded-2xl border border-emerald-100 bg-emerald-50/50 p-4">
                                        <span className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-600 text-white">
                                            <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5" fill="none" viewBox="0 0 24 24" strokeWidth={1.8} stroke="currentColor">
                                                <path strokeLinecap="round" strokeLinejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                                <path strokeLinecap="round" strokeLinejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
                                            </svg>
                                        </span>
                                        <div>
                                            <p className="text-sm font-bold text-slate-800">Alamat</p>
                                            <p className="mt-1 text-xs leading-relaxed text-slate-600">
                                                Jl.setia Ujung/Bintang Terang NO. 38 KM 13.8, Puji Mulyo, Binjai, Kabupaten Deliserdang Sumatera Utara 20351<br />

                                            </p>
                                        </div>
                                    </li>
                                    <li className="flex gap-4 rounded-2xl border border-emerald-100 bg-emerald-50/50 p-4">
                                        <span className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-600 text-white">
                                            <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5" fill="none" viewBox="0 0 24 24" strokeWidth={1.8} stroke="currentColor">
                                                <path strokeLinecap="round" strokeLinejoin="round" d="M12 6v6l4 2m6-2a10 10 0 1 1-20 0 10 10 0 0 1 20 0Z" />
                                            </svg>
                                        </span>
                                        <div>
                                            <p className="text-sm font-bold text-slate-800">Jam Buka</p>
                                            <p className="mt-1 text-xs leading-relaxed text-slate-600">
                                                Setiap hari, 06.00 – 00.00 WIB
                                            </p>
                                        </div>
                                    </li>
                                    <li className="flex gap-4 rounded-2xl border border-emerald-100 bg-emerald-50/50 p-4">
                                        <span className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-600 text-white">
                                            <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5" fill="none" viewBox="0 0 24 24" strokeWidth={1.8} stroke="currentColor">
                                                <path strokeLinecap="round" strokeLinejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z" />
                                            </svg>
                                        </span>
                                        <div>
                                            <p className="text-sm font-bold text-slate-800">Kontak</p>
                                            <p className="mt-1 text-xs leading-relaxed text-slate-600">
                                                WhatsApp: +62    812-0000-0000<br />

                                            </p>
                                        </div>
                                    </li>
                                </ul>

                                <div className="mt-8 flex flex-wrap gap-3">
                                    <Link
                                        href="/booking"
                                        className="inline-flex items-center gap-2 rounded-full bg-emerald-600 px-6 py-3 text-sm font-black text-white shadow-lg shadow-emerald-600/30 transition hover:bg-emerald-700"
                                    >
                                        Booking Sekarang
                                    </Link>
                                    <a
                                        href="https://maps.google.com/?q=Medan"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        className="inline-flex items-center gap-2 rounded-full border border-emerald-200 bg-white px-6 py-3 text-sm font-bold text-emerald-700 transition hover:bg-emerald-50"
                                    >
                                        Buka di Google Maps
                                    </a>
                                </div>
                            </div>

                            <div className="overflow-hidden rounded-[2rem] border border-emerald-100 shadow-xl shadow-emerald-900/10">
                                <iframe
                                    title="Lokasi Mahjong Medan"
                                    src="https://maps.google.com/maps?q=Medan&t=&z=14&ie=UTF8&iwloc=&output=embed"
                                    className="h-[420px] w-full border-0"
                                    loading="lazy"
                                    referrerPolicy="no-referrer-when-downgrade"
                                    allowFullScreen
                                />
                            </div>
                        </div>
                    </div>
                </section>

                {/* Footer */}
                <footer className="bg-emerald-950 py-12 text-white">
                    <div className="mx-auto max-w-6xl px-4 sm:px-6">
                        <div className="flex flex-col items-center justify-between gap-6 sm:flex-row">
                            <div className="flex items-center gap-2.5">
                                <span className="flex h-10 w-8 items-center justify-center rounded-lg border-b-2 border-emerald-950/30 bg-gradient-to-b from-white to-emerald-50 text-lg font-black text-rose-600 shadow-md">
                                    中
                                </span>
                                <span className="text-base font-black tracking-tight">Mahjong Medan</span>
                            </div>

                            <ul className="flex flex-wrap items-center justify-center gap-2">
                                {menuSections.map((item) => (
                                    <li key={item.id}>
                                        <a
                                            href={`#${item.id}`}
                                            onClick={(event) => handleAnchor(event, item.id)}
                                            className="rounded-full px-3 py-2 text-xs font-semibold text-emerald-200 transition hover:bg-white/10 hover:text-white"
                                        >
                                            {item.label}
                                        </a>
                                    </li>
                                ))}
                            </ul>

                            {pengguna ? (
                                <Link
                                    href="/home"
                                    className="rounded-full bg-white/10 px-4 py-2 text-xs font-bold text-white ring-1 ring-white/20 transition hover:bg-white/20"
                                >
                                    Buka Aplikasi
                                </Link>
                            ) : (
                                <Link
                                    href="/register"
                                    className="rounded-full bg-lime-300 px-4 py-2 text-xs font-black text-emerald-950 transition hover:bg-lime-200"
                                >
                                    Daftar Sekarang
                                </Link>
                            )}
                        </div>
                        <p className="mt-10 text-center text-[11px] text-emerald-300/70">
                            Mahjong Medan &middot; Booking mudah, main nyaman &middot; &copy; {new Date().getFullYear()}
                        </p>
                    </div>
                </footer>
            </div>
        </>
    );
}
