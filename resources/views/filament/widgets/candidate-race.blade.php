<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">Perolehan Suara</x-slot>
        <x-slot name="description">Batang sebanding dengan paslon teratas — foto memudahkan banding sekilas.</x-slot>

        <div wire:poll.5s class="flex flex-col gap-4">
            @forelse ($rows as $row)
                <div class="flex items-center gap-4">
                    @if ($row['photo'])
                        <img src="{{ $row['photo'] }}" alt="Paslon No. {{ $row['number'] }}" width="96" height="112" loading="lazy"
                            class="h-28 w-24 shrink-0 rounded-xl object-cover object-top ring-1 ring-slate-200">
                    @else
                        <div class="flex h-28 w-24 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-3xl font-black text-slate-300 ring-1 ring-slate-200">
                            {{ $row['number'] }}
                        </div>
                    @endif

                    <div class="min-w-0 flex-1">
                        <div class="flex items-baseline justify-between gap-2">
                            <p class="truncate text-sm font-bold text-slate-900 dark:text-white">
                                <span class="mr-1.5 rounded-full px-2 py-0.5 text-xs font-black text-white" style="background-color: {{ $row['color'] }}">No. {{ $row['number'] }}</span>{{ $row['names'] }}
                            </p>
                            <p class="shrink-0 text-sm font-black text-slate-900 dark:text-white">{{ number_format($row['votes']) }} <span class="text-xs font-medium text-slate-400">suara</span></p>
                        </div>
                        <div class="mt-1.5 h-3 overflow-hidden rounded-full bg-slate-100 dark:bg-white/10">
                            <div class="h-full rounded-full transition-all duration-500" style="width: {{ $row['percent'] }}%; background-color: {{ $row['color'] }}"></div>
                        </div>
                        @if ($row['tagline'])
                            <p class="mt-1 truncate text-xs italic text-slate-400">{{ $row['tagline'] }}</p>
                        @endif
                    </div>
                </div>
            @empty
                <p class="text-sm text-slate-400">Belum ada paslon.</p>
            @endforelse
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
