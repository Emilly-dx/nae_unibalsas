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
    import { index as triageIndex } from '@/routes/triage';
    import { index as referralsIndex } from '@/routes/referrals';
    import { index as agendaIndex } from '@/routes/agenda';
    import { index as reportsIndex } from '@/routes/reports';
    import CalendarClock from '@lucide/svelte/icons/calendar-clock';
    import ScrollText from '@lucide/svelte/icons/scroll-text';
    import ShieldCheck from '@lucide/svelte/icons/shield-check';
    import UserCog from '@lucide/svelte/icons/user-cog';
    import { index as auditIndex } from '@/routes/admin/audit';
    import { index as rolesIndex } from '@/routes/admin/roles';
    import { index as usersIndex } from '@/routes/admin/users';
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
        },
        {
            title: 'Triagem e atendimentos',
            href: triageIndex(),
            icon: CalendarDays,
            permission: 'attendances.view',
        },
        {
            title: 'Agenda',
            href: agendaIndex(),
            icon: CalendarClock,
            permission: 'attendances.view',
        },
        {
            title: 'Encaminhamentos',
            href: referralsIndex(),
            icon: Route,
            permission: 'referrals.view',
        },
        {
            title: 'Relatórios',
            href: reportsIndex(),
            icon: ChartColumn,
            permission: 'reports.view',
        },
    ];

    // Administração: contas da equipe, perfis de acesso e auditoria.
    const adminNavItems: NavItem[] = [
        { title: 'Usuários', href: usersIndex(), icon: UserCog, permission: 'users.view' },
        { title: 'Perfis de acesso', href: rolesIndex(), icon: ShieldCheck, permission: 'acl.manage_permissions' },
        { title: 'Auditoria', href: auditIndex(), icon: ScrollText, permission: 'audit.view' },
    ];

    const visible = (items: NavItem[]): NavItem[] =>
        items.filter((item) => !item.permission || can(page.props.auth, item.permission));

    const visibleNavItems = $derived(visible(mainNavItems));
    const visibleAdminItems = $derived(visible(adminNavItems));
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
        {#if visibleAdminItems.length > 0}
            <NavMain items={visibleAdminItems} label="Administração" />
        {/if}
    </SidebarContent>

    <SidebarFooter>
        <NavUser />
    </SidebarFooter>
</Sidebar>
{@render children?.()}
