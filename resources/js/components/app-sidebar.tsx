import { Link, usePage } from '@inertiajs/react';
import {
    Briefcase,
    Cpu,
    LayoutGrid,
    Lock,
    Send,
    ShieldCheck,
    UserRound,
} from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import { TeamSwitcher } from '@/components/team-switcher';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import { index as applicationsIndex } from '@/routes/applications';
import { index as assistantIndex } from '@/routes/assistant';
import { edit as candidateEdit } from '@/routes/candidate';
import { index as draftsIndex } from '@/routes/drafts';
import { index as jobsIndex } from '@/routes/jobs';
import type { NavItem } from '@/types';

export function AppSidebar() {
    const page = usePage();
    const dashboardUrl = page.props.currentTeam
        ? dashboard(page.props.currentTeam.slug)
        : '/';

    const pendingDrafts = page.props.jobpilot?.pendingDrafts ?? 0;

    const mainNavItems: NavItem[] = [
        {
            title: 'Dashboard',
            href: dashboardUrl,
            icon: LayoutGrid,
        },
        {
            title: 'Job Discovery',
            href: jobsIndex(),
            icon: Briefcase,
        },
        {
            title: 'Review & Approval',
            href: draftsIndex(),
            icon: ShieldCheck,
            badge: pendingDrafts > 0 ? pendingDrafts : null,
        },
        {
            title: 'Application Tracker',
            href: applicationsIndex(),
            icon: Send,
        },
    ];

    const candidateNavItems: NavItem[] = [
        {
            title: 'Candidate Profile',
            href: candidateEdit(),
            icon: UserRound,
        },
        {
            title: 'System & Local AI',
            href: assistantIndex(),
            icon: Cpu,
        },
    ];

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboardUrl} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <TeamSwitcher />
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={mainNavItems} label="JobPilot" />
                <NavMain items={candidateNavItems} label="Candidate" />
            </SidebarContent>

            <SidebarFooter>
                <div className="mx-2 mt-auto rounded-lg border border-sidebar-border/70 p-3 text-xs group-data-[collapsible=icon]:hidden">
                    <div className="flex items-center gap-1.5 font-medium text-amber-600 dark:text-amber-400">
                        <Lock className="size-3" />
                        Human-in-the-loop gate
                    </div>
                    <p className="mt-1 text-muted-foreground">
                        Nothing is sent without your explicit, hash-bound
                        approval.
                    </p>
                </div>
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
