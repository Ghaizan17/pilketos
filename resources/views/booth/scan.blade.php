<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Bilik Suara — Pilketos SMKN 2 Banjarmasin</title>
    <link rel="icon" href="{{ asset('images/logo-smkn2.png') }}">
    @vite(['resources/css/app.css', 'resources/js/booth.js'])
</head>
<body class="min-h-screen bg-slate-100 font-sans text-slate-900 antialiased">
<div
    x-data="booth({ closed: {{ $isOpen ? 'false' : 'true' }}, candidates: {{ Js::from($candidatesPayload) }} })"
    class="mx-auto flex min-h-screen w-full max-w-2xl flex-col items-center px-4 py-8"
>
    <!-- <div class="flex w-full items-center justify-between">
        <a href="{{ route('home') }}" class="text-xs font-semibold text-slate-500 hover:text-indigo-600">← Beranda</a>
        <form method="POST" action="{{ route('booth.logout') }}">
            @csrf
            <button type="submit" class="rounded-lg bg-white px-3 py-1.5 text-xs font-semibold text-slate-600 ring-1 ring-slate-200 transition hover:bg-slate-50">
                Keluar ({{ auth()->user()?->username }})
            </button>
        </form>
    </div> -->
    {{-- Brand --}}
    <header class="booth-fade-up flex flex-col items-center text-center">
        <img src="{{ asset('images/logo-smkn2.png') }}" alt="Logo SMKN 2 Banjarmasin" class="h-20 w-20 object-cover">
        <p class="mt-3 text-xs font-bold uppercase tracking-[0.3em] text-indigo-600">SMKN 2 Banjarmasin</p>
        <h1 class="mt-1 text-3xl font-black tracking-tight text-slate-900">PILKETOS</h1>
        <p class="mt-1 text-sm text-slate-500">Pemilihan Ketua &amp; Wakil Ketua OSIS</p>
    </header>

    @if (! $isOpen)
        <div class="booth-fade-up mt-8 w-full rounded-2xl border border-red-200 bg-white px-6 py-10 text-center shadow-sm" role="alert">
            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-red-50">
                <svg class="h-7 w-7 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z"/></svg>
            </div>
            <p class="mt-3 text-2xl font-extrabold text-slate-900">Pemilihan Ditutup</p>
            <p class="mt-2 text-sm text-slate-500">Bilik suara tidak aktif. Hubungi panitia untuk informasi lebih lanjut.</p>
        </div>
    @else
        {{-- Scanner card --}}
        <div class="booth-fade-up mt-8 w-full rounded-3xl border border-slate-200 bg-white p-5 shadow-xl shadow-slate-900/5" x-show="['scanning','resolving','camera_error'].includes(state)">
            {{-- Viewfinder --}}
            <div class="relative aspect-square w-full overflow-hidden rounded-2xl bg-slate-900 ring-1 ring-slate-900/10">
                <video x-ref="video" class="h-full w-full object-cover" playsinline muted></video>

                {{-- dim + frame --}}
                <div class="pointer-events-none absolute inset-0" x-show="cameraOn">
                    <div class="absolute inset-0 bg-black/30" style="clip-path: polygon(0 0, 100% 0, 100% 100%, 0 100%, 0 0, 6% 6%, 6% 94%, 94% 94%, 94% 6%, 6% 6%);"></div>
                    {{-- corner brackets --}}
                    <div class="absolute left-[6%] top-[6%] h-10 w-10 rounded-tl-xl border-l-4 border-t-4 border-indigo-500"></div>
                    <div class="absolute right-[6%] top-[6%] h-10 w-10 rounded-tr-xl border-r-4 border-t-4 border-indigo-500"></div>
                    <div class="absolute bottom-[6%] left-[6%] h-10 w-10 rounded-bl-xl border-b-4 border-l-4 border-indigo-500"></div>
                    <div class="absolute bottom-[6%] right-[6%] h-10 w-10 rounded-br-xl border-b-4 border-r-4 border-indigo-500"></div>
                    {{-- laser --}}
                    <div class="booth-laser absolute left-[8%] right-[8%] h-0.5 rounded-full bg-gradient-to-r from-transparent via-cyan-400 to-transparent shadow-[0_0_18px_3px_rgba(34,211,238,0.8)]" x-show="state === 'scanning'"></div>
                    {{-- pulse ring saat resolving --}}
                    <div class="absolute inset-0 flex items-center justify-center" x-show="state === 'resolving'">
                        <div class="booth-pulse-ring absolute h-28 w-28 rounded-full border-2 border-cyan-300"></div>
                        <div class="rounded-full bg-black/60 px-4 py-2 text-sm font-semibold text-cyan-200 backdrop-blur">Memeriksa QR…</div>
                    </div>
                </div>

                {{-- preparing overlay --}}
                <div class="absolute inset-0 flex flex-col items-center justify-center gap-3 bg-slate-900" x-show="!cameraOn && state !== 'camera_error' && state !== 'resolving'">
                    <svg class="h-10 w-10 animate-spin text-indigo-400" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-90" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4z"/></svg>
                    <p class="text-sm font-medium text-slate-300">Menyiapkan kamera…</p>
                </div>

                {{-- camera error --}}
                <div class="absolute inset-0 flex flex-col items-center justify-center gap-3 bg-slate-900 px-6 text-center" x-show="state === 'camera_error'">
                    <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-red-500/15 ring-1 ring-red-400/30">
                        <svg class="h-7 w-7 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3 7V5a2 2 0 0 1 2-2h2m14 0h2a2 2 0 0 1 2 2v2m0 10v2a2 2 0 0 1-2 2h-2M3 17v2a2 2 0 0 0 2 2h2M7 12h10"/></svg>
                    </div>
                    <p class="text-sm text-slate-300" x-text="cameraError"></p>
                    <button type="button" @click="startCamera()" class="rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-bold text-white shadow-lg shadow-indigo-600/30 transition hover:bg-indigo-500 active:scale-95">Coba Lagi</button>
                </div>
            </div>

            {{-- Controls --}}
            <div class="mt-4 flex flex-wrap items-center gap-2">
                <select
                    x-model="selectedDeviceId"
                    @change="cameraOn && restartCamera()"
                    x-show="devices.length > 1"
                    class="rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-700"
                    aria-label="Pilih kamera"
                >
                    <template x-for="d in devices" :key="d.deviceId">
                        <option :value="d.deviceId" x-text="d.label || ('Kamera ' + (devices.indexOf(d) + 1))"></option>
                    </template>
                </select>
                <span class="ml-auto flex items-center gap-1.5 text-xs font-medium text-slate-500" x-show="cameraOn && state === 'scanning'">
                    <span class="relative flex h-2 w-2"><span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-500 opacity-75"></span><span class="relative inline-flex h-2 w-2 rounded-full bg-emerald-500"></span></span>
                    Arahkan QR ke bingkai
                </span>
            </div>

            <p class="mt-3 rounded-xl bg-red-50 px-3 py-2 text-sm text-red-600 ring-1 ring-red-200" x-show="cameraError && state !== 'camera_error'" x-text="cameraError"></p>
            <p class="mt-3 rounded-xl bg-amber-50 px-3 py-2 text-sm text-amber-700 ring-1 ring-amber-200" x-show="notice" x-text="notice"></p>
        </div>
    @endif

    {{-- Modal 1: Pilih Paslon --}}
    <div
        x-show="state === 'selecting' || state === 'confirming'"
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4 backdrop-blur-sm"
        role="dialog" aria-modal="true" aria-label="Pilih Paslon"
    >
        <div
            x-show="state === 'selecting' || state === 'confirming'"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95 translate-y-4"
            x-transition:enter-end="opacity-100 scale-100 translate-y-0"
            class="max-h-[92vh] w-full max-w-4xl overflow-y-auto rounded-3xl border border-slate-200 bg-white p-6 shadow-2xl sm:p-8"
        >
            <div class="flex items-center gap-4">
                <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl ring-1"
                    :class="(student?.jk ?? 'L') === 'P' ? 'bg-pink-50 ring-pink-200' : 'bg-blue-50 ring-blue-200'">
                    <iconify-icon icon="mdi:account" width="40" height="40"
                        :style="{ color: (student?.jk ?? 'L') === 'P' ? '#ec4899' : '#2563eb' }"></iconify-icon>
                </div>
                <div>
                    <p class="text-xs uppercase tracking-widest text-slate-400">Pemilih</p>
                    <h2 class="text-xl font-bold text-slate-900 sm:text-2xl" x-text="student?.name"></h2>
                    <p class="text-sm text-slate-500" x-text="student?.classroom ?? ''"></p>
                </div>
            </div>

            <p class="mt-5 text-sm font-semibold text-slate-700">Pilih salah satu paslon:</p>
            <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-3">
                <template x-for="c in candidates" :key="c.id">
                    <button
                        type="button"
                        @click="selectedId = c.id"
                        :class="selectedId === c.id
                            ? 'border-indigo-500 bg-indigo-50 shadow-lg shadow-indigo-500/15'
                            : 'border-slate-200 bg-white hover:border-indigo-300 hover:bg-indigo-50/50'"
                        class="overflow-hidden rounded-2xl border-2 text-left transition active:scale-[0.98]"
                    >
                        <div class="relative">
                            <template x-if="c.photo">
                                <img :src="c.photo" :alt="'Paslon No. ' + c.number" class="aspect-[4/5] w-full object-cover">
                            </template>
                            <template x-if="!c.photo">
                                <div class="flex aspect-[4/5] w-full items-center justify-center bg-slate-100">
                                    <span class="text-6xl font-black text-slate-300" x-text="c.number"></span>
                                </div>
                            </template>
                            <span class="absolute left-2 top-2 rounded-full bg-slate-900/70 px-2.5 py-1 text-xs font-black text-white backdrop-blur" x-text="'No. ' + c.number"></span>
                            <span class="absolute right-2 top-2 flex h-6 w-6 items-center justify-center rounded-full bg-indigo-600 text-white" x-show="selectedId === c.id">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            </span>
                        </div>
                        <div class="p-3">
                            <p class="text-sm font-semibold text-slate-900" x-text="c.ketua + ' & ' + c.wakil"></p>
                            <p class="mt-0.5 text-xs italic text-slate-500" x-show="c.tagline" x-text="c.tagline"></p>
                        </div>
                    </button>
                </template>
            </div>

            <p class="mt-3 rounded-xl bg-amber-50 px-3 py-2 text-sm text-amber-700 ring-1 ring-amber-200" x-show="notice" x-text="notice"></p>

            <div class="mt-5 flex gap-2">
                <button
                    type="button"
                    @click="resetToScanning()"
                    class="flex-1 rounded-xl bg-slate-100 px-4 py-3 text-sm font-semibold text-slate-700 ring-1 ring-slate-200 transition hover:bg-slate-200 active:scale-[0.98]"
                >Batal</button>
                <button
                    type="button"
                    @click="askConfirm()"
                    :disabled="!selectedId || confirming"
                    class="flex-[2] rounded-xl bg-gradient-to-r from-indigo-600 to-cyan-500 px-6 py-3 text-sm font-bold text-white shadow-lg shadow-indigo-600/30 transition hover:scale-[1.02] active:scale-[0.98] disabled:cursor-not-allowed disabled:opacity-40 disabled:hover:scale-100"
                ><span x-text="confirming ? 'Mencatat…' : 'Pilih Paslon Ini'"></span></button>
            </div>
        </div>
    </div>

    {{-- Modal 2: Popup konfirmasi --}}
    <div
        x-show="state === 'confirm_popup'"
        x-cloak
        class="fixed inset-0 z-[60] flex items-center justify-center bg-slate-900/60 p-4 backdrop-blur-sm"
        role="alertdialog" aria-modal="true" aria-label="Konfirmasi pilihan"
    >
        <div
            x-show="state === 'confirm_popup'"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-90 translate-y-4"
            x-transition:enter-end="opacity-100 scale-100 translate-y-0"
            class="booth-fade-up w-full max-w-sm rounded-3xl border border-slate-200 bg-white p-6 text-center shadow-2xl"
        >
            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-indigo-50 ring-1 ring-indigo-200">
                <svg class="h-7 w-7 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
            </div>
            <h2 class="mt-3 text-lg font-extrabold text-slate-900">Kunci pilihan?</h2>
            <p class="mt-1 text-sm text-slate-500">Suara untuk <span class="font-bold text-slate-900" x-text="'Paslon No. ' + candidateNumber(selectedId)"></span> tidak bisa diubah.</p>
            <div class="mt-5 flex gap-2">
                <button
                    type="button"
                    @click="state = 'selecting'"
                    class="flex-1 rounded-xl bg-slate-100 px-4 py-3 text-sm font-semibold text-slate-700 ring-1 ring-slate-200 transition hover:bg-slate-200 active:scale-[0.98]"
                >Ubah</button>
                <button
                    type="button"
                    @click="confirmVote()"
                    :disabled="confirming"
                    class="flex-[2] rounded-xl bg-gradient-to-r from-emerald-500 to-teal-500 px-4 py-3 text-sm font-bold text-white shadow-lg shadow-emerald-500/30 transition hover:scale-[1.02] active:scale-[0.98] disabled:opacity-50"
                ><span x-text="confirming ? 'Mencatat…' : 'Ya, Konfirmasi'"></span></button>
            </div>
        </div>
    </div>

    {{-- Toast sukses kanan --}}
    <div class="pointer-events-none fixed right-4 top-4 z-[70] flex w-80 flex-col gap-2" aria-live="polite">
        <template x-for="t in toasts" :key="t.id">
            <div class="booth-toast pointer-events-auto overflow-hidden rounded-2xl border border-emerald-200 bg-white shadow-2xl">
                <div class="flex items-start gap-3 p-4">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-emerald-100">
                        <svg class="h-5 w-5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    </div>
                    <div class="min-w-0">
                        <p class="text-sm font-bold text-slate-900">Suara tercatat!</p>
                        <p class="truncate text-xs text-slate-500" x-text="t.message"></p>
                    </div>
                    <button type="button" @click="dismissToast(t.id)" class="ml-auto shrink-0 rounded-lg p-1 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="booth-toast-progress h-1 bg-emerald-500/70"></div>
            </div>
        </template>
    </div>

    {{-- Error popups --}}
    <div
        x-show="state === 'unknown_qr'"
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4 backdrop-blur-sm"
        role="alertdialog" aria-modal="true" aria-label="QR tidak dikenal"
    >
        <div x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" class="w-full max-w-sm rounded-3xl border border-slate-200 bg-white p-6 text-center shadow-2xl">
            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-red-50 ring-1 ring-red-200">
                <svg class="h-7 w-7 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z"/></svg>
            </div>
            <h2 class="mt-3 text-xl font-extrabold text-slate-900">QR Tidak Dikenal</h2>
            <p class="mt-1 text-sm text-slate-500">QR tidak terdaftar, hubungi panitia.</p>
            <button type="button" @click="resetToScanning()" class="mt-5 w-full rounded-xl bg-slate-900 px-4 py-3 text-sm font-bold text-white transition hover:bg-slate-700 active:scale-[0.98]">Kembali Scan</button>
        </div>
    </div>

    <div
        x-show="state === 'already_voted'"
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4 backdrop-blur-sm"
        role="alertdialog" aria-modal="true" aria-label="Sudah memilih"
    >
        <div x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" class="w-full max-w-sm rounded-3xl border border-slate-200 bg-white p-6 text-center shadow-2xl">
            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-amber-50 ring-1 ring-amber-200">
                <svg class="h-7 w-7 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
            </div>
            <h2 class="mt-3 text-xl font-extrabold text-slate-900">Sudah Memilih</h2>
            <p class="mt-1 text-sm text-slate-500">NIS ini sudah memilih.</p>
            <button type="button" @click="resetToScanning()" class="mt-5 w-full rounded-xl bg-slate-900 px-4 py-3 text-sm font-bold text-white transition hover:bg-slate-700 active:scale-[0.98]">Kembali Scan</button>
        </div>
    </div>
</div>
</body>
</html>
