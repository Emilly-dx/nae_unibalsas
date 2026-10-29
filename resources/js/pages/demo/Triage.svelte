<script module lang="ts">
    import { dashboard } from '@/routes';

    export const layout = {
        breadcrumbs: [
            { title: 'Painel', href: dashboard() },
            { title: 'Triagem e atendimentos', href: '/triagem' },
        ],
    };
</script>

<script lang="ts">
    import { Link } from '@inertiajs/svelte';
    import FileText from '@lucide/svelte/icons/file-text';
    import Plus from '@lucide/svelte/icons/plus';
    import { toast } from 'svelte-sonner';
    import AppHead from '@/components/AppHead.svelte';
    import DemoNotice from '@/components/demo/DemoNotice.svelte';
    import FieldSelect from '@/components/demo/FieldSelect.svelte';
    import WeekAgenda from '@/components/demo/WeekAgenda.svelte';
    import { Button } from '@/components/ui/button';
    import {
        Dialog,
        DialogClose,
        DialogContent,
        DialogDescription,
        DialogFooter,
        DialogTitle,
    } from '@/components/ui/dialog';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';
    import type { AgendaEvent, TriageRequest } from '@/lib/demo/data';
    import {
        attendanceChipStyles,
        attendanceTypeLabels,
        demoStudent,
        registeredAttendances,
        triageQueue,
        weekAgenda,
        weekDays,
    } from '@/lib/demo/data';
    import { cn } from '@/lib/utils';

    type Tab = 'triagem' | 'agenda' | 'atendimentos';

    const initialTab = (): Tab => {
        if (typeof window === 'undefined') {
            return 'triagem';
        }

        const tab = new URLSearchParams(window.location.search).get('aba');

        return tab === 'agenda' || tab === 'atendimentos' ? tab : 'triagem';
    };

    let tab = $state<Tab>(initialTab());
    let queue = $state<TriageRequest[]>([...triageQueue]);
    let events = $state<AgendaEvent[]>([...weekAgenda]);

    let selected = $state<TriageRequest | null>(null);
    let formOpen = $state(false);
    let scheduleOpen = $state(false);
    let scheduleDay = $state('2');
    let scheduleTime = $state('11:00');
    let professional = $state('Psicopedagoga do NAE');

    function openForm(request: TriageRequest): void {
        selected = request;
        formOpen = true;
    }

    function openSchedule(request: TriageRequest): void {
        selected = request;
        formOpen = false;
        scheduleOpen = true;
    }

    function confirmSchedule(): void {
        if (!selected) {
            return;
        }

        const [hour, minutes] = scheduleTime.split(':').map(Number);
        const request = selected;

        events = [
            ...events,
            {
                day: Number(scheduleDay),
                start: hour + minutes / 60,
                duration: 1,
                student: request.student,
                type: 'primeira_conversa',
            },
        ];
        queue = queue.filter((item) => item.id !== request.id);
        scheduleOpen = false;
        toast.success(`1ª conversa de ${request.student} agendada (${weekDays[Number(scheduleDay)]}, ${scheduleTime}).`);
    }

    const tabs: { value: Tab; label: string }[] = [
        { value: 'triagem', label: 'Triagem' },
        { value: 'agenda', label: 'Agenda' },
        { value: 'atendimentos', label: 'Atendimentos registrados' },
    ];
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
            <Button variant="outline" asChild>
                {#snippet children(props)}
                    <Link {...props} href="/triagem/nova-solicitacao"><Plus class="size-4" /> Nova solicitação</Link>
                {/snippet}
            </Button>
            <Button asChild>
                {#snippet children(props)}
                    <Link {...props} href="/atendimentos/registrar"><FileText class="size-4" /> Registrar atendimento</Link>
                {/snippet}
            </Button>
        </div>
    </div>

    <DemoNotice milestone="M4 — Triagem + Atendimentos" />

    <div class="flex w-fit flex-wrap rounded-lg border bg-muted/60 p-1" role="tablist">
        {#each tabs as item (item.value)}
            <button
                type="button"
                role="tab"
                aria-selected={tab === item.value}
                class={cn(
                    'flex items-center gap-2 rounded-md px-3 py-1.5 text-sm transition-colors',
                    tab === item.value ? 'bg-background font-medium shadow-sm dark:bg-secondary' : 'text-muted-foreground hover:text-foreground',
                )}
                onclick={() => (tab = item.value)}
            >
                {item.label}
                {#if item.value === 'triagem' && queue.length > 0}
                    <span class="rounded-full bg-primary px-1.5 text-xs text-primary-foreground">{queue.length}</span>
                {/if}
            </button>
        {/each}
    </div>

    {#if tab === 'triagem'}
        <div class="grid gap-6 xl:grid-cols-[minmax(320px,1fr)_2fr]">
            <section class="overflow-hidden rounded-xl border bg-card">
                <header class="border-b px-5 py-4">
                    <h2 class="font-semibold">Fila de triagem</h2>
                    <p class="text-xs text-muted-foreground">Formulários de Solicitação do Apoio do NAE</p>
                </header>
                {#if queue.length === 0}
                    <p class="p-6 text-center text-sm text-muted-foreground">Nenhuma solicitação aguardando triagem.</p>
                {:else}
                    <ul class="divide-y">
                        {#each queue as request (request.id)}
                            <li class="space-y-2 px-5 py-4">
                                <div class="flex items-center justify-between gap-2">
                                    <p class="font-medium">{request.student}</p>
                                    {#if request.priority === 'prioritaria'}
                                        <span class="rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-800 dark:bg-amber-950 dark:text-amber-300">Prioritária</span>
                                    {:else}
                                        <span class="rounded-full bg-muted px-2 py-0.5 text-xs font-medium text-muted-foreground">Normal</span>
                                    {/if}
                                </div>
                                <p class="text-sm">{request.reason}</p>
                                <p class="text-xs text-muted-foreground">{request.origin} · {request.date}</p>
                                <div class="flex flex-wrap gap-2 pt-1">
                                    <Button variant="outline" size="sm" onclick={() => openForm(request)}>Ver formulário</Button>
                                    <Button size="sm" onclick={() => openSchedule(request)}>Agendar 1ª conversa</Button>
                                </div>
                            </li>
                        {/each}
                    </ul>
                {/if}
            </section>
            <WeekAgenda {events} />
        </div>
    {:else if tab === 'agenda'}
        <WeekAgenda {events} />
    {:else}
        <section class="overflow-hidden rounded-xl border bg-card">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[640px] text-sm">
                    <thead class="bg-muted/50 text-left text-muted-foreground">
                        <tr>
                            <th class="px-5 py-3 font-medium">Data</th>
                            <th class="px-5 py-3 font-medium">Estudante</th>
                            <th class="px-5 py-3 font-medium">Tipo</th>
                            <th class="px-5 py-3 font-medium">Profissional</th>
                            <th class="px-5 py-3 font-medium">Modalidade</th>
                        </tr>
                    </thead>
                    <tbody>
                        {#each registeredAttendances as attendance (attendance.date + attendance.student)}
                            {@const student = demoStudent(attendance.student)}
                            <tr class="border-t">
                                <td class="px-5 py-3 tabular-nums">{attendance.date}</td>
                                <td class="px-5 py-3">
                                    <p class="font-medium">{student.name}</p>
                                    <p class="text-xs text-muted-foreground">{student.course} · {student.period}º período</p>
                                </td>
                                <td class="px-5 py-3">
                                    <span class={cn('rounded-full px-2.5 py-0.5 text-xs font-medium', attendanceChipStyles[attendance.type])}>
                                        {attendanceTypeLabels[attendance.type]}
                                    </span>
                                </td>
                                <td class="px-5 py-3">{attendance.professional}</td>
                                <td class="px-5 py-3">{attendance.modality}</td>
                            </tr>
                        {/each}
                    </tbody>
                </table>
            </div>
            <p class="border-t px-5 py-3 text-xs text-muted-foreground">
                O conteúdo dos atendimentos é sigiloso: a lista mostra apenas os dados de identificação do registro.
            </p>
        </section>
    {/if}
</div>

<Dialog bind:open={formOpen}>
    <DialogContent>
        {#if selected}
            {@const student = demoStudent(selected.student)}
            <div class="space-y-4">
                <div class="space-y-1">
                    <DialogTitle>Solicitação de apoio — {selected.student}</DialogTitle>
                    <DialogDescription>{student.course} · {student.period}º período</DialogDescription>
                </div>
                <dl class="grid grid-cols-[auto_1fr] gap-x-4 gap-y-2 text-sm">
                    <dt class="text-muted-foreground">Solicitante</dt><dd>{selected.requester}</dd>
                    <dt class="text-muted-foreground">Origem</dt><dd>{selected.origin}</dd>
                    <dt class="text-muted-foreground">Recebida em</dt><dd>{selected.date}</dd>
                    <dt class="text-muted-foreground">Motivo</dt><dd>{selected.reason}</dd>
                </dl>
                <div class="rounded-lg bg-muted/60 p-3 text-sm">{selected.description}</div>
                <DialogFooter class="gap-2">
                    <DialogClose><Button variant="secondary">Fechar</Button></DialogClose>
                    <Button onclick={() => selected && openSchedule(selected)}>Agendar 1ª conversa</Button>
                </DialogFooter>
            </div>
        {/if}
    </DialogContent>
</Dialog>

<Dialog bind:open={scheduleOpen}>
    <DialogContent>
        {#if selected}
            <div class="space-y-4">
                <div class="space-y-1">
                    <DialogTitle>Agendar 1ª conversa</DialogTitle>
                    <DialogDescription>{selected.student} · {selected.reason}</DialogDescription>
                </div>
                <div class="grid gap-3 sm:grid-cols-2">
                    <div class="grid gap-2">
                        <Label for="schedule-day">Dia</Label>
                        <FieldSelect id="schedule-day" bind:value={scheduleDay}>
                            {#each weekDays as day, index (day)}
                                <option value={String(index)}>{day}</option>
                            {/each}
                        </FieldSelect>
                    </div>
                    <div class="grid gap-2">
                        <Label for="schedule-time">Horário</Label>
                        <Input id="schedule-time" type="time" min="08:00" max="17:00" step="1800" bind:value={scheduleTime} />
                    </div>
                    <div class="grid gap-2 sm:col-span-2">
                        <Label for="schedule-professional">Profissional designado</Label>
                        <FieldSelect id="schedule-professional" bind:value={professional}>
                            <option>Psicopedagoga do NAE</option>
                            <option>Psicóloga do NAE</option>
                            <option>Pedagoga do NAE</option>
                        </FieldSelect>
                    </div>
                </div>
                <DialogFooter class="gap-2">
                    <DialogClose><Button variant="secondary">Cancelar</Button></DialogClose>
                    <Button onclick={confirmSchedule}>Confirmar agendamento</Button>
                </DialogFooter>
            </div>
        {/if}
    </DialogContent>
</Dialog>
