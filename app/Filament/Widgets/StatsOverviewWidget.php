<?php

namespace App\Filament\Widgets;

use App\Models\Business;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverviewWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $totalUsers = User::count();
        $activeSubscriptions = User::whereHas('subscriptions', fn ($q) => $q->where('stripe_status', 'active'))->count();
        $totalBusinesses = Business::count();
        $recentSignups = User::where('created_at', '>=', now()->subDays(30))->count();

        // Approximate MRR: count active Starter subs ($49) + Pro subs ($99)
        // We use Cashier's subscription items to determine plan by price ID
        $starterPrice = config('services.stripe.price_starter');
        $proPrice = config('services.stripe.price_pro');

        $starterCount = \Laravel\Cashier\SubscriptionItem::where('stripe_price', $starterPrice)->count();
        $proCount = \Laravel\Cashier\SubscriptionItem::where('stripe_price', $proPrice)->count();
        $mrr = ($starterCount * 49) + ($proCount * 99);

        return [
            Stat::make('Total users', $totalUsers)
                ->description('All registered accounts')
                ->color('gray'),

            Stat::make('Active subscriptions', $activeSubscriptions)
                ->description('Paying customers')
                ->color('success'),

            Stat::make('Approx. MRR', '$'.number_format($mrr).' AUD')
                ->description('Starter × $49 + Pro × $99')
                ->color('success'),

            Stat::make('Businesses', $totalBusinesses)
                ->description('Total business locations')
                ->color('info'),

            Stat::make('New signups (30d)', $recentSignups)
                ->description('Last 30 days')
                ->color('warning'),
        ];
    }
}
