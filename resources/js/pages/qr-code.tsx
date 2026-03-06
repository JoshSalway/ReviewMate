import { Head } from '@inertiajs/react';
import { Download, Copy, Check, QrCode } from 'lucide-react';
import { QRCodeSVG, QRCodeCanvas } from 'qrcode.react';
import { useState, useRef, useCallback } from 'react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import { qrCode } from '@/routes';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'QR Code', href: qrCode().url },
];

interface Props {
    business: {
        id: number;
        name: string;
        google_review_url: string;
    };
}

type QrSize = 'small' | 'medium' | 'large';
type QrStyle = 'teal' | 'black';

const sizeMap: Record<QrSize, number> = { small: 150, medium: 220, large: 300 };

export default function QrCodePage({ business }: Props) {
    const [size, setSize] = useState<QrSize>('medium');
    const [style, setStyle] = useState<QrStyle>('teal');
    const [showName, setShowName] = useState(true);
    const [copied, setCopied] = useState(false);
    const canvasRef = useRef<HTMLDivElement>(null);

    const fgColor = style === 'teal' ? '#0d9488' : '#000000';
    const qrSize = sizeMap[size];
    const url = business.google_review_url;

    const copyLink = useCallback(async () => {
        await navigator.clipboard.writeText(url);
        setCopied(true);
        setTimeout(() => setCopied(false), 2000);
    }, [url]);

    const downloadPng = useCallback(() => {
        const canvas = canvasRef.current?.querySelector('canvas');
        if (!canvas) return;
        const link = document.createElement('a');
        link.download = `${business.name.toLowerCase().replace(/\s+/g, '-')}-qr-code.png`;
        link.href = canvas.toDataURL('image/png');
        link.click();
    }, [business.name]);

    const useCaseItems = [
        { icon: '🧾', title: 'Receipts', description: 'Print on receipts or invoices' },
        { icon: '🪧', title: 'Counter display', description: 'Stand-alone display at checkout' },
        { icon: '💳', title: 'Business cards', description: 'Include on your business card' },
        { icon: '📱', title: 'Social media', description: 'Share in Instagram bio or stories' },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="QR Code" />
            <div className="flex flex-1 flex-col gap-6 p-6">
                <div>
                    <h1 className="text-2xl font-bold text-gray-900">QR Code</h1>
                    <p className="mt-1 text-sm text-gray-500">
                        Generate a QR code customers can scan to leave a review.
                    </p>
                </div>

                <div className="grid gap-6 lg:grid-cols-2">
                    {/* Configuration */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Customise</CardTitle>
                            <CardDescription>Configure your QR code appearance</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-5">
                            <div className="space-y-1.5">
                                <Label>Review link</Label>
                                <div className="flex items-center gap-2">
                                    <code className="flex-1 truncate rounded-md bg-gray-100 px-3 py-2 text-xs text-gray-700">
                                        {url === '#' ? 'Set your Google Place ID in Business Settings' : url}
                                    </code>
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        onClick={copyLink}
                                        disabled={url === '#'}
                                        className="shrink-0"
                                    >
                                        {copied ? <Check className="h-4 w-4 text-green-600" /> : <Copy className="h-4 w-4" />}
                                    </Button>
                                </div>
                            </div>

                            <div className="space-y-1.5">
                                <Label>Size</Label>
                                <Select value={size} onValueChange={(v) => setSize(v as QrSize)}>
                                    <SelectTrigger>
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="small">Small (150px)</SelectItem>
                                        <SelectItem value="medium">Medium (220px)</SelectItem>
                                        <SelectItem value="large">Large (300px)</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <div className="space-y-1.5">
                                <Label>Style</Label>
                                <div className="flex gap-3">
                                    {(['teal', 'black'] as QrStyle[]).map((s) => (
                                        <button
                                            key={s}
                                            onClick={() => setStyle(s)}
                                            className={`flex flex-1 items-center justify-center gap-2 rounded-lg border-2 py-2.5 text-sm font-medium transition-all ${
                                                style === s
                                                    ? 'border-teal-600 bg-teal-50 text-teal-700'
                                                    : 'border-gray-200 text-gray-600 hover:border-gray-300'
                                            }`}
                                        >
                                            <span
                                                className="h-4 w-4 rounded-full"
                                                style={{ backgroundColor: s === 'teal' ? '#0d9488' : '#000000' }}
                                            />
                                            {s === 'teal' ? 'Teal branded' : 'Black & white'}
                                        </button>
                                    ))}
                                </div>
                            </div>

                            <div className="flex items-center justify-between rounded-lg border border-gray-200 px-4 py-3">
                                <div>
                                    <p className="text-sm font-medium text-gray-900">Show business name</p>
                                    <p className="text-xs text-gray-500">Display name below the QR code</p>
                                </div>
                                <button
                                    onClick={() => setShowName(!showName)}
                                    className={`relative inline-flex h-6 w-11 items-center rounded-full transition-colors ${
                                        showName ? 'bg-teal-600' : 'bg-gray-200'
                                    }`}
                                >
                                    <span
                                        className={`inline-block h-4 w-4 rounded-full bg-white shadow transition-transform ${
                                            showName ? 'translate-x-6' : 'translate-x-1'
                                        }`}
                                    />
                                </button>
                            </div>

                            <div className="flex gap-3 pt-2">
                                <Button
                                    onClick={downloadPng}
                                    disabled={url === '#'}
                                    className="flex-1 bg-teal-600 hover:bg-teal-700"
                                >
                                    <Download className="mr-2 h-4 w-4" />
                                    Download PNG
                                </Button>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Preview */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Preview</CardTitle>
                            <CardDescription>How your QR code will look when printed</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="flex flex-col items-center justify-center rounded-xl border-2 border-dashed border-gray-200 bg-white p-8">
                                {url === '#' ? (
                                    <div className="flex flex-col items-center gap-3 text-center">
                                        <QrCode className="h-16 w-16 text-gray-300" />
                                        <p className="text-sm text-gray-500">
                                            Set your Google Place ID in Business Settings to generate a QR code.
                                        </p>
                                    </div>
                                ) : (
                                    <>
                                        {/* Hidden canvas for PNG download */}
                                        <div ref={canvasRef} className="hidden">
                                            <QRCodeCanvas
                                                value={url}
                                                size={qrSize}
                                                fgColor={fgColor}
                                                level="H"
                                            />
                                        </div>

                                        {/* Visible SVG preview */}
                                        <QRCodeSVG
                                            value={url}
                                            size={qrSize}
                                            fgColor={fgColor}
                                            level="H"
                                            className="rounded-lg"
                                        />

                                        {showName && (
                                            <p
                                                className="mt-4 text-center font-semibold"
                                                style={{ color: fgColor, fontSize: qrSize > 200 ? '16px' : '13px' }}
                                            >
                                                {business.name}
                                            </p>
                                        )}

                                        <p className="mt-2 text-xs text-gray-400">Scan to leave a Google review</p>
                                    </>
                                )}
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* Use case ideas */}
                <Card>
                    <CardHeader>
                        <CardTitle>Where to use your QR code</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                            {useCaseItems.map((item) => (
                                <div key={item.title} className="flex items-start gap-3 rounded-lg border border-gray-100 bg-gray-50 p-4">
                                    <span className="text-2xl">{item.icon}</span>
                                    <div>
                                        <p className="text-sm font-medium text-gray-900">{item.title}</p>
                                        <p className="mt-0.5 text-xs text-gray-500">{item.description}</p>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
