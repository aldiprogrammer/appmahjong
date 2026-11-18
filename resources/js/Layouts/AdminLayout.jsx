import { Link, router, usePage } from '@inertiajs/react';
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

function VenueIcon({ className = 'h-5 w-5' }) {
    return (
        <svg xmlns="http://www.w3.org/2000/svg" className={className} fill="none" viewBox="0 0 24 24" strokeWidth={1.8} stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
            <path strokeLinecap="round" strokeLinejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
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

function KategoriMenuIcon({ className = 'h-5 w-5' }) {
    return (
        <svg xmlns="http://www.w3.org/2000/svg" className={className} fill="none" viewBox="0 0 24 24" strokeWidth={1.8} stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" d="M3.75 6.75A1.5 1.5 0 0 1 5.25 5.25h4.19a1.5 1.5 0 0 1 1.06.44l1.31 1.31h7.94a1.5 1.5 0 0 1 1.5 1.5v8.25a1.5 1.5 0 0 1-1.5 1.5H5.25a1.5 1.5 0 0 1-1.5-1.5V6.75Z" />
        </svg>
    );
}

function MenuMakananIcon({ className = 'h-5 w-5' }) {
    return (
        <svg xmlns="http://www.w3.org/2000/svg" className={className} fill="none" viewBox="0 0 24 24" strokeWidth={1.8} stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" d="M4.5 4.5v6a2.25 2.25 0 0 0 2.25 2.25h.75v6.75M7.5 4.5v6m-3-6v6m6.75-6h3.75A2.25 2.25 0 0 1 17.25 6.75v0A2.25 2.25 0 0 1 15 9H11.25V4.5Zm0 4.5v10.5" />
        </svg>
    );
}

function KasirIcon({ className = 'h-5 w-5' }) {
    return (
        <svg xmlns="http://www.w3.org/2000/svg" className={className} fill="none" viewBox="0 0 24 24" strokeWidth={1.8} stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" d="M2.25 8.25h19.5M4.5 8.25v10.125A1.875 1.875 0 0 0 6.375 20.25h11.25a1.875 1.875 0 0 0 1.875-1.875V8.25M9 12.75h6M6.75 3.75h10.5a1.5 1.5 0 0 1 1.5 1.5v3H5.25v-3a1.5 1.5 0 0 1 1.5-1.5Z" />
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

function StatusVoucherIcon({ className = 'h-5 w-5' }) {
    return (
        <svg xmlns="http://www.w3.org/2000/svg" className={className} fill="none" viewBox="0 0 24 24" strokeWidth={1.8} stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" d="M16.5 6V4.75A2.25 2.25 0 0 0 14.25 2.5h-4.5A2.25 2.25 0 0 0 7.5 4.75V6m9 0H7.5m9 0v12.75A2.25 2.25 0 0 1 14.25 21h-4.5A2.25 2.25 0 0 1 7.5 18.75V6m9 6H7.5" />
        </svg>
    );
}

function VoucherIcon({ className = 'h-5 w-5' }) {
    return (
        <svg xmlns="http://www.w3.org/2000/svg" className={className} fill="none" viewBox="0 0 24 24" strokeWidth={1.8} stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" d="M16.5 6V4.75A2.25 2.25 0 0 0 14.25 2.5h-4.5A2.25 2.25 0 0 0 7.5 4.75V6m9 0H7.5m9 0v12.75A2.25 2.25 0 0 1 14.25 21h-4.5A2.25 2.25 0 0 1 7.5 18.75V6" />
        </svg>
    );
}

function SettingRoomIcon({ className = 'h-5 w-5' }) {
    return (
        <svg xmlns="http://www.w3.org/2000/svg" className={className} fill="none" viewBox="0 0 24 24" strokeWidth={1.8} stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 1 1-3 0m3 0a1.5 1.5 0 1 0-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-3.75 0H7.5m5.25-6h6.75m-6.75 0a1.5 1.5 0 1 1-3 0m3 0a1.5 1.5 0 1 0-3 0m-3.75 0h3.75" />
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

function PeraturanIcon({ className = 'h-5 w-5' }) {
    return (
        <svg xmlns="http://www.w3.org/2000/svg" className={className} fill="none" viewBox="0 0 24 24" strokeWidth={1.8} stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25" />
        </svg>
    );
}

function AturanPointIcon({ className = 'h-5 w-5' }) {
    return (
        <svg xmlns="http://www.w3.org/2000/svg" className={className} fill="none" viewBox="0 0 24 24" strokeWidth={1.8} stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3-9v10.5A2.25 2.25 0 0 1 13.5 21h-9A2.25 2.25 0 0 1 2.25 18.75V5.25A2.25 2.25 0 0 1 4.5 3h6.879a2.25 2.25 0 0 1 1.59.659l3.372 3.372a2.25 2.25 0 0 1 .659 1.59V9m-3 0h-3.75a.75.75 0 0 1-.75-.75V4.5" />
        </svg>
    );
}

function MahjongFaanRuleIcon({ className = 'h-5 w-5' }) {
    return (
        <svg xmlns="http://www.w3.org/2000/svg" className={className} fill="none" viewBox="0 0 24 24" strokeWidth={1.8} stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 0 0-2.456 2.456Z" />
        </svg>
    );
}

function PrinterMenuIcon({ className = 'h-5 w-5' }) {
    return (
        <svg xmlns="http://www.w3.org/2000/svg" className={className} fill="none" viewBox="0 0 24 24" strokeWidth={1.8} stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" d="M6.75 7.5V4.5a1.5 1.5 0 0 1 1.5-1.5h7.5a1.5 1.5 0 0 1 1.5 1.5v3M6.75 16.5H4.5a1.5 1.5 0 0 1-1.5-1.5v-4.5a1.5 1.5 0 0 1 1.5-1.5h15a1.5 1.5 0 0 1 1.5 1.5V15a1.5 1.5 0 0 1-1.5 1.5h-2.25M6.75 13.5h10.5a.75.75 0 0 1 .75.75v4.5a.75.75 0 0 1-.75.75H6.75a.75.75 0 0 1-.75-.75v-4.5a.75.75 0 0 1 .75-.75Z" />
        </svg>
    );
}

function SuplayerFoodIcon({ className = 'h-5 w-5' }) {
    return (
        <svg xmlns="http://www.w3.org/2000/svg" className={className} fill="none" viewBox="0 0 24 24" strokeWidth={1.8} stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" d="M8.25 7.5V4.75a.75.75 0 0 1 .75-.75h6a.75.75 0 0 1 .75.75V7.5m-7.5 0h7.5m-9 0h10.5A1.5 1.5 0 0 1 18.75 9v9.75a2.25 2.25 0 0 1-2.25 2.25h-9A2.25 2.25 0 0 1 5.25 18.75V9a1.5 1.5 0 0 1 1.5-1.5Zm1.5 0V4.5A1.5 1.5 0 0 1 8.25 3h7.5a1.5 1.5 0 0 1 1.5 1.5v1.5" />
            <path strokeLinecap="round" strokeLinejoin="round" d="M2.25 12h19.5M5.25 12v2.25M18.75 12v2.25" />
        </svg>
    );
}

function PembayaranIcon({ className = 'h-5 w-5' }) {
    return (
        <svg xmlns="http://www.w3.org/2000/svg" className={className} fill="none" viewBox="0 0 24 24" strokeWidth={1.8} stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" d="M2.25 6h15.5A.75.75 0 0 1 18 6.75v8.25a.75.75 0 0 1-.75.75H2.25a.75.75 0 0 1-.75-.75V6.75a.75.75 0 0 1 .75-.75zm15-2.25A2.25 2.25 0 0 0 13.5 3.75h-3A2.25 2.25 0 0 0 8.25 6v.008h.02A2.25 2.25 0 0 0 11.25 8.25h3a2.25 2.25 0 0 0 2.25-2.25v-8.5zM4.5 9a.75.75 0 0 1-.75.75H1.5a.75.75 0 0 1 0-1.5h2.25a.75.75 0 0 1 .75.75zm16.5 0a.75.75 0 0 0-1.5 0v8.5a2.25 2.25 0 0 0 2.25 2.25h3a2.25 2.25 0 0 0 2.25-2.25v-8.5a2.25 2.25 0 0 0-2.25-2.25z" />
        </svg>
    );
}
/**
 * Ikon tiap menu panel admin. Kuncinya sama dengan kunci pada prop `adminMenus`
 * (segmen nama rute setelah "admin.").
 */
const menuIcons = {
    dashboard: DashboardIcon,
    kasir: KasirIcon,
    mejas: MejaIcon,
    vanues: VenueIcon,
    'jam-mains': JamMainIcon,
    'harga-jam-mains': HargaIcon,
    'kategori-menus': KategoriMenuIcon,
    menus: MenuMakananIcon,
    bookings: BookingIcon,
    penggunas: PenggunaIcon,
    'setting-booking-rooms': SettingRoomIcon,
    'aturan-points': AturanPointIcon,
    'mahjong-faan-rules': MahjongFaanRuleIcon,
    'status-vouchers': StatusVoucherIcon,
    vouchers: VoucherIcon,
    admins: AdminIcon,
    printers: PrinterMenuIcon,
    'suplayer-foods': SuplayerFoodIcon,
    pembayarans: PembayaranIcon,
};

/**
 * Menu yang ditampilkan sebagai submenu di dalam grup menu tertentu.
 * Kunci sama dengan kunci pada prop `adminMenus`.
 */
const menuGroups = {
    'aturan-points': {
        label: 'Pengaturan',
        icon: PeraturanIcon,
    },
    'mahjong-faan-rules': {
        label: 'Pengaturan',
        icon: PeraturanIcon,
    },
    'setting-booking-rooms': {
        label: 'Pengaturan',
        icon: SettingRoomIcon,
    },
    'status-vouchers': {
        label: 'Pengaturan',
        icon: VoucherIcon,
    },
    printers: {
        label: 'Pengaturan',
        icon: PrinterMenuIcon,
    },
    pembayarans: {
        label: 'Pengaturan',
        icon: PembayaranIcon,
    },
};

function MenuIcon() {
    return (
        <svg xmlns="http://www.w3.org/2000/svg" className="h-6 w-6" fill="none" viewBox="0 0 24 24" strokeWidth={1.8} stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5M3.75 17.25h16.5" />
        </svg>
    );
}

export default function AdminLayout({ title = 'Dashboard', subtitle = 'Panel Administrasi Mahjong', children }) {
    const [sidebarOpen, setSidebarOpen] = useState(false);
    const [openGroups, setOpenGroups] = useState({});
    const [showLogoutModal, setShowLogoutModal] = useState(false);
    const [loggingOut, setLoggingOut] = useState(false);
    const { url, props } = usePage();
    const currentAdmin = props.auth?.admin ?? null;
    const hakAkses = currentAdmin?.hak_akses ?? null;

    const logout = () => {
        setLoggingOut(true);
        router.post('/admin/logout', undefined, {
            onFinish: () => setLoggingOut(false),
        });
    };

    const today = new Intl.DateTimeFormat('id-ID', {
        day: '2-digit',
        month: 'long',
        year: 'numeric',
    }).format(new Date());

    const menus = Object.entries(props.adminMenus ?? {})
        .map(([key, name]) => ({
            key,
            name,
            href: `/admin/${key}`,
            icon: menuIcons[key] ?? DashboardIcon,
        }))
        .filter((menu) => hakAkses === null || hakAkses.includes(menu.key));
    const isActive = (href) => url.startsWith(href);

    const menuSections = [];
    const grupTercatat = new Set();

    menus.forEach((menu) => {
        const grup = menuGroups[menu.key];

        if (!grup) {
            menuSections.push({ type: 'menu', menu });

            return;
        }

        if (grupTercatat.has(grup.label)) {
            return;
        }

        grupTercatat.add(grup.label);
        menuSections.push({
            type: 'group',
            grup,
            items: menus.filter((item) => menuGroups[item.key]?.label === grup.label),
        });
    });

    const grupTerbuka = (grup, items) =>
        openGroups[grup.label] ?? items.some((item) => isActive(item.href));

    return (
        <>
            <aside
                className={`fixed inset-y-0 left-0 z-40 flex w-64 flex-col overflow-hidden bg-gradient-to-b from-emerald-700 to-emerald-900 shadow-2xl transition-transform duration-300 ease-in-out lg:translate-x-0 ${
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

                <nav className="admin-sidebar-scroll min-h-0 flex-1 space-y-1 overflow-y-auto overscroll-contain px-3 py-6">
                    <p className="px-3 pb-2 text-[11px] font-semibold uppercase tracking-widest text-emerald-300">Menu</p>

                    {menuSections.map((section) => {
                        if (section.type === 'group') {
                            const { grup, items } = section;
                            const GroupIcon = grup.icon;
                            const grupAktif = items.some((item) => isActive(item.href));
                            const terbuka = grupTerbuka(grup, items);

                            return (
                                <div key={grup.label}>
                                    <button
                                        type="button"
                                        onClick={() =>
                                            setOpenGroups((previous) => ({ ...previous, [grup.label]: !terbuka }))
                                        }
                                        aria-expanded={terbuka}
                                        className={`group flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition ${
                                            grupAktif
                                                ? 'bg-white/10 text-white'
                                                : 'text-emerald-100 hover:bg-white/10 hover:text-white'
                                        }`}
                                    >
                                        <GroupIcon className={`h-5 w-5 ${grupAktif ? 'text-white' : 'text-emerald-200'}`} />
                                        {grup.label}
                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            className={`ml-auto h-4 w-4 transition-transform ${terbuka ? 'rotate-180' : ''}`}
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            strokeWidth={2}
                                            stroke="currentColor"
                                        >
                                            <path strokeLinecap="round" strokeLinejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                                        </svg>
                                    </button>

                                    {terbuka && (
                                        <div className="mt-1 ml-5 space-y-1 border-l border-white/15 pl-3">
                                            {items.map((menu) => {
                                                const Icon = menu.icon;
                                                const active = isActive(menu.href);

                                                return (
                                                    <Link
                                                        key={menu.key}
                                                        href={menu.href}
                                                        className={`group flex items-center gap-3 rounded-xl px-3 py-2 text-sm font-medium transition ${
                                                            active
                                                                ? 'bg-white text-emerald-700 shadow-sm'
                                                                : 'text-emerald-100 hover:bg-white/10 hover:text-white'
                                                        }`}
                                                    >
                                                        <Icon
                                                            className={`h-4 w-4 ${active ? 'text-emerald-600' : 'text-emerald-200'}`}
                                                        />
                                                        {menu.name}
                                                        {active && (
                                                            <span className="ml-auto h-1.5 w-1.5 rounded-full bg-emerald-500" />
                                                        )}
                                                    </Link>
                                                );
                                            })}
                                        </div>
                                    )}
                                </div>
                            );
                        }

                        const { menu } = section;
                        const Icon = menu.icon;
                        const active = isActive(menu.href);

                        return (
                            <Link
                                key={menu.key}
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

                <div className="m-3 shrink-0 rounded-xl bg-white/10 p-4 ring-1 ring-white/10">
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
                        <button
                            type="button"
                            onClick={() => setShowLogoutModal(true)}
                            className="rounded-full border border-emerald-200 bg-white px-3 py-1.5 text-xs font-medium text-emerald-700 transition hover:bg-emerald-50"
                        >
                            Keluar
                        </button>
                        <div className="flex items-center gap-2 rounded-full border border-emerald-100 bg-white py-1 pl-1 pr-3">
                            <span className="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-emerald-600 text-sm font-semibold uppercase text-white">
                                {currentAdmin?.username?.charAt(0) ?? 'A'}
                            </span>
                            <span className="hidden leading-tight sm:block">
                                <span className="block text-sm font-medium text-slate-700">
                                    {currentAdmin?.username ?? 'Admin'}
                                </span>
                                <span className="block text-[11px] font-normal text-slate-500">
                                    {currentAdmin?.vanue?.nama ?? 'Belum ada venue'}
                                </span>
                            </span>
                        </div>
                    </div>
                </header>

                <main className="flex-1 p-4 sm:p-6 lg:p-8">{children}</main>

                <footer className="border-t border-emerald-100 bg-white px-6 py-4 text-center text-xs text-slate-500">
                    &copy; {new Date().getFullYear()} Mahjong Admin Panel. Semua hak dilindungi.
                </footer>
            </div>

            {showLogoutModal && (
                <div
                    className="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4 backdrop-blur-sm"
                    role="dialog"
                    aria-modal="true"
                >
                    <div className="w-full max-w-sm rounded-2xl bg-white p-6 shadow-2xl">
                        <div className="flex items-start gap-4">
                            <span className="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-red-50 text-red-600 ring-1 ring-red-100">
                                <svg xmlns="http://www.w3.org/2000/svg" className="h-6 w-6" fill="none" viewBox="0 0 24 24" strokeWidth={1.8} stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9" />
                                </svg>
                            </span>
                            <div>
                                <h2 className="text-sm font-bold text-slate-900">Keluar dari panel admin?</h2>
                                <p className="mt-1 text-xs leading-relaxed text-slate-500">
                                    Apakah Anda ingin keluar? Sesi admin Anda akan diakhiri dan perlu login kembali.
                                </p>
                            </div>
                        </div>

                        <div className="mt-6 flex justify-end gap-2">
                            <button
                                type="button"
                                onClick={() => setShowLogoutModal(false)}
                                disabled={loggingOut}
                                className="rounded-lg border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-50 disabled:opacity-50"
                            >
                                Batal
                            </button>
                            <button
                                type="button"
                                onClick={logout}
                                disabled={loggingOut}
                                className="rounded-lg bg-red-600 px-4 py-2 text-xs font-semibold text-white transition hover:bg-red-700 disabled:opacity-50"
                            >
                                {loggingOut ? 'Keluar...' : 'Ya, Keluar'}
                            </button>
                        </div>
                    </div>
                </div>
            )}
        </>
    );
}
