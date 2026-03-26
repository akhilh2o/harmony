<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SubscriptionResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\Section;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;

class SubscriptionResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon  = 'heroicon-o-credit-card';
    protected static ?string $navigationLabel = 'Subscriptions';
    protected static ?string $navigationGroup = 'Users Management';
    protected static ?string $slug            = 'subscriptions';
    protected static ?int    $navigationSort  = 2;

    // Badge — count of active subscribers
    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('is_subscribed', true)->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }

    // ─── Form (Edit subscription for a user) ─────────────────
    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('User')->schema([
                Forms\Components\TextInput::make('name')
                    ->disabled(),
                Forms\Components\TextInput::make('email')
                    ->disabled(),
            ])->columns(2),

            Section::make('Subscription')->schema([
                Forms\Components\Toggle::make('is_subscribed')
                    ->label('Active Subscription')
                    ->live()
                    ->afterStateUpdated(function ($state, Forms\Set $set) {
                        if (!$state) {
                            $set('subscription_plan', null);
                            $set('subscription_expires_at', null);
                        }
                    }),

                Forms\Components\Select::make('subscription_plan')
                    ->label('Plan')
                    ->options([
                        'monthly' => 'Monthly',
                        'yearly'  => 'Yearly',
                    ])
                    ->visible(fn (Forms\Get $get) => $get('is_subscribed'))
                    ->live()
                    ->afterStateUpdated(function ($state, Forms\Set $set) {
                        // Auto-set expiry based on plan
                        if ($state === 'monthly') {
                            $set('subscription_expires_at', now()->addMonth()->format('Y-m-d H:i:s'));
                        } elseif ($state === 'yearly') {
                            $set('subscription_expires_at', now()->addYear()->format('Y-m-d H:i:s'));
                        }
                    }),

                Forms\Components\DateTimePicker::make('subscription_expires_at')
                    ->label('Expires At')
                    ->visible(fn (Forms\Get $get) => $get('is_subscribed'))
                    ->helperText('Auto-filled when plan is selected. You can override manually.'),
            ])->columns(1),
        ]);
    }

    // ─── Table ────────────────────────────────────────────────
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('avatar')
                    ->circular()
                    ->size(40)
                    ->defaultImageUrl(
                        fn ($record) => 'https://ui-avatars.com/api/?name=' . urlencode($record->name) . '&color=c9a84c&background=27221a'
                    ),

                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_subscribed')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-badge')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->sortable(),

                Tables\Columns\TextColumn::make('subscription_plan')
                    ->label('Plan')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'yearly'  => 'success',
                        'monthly' => 'warning',
                        default   => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => $state ? ucfirst($state) : '—'),

                Tables\Columns\TextColumn::make('subscription_expires_at')
                    ->label('Expires At')
                    ->dateTime('d M Y, h:i A')
                    ->sortable()
                    ->color(fn ($record) => $record->subscription_expires_at?->isPast() ? 'danger' : 'success')
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Joined')
                    ->date('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])

            ->filters([
                Tables\Filters\TernaryFilter::make('is_subscribed')
                    ->label('Subscription Status')
                    ->trueLabel('Active Subscribers')
                    ->falseLabel('Non-Subscribers')
                    ->native(false),

                Tables\Filters\SelectFilter::make('subscription_plan')
                    ->label('Plan')
                    ->options([
                        'monthly' => 'Monthly',
                        'yearly'  => 'Yearly',
                    ]),
            ])

            ->actions([
                // ── Quick: Activate monthly ──────────────────
                Action::make('activate_monthly')
                    ->label('Monthly')
                    ->icon('heroicon-o-plus-circle')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Activate Monthly Plan')
                    ->modalDescription(fn ($record) => "Activate monthly subscription for {$record->name}?")
                    ->action(function ($record) {
                        $record->update([
                            'is_subscribed'           => true,
                            'subscription_plan'       => 'monthly',
                            'subscription_expires_at' => now()->addMonth(),
                        ]);
                        Notification::make()
                            ->title('Monthly subscription activated!')
                            ->success()
                            ->send();
                    })
                    ->visible(fn ($record) => !$record->is_subscribed),

                // ── Quick: Activate yearly ───────────────────
                Action::make('activate_yearly')
                    ->label('Yearly')
                    ->icon('heroicon-o-star')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Activate Yearly Plan')
                    ->modalDescription(fn ($record) => "Activate yearly subscription for {$record->name}?")
                    ->action(function ($record) {
                        $record->update([
                            'is_subscribed'           => true,
                            'subscription_plan'       => 'yearly',
                            'subscription_expires_at' => now()->addYear(),
                        ]);
                        Notification::make()
                            ->title('Yearly subscription activated!')
                            ->success()
                            ->send();
                    })
                    ->visible(fn ($record) => !$record->is_subscribed),

                // ── Quick: Cancel ────────────────────────────
                Action::make('cancel')
                    ->label('Cancel')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Cancel Subscription')
                    ->modalDescription(fn ($record) => "Cancel subscription for {$record->name}?")
                    ->action(function ($record) {
                        $record->update([
                            'is_subscribed'           => false,
                            'subscription_plan'       => null,
                            'subscription_expires_at' => null,
                        ]);
                        Notification::make()
                            ->title('Subscription cancelled.')
                            ->warning()
                            ->send();
                    })
                    ->visible(fn ($record) => $record->is_subscribed),

                Tables\Actions\EditAction::make()->label('Edit'),
            ])

            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    // ── Bulk activate monthly ────────────────
                    Tables\Actions\BulkAction::make('bulk_monthly')
                        ->label('Activate Monthly')
                        ->icon('heroicon-o-plus-circle')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $records->each(fn ($r) => $r->update([
                                'is_subscribed'           => true,
                                'subscription_plan'       => 'monthly',
                                'subscription_expires_at' => now()->addMonth(),
                            ]));
                            Notification::make()->title('Monthly plans activated!')->success()->send();
                        }),

                    // ── Bulk activate yearly ─────────────────
                    Tables\Actions\BulkAction::make('bulk_yearly')
                        ->label('Activate Yearly')
                        ->icon('heroicon-o-star')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $records->each(fn ($r) => $r->update([
                                'is_subscribed'           => true,
                                'subscription_plan'       => 'yearly',
                                'subscription_expires_at' => now()->addYear(),
                            ]));
                            Notification::make()->title('Yearly plans activated!')->success()->send();
                        }),

                    // ── Bulk cancel ──────────────────────────
                    Tables\Actions\BulkAction::make('bulk_cancel')
                        ->label('Cancel Subscriptions')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $records->each(fn ($r) => $r->update([
                                'is_subscribed'           => false,
                                'subscription_plan'       => null,
                                'subscription_expires_at' => null,
                            ]));
                            Notification::make()->title('Subscriptions cancelled.')->warning()->send();
                        }),
                ]),
            ])

            ->defaultSort('subscription_expires_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSubscriptions::route('/'),
            'edit'  => Pages\EditSubscription::route('/{record}/edit'),
        ];
    }
}