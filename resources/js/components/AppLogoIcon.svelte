<script lang="ts">
    let {
        class: className = '',
        ...rest
    }: {
        class?: string;
        [key: string]: unknown;
    } = $props();
</script>

<svg
    xmlns="http://www.w3.org/2000/svg"
    viewBox="0 0 24 24"
    fill="none"
    class={className}
    {...rest}
>
    <path
        fill="currentColor"
        d="M12 21.35s-7.5-4.7-10.28-9.03C-.32 8.94.94 4.7 4.5 3.3c2.6-1.02 5.4.04 7.5 2.9 2.1-2.86 4.9-3.92 7.5-2.9 3.56 1.4 4.82 5.64 2.78 9.02C19.5 16.65 12 21.35 12 21.35Z"
    />
</svg>
