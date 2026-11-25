<script module lang="ts">
    import { dashboard } from '@/routes';
    import { index } from '@/routes/referrals';

    export const layout = {
        breadcrumbs: [
            { title: 'Painel', href: dashboard() },
            { title: 'Encaminhamentos', href: index() },
        ],
    };
</script>

<script lang="ts">
    import { Link, router } from '@inertiajs/svelte';
    import ChevronLeft from '@lucide/svelte/icons/chevron-left';
    import ChevronRight from '@lucide/svelte/icons/chevron-right';
    import Plus from '@lucide/svelte/icons/plus';
    import Route from '@lucide/svelte/icons/route';
    import Search from '@lucide/svelte/icons/search';
    import AppHead from '@/components/AppHead.svelte';
    import FieldSelect from '@/components/form/FieldSelect.svelte';
    import Segmented from '@/components/form/Segmented.svelte';
    import { Button } from '@/components/ui/button';
    import { Input } from '@/components/ui/input';
    import { referralStatusClass } from '@/lib/attendances';
    import { cn } from '@/lib/utils';
    import { create, show } from '@/routes/referrals';
    import type { Option, Paginated, ReferralSummary } from '@/types';

    type Filters = { tipo: string; situacao: string; meus: boolean; busca: string };

    let {
        referrals,
        filters,
        statuses,
        counts,
        canCreate,
    }: {
        referrals: Paginated<ReferralSummary>;
        filters: Filters;
        kinds: Option[];
        statuses: Option[];
        counts: { open: number; awaitingMe: number; overdue: number };
        canCreate: boolean;
    } = $props();

    // svelte-ignore state_referenced_locally
    let kind = $state(filters.tipo || 'todos');
    // svelte-ignore state_referenced_locally
    let status = $state(filters.situacao);
    // svelte-ignore state_referenced_locally
    let mine = $state(filters.meus);
    // svelte-ignore state_referenced_locally
    let search = $state(filters.busca);

    let debounce: ReturnType<typeof setTimeout> | undefined;

    function apply(): void {
        clearTimeout(debounce);

        const query: Record<string, string> = {};

        if (kind !== 'todos') query.tipo = kind;
        if (status) query.situacao = status;
        if (mine) query.meus = '1';
        if (search.trim()) query.busca = search.trim();

        router.get(index().url, query, { preserveState: true, preserveScroll: true, replace: true });
    }

    function onSearch(): void {
        clearTimeout(debounce);
        debounce = setTimeout(apply, 350);
    }

    function quick(value: { status?: string; mine?: boolean }): void {
        status = value.status ?? '';
        mine = value.mine ?? false;
        kind = 'todos';
        apply();
    }
</script>

<AppHead title="Encaminhamentos" />

