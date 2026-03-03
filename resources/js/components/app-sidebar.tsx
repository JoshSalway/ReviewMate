import { LayoutGrid, Send, Users, FileText, Zap, Settings, QrCode, GitBranch, Star, CreditCard, Bell, BarChart2 } from 'lucide-react';
import { BusinessSwitcher } from '@/components/business-switcher';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
} from '@/components/ui/sidebar';
import { dashboard, qrCode, emailFlow, analytics } from '@/routes';
import * as reviews from '@/routes/reviews';
import * as customers from '@/routes/customers';
import * as requests from '@/routes/requests';
import * as templates from '@/routes/templates';
import * as quickSend from '@/routes/quick-send';
import * as settingsRoutes from '@/routes/settings';
import type { NavItem } from '@/types';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'Quick Send',
        href: quickSend.index(),
        icon: Zap,
    },
    {
        title: 'Customers',
        href: customers.index(),
        icon: Users,
    },
    {
        title: 'Requests',
        href: requests.index(),
        icon: Send,
    },
    {
        title: 'Reviews',
        href: reviews.index(),
        icon: Star,
    },
    {
        title: 'Templates',
        href: templates.index(),
        icon: FileText,
    },
    {
        title: 'QR Code',
        href: qrCode(),
        icon: QrCode,
    },
    {
        title: 'Email Flow',
        href: emailFlow(),
        icon: GitBranch,
    },
    {
        title: 'Analytics',
        href: analytics(),
        icon: BarChart2,
    },
];

const footerNavItems: NavItem[] = [
    {
        title: 'Business Settings',
        href: settingsRoutes.business(),
        icon: Settings,
    },
    {
        title: 'Billing',
        href: settingsRoutes.billing(),
        icon: CreditCard,
    },
    {
        title: 'Notifications',
        href: settingsRoutes.notifications(),
        icon: Bell,
    },
];

export function AppSidebar() {
    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <BusinessSwitcher />
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={mainNavItems} />
            </SidebarContent>

            <SidebarFooter>
                <NavMain items={footerNavItems} />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
