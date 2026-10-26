<?php

namespace App\Filament\Widgets;

use App\Models\Classroom;
use App\Models\ElectionSetting;
use App\Models\Student;
use App\Models\Vote;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ElectionStatsOverview extends StatsOverviewWidget
{
    protected ?string $pollingInterval = '5s';

    protected function getStats(): array
    {
        $total = Student::query()->count();
        $voted = Vote::query()->count();
        $notVoted = $total - $voted;
        $percent = $total > 0 ? round(($voted / $total) * 100, 1) : 0;
        $stats = [
            Stat::make('Total Pemilih', number_format($total))
                ->description(Classroom::query()->count().' kelas terdaftar')
                ->descriptionIcon(Heroicon::OutlinedUsers)
                ->descriptionColor('primary')
                ->chart([0, $total])
                ->chartColor('primary'),
            Stat::make('Sudah Memilih', number_format($voted))
                ->description('suara tercatat di bilik')
                ->descriptionIcon(Heroicon::OutlinedCheckBadge)
                ->descriptionColor('success')
                ->chart([0, $voted])
                ->chartColor('success'),
            Stat::make('Belum Memilih', number_format($notVoted))
                ->description('belum datang ke bilik')
                ->descriptionIcon(Heroicon::OutlinedClock)
                ->descriptionColor('warning')
                ->chart([0, $notVoted])
                ->chartColor('warning'),
            Stat::make('Partisipasi', "{$percent}%")
                ->description("{$voted} dari {$total} pemilih")
                ->descriptionIcon(Heroicon::OutlinedArrowTrendingUp)
                ->descriptionColor('info')
                ->chart([0, $percent])
                ->chartColor('info'),
        ];

        if (! ElectionSetting::isOpen()) {
            $stats[] = Stat::make('Hasil Final', 'Ditutup')
                ->description('pemilihan dikunci panitia')
                ->descriptionIcon(Heroicon::OutlinedTrophy)
                ->descriptionColor('warning');
        }

        return $stats;
    }
}
