import { Link, useForm } from '@inertiajs/react';
import { useEffect } from 'react';

/**
 * Modal wajib isi nomor WhatsApp sebelum user boleh booking meja
 * maupun bergabung ke room booking.
 */
export default function ModalWhatsapp({ nilaiAwal = '' }) {
    const { data, setData, post, processing, errors } = useForm({ nowa: nilaiAwal });

    useEffect(() => {
        const overflowSebelumnya = document.body.style.overflow;
        document.body.style.overflow = 'hidden';

        return () => {
            document.body.style.overflow = overflowSebelumnya;
        };
    }, []);

    const simpan = (event) => {
        event.preventDefault();

        post('/profil/whatsapp', {
            preserveScroll: true,
            preserveState: true,
        });
    };

    return (
        <div
            className="fixed inset-0 z-50 flex items-end justify-center bg-slate-900/60 p-4 backdrop-blur-sm sm:items-center"
            role="dialog"
            aria-modal="true"
            aria-labelledby="judul-modal-whatsapp"
        >
            <div className="w-full max-w-sm overflow-hidden rounded-2xl bg-white shadow-2xl">
                <div className="bg-gradient-to-br from-emerald-600 to-emerald-800 px-5 py-4 text-white">
                    <span className="flex h-10 w-10 items-center justify-center rounded-xl bg-white/15">
                        <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5" fill="none" viewBox="0 0 24 24" strokeWidth={1.8} stroke="currentColor">
                            <path strokeLinecap="round" strokeLinejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z" />
                        </svg>
                    </span>
                    <h2 id="judul-modal-whatsapp" className="mt-3 text-base font-bold">
                        Lengkapi Nomor WhatsApp
                    </h2>
                    <p className="mt-1 text-xs leading-relaxed text-emerald-100">
                        Nomor WhatsApp wajib diisi sebelum kamu bisa booking meja. Kami memakainya untuk menghubungi kamu terkait booking dan pembayaran.
                    </p>
                </div>

                <form onSubmit={simpan} className="space-y-3 px-5 py-4">
                    <div>
                        <label htmlFor="nowa-booking" className="mb-1.5 block text-xs font-semibold text-slate-700">
                            Nomor WhatsApp
                        </label>
                        <input
                            id="nowa-booking"
                            type="tel"
                            inputMode="tel"
                            autoComplete="tel"
                            autoFocus
                            required
                            value={data.nowa}
                            onChange={(event) => setData('nowa', event.target.value)}
                            placeholder="08xxxxxxxxxx"
                            className={`w-full rounded-xl border px-3.5 py-2.5 text-sm text-slate-800 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-emerald-600 focus:ring-4 focus:ring-emerald-600/10 ${errors.nowa ? 'border-rose-300 bg-rose-50/40' : 'border-slate-200 bg-white'
                                }`}
                        />
                        {errors.nowa && (
                            <p className="mt-1.5 text-xs font-medium text-rose-600">{errors.nowa}</p>
                        )}
                    </div>

                    <button
                        type="submit"
                        disabled={processing || data.nowa.trim() === ''}
                        className="flex w-full items-center justify-center gap-2 rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-bold text-white shadow-lg shadow-emerald-200 transition hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-60"
                    >
                        {processing ? 'Menyimpan...' : 'Simpan & Lanjutkan'}
                    </button>

                    <Link
                        href="/home"
                        className="block text-center text-[11px] font-medium text-slate-400 transition hover:text-slate-600"
                    >
                        Kembali ke beranda
                    </Link>
                </form>
            </div>
        </div>
    );
}
