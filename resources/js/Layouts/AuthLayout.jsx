import { Link } from '@inertiajs/react';

const TILE_SIZES = {
    md: {
        tile: 'h-28 w-[5.2rem] rounded-[0.9rem]',
        inner: 'inset-1.5 rounded-[0.65rem]',
        stand: 'bottom-2 h-1 w-9',
    },
    sm: {
        tile: 'h-14 w-10 rounded-lg',
        inner: 'inset-1 rounded-[0.35rem]',
        stand: 'bottom-1 h-0.5 w-4',
    },
};

function MahjongTile({ size = 'md', className = '', children }) {
    const styles = TILE_SIZES[size];

    return (
        <div
            className={`relative flex items-center justify-center bg-gradient-to-b from-[#fffdf7] via-[#f7f1e1] to-[#e7dcc0] shadow-[0_22px_40px_-18px_rgba(0,0,0,0.85)] ring-1 ring-[#cbbc95] ${styles.tile} ${className}`}
        >
            <span className={`pointer-events-none absolute border border-[#e9dfc5] ${styles.inner}`} />
            <span className="pointer-events-none absolute inset-x-2 top-1.5 h-2 rounded-full bg-white/70 blur-[2px]" />
            <span className="relative">{children}</span>
            <span className={`pointer-events-none absolute rounded-full bg-[#d5c6a0] ${styles.stand}`} />
        </div>
    );
}

function RedDragon({ small = false }) {
    return (
        <span className={`font-serif font-black leading-none text-[#b3261e] ${small ? 'text-2xl' : 'text-[2.75rem]'}`}>
            中
        </span>
    );
}

function GreenDragon({ small = false }) {
    return (
        <span className={`font-serif font-black leading-none text-[#0f766e] ${small ? 'text-2xl' : 'text-[2.75rem]'}`}>
            發
        </span>
    );
}

function WanTile({ small = false }) {
    return (
        <span className="flex flex-col items-center leading-none">
            <span className={`font-serif font-black text-slate-800 ${small ? 'text-[10px]' : 'text-lg'}`}>一</span>
            <span className={`font-serif font-black text-[#b3261e] ${small ? 'text-xl' : 'text-[2.4rem]'}`}>萬</span>
        </span>
    );
}

function DotsTile({ small = false }) {
    const dot = small ? 'h-1.5 w-1.5' : 'h-2.5 w-2.5';

    return (
        <span className={`grid grid-cols-2 ${small ? 'gap-1' : 'gap-1.5'}`}>
            {[0, 1, 2, 3].map((index) => (
                <span
                    key={index}
                    className={`${dot} rounded-full bg-emerald-700 ring-1 ring-emerald-900/30`}
                />
            ))}
        </span>
    );
}

function BambooTile({ small = false }) {
    const stick = small ? 'h-5 w-1' : 'h-10 w-1.5';

    return (
        <span className={`flex items-end ${small ? 'gap-0.5' : 'gap-1'}`}>
            {[0, 1, 2].map((index) => (
                <span
                    key={index}
                    className={`${stick} rounded-full bg-gradient-to-b from-emerald-600 to-emerald-800 ring-1 ring-emerald-900/20`}
                />
            ))}
        </span>
    );
}

function CheckIcon() {
    return (
        <svg xmlns="http://www.w3.org/2000/svg" className="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" strokeWidth={2.5} stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" d="m4.5 12.75 6 6 9-13.5" />
        </svg>
    );
}

function BackIcon() {
    return (
        <svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4" fill="none" viewBox="0 0 24 24" strokeWidth={2.2} stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
        </svg>
    );
}

function Brand({ dark = false }) {
    return (
        <Link href="/" className="group flex items-center gap-3">
            <span className="relative flex h-11 w-11 items-center justify-center overflow-hidden rounded-xl bg-gradient-to-b from-[#fffdf7] to-[#eadfc2] shadow-lg ring-1 ring-[#cbbc95] transition group-hover:-translate-y-0.5">
                <span className="absolute inset-1 rounded-lg border border-[#e6dcc0]" />
                <span className="relative font-serif text-xl font-black text-[#b3261e]">中</span>
            </span>
            <span className="leading-tight">
                <span className={`block text-[11px] font-bold uppercase tracking-[0.28em] ${dark ? 'text-[#e8cf94]' : 'text-emerald-800'}`}>
                    Mahjong Medan
                </span>

            </span>
        </Link>
    );
}

