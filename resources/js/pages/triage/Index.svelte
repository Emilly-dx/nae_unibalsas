<script module lang="ts">
    import { dashboard } from '@/routes';
    import { index } from '@/routes/triage';

    export const layout = {
        breadcrumbs: [
            { title: 'Painel', href: dashboard() },
            { title: 'Triagem e atendimentos', href: index() },
        ],
    };
</script>

<script lang="ts">
    import { Link, router } from '@inertiajs/svelte';
    import CalendarPlus from '@lucide/svelte/icons/calendar-plus';
    import ChevronLeft from '@lucide/svelte/icons/chevron-left';
    import ChevronRight from '@lucide/svelte/icons/chevron-right';
    import FileText from '@lucide/svelte/icons/file-text';
    import Inbox from '@lucide/svelte/icons/inbox';
    import Plus from '@lucide/svelte/icons/plus';
    import AppHead from '@/components/AppHead.svelte';
    import WeekAgenda from '@/components/attendances/WeekAgenda.svelte';
    import { Button } from '@/components/ui/button';
    import { attendanceChipClass, attendanceLabel, attendanceStatusClass } from '@/lib/attendances';
    import { cn } from '@/lib/utils';
    import { create as scheduleAttendance, show as showAttendance } from '@/routes/attendances';
    import { create as registerAttendance } from '@/routes/attendances/register';
    import { create as createRequest, show as showRequest } from '@/routes/triage/requests';
    import type { AttendanceSummary, Paginated, QueueItem, Week } from '@/types';

    type Tab = 'triagem' | 'agenda' | 'atendimentos';

    let {
        tab,
        queue,
        week,
        registered,
        can,
    }: {
        tab: Tab;
        queue: QueueItem[];
        week: Week | null;
        registered: Paginated<AttendanceSummary> | null;
        can: {
            triage: boolean;
            createRequest: boolean;
            schedule: boolean;
            register: boolean;
            attendances: boolean;
        };
    } = $props();

    // A aba atual fica na URL, para que recarregar ou voltar mantenha a tela.
    function selectTab(value: Tab): void {
        router.get(index().url, { aba: value }, { preserveState: true, preserveScroll: true, replace: true });
    }

    const tabs = $derived(
        (
            [
                { value: 'triagem', label: 'Triagem', show: can.triage },
                { value: 'agenda', label: 'Agenda', show: can.attendances },
                { value: 'atendimentos', label: 'Atendimentos registrados', show: can.attendances },
            ] as const
        ).filter((item) => item.show),
    );
</script>

<AppHead title="Triagem e atendimentos" />

