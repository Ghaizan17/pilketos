<x-filament-panels::page>
    {{ $this->content }}

    @if ($this->jobId && ($this->job['status'] ?? null) === 'processing')
        <div wire:poll.1s="tickPdf" class="mt-6">
            <x-filament::section>
                <x-slot name="heading">Membuat PDF…</x-slot>

                <div class="h-3 w-full overflow-hidden rounded-full bg-[#041562]/10 dark:bg-white/10">
                    <div
                        class="h-3 rounded-full bg-[#11468F] transition-all duration-500"
                        style="width: {{ $this->jobPercent }}%"
                    ></div>
                </div>

                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                    Menyiapkan {{ $this->job['processed'] ?? 0 }} / {{ $this->job['total'] ?? 0 }} QR
                    ({{ $this->jobPercent }}%) — jangan tutup tab ini.
                </p>
            </x-filament::section>
        </div>
    @endif

    @if ($this->jobId && ($this->job['status'] ?? null) === 'done')
        <div class="mt-6">
            <x-filament::section>
                <x-slot name="heading">PDF siap</x-slot>

                <div class="rounded-lg bg-success-50 p-4 text-sm text-success-700 dark:bg-success-500/10 dark:text-success-300">
                    <p class="font-semibold">
                        {{ $this->job['label'] ?? '' }}: {{ $this->job['total'] ?? 0 }} kartu
                        ({{ (int) ceil(($this->job['total'] ?? 0) / 20) }} halaman A4).
                    </p>
                </div>

                <div class="mt-4 flex flex-wrap justify-end gap-2">
                    <x-filament::button color="gray" wire:click="resetJob">
                        Buat Ulang
                    </x-filament::button>
                    <x-filament::button tag="a" :href="$this->downloadUrl" icon="heroicon-o-arrow-down-tray">
                        Unduh PDF
                    </x-filament::button>
                </div>
            </x-filament::section>
        </div>
    @endif

    @if ($this->jobId && ($this->job['status'] ?? null) === 'error')
        <div class="mt-6">
            <x-filament::section>
                <x-slot name="heading">Gagal membuat PDF</x-slot>

                <div class="rounded-lg bg-danger-50 p-4 text-sm text-danger-700 dark:bg-danger-500/10 dark:text-danger-300">
                    {{ $this->job['message'] ?? 'Terjadi kesalahan.' }}
                </div>

                <div class="mt-4 flex justify-end gap-2">
                    <x-filament::button color="gray" wire:click="resetJob">
                        Coba Lagi
                    </x-filament::button>
                </div>
            </x-filament::section>
        </div>
    @endif

    <div class="mt-6">
        <x-filament::section collapsible collapsed>
            <x-slot name="heading">Tentang kartu cetak</x-slot>

            <p class="text-sm text-gray-600 dark:text-gray-400">
                Kartu kecil berisi QR payload UUID murid, nama, dan kelas/jurusan murid.
                20 kartu per halaman A4 portrait (4 kolom × 5 baris). Payload QR sama dengan
                kartu satuan, jadi hasil cetak bisa langsung dipakai di bilik suara.
            </p>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                PDF besar dibuat bertahap dengan progress di atas — jangan tutup tab selama
                proses berjalan. QR di-cache, pembuatan berikutnya jauh lebih cepat.
                Pertimbangkan mencetak per kelas.
            </p>
        </x-filament::section>
    </div>
</x-filament-panels::page>
