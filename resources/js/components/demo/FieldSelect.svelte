<script lang="ts">
    import type { Snippet } from 'svelte';
    import type { HTMLSelectAttributes } from 'svelte/elements';
    import { cn } from '@/lib/utils';

    let {
        class: className = '',
        value = $bindable(),
        children,
        ...rest
    }: HTMLSelectAttributes & { children?: Snippet } = $props();
</script>

<select
    bind:value
    class={cn(
        'flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-xs focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none',
        className,
    )}
    {...rest}
>
    {@render children?.()}
</select>
