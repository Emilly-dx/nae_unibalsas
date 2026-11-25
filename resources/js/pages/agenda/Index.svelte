<script module lang="ts">
    import { dashboard } from '@/routes';
    import { index } from '@/routes/agenda';

    export const layout = {
        breadcrumbs: [
            { title: 'Painel', href: dashboard() },
            { title: 'Agenda', href: index() },
        ],
    };
</script>

<script lang="ts">
    import { Link, router } from '@inertiajs/svelte';
    import CalendarPlus from '@lucide/svelte/icons/calendar-plus';
    import CalendarX from '@lucide/svelte/icons/calendar-x';
    import AppHead from '@/components/AppHead.svelte';
    import WeekAgenda from '@/components/attendances/WeekAgenda.svelte';
    import FieldSelect from '@/components/form/FieldSelect.svelte';
    import { Button } from '@/components/ui/button';
    import { attendanceChipClass, attendanceLabel } from '@/lib/attendances';
    import { cn } from '@/lib/utils';
    import { create as scheduleAttendance, edit as reschedule, show as showAttendance } from '@/routes/attendances';
    import type { AttendanceSummary, NumberOption, Week } from '@/types';

    type Upcoming = AttendanceSummary & { weekday: string; canReschedule: boolean };

    let {
        week,
        upcoming,
        upcomingDays,
        filters,
        professionals,
        isProfessional,
        canSchedule,
    }: {
        week: Week;
        upcoming: Upcoming[];
        upcomingDays: number;
        filters: { profissional: string; semana: string };
        professionals: NumberOption[];
        isProfessional: boolean;
        canSchedule: boolean;
    } = $props();

    // svelte-ignore state_referenced_locally
    let professional = $state(filters.profissional);

    const singleProfessional = $derived(professional !== '');

    function query(extra: Record<string, string> = {}): Record<string, string> {
        return { profissional: professional, ...(filters.semana ? { semana: filters.semana } : {}), ...extra };
    }

    function applyProfessional(): void {
        router.get(index().url, query(), { preserveState: true, preserveScroll: true, replace: true });
    }

    const weekHref = (date: string): string => index({ query: query({ semana: date }) }).url;

    // Agrupa os próximos atendimentos por dia.
    const groups = $derived.by(() => {
        const map = new Map<string, Upcoming[]>();

        for (const item of upcoming) {
            const key = `${item.weekday}, ${item.date}`;
            map.set(key, [...(map.get(key) ?? []), item]);
        }

        return [...map.entries()];
    });
</script>

<AppHead title="Agenda" />

<div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
    <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight">Agenda</h1>
            <p class="text-sm text-muted-foreground">Atendimentos que ainda vão acontecer e os horários de cada profissional.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <FieldSelect class="h-9 w-64" aria-label="Profissional" bind:value={professional} onchange={applyProfessional}>
                {#if isProfessional}<option value="meu">Minha agenda</option>{/if}
                <option value="">Todos os profissionais</option>
                {#each professionals as option (option.value)}
                    <option value={String(option.value)}>{option.label}</option>
                {/each}
            </FieldSelect>
            {#if canSchedule}
                <Button asChild>
                    {#snippet children(props)}
                        <Link {...props} href={scheduleAttendance().url}><CalendarPlus class="size-4" /> Agendar</Link>
                    {/snippet}
                </Button>
            {/if}
        </div>
    </div>

    <div class="grid gap-6 xl:grid-cols-[2fr_minmax(300px,1fr)]">
        <WeekAgenda {week} hrefFor={weekHref} showProfessional={!singleProfessional} />

        <section class="overflow-hidden rounded-xl border bg-card xl:self-start">
            <header class="border-b px-5 py-4">
                <h2 class="font-semibold">Próximos atendimentos</h2>
                <p class="text-xs text-muted-foreground">Agendados para os próximos {upcomingDays} dias</p>
            </header>
            {#if upcoming.length === 0}
                <div class="flex flex-col items-center gap-2 p-8 text-center text-sm text-muted-foreground">
                    <CalendarX class="size-6" />
                    Nenhum atendimento agendado.
                </div>
            {:else}
                <div class="max-h-[640px] overflow-y-auto">
                    {#each groups as [day, items] (day)}
                        <p class="sticky top-0 border-b bg-muted/80 px-5 py-1.5 text-xs font-medium text-muted-foreground backdrop-blur">{day}</p>
                        <ul class="divide-y">
                            {#each items as item (item.id)}
                                <li class="flex items-start gap-3 px-5 py-3">
                                    <span class="w-12 pt-0.5 font-mono text-sm font-semibold">{item.time}</span>
                                    <div class="min-w-0 flex-1">
                                        <Link href={showAttendance(item.id).url} class="block truncate text-sm font-medium hover:underline">
                                            {item.student.name}
                                        </Link>
                                        <div class="mt-0.5 flex flex-wrap items-center gap-1.5 text-xs text-muted-foreground">
                                            <span class={cn('rounded-full px-2 py-0.5 font-medium', attendanceChipClass(item))}>{attendanceLabel(item)}</span>
                                            {#if !singleProfessional}<span>{item.professional}</span>{/if}
                                            <span>· {item.modality_label}</span>
                                        </div>
                                    </div>
                                    {#if item.canReschedule}
                                        <Link href={reschedule(item.id).url} class="text-xs text-primary underline-offset-4 hover:underline">Remarcar</Link>
                                    {/if}
                                </li>
                            {/each}
                        </ul>
                    {/each}
                </div>
            {/if}
        </section>
    </div>
</div>
