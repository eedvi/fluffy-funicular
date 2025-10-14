<?php

namespace App\Filament\Resources\ActivityResource\Pages;

use App\Filament\Resources\ActivityResource;
use Filament\Actions;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components;
use Filament\Resources\Pages\ViewRecord;

class ViewActivity extends ViewRecord
{
    protected static string $resource = ActivityResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Components\Section::make('Información General')
                    ->schema([
                        Components\TextEntry::make('description')
                            ->label('Descripción')
                            ->columnSpanFull(),

                        Components\TextEntry::make('log_name')
                            ->label('Tipo de Log')
                            ->badge(),

                        Components\TextEntry::make('event')
                            ->label('Evento')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'created' => 'success',
                                'updated' => 'info',
                                'deleted' => 'danger',
                                default => 'gray',
                            }),

                        Components\TextEntry::make('subject_type')
                            ->label('Tipo de Registro')
                            ->formatStateUsing(fn ($state) => class_basename($state))
                            ->badge(),

                        Components\TextEntry::make('subject_id')
                            ->label('ID del Registro'),

                        Components\TextEntry::make('causer.name')
                            ->label('Realizado por')
                            ->default('Sistema')
                            ->icon('heroicon-m-user'),

                        Components\TextEntry::make('created_at')
                            ->label('Fecha y Hora')
                            ->dateTime('d/m/Y H:i:s')
                            ->icon('heroicon-m-clock'),
                    ])
                    ->columns(2),

                Components\Section::make('Detalles de la Sesión')
                    ->schema([
                        Components\TextEntry::make('properties.ip')
                            ->label('Dirección IP')
                            ->default('N/A')
                            ->icon('heroicon-m-globe-alt'),

                        Components\TextEntry::make('properties.user_agent')
                            ->label('Navegador/Dispositivo')
                            ->default('N/A')
                            ->columnSpanFull()
                            ->icon('heroicon-m-computer-desktop'),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Components\Section::make('Cambios Realizados')
                    ->schema([
                        Components\ViewField::make('changes')
                            ->label('')
                            ->view('filament.components.activity-changes')
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->visible(fn ($record) => !empty($record->properties['old'] ?? []) || !empty($record->properties['attributes'] ?? [])),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('back')
                ->label('Volver')
                ->url(ActivityResource::getUrl('index'))
                ->icon('heroicon-o-arrow-left')
                ->color('gray'),
        ];
    }
}
