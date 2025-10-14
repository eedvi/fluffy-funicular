<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Alareqi\FilamentPwa\FilamentPwaPlugin;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Filament\Facades\Filament;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Table;

class AdminPanelProvider extends PanelProvider
{   
    public function boot(): void
    {
        // Global config to set up the table pagination. If a table has local configuration it will override this configuration.
        Table::configureUsing(function (Table $table): void {
            $table
                ->filtersLayout(FiltersLayout::AboveContentCollapsible)
                ->paginationPageOptions([5, 10, 25])
                ->defaultSort('created_at', 'desc')
                ->deferLoading()
                ->striped();
        });

        Filament::serving(function () {
            set_time_limit(300);
            ini_set('memory_limit', '512M');
        });
    }
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->brandName('Casa de Empeño')
            ->colors([
                'primary' => Color::Amber,
            ])
            ->databaseNotifications()
            ->databaseNotificationsPolling('30s')
            ->sidebarCollapsibleOnDesktop()
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
                ThrottleRequests::class . ':60,1', // 60 requests per minute
            ])
            ->plugins([
                FilamentShieldPlugin::make(),
                FilamentPwaPlugin::make()
                    ->name('Sistema de Empeño')
                    ->shortName('Empeño')
                    ->description('Sistema de gestión de casa de empeño')
                    ->themeColor('#f59e0b')
                    ->backgroundColor('#ffffff')
                    ->orientation('portrait-primary')
                    ->standalone()
                    ->shortcuts([
                        [
                            'name' => 'Nuevo Préstamo',
                            'shortName' => 'Préstamo',
                            'description' => 'Crear un nuevo préstamo',
                            'url' => '/admin/loans/create',
                        ],
                        [
                            'name' => 'Nueva Venta',
                            'shortName' => 'Venta',
                            'description' => 'Registrar una nueva venta',
                            'url' => '/admin/sales/create',
                        ],
                        [
                            'name' => 'Nuevo Artículo',
                            'shortName' => 'Artículo',
                            'description' => 'Agregar un nuevo artículo',
                            'url' => '/admin/items/create',
                        ],
                    ]),
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
