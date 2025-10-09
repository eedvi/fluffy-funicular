<?php

namespace App\Filament\Widgets;

use App\Models\Customer;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

class TopCustomersWidget extends BaseWidget
{
    protected static ?int $sort = 4;

    protected int | string | array $columnSpan = 'full';

    protected static ?string $heading = 'Top 10 Clientes por Volumen de Negocio';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Customer::query()
                    ->withCount('loans')
                    ->withSum('loans', 'loan_amount')
                    ->orderByDesc('loans_sum_loan_amount')
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('rank')
                    ->label('#')
                    ->state(
                        fn ($rowLoop) => $rowLoop->iteration
                    )
                    ->badge()
                    ->color(fn ($rowLoop) => match ($rowLoop->iteration) {
                        1 => 'success',
                        2 => 'info',
                        3 => 'warning',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('full_name')
                    ->label('Cliente')
                    ->searchable(['first_name', 'last_name']),

                Tables\Columns\TextColumn::make('branch.name')
                    ->label('Sucursal')
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('loans_count')
                    ->label('Total Préstamos')
                    ->sortable()
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('loans_sum_loan_amount')
                    ->label('Monto Total Prestado')
                    ->money('GTQ')
                    ->sortable()
                    ->summarize([
                        Tables\Columns\Summarizers\Sum::make()
                            ->money('GTQ')
                            ->label('Total'),
                    ]),

                Tables\Columns\TextColumn::make('credit_score')
                    ->label('Puntaje')
                    ->badge()
                    ->color(fn ($state) => match (true) {
                        $state >= 750 => 'success',
                        $state >= 650 => 'info',
                        $state >= 550 => 'warning',
                        $state > 0 => 'danger',
                        default => 'gray'
                    })
                    ->formatStateUsing(fn ($state) => $state ?: 'N/A')
                    ->alignCenter(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Activo')
                    ->boolean()
                    ->alignCenter(),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('Ver')
                    ->icon('heroicon-o-eye')
                    ->url(fn (Customer $record) => route('filament.admin.resources.customers.edit', $record)),
            ]);
    }
}
