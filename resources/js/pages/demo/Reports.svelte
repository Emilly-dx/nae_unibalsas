<script module lang="ts">
    import { dashboard } from '@/routes';

    export const layout = {
        breadcrumbs: [
            { title: 'Painel', href: dashboard() },
            { title: 'Relatórios', href: '/relatorios' },
        ],
    };
</script>

<script lang="ts">
    import ChartColumn from '@lucide/svelte/icons/chart-column';
    import Download from '@lucide/svelte/icons/download';
    import { toast } from 'svelte-sonner';
    import AppHead from '@/components/AppHead.svelte';
    import DemoNotice from '@/components/demo/DemoNotice.svelte';
    import FieldSelect from '@/components/demo/FieldSelect.svelte';
    import { Button } from '@/components/ui/button';
    import { collectiveActions, monthlyAttendances, months, reportIndicators } from '@/lib/demo/data';

    const series = [
        { label: 'Psicopedagógico', color: 'var(--chart-1)' },
        { label: 'Psicológico', color: 'var(--chart-2)' },
        { label: 'Escuta e orientação', color: 'var(--chart-3)' },
    ];

    const max = Math.max(...monthlyAttendances.map((values) => values[0] + values[1] + values[2]));
    const scaleMax = Math.ceil(max / 10) * 10;
    const gridLines = [1, 0.75, 0.5, 0.25];

    function exportFile(format: string): void {
        toast.info(`Exportação em ${format} será gerada no módulo de relatórios (M8).`);
    }
</script>

<AppHead title="Relatórios" />

<div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
    <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight">Relatórios</h1>
            <p class="text-sm text-muted-foreground">Indicadores do NAE no mesmo formato do relatório anual de atividades.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <FieldSelect class="h-9 w-36" aria-label="Ano"><option>Ano 2026</option><option>Ano 2025</option></FieldSelect>
            <Button variant="outline" onclick={() => exportFile('PDF')}><Download class="size-4" /> Exportar PDF</Button>
            <Button onclick={() => exportFile('Excel')}><Download class="size-4" /> Exportar Excel</Button>
        </div>
    </div>

    <DemoNotice milestone="M8 — Relatórios + Indicadores" />

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        {#each reportIndicators as indicator (indicator.label)}
            <div class="flex items-center justify-between gap-3 rounded-xl border bg-card p-5">
                <div>
                    <p class="text-sm text-muted-foreground">{indicator.label}</p>
                    <p class="mt-1 text-3xl font-semibold tracking-tight tabular-nums">
                        {indicator.value.toLocaleString('pt-BR')}
                    </p>
                </div>
                <span class="rounded-lg bg-accent p-2.5 text-accent-foreground"><ChartColumn class="size-4" /></span>
            </div>
        {/each}
    </div>

    <div class="grid gap-6 xl:grid-cols-[1.8fr_1fr]">
        <section class="rounded-xl border bg-card p-5">
            <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
                <h2 class="font-semibold">Atendimentos por mês e tipo</h2>
                <div class="flex flex-wrap gap-3 text-xs text-muted-foreground">
                    {#each series as item (item.label)}
                        <span class="flex items-center gap-1.5">
                            <span class="size-2.5 rounded-sm" style:background-color={item.color}></span>{item.label}
                        </span>
                    {/each}
                </div>
            </div>
            <div class="relative h-72">
                {#each gridLines as line (line)}
                    <div class="absolute inset-x-0 border-t border-dashed border-border" style:bottom="{line * 100}%">
                        <span class="absolute -top-2 -left-1 -translate-x-full bg-card pr-1 text-[10px] text-muted-foreground">
                            {Math.round(scaleMax * line)}
                        </span>
                    </div>
                {/each}
                <div class="absolute inset-0 ml-4 flex items-end gap-2 border-b sm:gap-4">
                    {#each monthlyAttendances as values, index (months[index])}
                        {@const total = values[0] + values[1] + values[2]}
                        <div
                            class="group relative flex flex-1 flex-col-reverse overflow-hidden rounded-t-sm"
                            style:height="{(total / scaleMax) * 100}%"
                            title="{months[index]}: {total} atendimentos"
                        >
                            {#each values as value, serie (serie)}
                                <div style:height="{total ? (value / total) * 100 : 0}%" style:background-color={series[serie].color}></div>
                            {/each}
                        </div>
                    {/each}
                </div>
            </div>
            <div class="mt-2 ml-4 flex gap-2 sm:gap-4">
                {#each months as month (month)}
                    <span class="flex-1 text-center text-xs text-muted-foreground">{month}</span>
                {/each}
            </div>
        </section>

        <section class="rounded-xl border bg-card p-5">
            <h2 class="mb-2 font-semibold">Ações coletivas</h2>
            <ul class="divide-y">
                {#each collectiveActions as action (action.name)}
                    <li class="flex items-center justify-between gap-3 py-3">
                        <div>
                            <p class="text-sm font-medium">{action.name}</p>
                            <p class="text-xs text-muted-foreground">{action.description}</p>
                        </div>
                        <span class="font-mono text-sm tabular-nums">{action.reached.toLocaleString('pt-BR')}</span>
                    </li>
                {/each}
            </ul>
            <p class="mt-2 text-xs text-muted-foreground">Coluna à direita: estudantes alcançados.</p>
        </section>
    </div>
</div>
