<?php

namespace App\Filament\Resources\ItemResource\RelationManagers;

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
                    ->modalHeading('Detalles del Cambio')
                    ->modalContent(fn ($record) => view('filament.pages.activity-details', ['activity' => $record])),
            ])
            ->bulkActions([
                // No permitir eliminación masiva de auditoría
            ])
            ->paginated([10, 25, 50]);
    }
}
