import { AppContent } from '@/components/app-content';
import { AppShell } from '@/components/app-shell';
import { AppSidebar } from '@/components/app-sidebar';
import { AppSidebarHeader } from '@/components/app-sidebar-header';
import type { AppLayoutProps } from '@/types';

export default function AppSidebarLayout({
    children,
    breadcrumbs = [],
}: AppLayoutProps) {
    return (
        <AppShell variant="sidebar">
            <AppSidebar />
            <AppContent variant="sidebar" className="overflow-x-hidden">
                <AppSidebarHeader breadcrumbs={breadcrumbs} />
                {children}
                <footer className="mt-auto border-t border-gray-100 px-4 py-4 md:px-6">
                    <p className="text-xs text-gray-400">
                        &copy; {new Date().getFullYear()} ReviewMate &middot;{' '}
                        <a href="/terms" className="hover:text-gray-600 transition-colors">
                            Terms
                        </a>{' '}
                        &middot;{' '}
                        <a href="/privacy" className="hover:text-gray-600 transition-colors">
                            Privacy
                        </a>
                    </p>
                </footer>
            </AppContent>
        </AppShell>
    );
}
