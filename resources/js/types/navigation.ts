import type { LinkComponentBaseProps } from '@inertiajs/core';
import type { Component, SvelteComponent } from 'svelte';

type NavIcon =
    | Component<{ class?: string }>
    | (new (...args: any[]) => SvelteComponent<{ class?: string }>);

export type BreadcrumbItem = {
    title: string;
    href: NonNullable<LinkComponentBaseProps['href']>;
};

export type NavItem = {
    title: string;
    href: NonNullable<LinkComponentBaseProps['href']>;
    icon?: NavIcon;
    isActive?: boolean;
    /** Permissão exigida para exibir o item (apenas UI). */
    permission?: string;
    /** Marco do roadmap exibido ao lado do item (ex.: M3). */
    badge?: string;
    /** Item de tela de demonstração (some quando NAE_DEMO_SCREENS=false). */
    demo?: boolean;
};
