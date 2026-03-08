import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { connect as googleConnect } from '@/routes/google';
import { connectGoogle as connectGoogleRoute } from '@/routes/onboarding';

interface Location {
    name: string;
    title: string;
    place_id: string;
}

interface Props {
    business: {
        id: number;
        name: string;
        google_place_id: string | null;
    };
    isGoogleConnected: boolean;
    locations: Location[];
}

export default function ConnectGoogle({ business, isGoogleConnected, locations }: Props) {
    const initialPlaceId = locations.length === 1 ? locations[0].place_id : (business.google_place_id ?? '');
    const [placeId, setPlaceId] = useState(initialPlaceId);
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

    const renderPlaceIdSection = () => {
        // Connected + single location auto-discovered
        if (isGoogleConnected && locations.length === 1) {
            return (
                <div className="space-y-3">
                    <div className="rounded-lg border border-green-200 bg-green-50 p-4">
                        <p className="text-sm font-medium text-green-800">Connected to Google Business Profile</p>
                        <p className="mt-1 text-sm text-green-700">
                            Found your business: <strong>{locations[0].title}</strong>
                        </p>
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="place-id">Google Place ID</Label>
                        <Input
                            id="place-id"
                            value={placeId}
                            onChange={(e) => setPlaceId(e.target.value)}
                            className="font-mono text-sm"
                        />
                        <p className="text-xs text-muted-foreground">Auto-filled from your Google Business Profile. You can edit this if needed.</p>
                    </div>
                </div>
            );
        }

        // Connected + multiple locations — show dropdown
        if (isGoogleConnected && locations.length > 1) {
            return (
                <div className="space-y-3">
                    <div className="rounded-lg border border-green-200 bg-green-50 p-4">
                        <p className="text-sm font-medium text-green-800">Connected to Google Business Profile</p>
                        <p className="mt-1 text-sm text-green-700">
                            We found {locations.length} locations — select yours below.
                        </p>
                    </div>
                    <div className="space-y-2">
                        <Label>Select your location</Label>
                        <Select onValueChange={(value) => setPlaceId(value)} defaultValue={placeId || undefined}>
                            <SelectTrigger>
                                <SelectValue placeholder="Choose a location..." />
                            </SelectTrigger>
                            <SelectContent>
                                {locations.map((location) => (
                                    <SelectItem key={location.name} value={location.place_id}>
                                        {location.title}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>
                </div>
            );
        }

        // Not connected or connected but no place IDs returned — manual input
        return (
            <>
                {!isGoogleConnected && (
                    <div className="mb-4">
                        <a href={googleConnect().url}>
                            <Button type="button" variant="outline" className="w-full border-border">
                                <svg className="mr-2 h-4 w-4" viewBox="0 0 24 24">
                                    <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" />
                                    <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" />
                                    <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" />
                                    <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" />
                                </svg>
                                Connect with Google
                            </Button>
                        </a>
                        <div className="my-4 flex items-center gap-3">
                            <div className="h-px flex-1 bg-border" />
                            <span className="text-xs text-muted-foreground">or enter manually</span>
                            <div className="h-px flex-1 bg-border" />
                        </div>
                    </div>
                )}

                {isGoogleConnected && (
                    <div className="mb-4 rounded-lg border border-amber-200 bg-amber-50 p-3">
                        <p className="text-sm text-amber-800">
                            Connected, but we couldn't retrieve your Place ID automatically. Please enter it below.
                        </p>
                    </div>
                )}

                <div className="mb-4 space-y-2">
                    <Label htmlFor="place-id">Your Google Place ID</Label>
                    <Input
                        id="place-id"
                        placeholder="e.g. ChIJN1t_tDeuEmsRUsoyG83frY4"
                        value={placeId}
                        onChange={(e) => setPlaceId(e.target.value)}
                        className="font-mono text-sm"
                    />
                    <p className="text-xs text-muted-foreground">This is a unique code that points customers directly to your Google review page.</p>
                </div>

                <Collapsible open={showInstructions} onOpenChange={setShowInstructions} className="mb-6">
                    <CollapsibleTrigger asChild>
                        <button
                            type="button"
                            className="flex w-full items-center justify-between rounded-lg border border-border bg-muted px-4 py-3 text-sm font-medium text-muted-foreground hover:bg-muted"
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
                        <div className="mt-2 rounded-lg border border-border bg-muted p-4">
                            <ol className="space-y-3 text-sm text-muted-foreground">
                                <li className="flex gap-3">
                                    <span className="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-teal-600 text-xs font-bold text-white">1</span>
                                    <span>
                                        Go to{' '}
                                        <a
                                            href="https://developers.google.com/maps/documentation/places/web-service/place-id"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            className="text-teal-600 underline"
                                        >
                                            Google's Place ID Finder
                                        </a>
                                    </span>
                                </li>
                                <li className="flex gap-3">
                                    <span className="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-teal-600 text-xs font-bold text-white">2</span>
                                    <span>
                                        Search for <strong>{business.name}</strong> in the search box
                                    </span>
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
            </>
        );
    };

    return (
        <>
            <Head title="Connect Google - Setup" />
            <div className="flex min-h-screen items-center justify-center bg-background p-4">
                <div className="w-full max-w-lg">
                    {/* Header */}
                    <div className="mb-8 text-center">
                        <div className="mb-4 flex items-center justify-center gap-2">
                            <div className="flex h-8 w-8 items-center justify-center rounded-full bg-teal-100 text-sm font-bold text-teal-600">✓</div>
                            <div className="h-px w-12 bg-teal-200" />
                            <div className="flex h-8 w-8 items-center justify-center rounded-full bg-teal-600 text-sm font-bold text-white">2</div>
                            <div className="h-px w-12 bg-border" />
                            <div className="flex h-8 w-8 items-center justify-center rounded-full bg-muted text-sm font-medium text-muted-foreground">3</div>
                        </div>
                        <p className="mb-1 text-sm font-medium text-teal-600">Step 2 of 3</p>
                        <h1 className="text-2xl font-bold text-foreground">Connect to Google — so customers can review you in one tap</h1>
                        <p className="mt-2 text-muted-foreground">This lets customers leave a review without searching for you on Google. Takes 30 seconds.</p>
                    </div>

                    <div className="rounded-xl bg-card p-6 shadow-sm ring-1 ring-border">
                        {/* Google logo */}
                        <div className="mb-6 flex items-center justify-center gap-3 rounded-lg border border-border bg-muted p-4">
                            <svg className="h-6 w-6" viewBox="0 0 24 24">
                                <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" />
                                <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" />
                                <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" />
                                <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" />
                            </svg>
                            <span className="text-sm font-medium text-foreground">Google Business Profile</span>
                        </div>

                        {renderPlaceIdSection()}

                        <Button
                            className="mt-2 w-full bg-teal-600 text-white hover:bg-teal-700"
                            onClick={handleSubmit}
                            disabled={processing || !placeId.trim()}
                        >
                            {processing ? 'Saving...' : 'Continue'}
                        </Button>

                        <div className="mt-3 text-center space-y-1">
                            <button
                                type="button"
                                className="text-sm text-muted-foreground hover:text-foreground"
                                onClick={() => router.visit('/onboarding/select-template')}
                            >
                                Skip for now
                            </button>
                            <p className="text-xs text-muted-foreground">You can connect Google later from Settings.</p>
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
}
