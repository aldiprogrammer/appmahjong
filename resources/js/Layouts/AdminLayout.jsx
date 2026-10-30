import { Link, usePage } from '@inertiajs/react';
import { useState } from 'react';

function BrandIcon() {
    return (
        <svg xmlns="http://www.w3.org/2000/svg" className="h-6 w-6" fill="none" viewBox="0 0 24 24" strokeWidth={1.8} stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" d="M4 5.25A2.25 2.25 0 0 1 6.25 3h11.5A2.25 2.25 0 0 1 20 5.25v13.5A2.25 2.25 0 0 1 17.75 21H6.25A2.25 2.25 0 0 1 4 18.75V5.25Z" />
            <path strokeLinecap="round" strokeLinejoin="round" d="M8 7h8M8 11h8M8 15h4" />
        </svg>
    );
}

function DashboardIcon({ className = 'h-5 w-5' }) {
    return (
        <svg xmlns="http://www.w3.org/2000/svg" className={className} fill="none" viewBox="0 0 24 24" strokeWidth={1.8} stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" d="M3 12l9-9 9 9M5.25 9.75V21h4.5v-6h4.5v6h4.5V9.75" />
        </svg>
    );
}

function MejaIcon({ className = 'h-5 w-5' }) {
    return (
        <svg xmlns="http://www.w3.org/2000/svg" className={className} fill="none" viewBox="0 0 24 24" strokeWidth={1.8} stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" d="M3 10h18M5 10v8m14-8v8M7 21h10M6 10V6a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v4" />
        </svg>
    );
}

function JamMainIcon({ className = 'h-5 w-5' }) {
    return (
        <svg xmlns="http://www.w3.org/2000/svg" className={className} fill="none" viewBox="0 0 24 24" strokeWidth={1.8} stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" d="M12 6v6l4 2m6-2a10 10 0 1 1-20 0 10 10 0 0 1 20 0Z" />
        </svg>
    );
}

function HargaIcon({ className = 'h-5 w-5' }) {
    return (
        <svg xmlns="http://www.w3.org/2000/svg" className={className} fill="none" viewBox="0 0 24 24" strokeWidth={1.8} stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" d="M9.568 3H5.25A2.25 2.25 0 0 0 3 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 0 0 5.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 0 0 9.568 3Z" />
            <path strokeLinecap="round" strokeLinejoin="round" d="M6 6h.008v.008H6V6Z" />
        </svg>
    );
}

