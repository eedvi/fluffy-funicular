<?php

namespace App\Filament\Pages;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;

class AppraisalCalculator extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-calculator';

    protected static string $view = 'filament.pages.appraisal-calculator';

    protected static ?string $navigationGroup = 'Herramientas';

    protected static ?string $navigationLabel = 'Calculadora de Tasación';

    protected static ?string $title = 'Calculadora de Tasación';

    public ?array $data = [];

    public $calculatedValue = null;
    public $suggestedLoanAmount = null;

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Tipo de Artículo')
                    ->schema([
                        Forms\Components\Select::make('item_type')
                            ->label('Tipo de Artículo')
                            ->required()
                            ->options([
                                'gold' => 'Oro/Joyería',
                                'electronics' => 'Electrónica',
                                'tools' => 'Herramientas',
                                'other' => 'Otro',
                            ])
                            ->reactive()
                            ->afterStateUpdated(fn ($set) => $this->resetCalculations()),
                    ]),

                Forms\Components\Section::make('Tasación de Oro/Joyería')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('weight')
                                    ->label('Peso (gramos)')
                                    ->numeric()
                                    ->required()
                                    ->minValue(0)
                                    ->suffix('g')
                                    ->reactive(),
                                Forms\Components\Select::make('karat')
                                    ->label('Quilates')
                                    ->required()
                                    ->options([
                                        '24' => '24K (99.9% puro)',
                                        '22' => '22K (91.6% puro)',
                                        '18' => '18K (75% puro)',
                                        '14' => '14K (58.3% puro)',
                                        '10' => '10K (41.7% puro)',
                                    ])
                                    ->reactive(),
                                Forms\Components\TextInput::make('gold_price_per_gram')
                                    ->label('Precio del Oro ($/g)')
                                    ->numeric()
                                    ->required()
                                    ->default(60)
                                    ->prefix('Q')
                                    ->helperText('Precio actual del oro por gramo')
                                    ->reactive(),
                            ]),
                        Forms\Components\Select::make('condition')
                            ->label('Condición')
                            ->required()
                            ->options([
                                'excellent' => 'Excelente (95%)',
                                'good' => 'Bueno (85%)',
                                'fair' => 'Regular (75%)',
                                'poor' => 'Malo (60%)',
                            ])
                            ->reactive(),
                    ])
                    ->visible(fn (Forms\Get $get) => $get('item_type') === 'gold'),

                Forms\Components\Section::make('Tasación de Electrónica')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('purchase_price')
                                    ->label('Precio de Compra Original')
                                    ->numeric()
                                    ->required()
                                    ->prefix('Q')
                                    ->reactive(),
                                Forms\Components\TextInput::make('age_months')
                                    ->label('Edad (meses)')
                                    ->numeric()
                                    ->required()
                                    ->minValue(0)
                                    ->suffix('meses')
                                    ->helperText('¿Hace cuántos meses se compró?')
                                    ->reactive(),
                                Forms\Components\Select::make('electronic_condition')
                                    ->label('Condición')
                                    ->required()
                                    ->options([
                                        'new' => 'Nuevo (100%)',
                                        'excellent' => 'Excelente (85%)',
                                        'good' => 'Bueno (70%)',
                                        'fair' => 'Regular (50%)',
                                        'poor' => 'Malo (30%)',
                                    ])
                                    ->reactive(),
                            ]),
                    ])
                    ->visible(fn (Forms\Get $get) => $get('item_type') === 'electronics'),

                Forms\Components\Section::make('Tasación General')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('estimated_value')
                                    ->label('Valor Estimado')
                                    ->numeric()
                                    ->required()
                                    ->prefix('Q')
                                    ->reactive(),
                                Forms\Components\Select::make('general_condition')
                                    ->label('Condición')
                                    ->required()
                                    ->options([
                                        'excellent' => 'Excelente (90%)',
                                        'good' => 'Bueno (75%)',
                                        'fair' => 'Regular (60%)',
                                        'poor' => 'Malo (40%)',
                                    ])
                                    ->reactive(),
                            ]),
                    ])
                    ->visible(fn (Forms\Get $get) => in_array($get('item_type'), ['tools', 'other'])),

                Forms\Components\Section::make('Parámetros del Préstamo')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('loan_to_value_ratio')
                                    ->label('Ratio Préstamo/Valor (%)')
                                    ->numeric()
                                    ->default(70)
                                    ->minValue(1)
                                    ->maxValue(100)
                                    ->suffix('%')
                                    ->helperText('Típicamente 60-80%')
                                    ->reactive(),
                                Forms\Components\Toggle::make('include_interest_protection')
                                    ->label('Incluir Protección de Intereses')
                                    ->helperText('Reduce el monto del préstamo para cubrir primer mes de interés')
                                    ->reactive(),
                            ]),
                    ]),
            ])
            ->statePath('data');
    }

    public function calculate(): void
    {
        $data = $this->form->getState();
        $itemType = $data['item_type'] ?? null;

        if (!$itemType) {
            Notification::make()
                ->warning()
                ->title('Seleccione un tipo de artículo')
                ->send();
            return;
        }

        $baseValue = 0;

        // Calculate based on item type
        switch ($itemType) {
            case 'gold':
                $weight = $data['weight'] ?? 0;
                $karat = $data['karat'] ?? 24;
                $pricePerGram = $data['gold_price_per_gram'] ?? 60;
                $condition = $data['condition'] ?? 'good';

                // Gold purity percentages
                $purity = [
                    '24' => 0.999,
                    '22' => 0.916,
                    '18' => 0.750,
                    '14' => 0.583,
                    '10' => 0.417,
                ];

                // Condition multipliers
                $conditionMultiplier = [
                    'excellent' => 0.95,
                    'good' => 0.85,
                    'fair' => 0.75,
                    'poor' => 0.60,
                ];

                $pureGold = $weight * ($purity[$karat] ?? 1);
                $baseValue = $pureGold * $pricePerGram * ($conditionMultiplier[$condition] ?? 0.85);
                break;

            case 'electronics':
                $purchasePrice = $data['purchase_price'] ?? 0;
                $ageMonths = $data['age_months'] ?? 0;
                $condition = $data['electronic_condition'] ?? 'good';

                // Condition multipliers
                $conditionMultiplier = [
                    'new' => 1.00,
                    'excellent' => 0.85,
                    'good' => 0.70,
                    'fair' => 0.50,
                    'poor' => 0.30,
                ];

                // Depreciation: 5% per month for first 12 months, then 2% per month
                $depreciation = min($ageMonths * 0.05, 0.60);
                if ($ageMonths > 12) {
                    $depreciation = 0.60 + (($ageMonths - 12) * 0.02);
                }
                $depreciation = min($depreciation, 0.90); // Max 90% depreciation

                $baseValue = $purchasePrice * (1 - $depreciation) * ($conditionMultiplier[$condition] ?? 0.70);
                break;

            case 'tools':
            case 'other':
                $estimatedValue = $data['estimated_value'] ?? 0;
                $condition = $data['general_condition'] ?? 'good';

                $conditionMultiplier = [
                    'excellent' => 0.90,
                    'good' => 0.75,
                    'fair' => 0.60,
                    'poor' => 0.40,
                ];

                $baseValue = $estimatedValue * ($conditionMultiplier[$condition] ?? 0.75);
                break;
        }

        // Calculate loan amount
        $ltvRatio = ($data['loan_to_value_ratio'] ?? 70) / 100;
        $loanAmount = $baseValue * $ltvRatio;

        // Adjust for interest protection if requested
        if ($data['include_interest_protection'] ?? false) {
            // Assuming 10% monthly interest rate
            $interestRate = 0.10;
            $loanAmount = $loanAmount / (1 + $interestRate);
        }

        $this->calculatedValue = round($baseValue, 2);
        $this->suggestedLoanAmount = round($loanAmount, 2);

        Notification::make()
            ->success()
            ->title('Tasación Calculada')
            ->body("Valor: $" . number_format($this->calculatedValue, 2) . " | Préstamo Sugerido: $" . number_format($this->suggestedLoanAmount, 2))
            ->send();
    }

    public function resetCalculations(): void
    {
        $this->calculatedValue = null;
        $this->suggestedLoanAmount = null;
    }
}
