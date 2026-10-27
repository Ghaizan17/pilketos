<?php

namespace App\Filament\Widgets;

use App\Models\Candidate;
use Filament\Widgets\ChartWidget;

class VotesPerCandidateChart extends ChartWidget
{
    protected ?string $heading = 'Suara per Paslon';

    protected ?string $pollingInterval = '15s';

    // Tanpa aspect-ratio: Chart.js responsif penuh mengikuti tinggi wadah grid,
    // agar sejajar dengan tumpukan card di kanan. Tinggi min menjaga pie tetap lega.
    protected ?string $maxHeight = '420px';

    protected function getType(): string
    {
        return 'pie';
    }

    protected function getData(): array
    {
        $candidates = Candidate::query()->orderBy('number')->withCount('votes')->get();

        // Rotasi indigo + aksen modern agar tiap paslon punya warna beda.
        $palette = ['#4f46e5', '#0ea5e9', '#10b981', '#f59e0b', '#8b5cf6', '#64748b'];

        return [
            'datasets' => [
                [
                    'data' => $candidates->map(fn ($c): int => (int) $c->votes_count)->all(),
                    'backgroundColor' => $candidates->keys()->map(fn ($i): string => $palette[$i % count($palette)])->all(),
                    'borderColor' => '#ffffff',
                    'borderWidth' => 2,
                ],
            ],
            'labels' => $candidates
                ->map(fn ($c): string => "No. {$c->number} — {$c->ketua_name} & {$c->wakil_name}")
                ->all(),
        ];
    }

    protected function getOptions(): array
    {
        return [
            'responsive' => true,
            'maintainAspectRatio' => false,
            'layout' => ['padding' => 8],
            'plugins' => [
                'legend' => ['position' => 'bottom'],
            ],
        ];
    }
}
