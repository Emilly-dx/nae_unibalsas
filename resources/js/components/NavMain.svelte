<script lang="ts">
    import { Link } from '@inertiajs/svelte';
    import {
        SidebarGroup,
        SidebarGroupLabel,
        SidebarMenu,
        SidebarMenuButton,
        SidebarMenuItem,
    } from '@/components/ui/sidebar';
    import { currentUrlState } from '@/lib/currentUrl.svelte';
    import { toUrl } from '@/lib/utils';
    import type { NavItem } from '@/types';

    let {
        items = [],
    }: {
        items: NavItem[];
    } = $props();

    const url = currentUrlState();
</script>

<SidebarGroup class="px-2 py-0">
    <SidebarGroupLabel>Plataforma</SidebarGroupLabel>
    <SidebarMenu>
        {#each items as item (toUrl(item.href))}
            <SidebarMenuItem>
                <SidebarMenuButton
                    asChild
                    isActive={url.isCurrentOrParentUrl(item.href, url.currentUrl)}
                    tooltip={item.title}
                >
                    {#snippet children(props)}
                        <Link
                            {...props}
                            href={toUrl(item.href)}
                            class={`${props.class} h-auto min-h-8`}
                        >
                            {#if item.icon}
                                <item.icon class="size-4 shrink-0" />
                            {/if}
                            <span class="leading-tight">{item.title}</span>
                            {#if item.badge}
                                <span
                                    class="ml-auto shrink-0 rounded border border-sidebar-border px-1.5 py-px font-mono text-[10px] text-sidebar-foreground/70 group-data-[collapsible=icon]:hidden"
                                >
                                    {item.badge}
                                </span>
                            {/if}
                        </Link>
                    {/snippet}
                </SidebarMenuButton>
            </SidebarMenuItem>
        {/each}
    </SidebarMenu>
</SidebarGroup>
