<?php

namespace App\Filament\Resources\SessionResource\Pages;

use App\Filament\Resources\SessionResource;
use Filament\Actions;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewSession extends ViewRecord
{
    protected static string $resource = SessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Información de la Sesión')
                    ->schema([
                        Infolists\Components\Group::make([
                            Infolists\Components\TextEntry::make('user.name')
                                ->label('Usuario')
                                ->badge()
                                ->color('info')
                                ->default('Usuario no identificado'),
                            Infolists\Components\TextEntry::make('user.email')
                                ->label('Email')
                                ->placeholder('N/A'),
                            Infolists\Components\TextEntry::make('id')
                                ->label('ID de Sesión')
                                ->copyable(),
                        ])->columns(3),
                    ]),

                Infolists\Components\Section::make('Información de Conexión')
                    ->schema([
                        Infolists\Components\Group::make([
                            Infolists\Components\TextEntry::make('ip_address')
                                ->label('Dirección IP')
                                ->copyable()
                                ->icon('heroicon-o-globe-alt'),
                            Infolists\Components\TextEntry::make('user_agent')
                                ->label('Navegador/Dispositivo')
                                ->columnSpan(2)
                                ->icon('heroicon-o-computer-desktop'),
                        ])->columns(3),
                    ]),

                Infolists\Components\Section::make('Registro de Tiempo')
                    ->schema([
                        Infolists\Components\Group::make([
                            Infolists\Components\TextEntry::make('last_activity')
                                ->label('Última Actividad')
                                ->dateTime('d/m/Y H:i:s')
                                ->since()
                                ->icon('heroicon-o-clock'),
                            Infolists\Components\TextEntry::make('created_at')
                                ->label('Creada')
                                ->dateTime('d/m/Y H:i:s')
                                ->since()
                                ->placeholder('N/A')
                                ->icon('heroicon-o-calendar'),
                            Infolists\Components\TextEntry::make('updated_at')
                                ->label('Actualizada')
                                ->dateTime('d/m/Y H:i:s')
                                ->since()
                                ->placeholder('N/A')
                                ->icon('heroicon-o-arrow-path'),
                        ])->columns(3),
                    ]),

                Infolists\Components\Section::make('Datos de Sesión')
                    ->schema([
                        Infolists\Components\TextEntry::make('payload')
                            ->label('Información')
                            ->state(function ($record) {
                                if (!$record->payload) {
                                    return 'Sin datos';
                                }
                                // Decode and format payload
                                $decoded = base64_decode($record->payload);
                                $unserialized = @unserialize($decoded);
                                if ($unserialized !== false) {
                                    return json_encode($unserialized, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                                }
                                return 'Datos codificados';
                            })
                            ->markdown()
                            ->columnSpanFull(),
                    ])
                    ->collapsed(),
            ]);
    }
}
