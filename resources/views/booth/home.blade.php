<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pilketos — SMKN 2 Banjarmasin</title>
    <link rel="icon" href="{{ asset('images/logo-smkn2.png') }}">
    @vite(['resources/css/app.css'])
</head>
<body class="relative min-h-screen overflow-x-hidden bg-slate-100 font-sans text-slate-900 antialiased">
<div aria-hidden="true" class="pointer-events-none absolute -top-32 -left-32 h-96 w-96 rounded-full bg-indigo-300/40 blur-3xl booth-blob"></div>
<div aria-hidden="true" class="pointer-events-none absolute top-40 -right-32 h-96 w-96 rounded-full bg-cyan-300/40 blur-3xl booth-blob" style="animation-delay:-3.5s"></div>
<div class="relative mx-auto flex min-h-screen w-full max-w-5xl flex-col items-center px-4 py-10 sm:py-14">
    {{-- Hero --}}
    <header class="booth-fade-up w-full rounded-[2rem] border border-white/60 bg-white/80 p-8 text-center shadow-xl shadow-indigo-600/10 backdrop-blur sm:p-12" style="animation-delay:.08s">
        <img src="{{ asset('images/logo-smkn2.png') }}" alt="Logo SMKN 2 Banjarmasin" class="mx-auto h-20 w-20 object-cover">
        <p class="mt-4 text-xs font-bold uppercase tracking-[0.3em] text-indigo-600">SMKN 2 Banjarmasin</p>
        <h1 class="mt-2 bg-gradient-to-r from-indigo-600 to-cyan-500 bg-clip-text text-5xl font-black tracking-tight text-transparent sm:text-6xl">PILKETOS</h1>
        <p class="mt-2 text-sm text-slate-500">Pemilihan Ketua &amp; Wakil Ketua OSIS</p>
        @if (! $isOpen)
            <p class="mt-4 inline-block rounded-full bg-red-50 px-4 py-1.5 text-xs font-bold text-red-600 ring-1 ring-red-200">Pemilihan sedang ditutup</p>
        @else
            <p class="mt-4 inline-block rounded-full bg-emerald-50 px-4 py-1.5 text-xs font-bold text-emerald-600 ring-1 ring-emerald-200"><span class="mr-1.5 inline-block h-2 w-2 animate-pulse rounded-full bg-emerald-500"></span>Pemilihan sedang dibuka</p>
        @endif

        {{-- Mini-stat --}}
        <div class="mx-auto mt-6 grid max-w-lg grid-cols-3 gap-2 text-center">
            <div class="rounded-2xl bg-slate-50 px-2 py-3 ring-1 ring-slate-200/70">
                <p class="text-xl font-black text-slate-900">{{ count($candidates) }}</p>
                <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Paslon</p>
            </div>
            <div class="rounded-2xl bg-slate-50 px-2 py-3 ring-1 ring-slate-200/70">
                <p class="text-xl font-black text-slate-900">1</p>
                <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">QR = 1 Suara</p>
            </div>
            <div class="rounded-2xl bg-slate-50 px-2 py-3 ring-1 ring-slate-200/70">
                <p class="text-xl font-black text-slate-900">{{ $isOpen ? 'Buka' : 'Tutup' }}</p>
                <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Status</p>
            </div>
        </div>

        {{-- Aksi --}}
        <div class="mt-6 flex w-full flex-col items-center gap-3">
            @auth
                <a href="{{ route('booth.scan') }}" class="w-full max-w-sm rounded-2xl bg-gradient-to-r from-indigo-600 to-cyan-500 px-6 py-4 text-center text-sm font-bold text-white shadow-lg shadow-indigo-600/30 transition hover:scale-[1.02] active:scale-[0.98]">
                    Masuk Bilik Suara
                </a>
            @else
                <a href="{{ route('login') }}" class="w-full max-w-sm rounded-2xl bg-gradient-to-r from-indigo-600 to-cyan-500 px-6 py-4 text-center text-sm font-bold text-white shadow-lg shadow-indigo-600/30 transition hover:scale-[1.02] active:scale-[0.98]">
                    Login Operator Bilik
                </a>
                <p class="text-xs text-slate-400">Hanya operator bilik yang dapat membuka halaman scan.</p>
                <a href="#paslon" class="w-full max-w-sm rounded-2xl bg-white px-6 py-3.5 text-center text-sm font-bold text-indigo-600 ring-1 ring-indigo-200 transition hover:bg-indigo-50 active:scale-[0.98]">
                    Lihat Paslon
                </a>
            @endauth
        </div>
    </header>

    {{-- Pasangan calon --}}
    <main id="paslon" class="mt-12 w-full scroll-mt-6" style="animation-delay:.16s">
        <h2 class="text-center text-sm font-bold uppercase tracking-widest text-slate-400">Pasangan Calon</h2>
        <p class="mt-1 text-center text-sm text-slate-500">Kenali calon ketua &amp; wakil ketua OSIS periode ini.</p>
        <div class="mt-5 grid w-full grid-cols-1 gap-5 sm:grid-cols-2 {{ count($candidates) === 2 ? 'mx-auto max-w-3xl' : 'lg:grid-cols-3' }}">
            @foreach ($candidates as $candidate)
                <article class="booth-fade-up group mx-auto w-full max-w-sm overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm transition duration-300 hover:-translate-y-1 hover:shadow-xl hover:shadow-indigo-600/10">
                    <div class="relative">
                        @if ($candidate->photo_path)
                            <img src="{{ Storage::url($candidate->photo_path) }}" alt="Paslon No. {{ $candidate->number }}" class="aspect-[4/5] w-full object-cover">
                        @else
                            <div class="flex aspect-[4/5] w-full items-center justify-center bg-gradient-to-br from-indigo-100 via-indigo-50 to-cyan-100">
                                <span class="text-7xl font-black text-indigo-300">{{ $candidate->number }}</span>
                            </div>
                        @endif
                        <span class="absolute left-4 top-4 rounded-full bg-indigo-600 px-2.5 py-1 text-xs font-black text-white shadow-lg">No. {{ $candidate->number }}</span>
                    </div>
                    <div class="p-5">
                        <p class="text-sm font-bold">{{ $candidate->ketua_name }} &amp; {{ $candidate->wakil_name }}</p>
                        @if ($candidate->tagline)
                            <p class="mt-0.5 text-xs italic text-slate-500">{{ $candidate->tagline }}</p>
                        @endif
                    </div>
                </article>
            @endforeach
        </div>
    </main>

    {{-- Cara memilih --}}
    <section class="booth-fade-up mt-12 w-full rounded-[2rem] bg-slate-900 p-8 text-white shadow-xl sm:p-10" style="animation-delay:.24s">
        <h2 class="text-center text-sm font-bold uppercase tracking-widest text-slate-400">Cara Memilih</h2>
        <div class="mt-6 grid gap-4 text-left sm:grid-cols-3">
            <div class="rounded-2xl bg-white/5 p-5 ring-1 ring-white/10">
                <span class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-gradient-to-r from-indigo-600 to-cyan-500 text-sm font-black text-white">1</span>
                <p class="mt-3 text-sm font-bold">Ambil Kartu QR</p>
                <p class="mt-1 text-xs leading-relaxed text-slate-300">Setiap pemilih menerima kartu QR pribadi dari panitia.</p>
            </div>
            <div class="rounded-2xl bg-white/5 p-5 ring-1 ring-white/10">
                <span class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-gradient-to-r from-indigo-600 to-cyan-500 text-sm font-black text-white">2</span>
                <p class="mt-3 text-sm font-bold">Scan di Bilik Suara</p>
                <p class="mt-1 text-xs leading-relaxed text-slate-300">Operator memindai QR milikmu untuk membuka halaman pilihan.</p>
            </div>
            <div class="rounded-2xl bg-white/5 p-5 ring-1 ring-white/10">
                <span class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-gradient-to-r from-indigo-600 to-cyan-500 text-sm font-black text-white">3</span>
                <p class="mt-3 text-sm font-bold">Pilih &amp; Konfirmasi</p>
                <p class="mt-1 text-xs leading-relaxed text-slate-300">Pilih satu paslon lalu kunci pilihanmu. Satu QR hanya untuk satu suara.</p>
            </div>
        </div>
    </section>

    <footer class="mt-12 pb-4 text-center text-xs text-slate-400">
        <p>Satu QR hanya berlaku untuk satu suara • Pilihan Anda bersifat rahasia</p>
        <p class="mt-1">SMKN 2 Banjarmasin</p>
    </footer>
</div>
</body>
</html>
