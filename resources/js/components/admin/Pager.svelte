<script lang="ts">
    import { router } from '@inertiajs/svelte';
    import ChevronLeft from '@lucide/svelte/icons/chevron-left';
    import ChevronRight from '@lucide/svelte/icons/chevron-right';
    import { Button } from '@/components/ui/button';
    import type { Paginated } from '@/types';

    let { page }: { page: Paginated<unknown> } = $props();

    function go(url: string | null): void {
        if (url) {
            router.get(url, {}, { preserveScroll: true, preserveState: true });
        }
    }
</script>

{#if page.last_page > 1}
    <div class="flex flex-wrap items-center justify-between gap-3 border-t px-5 py-3 text-sm">
        <span class="text-muted-foreground">{page.from}–{page.to} de {page.total}</span>
        <div class="flex items-center gap-2">
            <Button variant="outline" size="sm" disabled={!page.prev_page_url} onclick={() => go(page.prev_page_url)}>
                <ChevronLeft class="size-4" /> Anterior
            </Button>
            <span class="text-muted-foreground">Página {page.current_page} de {page.last_page}</span>
            <Button variant="outline" size="sm" disabled={!page.next_page_url} onclick={() => go(page.next_page_url)}>
                Próxima <ChevronRight class="size-4" />
            </Button>
        </div>
    </div>
{/if}
