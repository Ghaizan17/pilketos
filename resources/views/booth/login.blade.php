<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login Bilik — Pilketos SMKN 2 Banjarmasin</title>
    <link rel="icon" href="{{ asset('images/logo-smkn2.png') }}">
    @vite(['resources/css/app.css'])
</head>
<body class="relative min-h-screen overflow-x-hidden bg-slate-100 font-sans text-slate-900 antialiased">
<div aria-hidden="true" class="pointer-events-none absolute -top-32 -left-32 h-96 w-96 rounded-full bg-indigo-300/40 blur-3xl booth-blob"></div>
<div aria-hidden="true" class="pointer-events-none absolute top-40 -right-32 h-96 w-96 rounded-full bg-cyan-300/40 blur-3xl booth-blob" style="animation-delay:-3.5s"></div>
<div class="relative mx-auto flex min-h-screen w-full max-w-md flex-col items-center justify-center px-4 py-10 sm:max-w-lg sm:py-14">
    <div class="booth-fade-up w-full rounded-[2rem] border border-white/60 bg-white/80 p-8 text-center shadow-xl shadow-indigo-600/10 backdrop-blur sm:p-10" style="animation-delay:.08s">
        <img src="{{ asset('images/logo-smkn2-192.webp') }}" width="192" height="192" fetchpriority="high" alt="Logo SMKN 2 Banjarmasin" class="mx-auto h-20 w-20 object-cover">
        <p class="mt-4 text-xs font-bold uppercase tracking-[0.3em] text-indigo-600">SMKN 2 Banjarmasin</p>
        <h1 class="mt-2 bg-gradient-to-r from-indigo-600 to-cyan-500 bg-clip-text text-3xl font-black tracking-tight text-transparent sm:text-4xl">Login Bilik Suara</h1>
        <p class="mt-2 text-sm text-slate-500">Masuk sebagai operator bilik untuk membuka scanner.</p>

        <form method="POST" action="{{ route('booth.login.attempt') }}" class="mt-6 text-left">
            @csrf

            @if ($errors->any())
                <p class="mb-4 rounded-xl bg-red-50 px-3 py-2 text-sm font-medium text-red-600 ring-1 ring-red-200">{{ $errors->first() }}</p>
            @endif

            <label for="username" class="text-sm font-semibold">Username</label>
            <input
                id="username" name="username" type="text" required autofocus autocomplete="username"
                value="{{ old('username') }}" placeholder="Masukkan Username Anda"
                class="mt-1 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3.5 text-sm outline-none transition placeholder:text-slate-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200"
            >

            <label for="password" class="mt-4 block text-sm font-semibold">Password</label>
            <div class="relative mt-1">
                <input
                    id="password" name="password" type="password" required autocomplete="current-password"
                    class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3.5 pr-12 text-sm outline-none transition placeholder:text-slate-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200"
                >
                <button type="button" id="toggle-password" aria-label="Tampilkan password" aria-pressed="false" class="absolute inset-y-0 right-0 flex items-center pr-4 text-slate-400 transition hover:text-indigo-600">
                    <svg id="icon-eye" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg>
                    <svg id="icon-eye-off" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="hidden h-5 w-5"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" /></svg>
                </button>
            </div>

            <label class="mt-4 flex cursor-pointer items-center gap-2 text-sm text-slate-600">
                <input type="checkbox" name="remember" value="1" class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                Ingat saya
            </label>

            <button type="submit" class="mt-5 w-full rounded-2xl bg-gradient-to-r from-indigo-600 to-cyan-500 px-6 py-4 text-sm font-bold text-white shadow-lg shadow-indigo-600/30 transition hover:scale-[1.01] active:scale-[0.99]">
                Masuk
            </button>
            <p class="mt-3 text-center text-xs text-slate-400">Gunakan akun operator yang diberikan panitia.</p>
        </form>
    </div>

    <a href="{{ route('home') }}" class="mt-6 text-xs font-semibold text-slate-500 hover:text-indigo-600">← Kembali ke beranda</a>
<script>
    document.getElementById('toggle-password').addEventListener('click', function () {
        var input = document.getElementById('password');
        var show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        document.getElementById('icon-eye').classList.toggle('hidden', show);
        document.getElementById('icon-eye-off').classList.toggle('hidden', !show);
        this.setAttribute('aria-label', show ? 'Sembunyikan password' : 'Tampilkan password');
        this.setAttribute('aria-pressed', String(show));
    });
</script>
</body>
</html>
