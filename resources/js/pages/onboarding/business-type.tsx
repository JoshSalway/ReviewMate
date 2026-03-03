import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import { businessType as businessTypeRoute } from '@/routes/onboarding';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent } from '@/components/ui/card';

const businessTypes = [
    { value: 'tradie', label: 'Tradie', emoji: '🔧', description: 'Plumber, electrician, builder' },
    { value: 'cafe', label: 'Cafe / Restaurant', emoji: '☕', description: 'Food & beverage' },
    { value: 'salon', label: 'Salon / Barber', emoji: '💇', description: 'Hair, beauty, nails' },
    { value: 'healthcare', label: 'Healthcare', emoji: '🏥', description: 'Doctor, dentist, physio' },
    { value: 'real_estate', label: 'Real Estate', emoji: '🏠', description: 'Agent, property manager' },
    { value: 'retail', label: 'Retail', emoji: '🛍️', description: 'Shop, boutique, store' },
    { value: 'pet_services', label: 'Pet Services', emoji: '🐾', description: 'Vet, groomer, trainer' },
    { value: 'fitness', label: 'Fitness', emoji: '💪', description: 'Gym, personal trainer, yoga' },
    { value: 'other', label: 'Other', emoji: '💼', description: 'Any other business' },
];

export default function BusinessType() {
    const [form, setForm] = useState({
        name: '',
        owner_name: '',
        type: '',
    });
    const [processing, setProcessing] = useState(false);

    const handleSubmit = () => {
        if (!form.name || !form.type) return;
        setProcessing(true);
        router.post(
            businessTypeRoute().url,
            form,
            {
                onFinish: () => setProcessing(false),
            },
        );
    };

    return (
        <>
            <Head title="Business Type - Setup" />
            <div className="flex min-h-screen items-center justify-center bg-gray-50 p-4">
                <div className="w-full max-w-2xl">
                    {/* Header */}
                    <div className="mb-8 text-center">
                        <div className="mb-4 flex items-center justify-center gap-2">
                            <div className="flex h-8 w-8 items-center justify-center rounded-full bg-teal-600 text-sm font-bold text-white">1</div>
                            <div className="h-px w-12 bg-gray-200" />
                            <div className="flex h-8 w-8 items-center justify-center rounded-full bg-gray-200 text-sm font-medium text-gray-400">2</div>
                            <div className="h-px w-12 bg-gray-200" />
                            <div className="flex h-8 w-8 items-center justify-center rounded-full bg-gray-200 text-sm font-medium text-gray-400">3</div>
                        </div>
                        <p className="mb-1 text-sm font-medium text-teal-600">Step 1 of 3</p>
                        <h1 className="text-2xl font-bold text-gray-900">Tell us about your business</h1>
                        <p className="mt-2 text-gray-500">We'll customise your review request templates to match your industry.</p>
                    </div>

                    <div className="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
                        {/* Business Name & Owner */}
                        <div className="mb-6 grid gap-4 sm:grid-cols-2">
                            <div className="space-y-2">
                                <Label htmlFor="business-name">Business Name</Label>
                                <Input
                                    id="business-name"
                                    placeholder="e.g. Smith's Plumbing"
                                    value={form.name}
                                    onChange={(e) => setForm({ ...form, name: e.target.value })}
                                />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="owner-name">Your Name</Label>
                                <Input
                                    id="owner-name"
                                    placeholder="e.g. John Smith"
                                    value={form.owner_name}
                                    onChange={(e) => setForm({ ...form, owner_name: e.target.value })}
                                />
                            </div>
                        </div>

                        {/* Business Type Grid */}
                        <div className="mb-6">
                            <Label className="mb-3 block">What type of business are you?</Label>
                            <div className="grid grid-cols-3 gap-3">
                                {businessTypes.map((type) => (
                                    <button
                                        key={type.value}
                                        type="button"
                                        onClick={() => setForm({ ...form, type: type.value })}
                                        className={`rounded-xl border-2 p-4 text-left transition ${
                                            form.type === type.value
                                                ? 'border-teal-600 bg-teal-50 shadow-sm'
                                                : 'border-gray-200 hover:border-gray-300 hover:bg-gray-50'
                                        }`}
                                    >
                                        <div className="mb-1 text-2xl">{type.emoji}</div>
                                        <div className={`text-sm font-semibold ${form.type === type.value ? 'text-teal-700' : 'text-gray-800'}`}>
                                            {type.label}
                                        </div>
                                        <div className="mt-0.5 text-xs text-gray-400">{type.description}</div>
                                    </button>
                                ))}
                            </div>
                        </div>

                        <Button
                            className="w-full bg-teal-600 hover:bg-teal-700 text-white"
                            onClick={handleSubmit}
                            disabled={processing || !form.name || !form.type}
                        >
                            {processing ? 'Continuing...' : 'Continue'}
                        </Button>
                    </div>
                </div>
            </div>
        </>
    );
}
