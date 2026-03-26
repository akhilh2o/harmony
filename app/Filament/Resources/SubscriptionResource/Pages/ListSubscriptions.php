<?php

namespace App\Filament\Resources\SubscriptionResource\Pages;

use App\Filament\Resources\SubscriptionResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListSubscriptions extends ListRecords
{
    protected static string $resource = SubscriptionResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    // ── Tabs: All / Active / Expired / None ──────────────────
    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All Users')
                ->badge(fn () => \App\Models\User::count()),

            'active' => Tab::make('Active')
                ->badge(fn () => \App\Models\User::where('is_subscribed', true)
                    ->where(fn ($q) => $q->whereNull('subscription_expires_at')
                        ->orWhere('subscription_expires_at', '>', now()))
                    ->count())
                ->badgeColor('success')
                ->modifyQueryUsing(fn (Builder $query) => $query
                    ->where('is_subscribed', true)
                    ->where(fn ($q) => $q->whereNull('subscription_expires_at')
                        ->orWhere('subscription_expires_at', '>', now()))
                ),

            'expired' => Tab::make('Expired')
                ->badge(fn () => \App\Models\User::where('is_subscribed', true)
                    ->where('subscription_expires_at', '<', now())
                    ->count())
                ->badgeColor('danger')
                ->modifyQueryUsing(fn (Builder $query) => $query
                    ->where('is_subscribed', true)
                    ->where('subscription_expires_at', '<', now())
                ),

            'none' => Tab::make('No Subscription')
                ->badge(fn () => \App\Models\User::where('is_subscribed', false)->count())
                ->badgeColor('gray')
                ->modifyQueryUsing(fn (Builder $query) => $query
                    ->where('is_subscribed', false)
                ),
        ];
    }
}