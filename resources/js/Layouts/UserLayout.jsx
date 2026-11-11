import { Link, usePage } from '@inertiajs/react';
import ScanMejaButton from './ScanMejaButton';

function HomeIcon({ className = 'h-5 w-5' }) {
    return (
        <svg xmlns="http://www.w3.org/2000/svg" className={className} fill="none" viewBox="0 0 24 24" strokeWidth={1.8} stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" d="M2.25 12l8.954-8.955a1.126 1.126 0 0 1 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75" />
        </svg>
    );
}

function BookingIcon({ className = 'h-5 w-5' }) {
    return (
        <svg xmlns="http://www.w3.org/2000/svg" className={className} fill="none" viewBox="0 0 24 24" strokeWidth={1.8} stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
        </svg>
    );
}

function RiwayatIcon({ className = 'h-5 w-5' }) {
    return (
        <svg xmlns="http://www.w3.org/2000/svg" className={className} fill="none" viewBox="0 0 24 24" strokeWidth={1.8} stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" d="M12 6v6l4 2m6-2a10 10 0 1 1-20 0 10 10 0 0 1 20 0Z" />
        </svg>
    );
}

function ProfilIcon({ className = 'h-5 w-5' }) {
    return (
        <svg xmlns="http://www.w3.org/2000/svg" className={className} fill="none" viewBox="0 0 24 24" strokeWidth={1.8} stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.5 20.25a7.5 7.5 0 0 1 15 0" />
        </svg>
    );
}

function RoomIcon({ className = 'h-5 w-5' }) {
    return (
        <svg xmlns="http://www.w3.org/2000/svg" className={className} fill="none" viewBox="0 0 24 24" strokeWidth={1.8} stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
        </svg>
    );
}

function RankIcon({ className = 'h-5 w-5' }) {
    return (
        <svg xmlns="http://www.w3.org/2000/svg" className={className} fill="none" viewBox="0 0 24 24" strokeWidth={1.8} stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" d="M16.5 18.75h-9m9 0a3 3 0 0 1 3 3h-15a3 3 0 0 1 3-3m9 0v-3.375c0-.621-.503-1.125-1.125-1.125h-.871M7.5 18.75v-3.375c0-.621.504-1.125 1.125-1.125h.872m5.007 0H9.497m5.007 0a7.454 7.454 0 0 1-.982-3.172M9.497 14.25a7.454 7.454 0 0 0 .981-3.172M5.25 4.236c-.982.143-1.954.317-2.916.52A6.003 6.003 0 0 0 7.73 9.728M5.25 4.236V4.5c0 2.108.966 3.99 2.48 5.228M5.25 4.236V2.721C7.456 2.41 9.71 2.25 12 2.25c2.291 0 4.545.16 6.75.47v1.516M7.73 9.728a6.726 6.726 0 0 0 2.748 1.35m8.272-6.842V4.5c0 2.108-.966 3.99-2.48 5.228m2.48-5.492a46.32 46.32 0 0 1 2.916.52 6.003 6.003 0 0 1-5.395 4.972m0 0a6.726 6.726 0 0 1-2.749 1.35m0 0a6.772 6.772 0 0 1-3.044 0" />
        </svg>
    );
}

const menus = [
    { name: 'Home', href: '/home', icon: HomeIcon },
    { name: 'Booking', href: '/booking', icon: BookingIcon },
    { name: 'Room', href: '/room-booking', icon: RoomIcon },
    { name: 'Ranking', href: '/ranking', icon: RankIcon },
    { name: 'Riwayat', href: '/riwayat', icon: RiwayatIcon },
    { name: 'Profil', href: '/profil', icon: ProfilIcon },
];

export default function UserLayout({ children }) {
    const { url } = usePage();
    const path = url.split('?')[0];
    const isActive = (href) => (href === '/' ? path === '/' : path.startsWith(href));

    return (
        <div className="min-h-screen bg-emerald-950/95 sm:bg-[#e8f4ed] sm:py-6">
            <div className="relative mx-auto flex min-h-screen w-full max-w-md flex-col bg-[#f6faf7] shadow-2xl sm:min-h-[calc(100vh-3rem)] sm:rounded-[2rem] sm:border sm:border-emerald-100">
                <main className="flex-1">{children}</main>

                <nav className="sticky bottom-0 z-40 border-t border-emerald-100/70 bg-white/95 px-2 pb-[max(0.75rem,env(safe-area-inset-bottom))] pt-2 shadow-[0_-12px_30px_-18px_rgba(6,78,59,0.28)] backdrop-blur-md sm:rounded-b-[2rem]">
                    <div className="grid grid-cols-7 items-end gap-1">
                        {[...menus.slice(0, 3), null, ...menus.slice(3)].map((menu) => {
                            if (menu === null) {
                                return <ScanMejaButton key="scan" />;
                            }

                            const Icon = menu.icon;
                            const active = isActive(menu.href);

                            return (
                                <Link
                                    key={menu.name}
                                    href={menu.href}
                                    className={`group relative flex flex-col items-center gap-1 rounded-2xl px-1 py-2 text-[10px] font-semibold transition-all duration-200 active:scale-95 ${active ? 'text-emerald-700' : 'text-slate-400 hover:text-emerald-600'
                                        }`}
                                >
                                    <span
                                        className={`relative flex h-10 w-10 items-center justify-center rounded-2xl transition-all duration-200 ${active
                                                ? 'bg-gradient-to-br from-emerald-500 to-emerald-600 text-white shadow-lg shadow-emerald-500/35'
                                                : 'text-slate-400 group-hover:bg-emerald-50 group-hover:text-emerald-600'
                                            }`}
                                    >
                                        <Icon className={`h-5 w-5 transition-transform duration-200 ${active ? 'scale-110' : ''}`} />
                                    </span>
                                    <span className="leading-none tracking-wide">{menu.name}</span>
                                    {active && <span className="h-1 w-1 rounded-full bg-emerald-500" />}
                                </Link>
                            );
                        })}
                    </div>
                </nav>
            </div>
        </div>
    );
}
