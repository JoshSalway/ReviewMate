import { Head, router } from '@inertiajs/react';
import { CheckCircle, Crown } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { billing as billingRoute } from '@/routes/settings';
import { subscribe as subscribeRoute, portal as portalRoute } from '@/routes/settings/billing';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Billing',
        href: billingRoute(),
    },
];

interface Props {
    plan: 'admin' | 'starter' | 'pro' | null;
    isAdmin: boolean;
    onFreePlan: boolean;
    subscription: { status: string; ends_at: string | null } | null;
    prices: { starter: string | null; pro: string | null };
}

const starterFeatures = [
    '1 business location',
    'Unlimited customers',
    'Email review requests',
    'AI reply suggestions',
    'Google review sync',
    'Automated follow-ups',
];

const proFeatures = [
    'Up to 5 business locations',
    'SMS review requests',
    'Everything in Starter',
    'Priority support',
];

function PlanBadge({ plan }: { plan: Props['plan'] }) {
    if (plan === 'admin') return <Badge className="bg-purple-100 text-purple-700 hover:bg-purple-100">Admin</Badge>;
    if (plan === 'pro') return <Badge className="bg-teal-100 text-teal-700 hover:bg-teal-100">Pro</Badge>;
    if (plan === 'starter') return <Badge className="bg-blue-100 text-blue-700 hover:bg-blue-100">Starter</Badge>;
    return <Badge className="bg-muted text-muted-foreground hover:bg-muted">Free</Badge>;
}

export default function Billing({ plan, isAdmin, onFreePlan, subscription, prices }: Props) {
    const handleSubscribe = (price: string | null) => {
        if (!price) return;
        router.post(subscribeRoute().url, { price });
    };

    const handlePortal = () => {
        router.post(portalRoute().url);
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Billing" />
            <div className="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold text-foreground">Billing</h1>
                        <p className="mt-1 text-sm text-muted-foreground">Manage your subscription and plan</p>
                    </div>
                    <PlanBadge plan={plan} />
                </div>

                {isAdmin && (
                    <div className="max-w-2xl rounded-xl border border-purple-200 bg-purple-50 p-4">
                        <div className="flex items-center gap-3">
                            <Crown className="h-5 w-5 text-purple-600" />
                            <div>
                                <p className="font-semibold text-purple-900">Admin account — all limits waived</p>
                                <p className="text-sm text-purple-700">You have full access to all features with no restrictions.</p>
                            </div>
                        </div>
                    </div>
                )}

                {!isAdmin && subscription && (
                    <div className="max-w-2xl rounded-xl border border-teal-200 bg-teal-50 p-4">
                        <p className="text-sm font-medium text-teal-900">
                            Active {plan === 'pro' ? 'Pro' : 'Starter'} subscription
                            {subscription.ends_at && ` · Cancels ${subscription.ends_at}`}
                        </p>
                        <Button
                            variant="outline"
                            size="sm"
                            className="mt-3 border-teal-300 text-teal-700"
                            onClick={handlePortal}
                        >
                            Manage subscription
                        </Button>
                    </div>
                )}

                {!isAdmin && (
                    <div className="grid max-w-4xl gap-6 sm:grid-cols-2">
                        {/* Starter */}
                        <Card className={plan === 'starter' ? 'border-blue-300 ring-1 ring-blue-300' : ''}>
                            <CardHeader className="pb-3">
                                <div className="flex items-center justify-between">
                                    <CardTitle className="text-base font-semibold">Starter</CardTitle>
                                    {plan === 'starter' && <Badge className="bg-blue-100 text-blue-700 hover:bg-blue-100">Current</Badge>}
                                </div>
                                <div className="flex items-end gap-1">
                                    <span className="text-3xl font-bold text-foreground">$49</span>
                                    <span className="mb-1 text-sm text-muted-foreground">/month</span>
                                </div>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <ul className="space-y-2">
                                    {starterFeatures.map((f) => (
                                        <li key={f} className="flex items-center gap-2 text-sm text-foreground">
                                            <CheckCircle className="h-4 w-4 shrink-0 text-teal-600" />
                                            {f}
                                        </li>
                                    ))}
                                </ul>
                                {plan !== 'starter' && plan !== 'pro' && (
                                    <Button
                                        className="w-full bg-blue-600 hover:bg-blue-700 text-white"
                                        onClick={() => handleSubscribe(prices.starter)}
                                        disabled={!prices.starter}
                                    >
                                        Get Starter
                                    </Button>
                                )}
                                {onFreePlan && (
                                    <p className="text-xs text-muted-foreground text-center">Free plan: 1 location · 50 customers · 10 requests/mo</p>
                                )}
                            </CardContent>
                        </Card>

                        {/* Pro */}
                        <Card className={`relative ${plan === 'pro' ? 'border-teal-300 ring-1 ring-teal-300' : 'border-teal-200'}`}>
                            <div className="absolute -top-3 left-1/2 -translate-x-1/2">
                                <Badge className="bg-teal-600 text-white hover:bg-teal-600">Most popular</Badge>
                            </div>
                            <CardHeader className="pb-3">
                                <div className="flex items-center justify-between">
                                    <CardTitle className="text-base font-semibold">Pro</CardTitle>
                                    {plan === 'pro' && <Badge className="bg-teal-100 text-teal-700 hover:bg-teal-100">Current</Badge>}
                                </div>
                                <div className="flex items-end gap-1">
                                    <span className="text-3xl font-bold text-foreground">$99</span>
                                    <span className="mb-1 text-sm text-muted-foreground">/month</span>
                                </div>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <ul className="space-y-2">
                                    {proFeatures.map((f) => (
                                        <li key={f} className="flex items-center gap-2 text-sm text-foreground">
                                            <CheckCircle className="h-4 w-4 shrink-0 text-teal-600" />
                                            {f}
                                        </li>
                                    ))}
                                </ul>
                                {plan !== 'pro' && (
                                    <Button
                                        className="w-full bg-teal-600 hover:bg-teal-700 text-white"
                                        onClick={() => handleSubscribe(prices.pro)}
                                        disabled={!prices.pro}
                                    >
                                        {plan === 'starter' ? 'Upgrade to Pro' : 'Get Pro'}
                                    </Button>
                                )}
                            </CardContent>
                        </Card>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