function FeltPattern({ faint = false }) {
    return (
        <>
            <div
                className={`pointer-events-none absolute inset-0 ${faint ? 'opacity-40' : 'opacity-80'}`}
                style={{
                    backgroundImage:
                        'radial-gradient(circle at 18% 12%, rgba(212,175,106,0.18), transparent 42%), radial-gradient(circle at 85% 80%, rgba(16,185,129,0.22), transparent 46%)',
                }}
            />
            <div
                className="pointer-events-none absolute inset-0 opacity-[0.05]"
                style={{
                    backgroundImage:
                        'repeating-linear-gradient(45deg, #ffffff 0px, #ffffff 1px, transparent 1px, transparent 12px), repeating-linear-gradient(-45deg, #ffffff 0px, #ffffff 1px, transparent 1px, transparent 12px)',
                }}
            />
        </>
    );
}

function TileRack() {
    return (
        <div className="flex items-center justify-center">
            <MahjongTile className="-mr-3 translate-y-5 rotate-[-16deg]">
                <DotsTile />
            </MahjongTile>
            <MahjongTile className="-mr-3 translate-y-2 rotate-[-8deg]">
                <BambooTile />
            </MahjongTile>
            <MahjongTile className="z-10 scale-110">
                <GreenDragon />
            </MahjongTile>
            <MahjongTile className="-ml-3 translate-y-2 rotate-[8deg]">
                <WanTile />
            </MahjongTile>
            <MahjongTile className="-ml-3 translate-y-5 rotate-[16deg]">
                <RedDragon />
            </MahjongTile>
        </div>
    );
}

