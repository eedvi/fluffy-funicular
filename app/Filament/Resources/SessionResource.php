<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SessionResource\Pages;
use App\Models\Session;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class SessionResource extends Resource
{
    protected static ?string $model = Session::class;

    protected static ?string $navigationIcon = 'heroicon-o-computer-desktop';

    protected static ?string $navigationGroup = 'Sistema';

    protected static ?string $modelLabel = 'Sesión';

    protected static ?string $pluralModelLabel = 'Sesiones Activas';

    protected static ?int $navigationSort = 9;

    /**
     * Filter query to show only authenticated sessions
     * Now uses the user_id column which is automatically populated on login
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->whereNotNull('user_id');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // View only - no creation or editing
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Usuario')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('user.email')
                    ->label('Email')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('ip_address')
                    ->label('Dirección IP')
                    ->searchable()
                    ->icon('heroicon-m-globe-alt'),

                Tables\Columns\TextColumn::make('browser')
                    ->label('Navegador')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Chrome' => 'success',
                        'Firefox' => 'warning',
                        'Safari' => 'info',
                        'Edge' => 'primary',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('device')
                    ->label('Dispositivo')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Desktop' => 'info',
                        'Mobile' => 'success',
                        'Tablet' => 'warning',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('last_activity')
                    ->label('Última Actividad')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable()
                    ->description(fn (Session $record): string =>
                        $record->isActive() ? 'Activo' : 'Inactivo'
                    ),

                Tables\Columns\IconColumn::make('is_current')
                    ->label('Sesión Actual')
                    ->boolean()
                    ->getStateUsing(fn (Session $record): bool =>
                        $record->id === session()->getId()
                    )
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('user_id')
                    ->label('Usuario')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\Filter::make('active_only')
                    ->label('Solo Activos')
                    ->query(fn (Builder $query): Builder =>
                        $query->where('last_activity', '>=', now()->subMinutes(5)->timestamp)
                    )
                    ->toggle(),
            ])
            ->actions([
                Tables\Actions\Action::make('terminate')
                    ->label('Terminar Sesión')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (Session $record): bool =>
                        $record->id !== session()->getId()
                    )
                    ->requiresConfirmation()
                    ->modalHeading('Terminar Sesión')
                    ->modalDescription('¿Estás seguro de que deseas terminar esta sesión? El usuario será desconectado.')
                    ->modalSubmitActionLabel('Sí, Terminar')
                    ->action(function (Session $record): void {
                        DB::table('sessions')->where('id', $record->id)->delete();

                        Notification::make()
                            ->success()
                            ->title('Sesión Terminada')
                            ->body('La sesión ha sido terminada exitosamente.')
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('terminate_sessions')
                        ->label('Terminar Sesiones')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Terminar Sesiones Seleccionadas')
                        ->modalDescription('¿Estás seguro de que deseas terminar las sesiones seleccionadas? Los usuarios serán desconectados.')
                        ->modalSubmitActionLabel('Sí, Terminar')
                        ->action(function ($records): void {
                            $currentSessionId = session()->getId();
                            $count = 0;

                            foreach ($records as $record) {
                                if ($record->id !== $currentSessionId) {
                                    DB::table('sessions')->where('id', $record->id)->delete();
                                    $count++;
                                }
                            }

                            Notification::make()
                                ->success()
                                ->title('Sesiones Terminadas')
                                ->body("{$count} sesión(es) han sido terminadas exitosamente.")
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ])
            ->defaultSort('last_activity', 'desc')
            ->poll('30s'); // Auto-refresh every 30 seconds
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSessions::route('/'),
        ];
    }

    // Disable create, edit capabilities
    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    // Only Admin and Gerente can access session management
    public static function canViewAny(): bool
    {
        return auth()->user()->hasAnyRole(['Admin', 'Gerente', 'super_admin']);
    }
}
