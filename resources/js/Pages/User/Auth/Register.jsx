import { Head, Link, useForm } from '@inertiajs/react';
import { useState } from 'react';
import AuthLayout from '../../../Layouts/AuthLayout';

function EyeIcon({ open }) {
    if (open) {
        return (
            <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5" fill="none" viewBox="0 0 24 24" strokeWidth={1.8} stroke="currentColor">
                <path strokeLinecap="round" strokeLinejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                <path strokeLinecap="round" strokeLinejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
            </svg>
        );
    }

    return (
        <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5" fill="none" viewBox="0 0 24 24" strokeWidth={1.8} stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" />
        </svg>
    );
}

function ArrowIcon() {
    return (
        <svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4 transition-transform group-hover:translate-x-0.5" fill="none" viewBox="0 0 24 24" strokeWidth={2.2} stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
        </svg>
    );
}

function ShieldIcon() {
    return (
        <svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4" fill="none" viewBox="0 0 24 24" strokeWidth={1.8} stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" />
        </svg>
    );
}

const labelClass = 'mb-1.5 block text-[11px] font-semibold uppercase tracking-[0.12em] text-slate-500';

export default function Register() {
    const [showPassword, setShowPassword] = useState(false);
    const { data, setData, post, processing, errors } = useForm({
        nama_depan: '',
        nama_balakang: '',
        email: '',
        nowa: '',
        password: '',
        password_confirmation: '',
    });

    const inputClass = (field) =>
        `w-full rounded-xl border px-3.5 py-3 text-base text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-emerald-600 focus:bg-white focus:ring-4 focus:ring-emerald-600/10 lg:text-sm ${errors[field] ? 'border-rose-300 bg-rose-50/50' : 'border-slate-200 bg-slate-50/70'
        }`;

    const submit = (event) => {
        event.preventDefault();
        post('/register');
    };

    return (
        <>
            <Head title="Daftar" />

            <AuthLayout
                badge="Halaman Register"
                mode="register"
                title="Buat akun anda"
                subtitle="Satu akun untuk booking meja, memilih jam main, dan dapatkan point."
                footer={
                    <div className="text-center text-sm text-slate-500">
                        Sudah punya akun?{' '}
                        <Link
                            href="/login"
                            className="font-semibold text-emerald-700 underline decoration-emerald-300 decoration-2 underline-offset-4 transition hover:text-emerald-900"
                        >
                            Masuk di sini
                        </Link>
                    </div>
                }
            >
                <form onSubmit={submit} className="space-y-4">
                    <div className="grid grid-cols-2 gap-3">
                        <div>
                            <label htmlFor="nama_depan" className={labelClass}>
                                Nama Depan
                            </label>
                            <input
                                id="nama_depan"
                                type="text"
                                value={data.nama_depan}
                                onChange={(event) => setData('nama_depan', event.target.value)}
                                placeholder="Nama depan"
                                autoComplete="given-name"
                                required
                                className={inputClass('nama_depan')}
                            />
                            {errors.nama_depan && (
                                <p className="mt-1.5 text-xs font-medium text-rose-600">{errors.nama_depan}</p>
                            )}
                        </div>

                        <div>
                            <label htmlFor="nama_balakang" className={labelClass}>
                                Nama Belakang
                            </label>
                            <input
                                id="nama_balakang"
                                type="text"
                                value={data.nama_balakang}
                                onChange={(event) => setData('nama_balakang', event.target.value)}
                                placeholder="Opsional"
                                autoComplete="family-name"
                                className={inputClass('nama_balakang')}
                            />
                            {errors.nama_balakang && (
                                <p className="mt-1.5 text-xs font-medium text-rose-600">{errors.nama_balakang}</p>
                            )}
                        </div>
                    </div>

                    <div>
                        <label htmlFor="email" className={labelClass}>
                            Email
                        </label>
                        <input
                            id="email"
                            type="email"
                            value={data.email}
                            onChange={(event) => setData('email', event.target.value)}
                            placeholder="email@contoh.com"
                            autoComplete="email"
                            required
                            className={inputClass('email')}
                        />
                        {errors.email && <p className="mt-1.5 text-xs font-medium text-rose-600">{errors.email}</p>}
                    </div>

                    <div>
                        <label htmlFor="nowa" className={labelClass}>
                            No. WhatsApp
                        </label>
                        <input
                            id="nowa"
                            type="tel"
                            value={data.nowa}
                            onChange={(event) => setData('nowa', event.target.value)}
                            placeholder="08xxxxxxxxxx"
                            autoComplete="tel"
                            required
                            className={inputClass('nowa')}
                        />
                        {errors.nowa && <p className="mt-1.5 text-xs font-medium text-rose-600">{errors.nowa}</p>}
                    </div>

                    <div>
                        <label htmlFor="password" className={labelClass}>
                            Password
                        </label>
                        <div className="relative">
                            <input
                                id="password"
                                type={showPassword ? 'text' : 'password'}
                                value={data.password}
                                onChange={(event) => setData('password', event.target.value)}
                                placeholder="Minimal 8 karakter"
                                autoComplete="new-password"
                                required
                                className={`${inputClass('password')} pr-11`}
                            />
                            <button
                                type="button"
                                onClick={() => setShowPassword((value) => !value)}
                                className="absolute inset-y-0 right-0 flex w-11 items-center justify-center text-slate-400 transition hover:text-emerald-700"
                                aria-label={showPassword ? 'Sembunyikan password' : 'Tampilkan password'}
                            >
                                <EyeIcon open={showPassword} />
                            </button>
                        </div>
                        {errors.password && (
                            <p className="mt-1.5 text-xs font-medium text-rose-600">{errors.password}</p>
                        )}
                    </div>

                    <div>
                        <label htmlFor="password_confirmation" className={labelClass}>
                            Konfirmasi Password
                        </label>
                        <input
                            id="password_confirmation"
                            type={showPassword ? 'text' : 'password'}
                            value={data.password_confirmation}
                            onChange={(event) => setData('password_confirmation', event.target.value)}
                            placeholder="Ulangi password"
                            autoComplete="new-password"
                            required
                            className={inputClass('password_confirmation')}
                        />
                        {errors.password_confirmation && (
                            <p className="mt-1.5 text-xs font-medium text-rose-600">{errors.password_confirmation}</p>
                        )}
                    </div>

                    <button
                        type="submit"
                        disabled={processing}
                        className="group flex w-full items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-emerald-700 to-emerald-600 px-5 py-3 text-sm font-bold text-white shadow-[0_18px_35px_-18px_rgba(6,78,59,0.9)] ring-1 ring-emerald-700/20 transition hover:from-emerald-800 hover:to-emerald-700 disabled:cursor-not-allowed disabled:opacity-60"
                    >
                        {processing ? (
                            <>
                                <svg className="h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" />
                                    <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8V0C5.373 0 0 5.373 0 12h4Z" />
                                </svg>
                                Mendaftar...
                            </>
                        ) : (
                            <>
                                Buat akun
                                <ArrowIcon />
                            </>
                        )}
                    </button>

                    <div className="flex items-start gap-2.5 rounded-xl border border-amber-200/70 bg-amber-50/70 px-3 py-2.5">
                        <span className="mt-0.5 text-amber-600">
                            <ShieldIcon />
                        </span>
                        <p className="text-[11px] leading-relaxed text-amber-800">
                            Gunakan nomor WhatsApp aktif. Nomor dan email dipakai untuk masuk ke akunmu.
                        </p>
                    </div>
                </form>
            </AuthLayout>
        </>
    );
}
