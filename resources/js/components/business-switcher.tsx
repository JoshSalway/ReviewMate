import { router, usePage } from '@inertiajs/react';
import { Building2, ChevronsUpDown, Plus, Check } from 'lucide-react';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    useSidebar,
} from '@/components/ui/sidebar';
import { switchMethod as switchBus } from '@/routes/businesses';

interface Business {
    id: number;
    name: string;
    type: string;
    is_current: boolean;
}

export function BusinessSwitcher() {
    const { businesses: businessList, currentBusiness } = usePage<{
        businesses: Business[];
        currentBusiness: { id: number; name: string } | null;
    }>().props;

    const { state } = useSidebar();

    const switchBusiness = (id: number) => {
        router.post(switchBus({ business: id }).url, {}, {
            preserveScroll: false,
        });
    };

    if (!currentBusiness) return null;

    return (
        <SidebarMenu>
            <SidebarMenuItem>
                <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                        <SidebarMenuButton
                            size="lg"
                            className="data-[state=open]:bg-sidebar-accent data-[state=open]:text-sidebar-accent-foreground"
                        >
                            <div className="flex aspect-square size-8 items-center justify-center rounded-lg bg-teal-600 text-white">
                                <Building2 className="size-4" />
                            </div>
                            <div className="grid flex-1 text-left text-sm leading-tight">
                                <span className="truncate font-semibold">{currentBusiness.name}</span>
                                <span className="truncate text-xs text-muted-foreground">ReviewMate</span>
                            </div>
                            <ChevronsUpDown className="ml-auto" />
                        </SidebarMenuButton>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent
                        className="w-(--radix-dropdown-menu-trigger-width) min-w-56 rounded-lg"
                        align="start"
                        side={state === 'collapsed' ? 'right' : 'bottom'}
                        sideOffset={4}
                    >
                        <DropdownMenuLabel className="text-xs text-muted-foreground">
                            Your businesses
                        </DropdownMenuLabel>
                        {businessList.map((business) => (
                            <DropdownMenuItem
                                key={business.id}
                                onClick={() => switchBusiness(business.id)}
                                className="gap-2 p-2"
                            >
                                <div className="flex size-6 items-center justify-center rounded-sm border bg-teal-50">
                                    <Building2 className="size-4 shrink-0 text-teal-600" />
                                </div>
                                <span className="flex-1 truncate">{business.name}</span>
                                {business.is_current && (
                                    <Check className="size-4 text-teal-600" />
                                )}
                            </DropdownMenuItem>
                        ))}
                        <DropdownMenuSeparator />
                        <DropdownMenuItem
                            className="gap-2 p-2"
                            onClick={() => router.get('/onboarding/business-type')}
                        >
                            <div className="flex size-6 items-center justify-center rounded-md border bg-background">
                                <Plus className="size-4" />
                            </div>
                            <span className="text-muted-foreground">Add business</span>
                        </DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>
            </SidebarMenuItem>
        </SidebarMenu>
    );
}
