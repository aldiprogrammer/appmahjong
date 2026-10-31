import { Head, Link, useForm } from '@inertiajs/react';

export default function Login() {
    const { data, setData, post, processing, errors } = useForm({
        username: '',
        password: '',
    });

    const submit = (e) => {
        e.preventDefault();
        post('/admin/login');
    };

    return (
        <>
            <Head title="Login Admin" />
            <div className="relative flex min-h-screen items-center justify-center overflow-hidden bg-gradient-to-br from-emerald-900 via-emerald-800 to-slate-900 px-4">
                <div className="relative w-full max-w-md">
                    <div className="mb-8 text-center">
                        <div className="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-white shadow-lg">
                            <svg viewBox="0 0 64 64" className="h-12 w-12" xmlns="http://www.w3.org/2000/svg">
                                <rect x="6" y="4" width="52" height="56" rx="8" fill="#fffdf6" stroke="#c9c0a6" strokeWidth="2" />
                                <rect x="12" y="10" width="40" height="44" rx="5" fill="none" stroke="#ded5bd" strokeWidth="1.5" />
                                <text x="32" y="42" textAnchor="middle" fontSize="30" fontWeight="700" fill="#c0392b" fontFamily="Georgia, serif">中</text>
                            </svg>
                        </div>
                        <h1 className="text-2xl font-semibold tracking-tight text-white">Mahjong Admin</h1>
                        <p className="mt-1 text-sm text-emerald-200/80">Masuk untuk mengelola panel administrasi</p>
                    </div>

                    <div className="rounded-2xl border border-white/10 bg-white/95 p-8 shadow-2xl backdrop-blur">
                        <form onSubmit={submit} className="space-y-5">
                            <div>
                                <label className="mb-1.5 block text-xs font-medium uppercase tracking-wider text-slate-500">
                                    Username
                                </label>
                                <input
                                    type="text"
                                    value={data.username}
                                    onChange={(e) => setData('username', e.target.value)}
                                    className="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm text-slate-900 transition focus:border-emerald-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20"
                                    autoComplete="username"
                                    placeholder="Masukkan username"
                                />
                                {errors.username && <p className="mt-1.5 text-xs text-rose-600">{errors.username}</p>}
                            </div>

                            <div>
                                <label className="mb-1.5 block text-xs font-medium uppercase tracking-wider text-slate-500">
                                    Password
                                </label>
                                <input
                                    type="password"
                                    value={data.password}
                                    onChange={(e) => setData('password', e.target.value)}
                                    className="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm text-slate-900 transition focus:border-emerald-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20"
                                    autoComplete="current-password"
                                    placeholder="Masukkan password"
                                />
                                {errors.password && <p className="mt-1.5 text-xs text-rose-600">{errors.password}</p>}
                            </div>

                            <button
                                type="submit"
                                disabled={processing}
                                className="w-full rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-emerald-600/20 transition hover:bg-emerald-500 disabled:opacity-60"
                            >
                                {processing ? 'Memproses...' : 'Masuk'}
                            </button>
                        </form>

                        <div className="mt-6 text-center">
                            <Link href="/" className="text-xs text-slate-400 transition hover:text-emerald-600">
                                Kembali ke beranda
                            </Link>
                        </div>
                    </div>

                    <p className="mt-6 text-center text-xs text-emerald-200/60">
                        &copy; {new Date().getFullYear()} Mahjong Admin Panel
                    </p>
                </div>
            </div>
        </>
    );
}
