<script lang="ts">
    import { Link, page } from '@inertiajs/svelte';
    import CalendarDays from '@lucide/svelte/icons/calendar-days';
    import ChartColumn from '@lucide/svelte/icons/chart-column';
    import LayoutGrid from '@lucide/svelte/icons/layout-grid';
    import Route from '@lucide/svelte/icons/route';
    import Users from '@lucide/svelte/icons/users';
    import type { Snippet } from 'svelte';
    import AppLogo from '@/components/AppLogo.svelte';
    import NavMain from '@/components/NavMain.svelte';
    import NavUser from '@/components/NavUser.svelte';
    import {
        Sidebar,
        SidebarContent,
        SidebarFooter,
        SidebarHeader,
        SidebarMenu,
        SidebarMenuButton,
        SidebarMenuItem,
    } from '@/components/ui/sidebar';
    import { can } from '@/lib/permissions';
    import { toUrl } from '@/lib/utils';
    import { dashboard } from '@/routes';
    import { index as studentsIndex } from '@/routes/students';
    import type { NavItem } from '@/types';

    let {
        children,
    }: {
        children?: Snippet;
    } = $props();

    const mainNavItems: NavItem[] = [
        {
            title: 'Painel',
            href: dashboard(),
            icon: LayoutGrid,
        },
        {
            title: 'Estudantes',
            href: studentsIndex(),
            icon: Users,
            permission: 'students.view',
            badge: 'M3',
        },
        // Telas de demonstração (protótipo navegável, dados fictícios).
        {
            title: 'Triagem e atendimentos',
            href: '/triagem',
            icon: CalendarDays,
            badge: 'M4',
            demo: true,
        },
        {
            title: 'Encaminhamentos',
            href: '/encaminhamentos',
            icon: Route,
            badge: 'M6',
            demo: true,
        },
        {
            title: 'Relatórios',
            href: '/relatorios',
            icon: ChartColumn,
            badge: 'M8',
            demo: true,
        },
    ];

    const visibleNavItems = $derived(
        mainNavItems.filter(
            (item) =>
                (!item.demo || page.props.demoScreens) &&
                (!item.permission || can(page.props.auth, item.permission)),
        ),
    );
</script>

<Sidebar collapsible="icon" variant="inset">
    <SidebarHeader>
        <SidebarMenu>
            <SidebarMenuItem>
                <SidebarMenuButton size="lg" asChild>
                    {#snippet children(props)}
                        <Link
                            {...props}
                            href={toUrl(dashboard())}
                            class={props.class}
                        >
                            <AppLogo />
                        </Link>
                    {/snippet}
                </SidebarMenuButton>
            </SidebarMenuItem>
        </SidebarMenu>
    </SidebarHeader>

    <SidebarContent>
        <NavMain items={visibleNavItems} />
    </SidebarContent>

    <SidebarFooter>
        <NavUser />
    </SidebarFooter>
</Sidebar>
{@render children?.()}
