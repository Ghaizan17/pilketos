<?php

namespace App\Filament\Widgets;

use App\Models\Classroom;
use Filament\Widgets\ChartWidget;

class TurnoutPerClassroomChart extends ChartWidget
{
    protected ?string $heading = 'Partisipasi per Kelas';

    protected ?string $pollingInterval = '15s';

    protected static ?int $sort = 3;

    protected int | string | array $columnSpan = 'full';

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $classrooms = Classroom::query()
            ->orderBy('name')
            ->withCount(['students', 'students as voted_students_count' => function ($query): void {
                $query->whereHas('vote');
            }])
            ->get();

        $voted = [];
        $notVoted = [];

        foreach ($classrooms as $classroom) {
            $votedCount = (int) $classroom->voted_students_count;
            $voted[] = $votedCount;
            $notVoted[] = max(0, (int) $classroom->students_count - $votedCount);
        }

        return [
            'datasets' => [
                [
                    'label' => 'Sudah Memilih',
                    'data' => $voted,
                    'backgroundColor' => '#4f46e5',
                    'borderRadius' => 4,
                ],
                [
                    'label' => 'Belum Memilih',
                    'data' => $notVoted,
                    'backgroundColor' => '#cbd5e1',
                    'borderRadius' => 4,
                ],
            ],
            'labels' => $classrooms->pluck('name')->all(),
        ];
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => ['position' => 'bottom'],
            ],
            'scales' => [
                'x' => ['stacked' => true],
                'y' => ['stacked' => true, 'beginAtZero' => true],
            ],
        ];
    }
}
