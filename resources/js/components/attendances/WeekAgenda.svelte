<script lang="ts">
    import { Link } from '@inertiajs/svelte';
    import ChevronLeft from '@lucide/svelte/icons/chevron-left';
    import ChevronRight from '@lucide/svelte/icons/chevron-right';
    import { attendanceBlockClass, attendanceLabel } from '@/lib/attendances';
    import { cn } from '@/lib/utils';
    import { show } from '@/routes/attendances';
    import { index as triageIndex } from '@/routes/triage';
    import type { Week } from '@/types';

    let {
        week,
        hrefFor = (date: string): string => triageIndex({ query: { aba: 'agenda', semana: date } }).url,
        showProfessional = true,
    }: {
        week: Week;
        hrefFor?: (date: string) => string;
        showProfessional?: boolean;
    } = $props();

    const HOUR = 56;

    // Faixa de horários: 08h–18h, ampliada se houver atendimento fora dela.
    const hours = $derived.by(() => {
        const starts = week.events.map((event) => Math.floor(event.start));
        const ends = week.events.map((event) => Math.ceil(event.start + event.hours));
        const first = Math.min(8, ...starts);
        const last = Math.max(18, ...ends);

        return Array.from({ length: last - first }, (_, index) => first + index);
    });

    const weekHref = (date: string): string => hrefFor(date);
</script>

<section class="flex min-w-0 flex-col overflow-hidden rounded-xl border bg-card">
    <header class="flex flex-wrap items-center gap-x-4 gap-y-2 border-b px-5 py-3">
        <h2 class="font-semibold">Agenda da semana</h2>
        <div class="flex items-center gap-2 text-sm">
            <Link href={weekHref(week.previous)} preserveScroll class="rounded-md border p-1 hover:bg-muted" aria-label="Semana anterior">
                <ChevronLeft class="size-4" />
            </Link>
            <span class="tabular-nums">{week.label}</span>
            <Link href={weekHref(week.next)} preserveScroll class="rounded-md border p-1 hover:bg-muted" aria-label="Próxima semana">
                <ChevronRight class="size-4" />
            </Link>
            {#if !week.isCurrent}
                <Link href={weekHref(week.current)} preserveScroll class="text-xs text-primary underline-offset-4 hover:underline">
                    Hoje
                </Link>
            {/if}
        </div>
        <div class="ml-auto flex flex-wrap gap-3 text-xs text-muted-foreground">
            <span class="flex items-center gap-1.5"><span class="size-2.5 rounded-sm bg-rose-600"></span>Psicopedagógico</span>
            <span class="flex items-center gap-1.5"><span class="size-2.5 rounded-sm bg-blue-600"></span>Psicológico</span>
            <span class="flex items-center gap-1.5"><span class="size-2.5 rounded-sm bg-amber-500"></span>Escuta / 1ª conversa</span>
        </div>
    </header>

    <div class="overflow-x-auto">
        <div class="min-w-[640px]" style:--days={week.days.length}>
            <div class="grid grid-cols-[56px_repeat(var(--days),1fr)] border-b text-sm">
                <div></div>
                {#each week.days as day (day.label)}
                    <div class={cn('border-l px-3 py-2 font-medium', day.isToday && 'text-primary')}>{day.label}</div>
                {/each}
            </div>
            <div class="grid grid-cols-[56px_repeat(var(--days),1fr)]">
                <div>
                    {#each hours as hour (hour)}
                        <div class="pr-2 text-right font-mono text-xs text-muted-foreground" style:height="{HOUR}px">
                            {String(hour).padStart(2, '0')}:00
                        </div>
                    {/each}
                </div>
                {#each week.days as day, dayIndex (day.label)}
                    <div class={cn('relative border-l', day.isToday && 'bg-accent/30')} style:height="{HOUR * hours.length}px">
                        {#each hours as hour (hour)}
                            <div class="border-t border-border/60" style:height="{HOUR}px"></div>
                        {/each}
                        {#each week.events.filter((event) => event.day === dayIndex) as event (event.id)}
                            <Link
                                href={show(event.id).url}
                                class={cn(
                                    'absolute inset-x-1 overflow-hidden rounded-md border-l-[3px] px-2 py-1 text-xs transition-colors',
                                    attendanceBlockClass(event),
                                    event.status !== 'scheduled' && 'opacity-60',
                                )}
                                style="top: {(event.start - hours[0]) * HOUR + 2}px; height: {event.hours * HOUR - 4}px"
                                title="{event.time} · {event.student.name} · {attendanceLabel(event)} · {event.professional} · {event.status_label}"
                            >
                                <p class="truncate font-semibold">{event.time} {event.student.name}</p>
                                <p class="truncate opacity-80">
                                    {attendanceLabel(event)}{showProfessional ? ` · ${event.professional}` : ''}
                                </p>
                            </Link>
                        {/each}
                    </div>
                {/each}
            </div>
        </div>
    </div>

    {#if week.events.length === 0}
        <p class="border-t px-5 py-3 text-sm text-muted-foreground">Nenhum atendimento agendado nesta semana.</p>
    {/if}
</section>
