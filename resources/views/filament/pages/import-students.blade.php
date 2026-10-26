<x-filament-panels::page>
    @php
        $status = $this->progress['status'] ?? null;
        $processing = $status === 'processing';
        $done = $status === 'done';
        $error = $status === 'error';
    @endphp

    <div class="space-y-6">
        {{-- Langkah 1: upload (progress upload file bawaan FileUpload) --}}
        @if (! $this->runId)
            <x-filament::section>
                <x-slot name="heading">
                    <span class="mr-2 inline-flex h-6 w-6 items-center justify-center rounded-full bg-[#041562] text-xs font-bold text-white">1</span>Pilih file
                </x-slot>
                {{ $this->form }}


                <div class="mt-4 flex justify-end">
                    <x-filament::button wire:click="startImport">
                        Mulai Import
                    </x-filament::button>
                </div>
            </x-filament::section>
        @endif

        {{-- Langkah 2: progress pemrosesan (poll tiap detik selama processing) --}}
        @if ($this->runId)
            <div @if ($processing) wire:poll.1s="tick" @endif>
                <x-filament::section>
                    <x-slot name="heading">
                        <span class="mr-2 inline-flex h-6 w-6 items-center justify-center rounded-full bg-[#041562] text-xs font-bold text-white">2</span>Memproses…
                    </x-slot>

                    <div class="h-3 w-full overflow-hidden rounded-full bg-[#041562]/10 dark:bg-white/10">
                        <div
                            class="h-3 rounded-full bg-[#11468F] transition-all duration-500"
                            style="width: {{ $this->progressPercent }}%"
                        ></div>
                    </div>

                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                        {{ $this->progress['processed'] ?? 0 }} / {{ $this->progress['total'] ?? 0 }} baris
                        ({{ $this->progressPercent }}%)
                        — {{ $this->progress['imported'] ?? 0 }} imported,
                        {{ $this->progress['skipped'] ?? 0 }} skipped
                    </p>

                    @if ($done)
                        <div class="mt-4 rounded-lg bg-success-50 p-4 text-sm text-success-700 dark:bg-success-500/10 dark:text-success-300">
                            <p class="font-semibold">
                                Import selesai: {{ $this->progress['imported'] }} imported,
                                {{ $this->progress['skipped'] }} skipped.
                            </p>
                            @if (! empty($this->progress['skips']))
                                <p class="mt-2 font-semibold">Contoh baris yang di-skip:</p>
                                <ul class="mt-1 list-disc pl-5">
                                    @foreach ($this->progress['skips'] as $skip)
                                        <li>Baris {{ $skip['baris'] }}: {{ $skip['alasan'] }}</li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>

                        <div class="mt-4 flex justify-end gap-2">
                            <x-filament::button color="gray" wire:click="resetImport">
                                Import File Lain
                            </x-filament::button>
                        </div>
                    @endif

                    @if ($error)
                        <div class="mt-4 rounded-lg bg-danger-50 p-4 text-sm text-danger-700 dark:bg-danger-500/10 dark:text-danger-300">
                            {{ $this->progress['message'] ?? 'Terjadi kesalahan.' }}
                        </div>

                        <div class="mt-4 flex justify-end gap-2">
                            <x-filament::button color="gray" wire:click="resetImport">
                                Coba Lagi
                            </x-filament::button>
                        </div>
                    @endif
                </x-filament::section>
            </div>
        @endif

        <x-filament::section collapsible collapsed>
            <x-slot name="heading">Format file yang didukung</x-slot>

            <div class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
                <p>
                    <span class="font-semibold">Dapodik (utama):</span>
                    file "Daftar Peserta Didik" hasil unduh Dapodik dipakai
                    langsung tanpa diubah. Header ada di baris 5
                    (No | Nama | NIPD | JK | NISN | … | Rombel Saat Ini),
                    data dari baris 7. Hanya kolom Nama, NIPD, JK, NISN,
                    dan Rombel Saat Ini yang dipakai; kolom lain
                    (NIK, alamat, data orang tua, dst.) diabaikan.
                </p>
                <p>
                    <span class="font-semibold">Template:</span>
                    header baris 1: <code>nama | nipd | nisn | jk | kelas</code>,
                    data dari baris 2.
                </p>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
