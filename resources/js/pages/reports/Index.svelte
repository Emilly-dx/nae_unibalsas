<script module lang="ts">
    import { dashboard } from '@/routes';
    import { index } from '@/routes/reports';

    export const layout = {
        breadcrumbs: [
            { title: 'Painel', href: dashboard() },
            { title: 'Relatórios', href: index() },
        ],
    };
</script>

<script lang="ts">
    import { router } from '@inertiajs/svelte';
    import Download from '@lucide/svelte/icons/download';
    import FileSpreadsheet from '@lucide/svelte/icons/file-spreadsheet';
    import Lock from '@lucide/svelte/icons/lock';
    import AppHead from '@/components/AppHead.svelte';
    import FieldSelect from '@/components/form/FieldSelect.svelte';
    import { Button } from '@/components/ui/button';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';
    import { pdf, spreadsheet } from '@/routes/reports';
    import type { NumberOption, Option, Report, ReportFilters } from '@/types';

    let {
        report,
        filters,
        options,
        can,
    }: {
        report: Report;
        filters: ReportFilters;
        options: {
            types: Option[];
            professionals: NumberOption[];
            courses: (Option & { modality: string })[];
            modalities: Option[];
        };
        can: { exportPdf: boolean; exportSpreadsheet: boolean };
    } = $props();

    // svelte-ignore state_referenced_locally
    let form = $state<ReportFilters>({ ...filters });

    const colors: Record<string, string> = {
        psychopedagogical: 'var(--chart-1)',
        psychological: 'var(--chart-2)',
        listening: 'var(--chart-3)',
    };

    const year = new Date().getFullYear();
    const presets = [
        { label: `Ano ${year}`, de: `${year}-01-01`, ate: `${year}-12-31` },
        { label: `${year}.1`, de: `${year}-01-01`, ate: `${year}-06-30` },
        { label: `${year}.2`, de: `${year}-07-01`, ate: `${year}-12-31` },
        { label: `Ano ${year - 1}`, de: `${year - 1}-01-01`, ate: `${year - 1}-12-31` },
    ];

    /** Só os filtros preenchidos vão para a URL. */
    function query(values: ReportFilters): Record<string, string> {
        return Object.fromEntries(Object.entries(values).filter(([, value]) => value !== ''));
    }

    function apply(): void {
        router.get(index().url, query(form), { preserveScroll: true, preserveState: true, replace: true });
    }

    function usePreset(preset: { de: string; ate: string }): void {
        form.de = preset.de;
        form.ate = preset.ate;
        apply();
    }

    function clear(): void {
        form = { de: '', ate: '', tipo: '', profissional: '', curso: '', modalidade: '' };
        router.get(index().url, {}, { preserveScroll: true });
    }

    const exportQuery = $derived(query(filters));
    const monthTotals = $derived(
        report.monthly.labels.map((_, index) => report.monthly.series.reduce((sum, serie) => sum + serie.values[index], 0)),
    );
    const scaleMax = $derived(Math.max(4, Math.ceil(Math.max(...monthTotals, 0) / 4) * 4));
    const gridLines = [1, 0.75, 0.5, 0.25];
    const reasonMax = $derived(Math.max(1, ...report.requests.byReason.map((item) => item.value)));
    const doneTotal = $derived(report.byType.reduce((sum, item) => sum + item.value, 0));
    const hasFilters = $derived(filters.tipo !== '' || filters.profissional !== '' || filters.curso !== '' || filters.modalidade !== '');
    const format = (value: number): string => value.toLocaleString('pt-BR');
    const percent = (value: number, total: number): string => (total > 0 ? `${Math.round((value / total) * 100)}%` : '—');
</script>

<AppHead title="Relatórios" />

