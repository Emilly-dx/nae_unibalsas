<script lang="ts">
    import type { HTMLTextareaAttributes } from 'svelte/elements';
    import { cn } from '@/lib/utils';

    let {
        class: className = '',
        value = $bindable(),
        ...rest
    }: HTMLTextareaAttributes = $props();
</script>

<textarea
    bind:value
    class={cn(
        'flex min-h-24 w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-xs placeholder:text-muted-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none',
        className,
    )}
    {...rest}
></textarea>
