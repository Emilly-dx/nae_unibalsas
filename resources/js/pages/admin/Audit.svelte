<script module lang="ts">
    import { dashboard } from '@/routes';
    import { index } from '@/routes/admin/audit';

    export const layout = {
        breadcrumbs: [
            { title: 'Painel', href: dashboard() },
            { title: 'Auditoria', href: index() },
        ],
    };
</script>

<script lang="ts">
    import { router } from '@inertiajs/svelte';
    import ScrollText from '@lucide/svelte/icons/scroll-text';
    import AppHead from '@/components/AppHead.svelte';
    import Pager from '@/components/admin/Pager.svelte';
    import FieldSelect from '@/components/form/FieldSelect.svelte';
    import { Button } from '@/components/ui/button';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';
    import type { NumberOption, Option, Paginated } from '@/types';

    type Entry = {
        id: number;
        date: string;
        causer: string | null;
        module: string;
        event: string;
        description: string;
        subject: string | null;
        fields: string[];
        ip: string | null;
    };
    type Filters = { modulo: string; acao: string; usuario: string; de: string; ate: string };

    let {
        activities,
        filters,
        modules,
        events,
        users,
    }: {
        activities: Paginated<Entry>;
        filters: Filters;
        modules: Option[];
        events: Option[];
        users: NumberOption[];
    } = $props();

    // svelte-ignore state_referenced_locally
    let form = $state<Filters>({ ...filters });

    function apply(): void {
        const query = Object.fromEntries(Object.entries(form).filter(([, value]) => value !== ''));
        router.get(index().url, query, { preserveState: true, preserveScroll: true, replace: true });
    }

    function clear(): void {
        form = { modulo: '', acao: '', usuario: '', de: '', ate: '' };
        apply();
    }

    const warning = (entry: Entry): boolean => entry.event === 'Senha incorreta' || entry.event === 'Bloqueio por tentativas';
</script>

<AppHead title="Auditoria" />

<div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
    <div>
        <h1 class="text-2xl font-semibold tracking-tight">Auditoria</h1>
        <p class="text-sm text-muted-foreground">
            Quem fez o quê e quando. Mostra quais campos mudaram, mas nunca o conteúdo: dados pessoais e sigilosos ficam de fora.
        </p>
    </div>

    <form
        class="grid gap-3 rounded-xl border bg-card p-4 sm:grid-cols-2 lg:grid-cols-[1fr_1fr_1fr_auto_auto_auto] lg:items-end"
        onsubmit={(event) => {
            event.preventDefault();
            apply();
        }}
    >
        <div class="grid gap-1.5">
            <Label for="modulo">Módulo</Label>
            <FieldSelect id="modulo" bind:value={form.modulo}>
                <option value="">Todos</option>
                {#each modules as option (option.value)}<option value={option.value}>{option.label}</option>{/each}
            </FieldSelect>
        </div>
        <div class="grid gap-1.5">
            <Label for="acao">Ação</Label>
            <FieldSelect id="acao" bind:value={form.acao}>
                <option value="">Todas</option>
                {#each events as option (option.value)}<option value={option.value}>{option.label}</option>{/each}
            </FieldSelect>
        </div>
        <div class="grid gap-1.5">
            <Label for="usuario">Usuário</Label>
            <FieldSelect id="usuario" bind:value={form.usuario}>
                <option value="">Todos</option>
                {#each users as option (option.value)}<option value={String(option.value)}>{option.label}</option>{/each}
            </FieldSelect>
        </div>
        <div class="grid gap-1.5">
            <Label for="de">De</Label>
            <Input id="de" type="date" bind:value={form.de} />
        </div>
        <div class="grid gap-1.5">
            <Label for="ate">Até</Label>
            <Input id="ate" type="date" bind:value={form.ate} min={form.de} />
        </div>
        <div class="flex gap-2">
            <Button type="button" variant="ghost" onclick={clear}>Limpar</Button>
            <Button type="submit">Filtrar</Button>
        </div>
    </form>

    <section class="overflow-hidden rounded-xl border bg-card">
        {#if activities.data.length === 0}
            <div class="flex flex-col items-center gap-2 p-10 text-center text-sm text-muted-foreground">
                <ScrollText class="size-7" />
                Nenhum registro encontrado.
            </div>
        {:else}
            <div class="overflow-x-auto">
                <table class="w-full min-w-[820px] text-sm">
                    <thead class="bg-muted/50 text-left text-xs text-muted-foreground">
                        <tr>
                            <th class="px-5 py-2.5 font-medium">Data e hora</th>
                            <th class="px-4 py-2.5 font-medium">Usuário</th>
                            <th class="px-4 py-2.5 font-medium">Ação</th>
                            <th class="px-4 py-2.5 font-medium">Registro</th>
                            <th class="px-5 py-2.5 font-medium">Detalhes</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        {#each activities.data as entry (entry.id)}
                            <tr class="align-top">
                                <td class="px-5 py-3 font-mono text-xs whitespace-nowrap">{entry.date}</td>
                                <td class="px-4 py-3">{entry.causer ?? '—'}</td>
                                <td class="px-4 py-3">
                                    <span class={warning(entry) ? 'font-medium text-primary' : 'font-medium'}>{entry.event}</span>
                                    <span class="block text-xs text-muted-foreground">{entry.module}</span>
                                </td>
                                <td class="px-4 py-3 text-xs">{entry.subject ?? '—'}</td>
                                <td class="px-5 py-3 text-xs text-muted-foreground">
                                    {#if entry.fields.length > 0}Campos: {entry.fields.join(', ')}{/if}
                                    {#if entry.description}<span class="block">{entry.description}</span>{/if}
                                    {#if entry.ip}<span class="block font-mono">IP {entry.ip}</span>{/if}
                                </td>
                            </tr>
                        {/each}
                    </tbody>
                </table>
            </div>
            <Pager page={activities} />
        {/if}
    </section>
</div>
