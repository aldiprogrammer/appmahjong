import { Link, usePage } from '@inertiajs/react';

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

const menus = [
    { name: 'Home', href: '/', icon: HomeIcon },
    { name: 'Booking', href: '/booking', icon: BookingIcon },
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

                <nav className="sticky bottom-0 z-40 border-t border-emerald-100 bg-white/95 px-3 pb-[max(0.5rem,env(safe-area-inset-bottom))] pt-1.5 backdrop-blur sm:rounded-b-[2rem]">
                    <div className="grid grid-cols-4 gap-1">
                        {menus.map((menu) => {
                            const Icon = menu.icon;
                            const active = isActive(menu.href);

                            return (
                                <Link
                                    key={menu.name}
                                    href={menu.href}
                                    className={`flex flex-col items-center gap-1 rounded-2xl py-1.5 text-[11px] font-semibold transition ${
                                        active ? 'text-emerald-700' : 'text-slate-400 hover:text-emerald-600'
                                    }`}
                                >
                                    <span
                                        className={`flex h-9 w-12 items-center justify-center rounded-full transition ${
                                            active ? 'bg-emerald-100 text-emerald-700' : 'text-slate-400'
                                        }`}
                                    >
                                        <Icon className="h-5 w-5" />
                                    </span>
                                    {menu.name}
                                </Link>
                            );
                        })}
                    </div>
                </nav>
            </div>
        </div>
    );
}
