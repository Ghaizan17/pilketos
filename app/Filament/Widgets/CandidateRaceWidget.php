<?php

namespace App\Filament\Widgets;

use App\Models\Candidate;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Storage;

class CandidateRaceWidget extends Widget
{
    protected string $view = 'filament.widgets.candidate-race';

    protected static ?int $sort = 3;

    protected int | string | array $columnSpan = 'full';

    protected function getViewData(): array
    {
        $candidates = Candidate::query()->orderBy('number')->withCount('votes')->get();
        $max = (int) $candidates->max('votes_count');

        $palette = ['#4f46e5', '#0ea5e9', '#10b981', '#f59e0b', '#8b5cf6', '#64748b'];

        $rows = $candidates->values()->map(function ($candidate, int $i) use ($max, $palette): array {
            $votes = (int) $candidate->votes_count;
            return [
                'number' => $candidate->number,
                'names' => "{$candidate->ketua_name} & {$candidate->wakil_name}",
                'tagline' => $candidate->tagline,
                'votes' => $votes,
                'percent' => $max > 0 ? round(($votes / $max) * 100) : 0,
                'color' => $palette[$i % count($palette)],
                'photo' => $candidate->photo_path ? Storage::url($candidate->photo_path) : null,
            ];
        })->all();

        return ['rows' => $rows];
    }
}
