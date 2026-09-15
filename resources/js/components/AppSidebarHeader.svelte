<script lang="ts">
    import { page } from '@inertiajs/svelte';
    import Breadcrumbs from '@/components/Breadcrumbs.svelte';
    import {
        Avatar,
        AvatarFallback,
        AvatarImage,
    } from '@/components/ui/avatar';
    import { Button } from '@/components/ui/button';
    import {
        DropdownMenu,
        DropdownMenuContent,
        DropdownMenuTrigger,
    } from '@/components/ui/dropdown-menu';
    import { SidebarTrigger } from '@/components/ui/sidebar';
    import UserMenuContent from '@/components/UserMenuContent.svelte';
    import { getInitials } from '@/lib/initials';
    import type { BreadcrumbItem } from '@/types';

    let {
        breadcrumbs = [],
    }: {
        breadcrumbs?: BreadcrumbItem[];
    } = $props();

    const auth = $derived(page.props.auth);
</script>

<header
    class="flex h-16 shrink-0 items-center justify-between gap-2 border-b border-sidebar-border/70 px-6 transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 md:px-4"
>
    <div class="flex min-w-0 items-center gap-2">
        <SidebarTrigger class="-ml-1" />
        {#if breadcrumbs && breadcrumbs.length > 0}
            <div class="hidden min-w-0 sm:block">
                <Breadcrumbs {breadcrumbs} />
            </div>
        {/if}
    </div>

    {#if auth.user}
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                {#snippet children(props)}
                    <Button
                        variant="ghost"
                        size="icon"
                        class="relative size-10 w-auto shrink-0 rounded-full p-1 focus-within:ring-2 focus-within:ring-primary"
                        onclick={props.onclick}
                        aria-expanded={props['aria-expanded']}
                        data-state={props['data-state']}
                    >
                        <Avatar class="size-8 overflow-hidden rounded-full">
                            {#if auth.user.avatar}
                                <AvatarImage
                                    src={auth.user.avatar}
                                    alt={auth.user.name}
                                />
                            {/if}
                            <AvatarFallback
                                class="rounded-full bg-sidebar-accent font-semibold text-sidebar-accent-foreground"
                            >
                                {getInitials(auth.user.name)}
                            </AvatarFallback>
                        </Avatar>
                    </Button>
                {/snippet}
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" class="w-56">
                <UserMenuContent user={auth.user} />
            </DropdownMenuContent>
        </DropdownMenu>
    {/if}
</header>

{#if breadcrumbs && breadcrumbs.length > 0}
    <div class="border-b border-sidebar-border/70 px-6 py-2 sm:hidden">
        <Breadcrumbs {breadcrumbs} />
    </div>
{/if}
