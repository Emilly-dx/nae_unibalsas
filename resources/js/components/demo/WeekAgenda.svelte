<script lang="ts">
    import ChevronLeft from '@lucide/svelte/icons/chevron-left';
    import ChevronRight from '@lucide/svelte/icons/chevron-right';
    import type { AgendaEvent } from '@/lib/demo/data';
    import {
        agendaHours,
        attendanceTypeLabels,
        attendanceTypeStyles,
        weekDays,
        weekLabel,
    } from '@/lib/demo/data';
    import { cn } from '@/lib/utils';

    let { events }: { events: AgendaEvent[] } = $props();

    const HOUR = 56;
    const first = agendaHours[0];
</script>

<section class="flex min-w-0 flex-col overflow-hidden rounded-xl border bg-card">
    <header class="flex flex-wrap items-center gap-x-4 gap-y-2 border-b px-5 py-3">
        <h2 class="font-semibold">Agenda da semana</h2>
        <div class="flex items-center gap-2 text-sm">
            <button type="button" class="rounded-md border p-1 hover:bg-muted" aria-label="Semana anterior">
                <ChevronLeft class="size-4" />
            </button>
            <span class="tabular-nums">{weekLabel}</span>
            <button type="button" class="rounded-md border p-1 hover:bg-muted" aria-label="Próxima semana">
                <ChevronRight class="size-4" />
            </button>
        </div>
        <div class="ml-auto flex flex-wrap gap-3 text-xs text-muted-foreground">
            <span class="flex items-center gap-1.5"><span class="size-2.5 rounded-sm bg-rose-600"></span>Psicopedagógico</span>
            <span class="flex items-center gap-1.5"><span class="size-2.5 rounded-sm bg-blue-600"></span>Psicológico</span>
            <span class="flex items-center gap-1.5"><span class="size-2.5 rounded-sm bg-amber-500"></span>Escuta e orientação</span>
        </div>
    </header>

    <div class="overflow-x-auto">
        <div class="min-w-[640px]">
            <div class="grid grid-cols-[56px_repeat(5,1fr)] border-b text-sm">
                <div></div>
                {#each weekDays as day (day)}
                    <div class="border-l px-3 py-2 font-medium">{day}</div>
                {/each}
            </div>
            <div class="grid grid-cols-[56px_repeat(5,1fr)]">
                <div>
                    {#each agendaHours as hour (hour)}
                        <div class="pr-2 text-right font-mono text-xs text-muted-foreground" style:height="{HOUR}px">
                            {String(hour).padStart(2, '0')}:00
                        </div>
                    {/each}
                </div>
                {#each weekDays as day, dayIndex (day)}
                    <div class="relative border-l" style:height="{HOUR * agendaHours.length}px">
                        {#each agendaHours as hour (hour)}
                            <div class="border-t border-border/60" style:height="{HOUR}px"></div>
                        {/each}
                        {#each events.filter((event) => event.day === dayIndex) as event (event.student + event.start)}
                            <div
                                class={cn('absolute inset-x-1 overflow-hidden rounded-md border-l-[3px] px-2 py-1 text-xs', attendanceTypeStyles[event.type])}
                                style:top="{(event.start - first) * HOUR + 2}px"
                                style:height="{event.duration * HOUR - 4}px"
                            >
                                <p class="truncate font-semibold">{event.student}</p>
                                <p class="truncate opacity-80">{attendanceTypeLabels[event.type]}</p>
                            </div>
                        {/each}
                    </div>
                {/each}
            </div>
        </div>
    </div>
</section>
