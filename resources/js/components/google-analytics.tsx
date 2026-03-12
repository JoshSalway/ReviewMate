import { useEffect } from 'react';
import { router } from '@inertiajs/react';

const GA_ID = import.meta.env.VITE_GA4_MEASUREMENT_ID as string | undefined;
const IS_PROD = import.meta.env.PROD as boolean;

function gtag(...args: unknown[]): void {
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    (window as any).dataLayer = (window as any).dataLayer ?? [];
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    (window as any).dataLayer.push(args);
}

export function GoogleAnalytics() {
    useEffect(() => {
        if (!IS_PROD || !GA_ID) return;

        // Inject the gtag.js script only once
        if (!document.getElementById('ga4-script')) {
            const script = document.createElement('script');
            script.id = 'ga4-script';
            script.src = `https://www.googletagmanager.com/gtag/js?id=${GA_ID}`;
            script.async = true;
            document.head.appendChild(script);
        }

        // Bootstrap gtag
        gtag('js', new Date());
        gtag('config', GA_ID, { send_page_view: false });

        // Fire a page_view on every Inertia navigation (including the initial one)
        const removeListener = router.on('navigate', (event) => {
            gtag('event', 'page_view', {
                page_title: document.title,
                page_location: event.detail.page.url,
            });
        });

        return () => {
            removeListener();
        };
    }, []);

    return null;
}
