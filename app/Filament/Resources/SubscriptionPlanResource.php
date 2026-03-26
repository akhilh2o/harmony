<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SubscriptionPlanResource\Pages;
use App\Models\SubscriptionPlan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\Section;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class SubscriptionPlanResource extends Resource
{
    protected static ?string $model           = SubscriptionPlan::class;
    protected static ?string $navigationIcon  = 'heroicon-o-tag';
    protected static ?string $navigationLabel = 'Subscription Plans';
    protected static ?string $navigationGroup = 'Users Management';
    protected static ?string $slug            = 'subscription-plans';
    protected static ?int    $navigationSort  = 3;

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('is_active', true)->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    // ─── FORM ─────────────────────────────────────────────────
    public static function form(Form $form): Form
    {
        return $form->schema([

            // Left column
            Forms\Components\Group::make()->schema([

                Section::make('Plan Details')->schema([
                    Forms\Components\TextInput::make('name')
                        ->required()
                        ->maxLength(100)
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn ($state, Forms\Set $set) =>
                            $set('slug', Str::slug($state))
                        )
                        ->placeholder('e.g. Monthly Plan'),

                    Forms\Components\TextInput::make('slug')
                        ->required()
                        ->unique(SubscriptionPlan::class, 'slug', ignoreRecord: true)
                        ->maxLength(50)
                        ->helperText('Auto-generated. Used as plan identifier.'),

                    Forms\Components\Select::make('duration_type')
                        ->required()
                        ->options([
                            'monthly'     => 'Monthly (30 days)',
                            'half_yearly' => 'Half Yearly (180 days)',
                            'yearly'      => 'Yearly (365 days)',
                        ])
                        ->live()
                        ->afterStateUpdated(function ($state, Forms\Set $set) {
                            $set('duration_days', match ($state) {
                                'monthly'     => 30,
                                'half_yearly' => 180,
                                'yearly'      => 365,
                                default       => 30,
                            });
                        })
                        ->native(false),

                    Forms\Components\TextInput::make('duration_days')
                        ->required()
                        ->numeric()
                        ->minValue(1)
                        ->label('Duration (days)')
                        ->helperText('Auto-filled. Override if needed.'),

                    Forms\Components\Textarea::make('description')
                        ->rows(2)
                        ->maxLength(500)
                        ->placeholder('Short description shown to users'),
                ])->columns(2),

                Section::make('Features')->schema([
                    Forms\Components\Repeater::make('features')
                        ->simple(
                            Forms\Components\TextInput::make('feature')
                                ->placeholder('e.g. Unlimited sessions')
                                ->required()
                        )
                        ->addActionLabel('Add Feature')
                        ->reorderable()
                        ->collapsible()
                        ->helperText('These appear as bullet points on the plan card'),
                ]),

            ])->columnSpan(['lg' => 2]),

            // Right column
            Forms\Components\Group::make()->schema([

                Section::make('Pricing')->schema([
                    Forms\Components\Select::make('currency')
                        ->options([
                            'INR' => '₹ INR',
                            'USD' => '$ USD',
                            'EUR' => '€ EUR',
                        ])
                        ->default('INR')
                        ->required()
                        ->native(false),

                    Forms\Components\TextInput::make('price')
                        ->required()
                        ->numeric()
                        ->minValue(0)
                        ->prefix(fn ($get) => match ($get('currency')) {
                            'USD' => '$', 'EUR' => '€', default => '₹',
                        })
                        ->placeholder('799'),

                    Forms\Components\TextInput::make('original_price')
                        ->numeric()
                        ->minValue(0)
                        ->prefix(fn ($get) => match ($get('currency')) {
                            'USD' => '$', 'EUR' => '€', default => '₹',
                        })
                        ->placeholder('1500')
                        ->helperText('Optional. Shows as strikethrough to indicate discount.'),
                ]),

                Section::make('IAP / Store')->schema([
                    Forms\Components\TextInput::make('iap_product_id')
                        ->label('IAP Product ID')
                        ->placeholder('e.g. monthly_plan')
                        ->helperText('Play Store / App Store SKU. Must match itemSkus in app.')
                        ->maxLength(100),
                ]),

                Section::make('Settings')->schema([
                    Forms\Components\Toggle::make('is_active')
                        ->label('Active')
                        ->default(true)
                        ->helperText('Inactive plans are hidden from app users.'),

                    Forms\Components\Toggle::make('is_popular')
                        ->label('Mark as Popular')
                        ->helperText('Shows "Most Popular" badge on this plan.'),

                    Forms\Components\TextInput::make('sort_order')
                        ->numeric()
                        ->default(0)
                        ->label('Sort Order')
                        ->helperText('Lower = shown first.'),
                ]),

            ])->columnSpan(['lg' => 1]),

        ])->columns(3);
    }

    // ─── TABLE ────────────────────────────────────────────────
    public static function table(Table $table): Table
    {
        return $table
            ->reorderable('sort_order')
            ->columns([
                Tables\Columns\TextColumn::make('sort_order')
                    ->label('#')
                    ->sortable()
                    ->width(40),

                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('duration_type')
                    ->label('Duration')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'monthly'     => 'warning',
                        'half_yearly' => 'info',
                        'yearly'      => 'success',
                        default       => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'monthly'     => 'Monthly',
                        'half_yearly' => 'Half Yearly',
                        'yearly'      => 'Yearly',
                        default       => ucfirst($state),
                    }),

                Tables\Columns\TextColumn::make('price')
                    ->label('Price')
                    ->formatStateUsing(fn ($state, $record) =>
                        ($record->original_price
                            ? '<s style="color:#999">₹' . number_format($record->original_price, 0) . '</s> '
                            : ''
                        ) . '₹' . number_format($state, 0)
                    )
                    ->html()
                    ->sortable(),

                Tables\Columns\TextColumn::make('iap_product_id')
                    ->label('IAP SKU')
                    ->placeholder('—')
                    ->toggleable(),

                Tables\Columns\IconColumn::make('is_popular')
                    ->label('Popular')
                    ->boolean()
                    ->trueColor('warning')
                    ->falseColor('gray'),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('danger'),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Updated')
                    ->since()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])

            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status')
                    ->trueLabel('Active only')
                    ->falseLabel('Inactive only')
                    ->native(false),

                Tables\Filters\SelectFilter::make('duration_type')
                    ->label('Duration')
                    ->options([
                        'monthly'     => 'Monthly',
                        'half_yearly' => 'Half Yearly',
                        'yearly'      => 'Yearly',
                    ])
                    ->native(false),
            ])

            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])

            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])

            ->defaultSort('sort_order', 'asc')
            ->emptyStateHeading('No subscription plans yet')
            ->emptyStateDescription('Create your first plan — Monthly, Half Yearly, or Yearly.')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('Create Plan'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListSubscriptionPlans::route('/'),
            'create' => Pages\CreateSubscriptionPlan::route('/create'),
            'edit'   => Pages\EditSubscriptionPlan::route('/{record}/edit'),
        ];
    }
}