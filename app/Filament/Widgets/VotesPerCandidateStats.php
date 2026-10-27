<?php

namespace App\Filament\Widgets;

use App\Models\Candidate;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class VotesPerCandidateStats extends StatsOverviewWidget
{
    protected ?string $heading = 'Suara Tiap Paslon';

    protected ?string $pollingInterval = '15s';

    protected int | string | array $columnSpan = 1;

    protected int | array | null $columns = 1;

    protected function getStats(): array
    {
        $candidates = Candidate::query()->orderBy('number')->withCount('votes')->get();
        $total = (int) $candidates->sum('votes_count');

        $colors = ['primary', 'success', 'warning', 'info', 'danger', 'secondary'];

        return $candidates->values()->map(function ($candidate, int $i) use ($total, $colors): Stat {
            $votes = (int) $candidate->votes_count;
            $percent = $total > 0 ? round(($votes / $total) * 100, 1) : 0;
            $color = $colors[$i % count($colors)];

            return Stat::make("Paslon No. {$candidate->number}", number_format($votes))
                ->description("{$candidate->ketua_name} & {$candidate->wakil_name} — {$percent}% suara")
                ->descriptionIcon(Heroicon::OutlinedUsers)
                ->descriptionColor($color)
                ->chart([0, $votes])
                ->chartColor($color);
        })->all();
    }
}
