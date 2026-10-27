<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pilketos — SMKN 2 Banjarmasin</title>
    <link rel="icon" href="{{ asset('images/logo-smkn2.png') }}">
    @vite(['resources/css/app.css'])
<body class="min-h-screen bg-slate-100 font-sans text-slate-900 antialiased">
<div class="mx-auto flex min-h-screen w-full max-w-3xl flex-col items-center px-4 py-10">
    {{-- Brand --}}
    <header class="flex flex-col items-center text-center">
        <img src="{{ asset('images/logo-smkn2.png') }}" alt="Logo SMKN 2 Banjarmasin" class="h-24 w-24 object-cover">
        <p class="mt-4 text-xs font-bold uppercase tracking-[0.3em] text-indigo-600">SMKN 2 Banjarmasin</p>
        <h1 class="mt-1 text-4xl font-black tracking-tight">PILKETOS</h1>
        <p class="mt-1 text-sm text-slate-500">Pemilihan Ketua &amp; Wakil Ketua OSIS</p>
        @if (! $isOpen)
            <p class="mt-4 rounded-full bg-red-50 px-4 py-1.5 text-xs font-bold text-red-600 ring-1 ring-red-200">Pemilihan sedang ditutup</p>
        @else
            <p class="mt-4 rounded-full bg-emerald-50 px-4 py-1.5 text-xs font-bold text-emerald-600 ring-1 ring-emerald-200">Pemilihan sedang dibuka</p>
        @endif
    </header>

    {{-- Kandidat --}}
    <h2 class="mt-10 text-sm font-bold uppercase tracking-widest text-slate-400">Pasangan Calon</h2>
    <div class="mt-4 grid w-full grid-cols-1 gap-4 md:flex md:justify-center">
        @foreach ($candidates as $candidate)
            <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
                @if ($candidate->photo_path)
                    <img src="{{ Storage::url($candidate->photo_path) }}" alt="Paslon No. {{ $candidate->number }}" class="aspect-[4/5] w-full object-cover">
                @else
                    <div class="flex aspect-[4/5] w-full items-center justify-center bg-gradient-to-br from-indigo-50 to-cyan-50">
                        <span class="text-7xl font-black text-indigo-200">{{ $candidate->number }}</span>
                    </div>
                @endif
                <div class="p-4">
                    <span class="inline-block rounded-full bg-indigo-600 px-2.5 py-1 text-xs font-black text-white">No. {{ $candidate->number }}</span>
                    <p class="mt-2 text-sm font-bold">{{ $candidate->ketua_name }} &amp; {{ $candidate->wakil_name }}</p>
                    @if ($candidate->tagline)
                        <p class="mt-0.5 text-xs italic text-slate-500">{{ $candidate->tagline }}</p>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    {{-- Aksi --}}
    <div class="mt-10 flex w-full flex-col items-center gap-3">
        @auth
            <a href="{{ route('booth.scan') }}" class="w-full max-w-sm rounded-2xl bg-gradient-to-r from-indigo-600 to-cyan-500 px-6 py-4 text-center text-sm font-bold text-white shadow-lg shadow-indigo-600/30 transition hover:scale-[1.02] active:scale-[0.98]">
                Masuk Bilik Suara
            </a>
        @else
            <a href="{{ route('login') }}" class="w-full max-w-sm rounded-2xl bg-gradient-to-r from-indigo-600 to-cyan-500 px-6 py-4 text-center text-sm font-bold text-white shadow-lg shadow-indigo-600/30 transition hover:scale-[1.02] active:scale-[0.98]">
                Login Operator Bilik
            </a>
            <p class="text-xs text-slate-400">Hanya operator bilik yang dapat membuka halaman scan.</p>
        @endauth
    </div>

    <footer class="mt-10 text-center text-xs text-slate-400">
        Satu QR hanya berlaku untuk satu suara • Pilihan Anda bersifat rahasia
    </footer>
</div>
</body>
</html>
