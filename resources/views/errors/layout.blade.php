<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('code', 'Error') — Pilketos SMKN 2 Banjarmasin</title>
    <link rel="icon" href="{{ asset('images/logo-smkn2.png') }}">
    @vite(['resources/css/app.css'])
</head>
<body class="relative min-h-screen overflow-x-hidden bg-slate-100 font-sans text-slate-900 antialiased">
<div aria-hidden="true" class="pointer-events-none absolute -top-32 -left-32 h-96 w-96 rounded-full bg-indigo-300/40 blur-3xl booth-blob"></div>
<div aria-hidden="true" class="pointer-events-none absolute top-40 -right-32 h-96 w-96 rounded-full bg-cyan-300/40 blur-3xl booth-blob" style="animation-delay:-3.5s"></div>
<div class="relative mx-auto flex min-h-screen w-full max-w-md flex-col items-center justify-center px-4 py-10 sm:py-14">
    <div class="booth-fade-up w-full rounded-[2rem] border border-white/60 bg-white/80 p-8 text-center shadow-xl shadow-indigo-600/10 backdrop-blur sm:p-10" style="animation-delay:.08s">
        <img src="{{ asset('images/logo-smkn2-192.webp') }}" width="192" height="192" alt="Logo SMKN 2 Banjarmasin" class="mx-auto h-16 w-16 object-cover">
        <p class="mt-4 text-xs font-bold uppercase tracking-[0.3em] text-indigo-600">Pilketos · SMKN 2 Banjarmasin</p>
        <h1 class="mt-2 bg-gradient-to-r from-indigo-600 to-cyan-500 bg-clip-text text-6xl font-black tracking-tight text-transparent">@yield('code', '500')</h1>
        <h2 class="mt-2 text-xl font-extrabold text-slate-900">@yield('title', 'Terjadi kesalahan')</h2>
        <p class="mt-2 text-sm text-slate-500">@yield('message', 'Maaf, terjadi kesalahan. Silakan coba lagi.')</p>
        <div class="mt-6 flex flex-col gap-2">
            @yield('actions')
            <a href="{{ url('/') }}" class="w-full rounded-2xl bg-gradient-to-r from-indigo-600 to-cyan-500 px-6 py-3.5 text-sm font-bold text-white shadow-lg shadow-indigo-600/30 transition hover:scale-[1.01] active:scale-[0.99]">Kembali ke Beranda</a>
            <button type="button" onclick="history.back()" class="w-full rounded-2xl bg-white px-6 py-3 text-sm font-bold text-indigo-600 ring-1 ring-indigo-200 transition hover:bg-indigo-50 active:scale-[0.98]">Kembali ke Halaman Sebelumnya</button>
        </div>
    </div>
</div>
</body>
</html>