export default function AuthLayout({ title, subtitle, badge, mode = 'login', children, footer }) {
    const tabClass = (active) =>
        `flex flex-1 items-center justify-center rounded-lg px-3 py-2 text-xs font-bold transition ${active ? 'bg-white text-emerald-800 shadow-sm' : 'text-slate-500 hover:text-slate-700'
        }`;

    return (
        <div className="min-h-screen bg-[#08281f] lg:bg-[#eef5ef] lg:py-8 xl:py-10">
            <style>{`
                @media (prefers-reduced-motion: no-preference) {
                    @keyframes auth-rise {
                        from { opacity: 0; transform: translateY(18px); }
                        to { opacity: 1; transform: translateY(0); }
                    }
                    @keyframes auth-fan {
                        from { opacity: 0; transform: translateY(26px); }
                        to { opacity: 1; transform: translateY(0); }
                    }
                    .auth-rise { animation: auth-rise 0.55s cubic-bezier(0.22, 1, 0.36, 1) both; }
                    .auth-fan { animation: auth-fan 0.65s cubic-bezier(0.22, 1, 0.36, 1) both 0.08s; }
                }
            `}</style>

            <div className="mx-auto grid min-h-screen w-full max-w-6xl overflow-hidden bg-[#f5faf6] lg:min-h-0 lg:grid-cols-[1.05fr_0.95fr] lg:rounded-[2rem] lg:shadow-[0_40px_90px_-40px_rgba(6,50,38,0.55)]">
                <aside className="relative hidden flex-col justify-between overflow-hidden bg-[#08281f] p-10 lg:flex">
                    <FeltPattern />
                    <div className="pointer-events-none absolute inset-x-12 top-0 h-px bg-gradient-to-r from-transparent via-[#d4af6a]/70 to-transparent" />
                    <span className="pointer-events-none absolute -bottom-14 -right-6 select-none font-serif text-[14rem] leading-none text-white/[0.03]">
                        中
                    </span>

                    <div className="relative z-10 flex items-center justify-between">
                        <Brand dark />
                        <Link
                            href="/login"
                            className="flex items-center gap-2 rounded-full border border-white/10 bg-white/5 px-3 py-1.5 text-[11px] font-semibold text-emerald-100/80 transition hover:border-[#d4af6a]/40 hover:text-[#e8cf94]"
                        >
                            <BackIcon />
                            Home
                        </Link>
                    </div>

                    <div className="relative z-10 py-10">
                        <div className="relative mb-10 flex h-56 items-center justify-center">
                            <span className="absolute h-40 w-56 rounded-full bg-emerald-400/15 blur-3xl" />
                            <span className="absolute h-24 w-24 rounded-full bg-[#d4af6a]/15 blur-2xl" />
                            <TileRack />
                        </div>

                        <p className="text-[10px] font-bold uppercase tracking-[0.38em] text-[#d4af6a]">
                            Mahjong Medan
                        </p>
                        <h2 className="mt-3 text-[2.1rem] font-bold leading-[1.15] tracking-tight text-white">
                            Bermain Mahjong
                            <br />
                            <span className="text-[#e8cf94]">Dengan mudah </span>
                        </h2>
                        <p className="mt-4 max-w-sm text-sm leading-relaxed text-emerald-100/65">
                            Buat akun anda, lalu pilih meja, atur jadwal, dan pantau status booking tanpa
                            perlu mengulang data pemesan.
                        </p>
                    </div>

                    <div className="relative z-10 space-y-3">

                    </div>
                </aside>

                <main className="relative flex min-h-screen flex-col bg-[#f5faf6] lg:min-h-0">
                    <div className="relative overflow-hidden bg-[#08281f] px-5 pb-16 pt-5 lg:hidden">
                        <FeltPattern faint />
                        <div className="pointer-events-none absolute inset-x-8 top-0 h-px bg-gradient-to-r from-transparent via-[#d4af6a]/70 to-transparent" />
                        <div className="pointer-events-none absolute -left-12 top-6 h-36 w-36 rounded-full border border-[#d4af6a]/10" />
                        <div className="pointer-events-none absolute -left-4 top-12 h-48 w-48 rounded-full border border-[#d4af6a]/[0.06]" />
                        <span className="pointer-events-none absolute -bottom-10 -left-3 select-none font-serif text-[7rem] leading-none text-white/[0.04]">
                            發
                        </span>

                        <div className="relative z-10 flex items-center justify-between">
                            <Brand dark />
                            <Link
                                href="/"
                                className="flex h-9 w-9 items-center justify-center rounded-full border border-white/10 bg-white/5 text-emerald-100/80 transition hover:text-[#e8cf94]"
                                aria-label="Kembali ke beranda"
                            >
                                <BackIcon />
                            </Link>
                        </div>

                        <div className="relative z-10 mt-5 flex h-[6.5rem] items-end justify-center">
                            <span className="absolute bottom-5 h-16 w-44 rounded-full bg-emerald-400/15 blur-2xl" />
                            <span className="absolute bottom-9 h-10 w-24 rounded-full bg-[#d4af6a]/20 blur-xl" />
                            <div className="auth-fan relative">
                                <div className="flex scale-90 items-end">
                                    <MahjongTile size="md" className="-mr-6 translate-y-8 rotate-[-18deg] scale-90">
                                        <DotsTile />
                                    </MahjongTile>
                                    <MahjongTile size="md" className="-mr-6 translate-y-3 rotate-[-9deg] scale-95">
                                        <BambooTile />
                                    </MahjongTile>
                                    <MahjongTile size="md" className="z-10 scale-105">
                                        <GreenDragon />
                                    </MahjongTile>
                                    <MahjongTile size="md" className="-ml-6 translate-y-3 rotate-[9deg] scale-95">
                                        <WanTile />
                                    </MahjongTile>
                                    <MahjongTile size="md" className="-ml-6 translate-y-8 rotate-[18deg] scale-90">
                                        <RedDragon />
                                    </MahjongTile>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div className="relative z-10 -mt-14 flex flex-1 px-4 pb-10 sm:px-6 lg:mt-0 lg:px-12 lg:py-12">
                        <div className="mx-auto w-full max-w-[25.5rem] self-center">
                            <div className="auth-rise rounded-[1.75rem] border border-white bg-white p-5 shadow-[0_30px_60px_-35px_rgba(6,78,59,0.45)] sm:p-7 lg:rounded-[1.4rem]">
                                <span className="mx-auto mb-4 block h-1 w-10 rounded-full bg-slate-200 lg:hidden" />

                                <div className="mb-5 flex rounded-xl bg-slate-100 p-1 lg:hidden">
                                    <Link href="/login" aria-current={mode === 'login' ? 'page' : undefined} className={tabClass(mode === 'login')}>
                                        Masuk
                                    </Link>
                                    <Link href="/register" aria-current={mode === 'register' ? 'page' : undefined} className={tabClass(mode === 'register')}>
                                        Daftar
                                    </Link>
                                </div>

                                <span className="hidden items-center gap-2 rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-[10px] font-bold uppercase tracking-[0.16em] text-emerald-800 lg:inline-flex">
                                    <span className="h-1.5 w-1.5 rounded-full bg-[#b3261e]" />
                                    {badge}
                                </span>
                                <h1 className="text-[1.5rem] font-bold leading-tight tracking-tight text-slate-900 lg:mt-4 lg:text-[1.65rem]">
                                    {title}
                                </h1>
                                <p className="mt-1.5 text-sm leading-relaxed text-slate-500">{subtitle}</p>

                                <div className="mt-5 lg:mt-6">{children}</div>
                            </div>

                            {footer && <div className="mt-5">{footer}</div>}

                            {/* <p className="mt-7 text-center text-[10px] font-medium uppercase tracking-[0.24em] text-slate-400">
                                Mahjong Club &middot; Main nyaman, booking rapi
                            </p> */}
                        </div>
                    </div>
                </main>
            </div>
        </div>
    );
}
