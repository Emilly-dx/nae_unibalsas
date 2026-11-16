<script lang="ts">
    import type { Component } from 'svelte';

    let {
        title,
        value,
        hint = '',
        icon: Icon,
    }: {
        title: string;
        value: string | number;
        hint?: string;
        icon: Component<{ class?: string }>;
    } = $props();
</script>

<div class="rounded-xl border bg-card p-5 text-card-foreground shadow-xs">
    <div class="flex items-start justify-between gap-3">
        <p class="text-sm font-medium">{title}</p>
        <span class="rounded-lg bg-accent p-2 text-accent-foreground">
            <Icon class="size-4" />
        </span>
    </div>
    <p class="mt-3 text-3xl font-semibold tracking-tight tabular-nums">{value}</p>
    {#if hint}
        <p class="mt-1 text-xs text-muted-foreground">{hint}</p>
    {/if}
</div>