function PenggunaIcon({ className = 'h-5 w-5' }) {
    return (
        <svg xmlns="http://www.w3.org/2000/svg" className={className} fill="none" viewBox="0 0 24 24" strokeWidth={1.8} stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
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

function AdminIcon({ className = 'h-5 w-5' }) {
    return (
        <svg xmlns="http://www.w3.org/2000/svg" className={className} fill="none" viewBox="0 0 24 24" strokeWidth={1.8} stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.5 20.25a7.5 7.5 0 0 1 15 0" />
        </svg>
    );
}

function MenuIcon() {
    return (
        <svg xmlns="http://www.w3.org/2000/svg" className="h-6 w-6" fill="none" viewBox="0 0 24 24" strokeWidth={1.8} stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5M3.75 17.25h16.5" />
        </svg>
    );
}

export default function AdminLayout({ title = 'Dashboard', subtitle = 'Panel Administrasi Mahjong', children }) {
    const [sidebarOpen, setSidebarOpen] = useState(false);
    const { url } = usePage();

    const today = new Intl.DateTimeFormat('id-ID', {
        day: '2-digit',
        month: 'long',
        year: 'numeric',
    }).format(new Date());

    const menus = [
        { name: 'Dashboard', href: '/admin/dashboard', icon: DashboardIcon },
        { name: 'Meja', href: '/admin/mejas', icon: MejaIcon },
        { name: 'Jam Main', href: '/admin/jam-mains', icon: JamMainIcon },
        { name: 'Harga Jam Main', href: '/admin/harga-jam-mains', icon: HargaIcon },
        { name: 'Booking', href: '/admin/bookings', icon: BookingIcon },
        { name: 'Pengguna', href: '/admin/penggunas', icon: PenggunaIcon },
        { name: 'Akun Admin', href: '/admin/admins', icon: AdminIcon },
    ];
    const isActive = (href) => url.startsWith(href);

    return (
        <>
            <aside
                className={`fixed inset-y-0 left-0 z-40 flex w-64 flex-col bg-gradient-to-b from-emerald-700 to-emerald-900 shadow-2xl transition-transform duration-300 ease-in-out lg:translate-x-0 ${
                    sidebarOpen ? 'translate-x-0' : '-translate-x-full'
                }`}
            >
                <div className="flex h-16 items-center gap-3 border-b border-white/10 px-6">
                    <span className="flex h-10 w-10 items-center justify-center rounded-xl bg-white/15 text-white ring-1 ring-white/20">
                        <BrandIcon />
                    </span>
                    <div className="leading-tight">
                        <p className="text-base font-semibold text-white">Mahjong</p>
                        <p className="text-xs text-emerald-200">Admin Panel</p>
                    </div>
                    <button
                        type="button"
                        onClick={() => setSidebarOpen(false)}
                        className="ml-auto inline-flex h-8 w-8 items-center justify-center rounded-lg text-emerald-200 transition hover:bg-white/10 hover:text-white lg:hidden"
                        aria-label="Tutup menu"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5" fill="none" viewBox="0 0 24 24" strokeWidth={1.8} stroke="currentColor">
                            <path strokeLinecap="round" strokeLinejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <nav className="flex-1 space-y-1 px-3 py-6">
                    <p className="px-3 pb-2 text-[11px] font-semibold uppercase tracking-widest text-emerald-300">Menu</p>

                    {menus.map((menu) => {
                        const Icon = menu.icon;
                        const active = isActive(menu.href);

                        return (
                            <Link
                                key={menu.name}
                                href={menu.href}
                                className={`group flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition ${
                                    active
                                        ? 'bg-white text-emerald-700 shadow-sm'
                                        : 'text-emerald-100 hover:bg-white/10 hover:text-white'
                                }`}
                            >
                                <Icon className={`h-5 w-5 ${active ? 'text-emerald-600' : 'text-emerald-200'}`} />
                                {menu.name}
                                {active && <span className="ml-auto h-1.5 w-1.5 rounded-full bg-emerald-500" />}
                            </Link>
                        );
                    })}
                </nav>

                <div className="m-3 rounded-xl bg-white/10 p-4 ring-1 ring-white/10">
                    <p className="text-sm font-semibold text-white">Butuh bantuan?</p>
                    <p className="mt-1 text-xs leading-relaxed text-emerald-200">
                        Kelola data meja, jam main, harga, booking, pengguna, dan akun admin dengan mudah dari panel ini.
                    </p>
                </div>
            </aside>

            <div
                onClick={() => setSidebarOpen(false)}
                className={`fixed inset-0 z-30 bg-slate-900/40 backdrop-blur-sm transition-opacity lg:hidden ${
                    sidebarOpen ? 'opacity-100' : 'pointer-events-none opacity-0'
                }`}
            />

            <div className="flex min-h-screen flex-col lg:pl-64">
                <header className="sticky top-0 z-20 flex h-16 items-center gap-4 border-b border-emerald-100 bg-white/90 px-4 backdrop-blur sm:px-6">
                    <button
                        type="button"
                        onClick={() => setSidebarOpen(true)}
                        className="inline-flex h-10 w-10 items-center justify-center rounded-lg text-emerald-700 transition hover:bg-emerald-50 lg:hidden"
                        aria-label="Buka menu"
                    >
                        <MenuIcon />
                    </button>

                    <div>
                        <p className="text-sm font-semibold text-slate-900">{title}</p>
                        <p className="text-xs text-slate-500">{subtitle}</p>
                    </div>

                    <div className="ml-auto flex items-center gap-3">
                        <span className="hidden rounded-full bg-emerald-50 px-3 py-1.5 text-xs font-medium text-emerald-700 ring-1 ring-emerald-100 sm:inline-flex">
                            {today}
                        </span>
                        <div className="flex items-center gap-2 rounded-full border border-emerald-100 bg-white py-1 pl-1 pr-3">
                            <span className="flex h-8 w-8 items-center justify-center rounded-full bg-emerald-600 text-sm font-semibold text-white">
                                A
                            </span>
                            <span className="hidden text-sm font-medium text-slate-700 sm:block">Admin</span>
                        </div>
                    </div>
                </header>

                <main className="flex-1 p-4 sm:p-6 lg:p-8">{children}</main>

                <footer className="border-t border-emerald-100 bg-white px-6 py-4 text-center text-xs text-slate-500">
                    &copy; {new Date().getFullYear()} Mahjong Admin Panel. Semua hak dilindungi.
                </footer>
            </div>
        </>
    );
}
