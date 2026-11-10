import { Head, router, usePage } from '@inertiajs/react';
import Swal from 'sweetalert2';
import 'sweetalert2/dist/sweetalert2.min.css';
import { useState } from 'react';
import UserLayout from '../../Layouts/UserLayout';

function formatTanggal(value) {
    if (!value) {
        return '-';
    }

    const date = new Date(`${value}T00:00:00`);

    if (Number.isNaN(date.getTime())) {
        return value;
    }

    return new Intl.DateTimeFormat('id-ID', {
        weekday: 'long',
        day: '2-digit',
        month: 'long',
        year: 'numeric',
    }).format(date);
}

function formatRupiah(value) {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(Number(value) || 0);
}

function RoomIcon({ className = 'h-5 w-5' }) {
    return (
        <svg xmlns="http://www.w3.org/2000/svg" className={className} fill="none" viewBox="0 0 24 24" strokeWidth={1.8} stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
        </svg>
    );
}

export default function RoomBooking({ rooms = [] }) {
    const { flash } = usePage().props;
    const [joining, setJoining] = useState(null);

    const joinRoom = (room) => {
        if (joining) {
            return;
        }

        Swal.fire({
            icon: 'question',
            title: 'Gabung room ini?',
            html: `Kamu akan bergabung ke room <strong>${room.pemilik}</strong> pada ${formatTanggal(room.tgl_main)}.`,
            showCancelButton: true,
            confirmButtonText: 'Ya, Gabung',
            cancelButtonText: 'Batal',
            buttonsStyling: false,
            width: 'min(26rem, calc(100vw - 2rem))',
            padding: '2rem 1.75rem',
            backdrop: 'rgba(15, 23, 42, 0.58)',
            customClass: {
                container: 'app-alert-container',
                popup: 'app-alert',
                title: 'app-alert-title',
                icon: 'app-alert-icon',
                htmlContainer: 'app-alert-message',
                actions: 'app-alert-actions',
                confirmButton: 'app-alert-confirm',
                cancelButton: 'app-alert-cancel',
            },
        }).then((result) => {
            if (!result.isConfirmed) {
                return;
            }

            setJoining(room.kode_room);

            router.post(`/room-booking/${room.kode_room}/join`, {}, {
                preserveScroll: true,
                onFinish: () => setJoining(null),
            });
        });
    };

    return (
        <>
            <Head title="Room Booking" />

            <header className="relative overflow-hidden rounded-b-[2rem] bg-gradient-to-br from-emerald-600 to-emerald-800 px-5 pb-10 pt-6 text-white">
                <div className="pointer-events-none absolute -right-10 -top-10 h-36 w-36 rounded-full bg-white/10" />
                <div className="relative flex items-center gap-3">
                    <span className="flex h-10 w-10 items-center justify-center rounded-full bg-white/10 ring-1 ring-white/20">
                        <RoomIcon />
                    </span>
                    <div>
                        <h1 className="text-lg font-bold">Room Booking</h1>
                        <p className="text-xs text-emerald-100">Gabung room public yang masih punya slot.</p>
                    </div>
                </div>
            </header>

            <div className="relative -mt-6 space-y-4 px-5 pb-8">
                {flash?.success && (
                    <div className="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
                        {flash.success}
                    </div>
                )}
                {flash?.error && (
                    <div className="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-700">
                        {flash.error}
                    </div>
                )}

                {rooms.length === 0 ? (
                    <div className="flex flex-col items-center rounded-2xl border border-emerald-100 bg-white px-6 py-14 text-center shadow-sm">
                        <span className="flex h-14 w-14 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-500">
                            <RoomIcon className="h-7 w-7" />
                        </span>
                        <h2 className="mt-4 text-sm font-bold text-slate-800">Belum ada room public</h2>
                        <p className="mt-1 max-w-xs text-xs leading-relaxed text-slate-500">
                            Room public yang dibuat lewat Public Booking akan muncul di sini dan bisa kamu ikuti.
                        </p>
                    </div>
                ) : (
                    rooms.map((room) => {
                        const penuh = room.sisa_slot === 0;
                        const sudahJoin = room.sudah_join;
                        const tidakBisa = penuh || sudahJoin || joining === room.kode_room;

                        return (
                            <section key={room.kode_room} className="overflow-hidden rounded-2xl border border-emerald-100 bg-white shadow-sm">
                                <div className="bg-gradient-to-br from-emerald-600 to-emerald-800 px-4 py-3 text-white">
                                    <div className="flex items-center justify-between gap-2">
                                        <p className="text-[10px] font-semibold uppercase tracking-wide text-emerald-100">
                                            Room {room.kode_room}
                                        </p>
                                        <span className={`rounded-full px-2.5 py-0.5 text-[10px] font-bold ${
                                            penuh ? 'bg-rose-500/90 text-white' : 'bg-white/90 text-emerald-700'
                                        }`}>
                                            {penuh ? 'Penuh' : `${room.sisa_slot} slot tersisa`}
                                        </span>
                                    </div>
                                    <p className="mt-1 text-sm font-bold">{room.id_meja}</p>
                                    <p className="text-[11px] text-emerald-100">{formatTanggal(room.tgl_main)}</p>
                                </div>

                                <div className="space-y-3 p-4">
                                    <div className="flex items-center justify-between text-xs">
                                        <span className="text-slate-500">Pemilik room</span>
                                        <span className="font-semibold text-slate-800">{room.pemilik}</span>
                                    </div>
                                    <div className="flex items-center justify-between text-xs">
                                        <span className="text-slate-500">Anggota</span>
                                        <span className="font-semibold text-slate-800">
                                            {room.jumlah_anggota} / {room.kapasitas} user
                                        </span>
                                    </div>
                                    <div className="flex items-start justify-between gap-3 text-xs">
                                        <span className="shrink-0 text-slate-500">Jam main</span>
                                        <div className="flex flex-wrap justify-end gap-1">
                                            {room.sesi.map((sesi, index) => (
                                                <span
                                                    key={index}
                                                    className="rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-semibold text-emerald-700 ring-1 ring-emerald-100"
                                                >
                                                    {sesi.jam_mulai} - {sesi.jam_selesai}
                                                </span>
                                            ))}
                                        </div>
                                    </div>
                                    <div className="flex items-center justify-between border-t border-dashed border-emerald-100 pt-3 text-xs">
                                        <span className="text-slate-500">
                                            {room.mode_pembayaran === 'one' ? 'Biaya bergabung' : 'Harga per user'}
                                        </span>
                                        <span className="text-sm font-bold text-emerald-700">
                                            {room.mode_pembayaran === 'one' ? 'Gratis' : formatRupiah(room.harga_per_user)}
                                        </span>
                                    </div>
                                    {room.mode_pembayaran === 'one' && (
                                        <p className="-mt-1 text-[10px] text-slate-500">
                                            Pembayaran sudah ditanggung pembuka room.
                                        </p>
                                    )}

                                    <button
                                        type="button"
                                        onClick={() => joinRoom(room)}
                                        disabled={tidakBisa}
                                        className="mt-1 flex w-full items-center justify-center gap-2 rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-emerald-700 disabled:cursor-not-allowed disabled:bg-slate-300"
                                    >
                                        {joining === room.kode_room
                                            ? 'Memproses...'
                                            : sudahJoin
                                                ? 'Sudah Bergabung'
                                                : penuh
                                                    ? 'Room Penuh'
                                                    : 'Gabung Room'}
                                    </button>
                                </div>
                            </section>
                        );
                    })
                )}
            </div>
        </>
    );
}

RoomBooking.layout = (page) => <UserLayout>{page}</UserLayout>;
