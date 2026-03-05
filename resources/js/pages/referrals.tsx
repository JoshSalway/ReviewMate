import { Head } from '@inertiajs/react';
import { useState } from 'react';
import AppLayout from '@/layouts/app-layout';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Referrals', href: '/referrals' },
];

interface Props {
    referralLink: string | null;
    stats: {
        total: number;
        signed_up: number;
        converted: number;
    };
    rewardsEarned: number;
    shareMessage?: string;
}

export default function Referrals({ referralLink, stats, rewardsEarned, shareMessage }: Props) {
    const [copiedLink, setCopiedLink] = useState(false);
    const [copiedMessage, setCopiedMessage] = useState(false);

    const handleCopyLink = () => {
        if (referralLink) {
            navigator.clipboard.writeText(referralLink).then(() => {
                setCopiedLink(true);
                setTimeout(() => setCopiedLink(false), 2000);
            });
        }
    };

    const handleCopyMessage = () => {
        if (shareMessage) {
            navigator.clipboard.writeText(shareMessage).then(() => {
                setCopiedMessage(true);
                setTimeout(() => setCopiedMessage(false), 2000);
            });
        }
    };

    const whatsappUrl = shareMessage
        ? `https://wa.me/?text=${encodeURIComponent(shareMessage)}`
        : null;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Referrals" />
            <div className="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
                <div>
                    <h1 className="text-2xl font-bold text-gray-900">Referral Program</h1>
                    <p className="mt-1 text-sm text-gray-500">
                        Share ReviewMate with other business owners — they get 1 month free, you get 1 month free.
                    </p>
                </div>

                <div className="max-w-2xl space-y-6">
                    {/* Stats */}
                    <div className="grid grid-cols-3 gap-4">
                        <Card>
                            <CardContent className="pt-6">
                                <div className="text-center">
                                    <div className="text-3xl font-bold text-gray-900">{stats.total}</div>
                                    <div className="mt-1 text-sm text-gray-500">Links shared</div>
                                </div>
                            </CardContent>
                        </Card>
                        <Card>
                            <CardContent className="pt-6">
                                <div className="text-center">
                                    <div className="text-3xl font-bold text-teal-600">{stats.signed_up}</div>
                                    <div className="mt-1 text-sm text-gray-500">Signed up</div>
                                </div>
                            </CardContent>
                        </Card>
                        <Card>
                            <CardContent className="pt-6">
                                <div className="text-center">
                                    <div className="text-3xl font-bold text-green-600">{rewardsEarned}</div>
                                    <div className="mt-1 text-sm text-gray-500">Months earned</div>
                                </div>
                            </CardContent>
                        </Card>
                    </div>

                    {/* Referral Link */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base font-semibold">Your Referral Link</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            {referralLink ? (
                                <>
                                    <div className="flex items-center gap-2">
                                        <code className="flex-1 rounded-md border border-gray-200 bg-gray-50 p-3 font-mono text-sm text-gray-800 break-all">
                                            {referralLink}
                                        </code>
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            onClick={handleCopyLink}
                                            className="shrink-0"
                                        >
                                            {copiedLink ? 'Copied!' : 'Copy'}
                                        </Button>
                                    </div>

                                    <div className="flex gap-2">
                                        {whatsappUrl && (
                                            <a
                                                href={whatsappUrl}
                                                target="_blank"
                                                rel="noopener noreferrer"
                                            >
                                                <Button variant="outline" size="sm" className="text-green-600 border-green-200 hover:bg-green-50">
                                                    Share on WhatsApp
                                                </Button>
                                            </a>
                                        )}
                                        {shareMessage && (
                                            <Button variant="outline" size="sm" onClick={handleCopyMessage}>
                                                {copiedMessage ? 'Message copied!' : 'Copy message'}
                                            </Button>
                                        )}
                                    </div>
                                </>
                            ) : (
                                <p className="text-sm text-gray-500 italic">
                                    Complete your business setup to get your referral link.
                                </p>
                            )}
                        </CardContent>
                    </Card>

                    {/* How it works */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base font-semibold">How it works</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <ol className="space-y-3 text-sm text-gray-600 list-decimal list-inside">
                                <li>Share your unique referral link with a fellow business owner</li>
                                <li>They sign up using your link and get <strong>1 month free</strong> automatically</li>
                                <li>When they convert to a paid plan, you get <strong>1 month free</strong> added to your account</li>
                                <li>No limits — refer as many businesses as you like</li>
                            </ol>

                            <div className="mt-4 rounded-lg border border-blue-100 bg-blue-50 p-4">
                                <p className="text-sm text-blue-700">
                                    <strong>Pro tip:</strong> Customers who leave you a review also receive a referral invite email automatically.
                                    If they sign up a business they own, you'll earn a reward too.
                                </p>
                            </div>

                            <div className="mt-3 flex items-center gap-2">
                                <Badge className="bg-teal-50 text-teal-700 hover:bg-teal-50 text-xs">Pro Plan Feature</Badge>
                                <span className="text-xs text-gray-500">Automated customer referral invites require a Pro plan</span>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}
