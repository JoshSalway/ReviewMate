import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import { connectGoogle as connectGoogleRoute } from '@/routes/onboarding';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible';

interface Props {
    business: {
        id: number;
        name: string;
    };
}

export default function ConnectGoogle({ business }: Props) {
    const [placeId, setPlaceId] = useState('');
    const [processing, setProcessing] = useState(false);
    const [showInstructions, setShowInstructions] = useState(false);

    const handleSubmit = () => {
        if (!placeId.trim()) return;
        setProcessing(true);
        router.post(
            connectGoogleRoute().url,
            { google_place_id: placeId },
            {
                onFinish: () => setProcessing(false),
            },
        );
    };

    return (
        <>
            <Head title="Connect Google - Setup" />
            <div className="flex min-h-screen items-center justify-center bg-gray-50 p-4">
                <div className="w-full max-w-lg">
                    {/* Header */}
                    <div className="mb-8 text-center">
                        <div className="mb-4 flex items-center justify-center gap-2">
                            <div className="flex h-8 w-8 items-center justify-center rounded-full bg-teal-100 text-sm font-bold text-teal-600">✓</div>
                            <div className="h-px w-12 bg-teal-200" />
                            <div className="flex h-8 w-8 items-center justify-center rounded-full bg-teal-600 text-sm font-bold text-white">2</div>
                            <div className="h-px w-12 bg-gray-200" />
                            <div className="flex h-8 w-8 items-center justify-center rounded-full bg-gray-200 text-sm font-medium text-gray-400">3</div>
                        </div>
                        <p className="mb-1 text-sm font-medium text-teal-600">Step 2 of 3</p>
                        <h1 className="text-2xl font-bold text-gray-900">Connect your Google Business</h1>
                        <p className="mt-2 text-gray-500">
                            We need your Google Place ID to send customers directly to your review page.
                        </p>
                    </div>

                    <div className="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
                        {/* Google logo */}
                        <div className="mb-6 flex items-center justify-center gap-3 rounded-lg border border-gray-100 bg-gray-50 p-4">
                            <svg className="h-6 w-6" viewBox="0 0 24 24">
                                <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                                <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                                <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                                <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                            </svg>
                            <span className="text-sm font-medium text-gray-700">Google Business Profile</span>
                        </div>

                        <div className="mb-4 space-y-2">
                            <Label htmlFor="place-id">Google Place ID</Label>
                            <Input
                                id="place-id"
                                placeholder="e.g. ChIJN1t_tDeuEmsRUsoyG83frY4"
                                value={placeId}
                                onChange={(e) => setPlaceId(e.target.value)}
                                className="font-mono text-sm"
                            />
                            <p className="text-xs text-gray-400">
                                Your Place ID uniquely identifies your business on Google Maps.
                            </p>
                        </div>

                        {/* Instructions Collapsible */}
                        <Collapsible open={showInstructions} onOpenChange={setShowInstructions} className="mb-6">
                            <CollapsibleTrigger asChild>
                                <button
                                    type="button"
                                    className="flex w-full items-center justify-between rounded-lg border border-gray-200 bg-gray-50 px-4 py-3 text-sm font-medium text-gray-600 hover:bg-gray-100"
                                >
                                    <span>How to find your Place ID</span>
                                    <svg
                                        className={`h-4 w-4 transition-transform ${showInstructions ? 'rotate-180' : ''}`}
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
                                    >
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 9l-7 7-7-7" />
                                    </svg>
                                </button>
                            </CollapsibleTrigger>
                            <CollapsibleContent>
                                <div className="mt-2 rounded-lg border border-gray-200 bg-gray-50 p-4">
                                    <ol className="space-y-3 text-sm text-gray-600">
                                        <li className="flex gap-3">
                                            <span className="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-teal-600 text-xs font-bold text-white">1</span>
                                            <span>Go to <a href="https://developers.google.com/maps/documentation/places/web-service/place-id" target="_blank" rel="noopener noreferrer" className="text-teal-600 underline">Google's Place ID Finder</a></span>
                                        </li>
                                        <li className="flex gap-3">
                                            <span className="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-teal-600 text-xs font-bold text-white">2</span>
                                            <span>Search for <strong>{business.name}</strong> in the search box</span>
                                        </li>
                                        <li className="flex gap-3">
                                            <span className="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-teal-600 text-xs font-bold text-white">3</span>
                                            <span>Click on your business in the results — the Place ID will appear below the map</span>
                                        </li>
                                        <li className="flex gap-3">
                                            <span className="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-teal-600 text-xs font-bold text-white">4</span>
                                            <span>Copy the Place ID (it starts with "ChIJ...") and paste it above</span>
                                        </li>
                                    </ol>
                                </div>
                            </CollapsibleContent>
                        </Collapsible>

                        <Button
                            className="w-full bg-teal-600 hover:bg-teal-700 text-white"
                            onClick={handleSubmit}
                            disabled={processing || !placeId.trim()}
                        >
                            {processing ? 'Connecting...' : 'Connect Google Business'}
                        </Button>

                        <div className="mt-3 text-center">
                            <button
                                type="button"
                                className="text-sm text-gray-400 hover:text-gray-600"
                                onClick={() => router.visit('/onboarding/select-template')}
                            >
                                Skip for now
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
}
