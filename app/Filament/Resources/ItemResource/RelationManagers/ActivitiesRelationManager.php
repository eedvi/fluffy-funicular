<?php

namespace App\Filament\Resources\ItemResource\RelationManagers;

use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ActivitiesRelationManager extends RelationManager
{
    protected static string $relationship = 'activities';

    protected static ?string $title = 'Historial de Cambios';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Registro de Auditoría del Artículo')
            ->description('Historial completo de cambios y modificaciones')
            ->columns([
                Tables\Columns\TextColumn::make('description')
                    ->label('Acción')
                    ->searchable()
                    ->wrap(),
                Tables\Columns\TextColumn::make('causer.name')
                    ->label('Usuario')
                    ->default('Sistema')
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('properties')
                    ->label('Cambios')
                    ->formatStateUsing(function ($state) {
                        if (!$state) return '-';

                        $changes = [];
                        if (isset($state['old']) && isset($state['attributes'])) {
                            foreach ($state['attributes'] as $key => $new) {
                                if (isset($state['old'][$key])) {
                                    $old = $state['old'][$key];
                                    if ($old != $new) {
                                        $changes[] = "{$key}: {$old} → {$new}";
                                    }
                                }
                            }
                        }
                        return $changes ? implode(', ', $changes) : 'Creación inicial';
                    })
                    ->wrap()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                //
            ])
            ->headerActions([
                // No crear actividades manualmente
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->modalHeading('Detalles del Cambio'),
            ])
            ->bulkActions([
                // No permitir eliminación masiva de auditoría
            ])
            ->paginated([10, 25, 50]);
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Información del Cambio')
                    ->schema([
                        Infolists\Components\Group::make([
                            Infolists\Components\TextEntry::make('log_name')
                                ->label('Registro')
                                ->badge()
                                ->color('gray'),
                            Infolists\Components\TextEntry::make('event')
                                ->label('Tipo de Evento')
                                ->badge()
                                ->formatStateUsing(fn (?string $state): string => match ($state) {
                                    'created' => 'Creado',
                                    'updated' => 'Actualizado',
                                    'deleted' => 'Eliminado',
                                    default => $state ?? 'N/A',
                                })
                                ->color(fn (?string $state): string => match ($state) {
                                    'created' => 'success',
                                    'updated' => 'info',
                                    'deleted' => 'danger',
                                    default => 'gray',
                                }),
                            Infolists\Components\TextEntry::make('created_at')
                                ->label('Fecha y Hora')
                                ->dateTime('d/m/Y H:i:s'),
                        ])->columns(3),
                        Infolists\Components\TextEntry::make('description')
                            ->label('Descripción')
                            ->columnSpanFull()
                            ->placeholder('Sin descripción'),
                    ]),

                Infolists\Components\Section::make('Usuario Responsable')
                    ->schema([
                        Infolists\Components\Group::make([
                            Infolists\Components\TextEntry::make('causer.name')
                                ->label('Usuario')
                                ->default('Sistema')
                                ->badge()
                                ->color('info'),
                            Infolists\Components\TextEntry::make('causer.email')
                                ->label('Email')
                                ->placeholder('N/A'),
                            Infolists\Components\TextEntry::make('causer_type')
                                ->label('Tipo')
                                ->formatStateUsing(fn (?string $state): string =>
                                    $state ? class_basename($state) : 'N/A'
                                ),
                        ])->columns(3),
                    ]),

                Infolists\Components\Section::make('Valores Anteriores')
                    ->schema([
                        Infolists\Components\TextEntry::make('properties.old')
                            ->label('')
                            ->formatStateUsing(function ($state) {
                                if (!$state) return 'Sin valores anteriores (registro nuevo)';

                                $formatted = [];
                                foreach ($state as $key => $value) {
                                    $formatted[] = "**{$key}:** " . json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                                }
                                return implode("\n\n", $formatted);
                            })
                            ->markdown()
                            ->columnSpanFull(),
                    ])
                    ->collapsed()
                    ->visible(fn ($record) => isset($record->properties['old'])),

                Infolists\Components\Section::make('Valores Nuevos')
                    ->schema([
                        Infolists\Components\TextEntry::make('properties.attributes')
                            ->label('')
                            ->formatStateUsing(function ($state) {
                                if (!$state) return 'Sin valores nuevos';

                                $formatted = [];
                                foreach ($state as $key => $value) {
                                    $formatted[] = "**{$key}:** " . json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                                }
                                return implode("\n\n", $formatted);
                            })
                            ->markdown()
                            ->columnSpanFull(),
                    ])
                    ->collapsed()
                    ->visible(fn ($record) => isset($record->properties['attributes'])),

                Infolists\Components\Section::make('Resumen de Cambios')
                    ->schema([
                        Infolists\Components\TextEntry::make('changes_summary')
                            ->label('')
                            ->state(function ($record) {
                                $properties = $record->properties;
                                if (!$properties || !isset($properties['old']) || !isset($properties['attributes'])) {
                                    return 'Creación inicial del registro';
                                }

                                $changes = [];
                                foreach ($properties['attributes'] as $key => $new) {
                                    if (isset($properties['old'][$key])) {
                                        $old = $properties['old'][$key];
                                        if ($old != $new) {
                                            $changes[] = "• **{$key}**: `{$old}` → `{$new}`";
                                        }
                                    } else {
                                        $changes[] = "• **{$key}**: (nuevo) → `{$new}`";
                                    }
                                }

                                return $changes ? implode("\n", $changes) : 'Sin cambios detectados';
                            })
                            ->markdown()
                            ->columnSpanFull(),
                    ])
                    ->visible(fn ($record) => isset($record->properties)),
            ]);
    }
}
