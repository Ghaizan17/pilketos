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
        <img src="{{ asset('images/logo-smkn2.png') }}" alt="Logo SMKN 2 Banjarmasin" class="mx-auto h-20 w-20 object-cover">
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
            <input
                id="password" name="password" type="password" required autocomplete="current-password"
                class="mt-1 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3.5 text-sm outline-none transition placeholder:text-slate-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200"
            >

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
</div>
</body>
</html>
