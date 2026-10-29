<script lang="ts">
    import { cn } from '@/lib/utils';

    let {
        options,
        value = $bindable(),
        name = '',
        class: className = '',
    }: {
        options: { value: string; label: string }[];
        value: string;
        name?: string;
        class?: string;
    } = $props();
</script>

<div
    class={cn('flex rounded-lg border bg-muted/60 p-1', className)}
    role="radiogroup"
>
    {#each options as option (option.value)}
        <button
            type="button"
            role="radio"
            aria-checked={value === option.value}
            class={cn(
                'flex-1 rounded-md px-3 py-1.5 text-sm transition-colors',
                value === option.value
                    ? 'bg-background font-medium text-foreground shadow-sm dark:bg-secondary'
                    : 'text-muted-foreground hover:text-foreground',
            )}
            onclick={() => (value = option.value)}
        >
            {option.label}
        </button>
    {/each}
    {#if name}
        <input type="hidden" {name} {value} />
    {/if}
</div>