<div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
    <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight">Triagem e atendimentos</h1>
            <p class="text-sm text-muted-foreground">
                Solicitações enviadas pelas coordenações, agenda do núcleo e registro dos atendimentos.
            </p>
        </div>
        <div class="flex flex-wrap gap-2">
            {#if can.createRequest}
                <Button variant="outline" asChild>
                    {#snippet children(props)}
                        <Link {...props} href={createRequest().url}><Plus class="size-4" /> Nova solicitação</Link>
                    {/snippet}
                </Button>
            {/if}
            {#if can.schedule}
                <Button variant="outline" asChild>
                    {#snippet children(props)}
                        <Link {...props} href={scheduleAttendance().url}><CalendarPlus class="size-4" /> Agendar</Link>
                    {/snippet}
                </Button>
            {/if}
            {#if can.register}
                <Button asChild>
                    {#snippet children(props)}
                        <Link {...props} href={registerAttendance().url}><FileText class="size-4" /> Registrar atendimento</Link>
                    {/snippet}
                </Button>
            {/if}
        </div>
    </div>

    <div class="flex w-fit flex-wrap rounded-lg border bg-muted/60 p-1" role="tablist">
        {#each tabs as item (item.value)}
            <button
                type="button"
                role="tab"
                aria-selected={tab === item.value}
                class={cn(
                    'flex items-center gap-2 rounded-md px-3 py-1.5 text-sm transition-colors',
                    tab === item.value
                        ? 'bg-background font-medium shadow-sm dark:bg-secondary'
                        : 'text-muted-foreground hover:text-foreground',
                )}
                onclick={() => selectTab(item.value)}
            >
                {item.label}
                {#if item.value === 'triagem' && queue.length > 0}
                    <span class="rounded-full bg-primary px-1.5 text-xs text-primary-foreground">{queue.length}</span>
                {/if}
            </button>
        {/each}
    </div>

    {#if tab === 'triagem' && can.triage}
        <div class={cn('grid gap-6', week && 'xl:grid-cols-[minmax(320px,1fr)_2fr]')}>
            <section class="overflow-hidden rounded-xl border bg-card">
                <header class="border-b px-5 py-4">
                    <h2 class="font-semibold">Fila de triagem</h2>
                    <p class="text-xs text-muted-foreground">Formulários de Solicitação do Apoio do NAE</p>
                </header>
                {#if queue.length === 0}
                    <div class="flex flex-col items-center gap-2 p-8 text-center text-sm text-muted-foreground">
                        <Inbox class="size-6" />
                        Nenhuma solicitação aguardando triagem.
                    </div>
                {:else}
                    <ul class="divide-y">
                        {#each queue as request (request.id)}
                            <li class="space-y-2 px-5 py-4">
                                <div class="flex items-center justify-between gap-2">
                                    <p class="font-medium">{request.student}</p>
                                    {#if request.priority === 'high'}
                                        <span class="rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-800 dark:bg-amber-950 dark:text-amber-300">Prioritária</span>
                                    {:else}
                                        <span class="rounded-full bg-muted px-2 py-0.5 text-xs font-medium text-muted-foreground">Normal</span>
                                    {/if}
                                </div>
                                <p class="text-sm">{request.reasons.join(', ') || 'Motivo não informado'}</p>
                                <p class="text-xs text-muted-foreground">{request.origin} · {request.date}</p>
                                <Button size="sm" asChild>
                                    {#snippet children(props)}
                                        <Link {...props} href={showRequest(request.id).url}>Abrir e triar</Link>
                                    {/snippet}
                                </Button>
                            </li>
                        {/each}
                    </ul>
                {/if}
            </section>
            {#if week}<WeekAgenda {week} />{/if}
        </div>
    {:else if tab === 'agenda' && week}
        <WeekAgenda {week} />
    {:else if tab === 'atendimentos' && registered}
        <section class="overflow-hidden rounded-xl border bg-card">
            {#if registered.data.length === 0}
                <p class="p-8 text-center text-sm text-muted-foreground">Nenhum atendimento registrado ainda.</p>
            {:else}
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[720px] text-sm">
                        <thead class="bg-muted/50 text-left text-muted-foreground">
                            <tr>
                                <th class="px-5 py-3 font-medium">Data</th>
                                <th class="px-5 py-3 font-medium">Estudante</th>
                                <th class="px-5 py-3 font-medium">Tipo</th>
                                <th class="px-5 py-3 font-medium">Profissional</th>
                                <th class="px-5 py-3 font-medium">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            {#each registered.data as attendance (attendance.id)}
                                <tr class="border-t transition-colors hover:bg-muted/40">
                                    <td class="px-5 py-3 tabular-nums">
                                        <Link href={showAttendance(attendance.id).url} class="hover:underline">{attendance.date}</Link>
                                    </td>
                                    <td class="px-5 py-3">
                                        <p class="font-medium">{attendance.student.name}</p>
                                        <p class="text-xs text-muted-foreground">{attendance.student.course} · {attendance.student.period}º período</p>
                                    </td>
                                    <td class="px-5 py-3">
                                        <span class={cn('rounded-full px-2.5 py-0.5 text-xs font-medium', attendanceChipClass(attendance))}>
                                            {attendanceLabel(attendance)}
                                        </span>
                                    </td>
                                    <td class="px-5 py-3">{attendance.professional}</td>
                                    <td class="px-5 py-3">
                                        <span class={cn('rounded-full px-2.5 py-0.5 text-xs font-medium', attendanceStatusClass(attendance.status))}>
                                            {attendance.status_label}
                                        </span>
                                    </td>
                                </tr>
                            {/each}
                        </tbody>
                    </table>
                </div>
            {/if}
            <div class="flex flex-wrap items-center justify-between gap-3 border-t px-5 py-3 text-xs text-muted-foreground">
                <p>O conteúdo dos atendimentos é sigiloso: a lista mostra só os dados de identificação do registro.</p>
                {#if registered.last_page > 1}
                    <div class="flex items-center gap-2">
                        <Button variant="outline" size="sm" disabled={!registered.prev_page_url}
                            onclick={() => registered.prev_page_url && router.get(registered.prev_page_url, {}, { preserveScroll: true })}>
                            <ChevronLeft class="size-4" /> Anterior
                        </Button>
                        <span>Página {registered.current_page} de {registered.last_page}</span>
                        <Button variant="outline" size="sm" disabled={!registered.next_page_url}
                            onclick={() => registered.next_page_url && router.get(registered.next_page_url, {}, { preserveScroll: true })}>
                            Próxima <ChevronRight class="size-4" />
                        </Button>
                    </div>
                {/if}
            </div>
        </section>
    {/if}
</div>