<div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
    <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight">Relatórios</h1>
            <p class="text-sm text-muted-foreground">Indicadores do NAE · {report.period}</p>
        </div>
        <div class="flex flex-wrap gap-2">
            {#if can.exportPdf}
                <Button variant="outline" asChild>
                    {#snippet children(props)}
                        <a {...props} href={pdf({ query: exportQuery }).url} target="_blank" rel="noopener"><Download class="size-4" /> Exportar PDF</a>
                    {/snippet}
                </Button>
            {/if}
            {#if can.exportSpreadsheet}
                <Button asChild>
                    {#snippet children(props)}
                        <a {...props} href={spreadsheet({ query: exportQuery }).url} download><FileSpreadsheet class="size-4" /> Exportar Excel</a>
                    {/snippet}
                </Button>
            {/if}
        </div>
    </div>

    <form
        class="grid gap-4 rounded-xl border bg-card p-4"
        onsubmit={(event) => {
            event.preventDefault();
            apply();
        }}
    >
        <div class="flex flex-wrap items-center gap-2">
            <span class="text-xs font-medium tracking-wide text-muted-foreground uppercase">Período rápido</span>
            {#each presets as preset (preset.label)}
                <button
                    type="button"
                    class="rounded-full border px-3 py-1 text-xs transition-colors hover:bg-accent {filters.de === preset.de && filters.ate === preset.ate
                        ? 'border-primary bg-accent font-medium text-accent-foreground'
                        : ''}"
                    onclick={() => usePreset(preset)}
                >
                    {preset.label}
                </button>
            {/each}
        </div>
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-6">
            <div class="grid gap-1.5">
                <Label for="de">De</Label>
                <Input id="de" type="date" bind:value={form.de} />
            </div>
            <div class="grid gap-1.5">
                <Label for="ate">Até</Label>
                <Input id="ate" type="date" bind:value={form.ate} min={form.de} />
            </div>
            <div class="grid gap-1.5">
                <Label for="tipo">Tipo de atendimento</Label>
                <FieldSelect id="tipo" bind:value={form.tipo}>
                    <option value="">Todos</option>
                    {#each options.types as option (option.value)}<option value={option.value}>{option.label}</option>{/each}
                </FieldSelect>
            </div>
            <div class="grid gap-1.5">
                <Label for="profissional">Profissional</Label>
                <FieldSelect id="profissional" bind:value={form.profissional}>
                    <option value="">Todos</option>
                    {#each options.professionals as option (option.value)}
                        <option value={String(option.value)}>{option.label}</option>
                    {/each}
                </FieldSelect>
            </div>
            <div class="grid gap-1.5">
                <Label for="curso">Curso</Label>
                <FieldSelect id="curso" bind:value={form.curso}>
                    <option value="">Todos</option>
                    {#each options.courses as option (option.value)}<option value={option.value}>{option.label}</option>{/each}
                </FieldSelect>
            </div>
            <div class="grid gap-1.5">
                <Label for="modalidade">Modalidade do curso</Label>
                <FieldSelect id="modalidade" bind:value={form.modalidade}>
                    <option value="">Todas</option>
                    {#each options.modalities as option (option.value)}<option value={option.value}>{option.label}</option>{/each}
                </FieldSelect>
            </div>
        </div>
        <div class="flex justify-end gap-2">
            <Button type="button" variant="ghost" onclick={clear}>Limpar filtros</Button>
            <Button type="submit">Aplicar filtros</Button>
        </div>
    </form>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        {#each report.summary as item (item.key)}
            <div class="rounded-xl border bg-card p-5">
                <p class="text-sm text-muted-foreground">{item.label}</p>
                <p class="mt-1 text-3xl font-semibold tracking-tight tabular-nums">{format(item.value)}</p>
                <p class="mt-1 text-xs text-muted-foreground">{item.hint}</p>
            </div>
        {/each}
    </div>

    <div class="grid gap-6 xl:grid-cols-[1.8fr_1fr]">
        <section class="rounded-xl border bg-card p-5">
            <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
                <h2 class="font-semibold">Atendimentos realizados por mês</h2>
                <div class="flex flex-wrap gap-3 text-xs text-muted-foreground">
                    {#each report.monthly.series as serie (serie.key)}
                        <span class="flex items-center gap-1.5">
                            <span class="size-2.5 rounded-sm" style:background-color={colors[serie.key]}></span>{serie.label}
                        </span>
                    {/each}
                </div>
            </div>
            <div class="relative ml-6 h-64">
                {#each gridLines as line (line)}
                    <div class="absolute inset-x-0 border-t border-dashed border-border" style:bottom="{line * 100}%">
                        <span class="absolute -top-2 -left-1 -translate-x-full pr-1 text-[10px] text-muted-foreground">
                            {Math.round(scaleMax * line)}
                        </span>
                    </div>
                {/each}
                <div class="absolute inset-0 flex items-end gap-1 border-b sm:gap-3">
                    {#each report.monthly.labels as month, index (month)}
                        <div
                            class="relative flex flex-1 flex-col-reverse overflow-hidden rounded-t-sm"
                            style:height="{(monthTotals[index] / scaleMax) * 100}%"
                            title="{month}: {monthTotals[index]} atendimentos"
                        >
                            {#each report.monthly.series as serie (serie.key)}
                                <div
                                    style:height="{monthTotals[index] ? (serie.values[index] / monthTotals[index]) * 100 : 0}%"
                                    style:background-color={colors[serie.key]}
                                ></div>
                            {/each}
                        </div>
                    {/each}
                </div>
            </div>
            <div class="mt-2 ml-6 flex gap-1 sm:gap-3">
                {#each report.monthly.labels as month (month)}
                    <span class="flex-1 truncate text-center text-[11px] text-muted-foreground">{month}</span>
                {/each}
            </div>
        </section>

        <section class="rounded-xl border bg-card p-5">
            <h2 class="mb-4 font-semibold">Por tipo e modalidade</h2>
            <ul class="space-y-3">
                {#each report.byType as item (item.key)}
                    <li>
                        <div class="mb-1 flex justify-between text-sm">
                            <span>{item.label}</span>
                            <span class="tabular-nums">{item.value} <span class="text-muted-foreground">({percent(item.value, doneTotal)})</span></span>
                        </div>
                        <div class="h-2 overflow-hidden rounded-full bg-muted">
                            <div class="h-full rounded-full" style:width={percent(item.value, doneTotal) === '—' ? '0%' : percent(item.value, doneTotal)} style:background-color={colors[item.key]}></div>
                        </div>
                    </li>
                {/each}
            </ul>
            <dl class="mt-5 grid grid-cols-2 gap-3 border-t pt-4">
                {#each report.byModality as item (item.label)}
                    <div>
                        <dt class="text-xs text-muted-foreground">{item.label}</dt>
                        <dd class="text-lg font-semibold tabular-nums">{format(item.value)}</dd>
                    </div>
                {/each}
            </dl>
        </section>
    </div>

    <section class="overflow-hidden rounded-xl border bg-card">
        <header class="border-b px-5 py-4">
            <h2 class="font-semibold">Atendimentos por profissional</h2>
        </header>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-muted/50 text-left text-xs text-muted-foreground">
                    <tr>
                        <th class="px-5 py-2 font-medium">Profissional</th>
                        <th class="px-3 py-2 text-right font-medium">Realizados</th>
                        <th class="px-3 py-2 text-right font-medium">Psicopedagógico</th>
                        <th class="px-3 py-2 text-right font-medium">Psicológico</th>
                        <th class="px-3 py-2 text-right font-medium">Escuta</th>
                        <th class="px-5 py-2 text-right font-medium">Cancelados</th>
                    </tr>
                </thead>
                <tbody class="divide-y tabular-nums">
                    {#each report.byProfessional as item (item.name)}
                        <tr>
                            <td class="px-5 py-2.5 font-medium">{item.name}</td>
                            <td class="px-3 py-2.5 text-right font-semibold">{item.total}</td>
                            <td class="px-3 py-2.5 text-right">{item.psychopedagogical}</td>
                            <td class="px-3 py-2.5 text-right">{item.psychological}</td>
                            <td class="px-3 py-2.5 text-right">{item.listening}</td>
                            <td class="px-5 py-2.5 text-right text-muted-foreground">{item.cancelled}</td>
                        </tr>
                    {:else}
                        <tr><td colspan="6" class="px-5 py-6 text-center text-muted-foreground">Nenhum atendimento no período.</td></tr>
                    {/each}
                </tbody>
            </table>
        </div>
    </section>

    <div class="grid gap-6 lg:grid-cols-2">
        <section class="overflow-hidden rounded-xl border bg-card">
            <header class="border-b px-5 py-4">
                <h2 class="font-semibold">Atendimentos por curso</h2>
            </header>
            <div class="max-h-96 overflow-y-auto">
                <table class="w-full text-sm">
                    <thead class="sticky top-0 bg-muted text-left text-xs text-muted-foreground">
                        <tr>
                            <th class="px-5 py-2 font-medium">Curso</th>
                            <th class="px-3 py-2 text-right font-medium">Estudantes</th>
                            <th class="px-5 py-2 text-right font-medium">Atendimentos</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y tabular-nums">
                        {#each report.byCourse as item (item.course)}
                            <tr>
                                <td class="px-5 py-2.5">{item.course}</td>
                                <td class="px-3 py-2.5 text-right">{item.students}</td>
                                <td class="px-5 py-2.5 text-right">{item.attendances}</td>
                            </tr>
                        {:else}
                            <tr><td colspan="3" class="px-5 py-6 text-center text-muted-foreground">Nenhum atendimento no período.</td></tr>
                        {/each}
                    </tbody>
                </table>
            </div>
        </section>

        <section class="rounded-xl border bg-card p-5">
            <h2 class="font-semibold">Distribuição das demandas</h2>
            <p class="mb-4 text-xs text-muted-foreground">
                Motivos marcados nas {format(report.requests.total)} solicitações de apoio do período (uma solicitação pode ter vários).
            </p>
            <ul class="space-y-2.5">
                {#each report.requests.byReason as item (item.label)}
                    <li class="grid grid-cols-[minmax(0,12rem)_1fr_2rem] items-center gap-3 text-sm">
                        <span class="truncate" title={item.label}>{item.label}</span>
                        <div class="h-2 overflow-hidden rounded-full bg-muted">
                            <div class="h-full rounded-full bg-primary" style:width="{(item.value / reasonMax) * 100}%"></div>
                        </div>
                        <span class="text-right tabular-nums">{item.value}</span>
                    </li>
                {/each}
            </ul>
        </section>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <section class="rounded-xl border bg-card p-5">
            <h2 class="mb-4 font-semibold">Encaminhamentos</h2>
            <div class="mb-4 grid grid-cols-2 gap-3">
                <div class="rounded-lg bg-muted/60 p-3">
                    <p class="text-xs text-muted-foreground">Internos</p>
                    <p class="text-2xl font-semibold tabular-nums">{format(report.referrals.internal)}</p>
                </div>
                <div class="rounded-lg bg-muted/60 p-3">
                    <p class="text-xs text-muted-foreground">Externos</p>
                    <p class="text-2xl font-semibold tabular-nums">{format(report.referrals.external)}</p>
                </div>
            </div>
            <dl class="divide-y text-sm">
                {#each report.referrals.byStatus as item (item.label)}
                    <div class="flex justify-between py-2"><dt>{item.label}</dt><dd class="tabular-nums">{item.value}</dd></div>
                {/each}
            </dl>
        </section>

        <section class="rounded-xl border bg-card p-5">
            <h2 class="mb-4 font-semibold">Origem das solicitações</h2>
            <dl class="divide-y text-sm">
                {#each report.requests.byOrigin as item (item.label)}
                    <div class="flex justify-between py-2"><dt>{item.label}</dt><dd class="tabular-nums">{item.value}</dd></div>
                {/each}
            </dl>
        </section>
    </div>

    <p class="flex items-start gap-2 rounded-lg bg-muted/60 p-3 text-xs text-muted-foreground">
        <Lock class="mt-0.5 size-3.5 shrink-0" />
        <span>
            Os relatórios mostram apenas números agregados: nenhum nome de estudante e nenhum conteúdo de atendimento aparece aqui
            ou nas exportações.{hasFilters ? ' Com filtros muito específicos, números pequenos podem permitir identificar alguém; compartilhe com cuidado.' : ''}
        </span>
    </p>
</div>
