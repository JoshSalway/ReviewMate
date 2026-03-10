import { Head } from '@inertiajs/react';

interface SeoProps {
    title: string;
    description: string;
    image?: string;
    url?: string;
    type?: string;
}

export function SeoHead({ title, description, image, url, type = 'website' }: SeoProps) {
    const siteName = 'ReviewMate';
    const fullTitle = title.includes('ReviewMate') ? title : `${title} | ${siteName}`;
    const defaultImage = '/og-image.png';

    return (
        <Head title={fullTitle}>
            <meta name="description" content={description} />

            <meta property="og:title" content={fullTitle} />
            <meta property="og:description" content={description} />
            <meta property="og:type" content={type} />
            <meta property="og:site_name" content={siteName} />
            {url && <meta property="og:url" content={url} />}
            <meta property="og:image" content={image ?? defaultImage} />

            <meta name="twitter:card" content="summary_large_image" />
            <meta name="twitter:title" content={fullTitle} />
            <meta name="twitter:description" content={description} />
            <meta name="twitter:image" content={image ?? defaultImage} />
        </Head>
    );
}