<div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
    <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight">Encaminhamentos</h1>
            <p class="text-sm text-muted-foreground">
                Encaminhamentos internos e externos ao NAE, com acompanhamento do retorno.
            </p>
        </div>
        {#if canCreate}
            <Button asChild>
                {#snippet children(props)}
                    <Link {...props} href={create().url}><Plus class="size-4" /> Novo encaminhamento</Link>
                {/snippet}
            </Button>
        {/if}
    </div>

    <div class="grid gap-3 sm:grid-cols-3">
        <button type="button" class="rounded-xl border bg-card p-4 text-left transition-colors hover:bg-muted/40" onclick={() => quick({ status: 'abertos' })}>
            <p class="text-sm text-muted-foreground">Em aberto</p>
            <p class="text-2xl font-semibold tabular-nums">{counts.open}</p>
        </button>
        <button type="button" class="rounded-xl border bg-card p-4 text-left transition-colors hover:bg-muted/40" onclick={() => quick({ status: 'abertos', mine: true })}>
            <p class="text-sm text-muted-foreground">Recebidos por mim, em aberto</p>
            <p class="text-2xl font-semibold tabular-nums">{counts.awaitingMe}</p>
        </button>
        <div class="rounded-xl border bg-card p-4">
            <p class="text-sm text-muted-foreground">Com prazo de retorno vencido</p>
            <p class={cn('text-2xl font-semibold tabular-nums', counts.overdue > 0 && 'text-destructive')}>{counts.overdue}</p>
        </div>
    </div>

    <div class="flex flex-col gap-3 rounded-xl border bg-card p-4 lg:flex-row lg:items-center">
        <div class="relative lg:w-72">
            <Search class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
            <Input type="search" class="pl-9" placeholder="Estudante (nome ou matrícula)" bind:value={search} oninput={onSearch} />
        </div>
        <Segmented
            class="lg:w-64"
            options={[
                { value: 'todos', label: 'Todos' },
                { value: 'internal', label: 'Internos' },
                { value: 'external', label: 'Externos' },
            ]}
            bind:value={() => kind, (value) => { kind = value; apply(); }}
        />
        <FieldSelect class="h-10 lg:w-56" aria-label="Situação" bind:value={status} onchange={apply}>
            <option value="">Todas as situações</option>
            <option value="abertos">Em aberto</option>
            {#each statuses as option (option.value)}<option value={option.value}>{option.label}</option>{/each}
        </FieldSelect>
        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" class="size-4 accent-primary" bind:checked={mine} onchange={apply} />
            Só os meus
        </label>
    </div>

    <section class="overflow-hidden rounded-xl border bg-card">
        {#if referrals.data.length === 0}
            <div class="flex flex-col items-center gap-2 p-10 text-center text-sm text-muted-foreground">
                <Route class="size-6" />
                Nenhum encaminhamento encontrado.
            </div>
        {:else}
            <div class="overflow-x-auto">
                <table class="w-full min-w-[760px] text-sm">
                    <thead class="bg-muted/50 text-left text-muted-foreground">
                        <tr>
                            <th class="px-5 py-3 font-medium">Estudante</th>
                            <th class="px-3 py-3 font-medium">Tipo</th>
                            <th class="px-3 py-3 font-medium">Origem → destino</th>
                            <th class="px-3 py-3 font-medium">Data</th>
                            <th class="px-3 py-3 font-medium">Retorno até</th>
                            <th class="px-3 py-3 font-medium">Situação</th>
                        </tr>
                    </thead>
                    <tbody>
                        {#each referrals.data as referral (referral.id)}
                            <tr class="cursor-pointer border-t transition-colors hover:bg-muted/40" onclick={() => router.visit(show(referral.id).url)}>
                                <td class="px-5 py-3">
                                    <Link href={show(referral.id).url} class="font-medium hover:underline">{referral.student.name}</Link>
                                    <p class="text-xs text-muted-foreground">{referral.student.course}</p>
                                </td>
                                <td class="px-3 py-3">{referral.kind_label}</td>
                                <td class="px-3 py-3">
                                    <p>{referral.destination}</p>
                                    <p class="text-xs text-muted-foreground">por {referral.origin}</p>
                                </td>
                                <td class="px-3 py-3 tabular-nums">{referral.referred_on}</td>
                                <td class={cn('px-3 py-3 tabular-nums', referral.overdue && 'font-medium text-destructive')}>
                                    {referral.return_deadline ?? '—'}{referral.overdue ? ' (vencido)' : ''}
                                </td>
                                <td class="px-3 py-3">
                                    <span class={cn('rounded-full px-2.5 py-0.5 text-xs font-medium whitespace-nowrap', referralStatusClass(referral.status))}>
                                        {referral.status_label}
                                    </span>
                                </td>
                            </tr>
                        {/each}
                    </tbody>
                </table>
            </div>
        {/if}
        <div class="flex flex-wrap items-center justify-between gap-3 border-t px-5 py-3 text-xs text-muted-foreground">
            <p>O motivo e o retorno são sigilosos: aparecem só para quem encaminhou e para o profissional de destino.</p>
            {#if referrals.last_page > 1}
                <div class="flex items-center gap-2">
                    <Button variant="outline" size="sm" disabled={!referrals.prev_page_url}
                        onclick={() => referrals.prev_page_url && router.get(referrals.prev_page_url, {}, { preserveScroll: true })}>
                        <ChevronLeft class="size-4" /> Anterior
                    </Button>
                    <span>Página {referrals.current_page} de {referrals.last_page}</span>
                    <Button variant="outline" size="sm" disabled={!referrals.next_page_url}
                        onclick={() => referrals.next_page_url && router.get(referrals.next_page_url, {}, { preserveScroll: true })}>
                        Próxima <ChevronRight class="size-4" />
                    </Button>
                </div>
            {/if}
        </div>
    </section>
</div>
