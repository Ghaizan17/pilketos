<x-filament-panels::page>
    {{ $this->content }}

    <div class="mt-4">
        <x-filament::section>
            <x-slot name="heading">Status saat ini</x-slot>

            <p class="text-sm">
                @if (\App\Models\ElectionSetting::isOpen())
                    <span class="font-semibold text-success-600">DIBUKA</span>
                    — bilik suara menerima suara.
                @else
                    <span class="font-semibold text-danger-600">DITUTUP</span>
                    — hasil quick-count dibekukan sebagai hasil final.
                @endif
            </p>

            @if (! \App\Models\ElectionSetting::isOpen() && \App\Models\Vote::count() === 0)
                <p class="mt-2 text-sm text-gray-500">
                    Tombol "Reset Suara" aktif saat pemilihan ditutup.
                </p>
            @endif
        </x-filament::section>
    </div>
</x-filament-panels::page>
