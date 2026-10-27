<?php

namespace App\Providers\Filament;

use App\Filament\Widgets\CandidateRaceWidget;
use App\Filament\Widgets\ElectionStatsOverview;
use App\Filament\Widgets\TurnoutPerClassroomChart;
use App\Filament\Widgets\VotesPerCandidateChart;
use App\Filament\Widgets\VotesPerCandidateStats;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->brandName('Pilketos · SMKN 2 Banjarmasin')
            ->brandLogo(asset('images/logo-smkn2-192.webp'))
            ->brandLogoHeight('2.5rem')
            ->darkModeBrandLogo(asset('images/logo-smkn2-192.webp'))
            ->favicon(asset('images/logo-smkn2.png'))
            ->simplePageMaxContentWidth('md')
            ->navigationGroups([
                'Data Pemilih',
                'Pemilihan',
            ])
            ->colors([
                'primary' => Color::Indigo,
                'secondary' => Color::Slate,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->login(\App\Filament\Auth\Pages\Login::class)
            ->pages([
                Dashboard::class,
            ])
            ->widgets([
                ElectionStatsOverview::class,
                VotesPerCandidateChart::class,
                VotesPerCandidateStats::class,
                CandidateRaceWidget::class,
                TurnoutPerClassroomChart::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
