<script module lang="ts">
    import { dashboard } from '@/routes';
    import { index } from '@/routes/triage';

    export const layout = (props: { request: { id: number; student: { name: string } } }) => ({
        breadcrumbs: [
            { title: 'Painel', href: dashboard() },
            { title: 'Triagem', href: index() },
            { title: `Solicitação — ${props.request.student.name}`, href: index() },
        ],
    });
</script>

<script lang="ts">
    import { Form, Link } from '@inertiajs/svelte';
    import Lock from '@lucide/svelte/icons/lock';
    import AppHead from '@/components/AppHead.svelte';
    import FieldSelect from '@/components/form/FieldSelect.svelte';
    import FieldTextarea from '@/components/form/FieldTextarea.svelte';
    import Segmented from '@/components/form/Segmented.svelte';
    import InputError from '@/components/InputError.svelte';
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
    import { attendanceLabel, attendanceStatusClass, todayValue } from '@/lib/attendances';
    import { cn } from '@/lib/utils';
    import { show as showAttendance } from '@/routes/attendances';
    import { show as showStudent } from '@/routes/students';
    import { giveBack, schedule } from '@/routes/triage/requests';
    import type { AttendanceSummary, NumberOption, Option } from '@/types';

    type SupportRequest = {
        id: number;
        student: { id: number; name: string; registration: string; course: string; period: number };
        requester_name: string;
        requester_email: string | null;
        origin: string;
        reasons: string[];
        description: string;
        student_aware: boolean;
        status: string;
        status_label: string;
        priority: string;
        priority_label: string;
        assignee: string | null;
        triager: string | null;
        triaged_at: string | null;
        triage_notes: string | null;
        triage_notes_hidden: boolean;
        created_at: string | null;
    };

    let {
        request,
        attendances,
        canPerform,
        professionals,
        priorities,
        types,
        modalities,
    }: {
        request: SupportRequest;
        attendances: AttendanceSummary[];
        canPerform: boolean;
        professionals: NumberOption[];
        priorities: Option[];
        types: Option[];
        modalities: Option[];
    } = $props();

    let priority = $state('normal');
    let modality = $state('presential');
    let returnOpen = $state(false);

    const statusClass: Record<string, string> = {
        pending_triage: 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300',
        scheduled: 'bg-blue-100 text-blue-700 dark:bg-blue-950 dark:text-blue-300',
        returned: 'bg-muted text-muted-foreground',
    };
</script>

{#snippet item(label: string, value: string | null | undefined)}
    <div class="grid gap-0.5">
        <dt class="text-xs font-medium tracking-wide text-muted-foreground uppercase">{label}</dt>
        <dd class="text-sm">{value || '—'}</dd>
    </div>
{/snippet}

<AppHead title="Solicitação de apoio" />

<div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
    <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight">Solicitação de apoio</h1>
            <p class="text-sm text-muted-foreground">Recebida em {request.created_at} · {request.origin}</p>
        </div>
        <Button variant="outline" asChild>
            {#snippet children(props)}<Link {...props} href={index().url}>Voltar à fila</Link>{/snippet}
        </Button>
    </div>

    <div class="grid gap-6 lg:grid-cols-[1.6fr_1fr] [&>*]:min-w-0">
        <section class="space-y-5 rounded-xl border bg-card p-5">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h2 class="font-semibold">Dados da solicitação</h2>
                    <p class="text-xs text-muted-foreground">Preenchido pela coordenação do curso.</p>
                </div>
                <span class={cn('rounded-full px-2.5 py-0.5 text-xs font-medium', statusClass[request.status])}>
                    {request.status_label}
                </span>
            </div>

            <div class="rounded-lg border p-4">
                <Link href={showStudent(request.student.id).url} class="font-medium hover:underline">{request.student.name}</Link>
                <p class="text-xs text-muted-foreground">
                    {request.student.registration} · {request.student.course} · {request.student.period}º período
                </p>
            </div>

            <dl class="grid gap-4 sm:grid-cols-2">
                {@render item('Solicitante', request.requester_name)}
                {@render item('E-mail do solicitante', request.requester_email)}
                {@render item('Estudante ciente', request.student_aware ? 'Sim' : 'Não')}
                {@render item('Motivos', request.reasons.join(', '))}
            </dl>

            <div class="grid gap-1">
                <p class="text-xs font-medium tracking-wide text-muted-foreground uppercase">Descrição da situação</p>
                <p class="rounded-lg bg-muted/60 p-3 text-sm whitespace-pre-line">{request.description}</p>
            </div>

            {#if attendances.length > 0}
                <div class="grid gap-2">
                    <p class="text-xs font-medium tracking-wide text-muted-foreground uppercase">Atendimentos gerados</p>
                    <ul class="divide-y rounded-lg border">
                        {#each attendances as attendance (attendance.id)}
                            <li class="flex items-center justify-between gap-3 px-3 py-2 text-sm">
                                <Link href={showAttendance(attendance.id).url} class="hover:underline">
                                    {attendance.date} às {attendance.time} · {attendanceLabel(attendance)} · {attendance.professional}
                                </Link>
                                <span class={cn('rounded-full px-2 py-0.5 text-xs', attendanceStatusClass(attendance.status))}>
                                    {attendance.status_label}
                                </span>
                            </li>
                        {/each}
                    </ul>
                </div>
            {/if}
        </section>

        <section class="flex flex-col gap-4 rounded-xl border bg-card p-5">
            <div>
                <h2 class="font-semibold">Triagem</h2>
                <p class="text-xs text-muted-foreground">Uso exclusivo da equipe do NAE.</p>
            </div>

            {#if request.status !== 'pending_triage'}
                <dl class="grid gap-3">
                    {@render item('Prioridade', request.priority_label)}
                    {@render item('Profissional designado', request.assignee)}
                    {@render item('Triagem feita por', request.triager ? `${request.triager} em ${request.triaged_at}` : null)}
                </dl>
                {#if request.triage_notes}
                    <div class="grid gap-1">
                        <p class="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                            {request.status === 'returned' ? 'Motivo da devolução' : 'Parecer da triagem'}
                        </p>
                        <p class="rounded-lg bg-muted/60 p-3 text-sm whitespace-pre-line">{request.triage_notes}</p>
                    </div>
                {:else if request.triage_notes_hidden}
                    <p class="flex items-center gap-2 text-xs text-muted-foreground">
                        <Lock class="size-3.5" /> O parecer é visível apenas para quem fez a triagem.
                    </p>
                {/if}
            {:else if canPerform}
                <Form {...schedule.form(request.id)} class="flex flex-1 flex-col gap-4" options={{ preserveScroll: true }}>
                    {#snippet children({ errors, processing })}
                        <div class="grid gap-2">
                            <span class="text-sm font-medium">Prioridade</span>
                            <Segmented name="priority" options={priorities} bind:value={priority} />
                        </div>
                        <div class="grid gap-2">
                            <Label for="assigned_to">Profissional designado <span class="text-primary">*</span></Label>
                            <FieldSelect id="assigned_to" name="assigned_to">
                                <option value="">Selecione</option>
                                {#each professionals as option (option.value)}<option value={option.value}>{option.label}</option>{/each}
                            </FieldSelect>
                            <InputError message={errors.assigned_to} />
                            {#if professionals.length === 0}
                                <p class="text-xs text-muted-foreground">Nenhum usuário com perfil de profissional do NAE.</p>
                            {/if}
                        </div>
                        <div class="grid gap-2">
                            <Label for="type">Tipo da 1ª conversa</Label>
                            <FieldSelect id="type" name="type" value="psychopedagogical">
                                {#each types as option (option.value)}<option value={option.value}>{option.label}</option>{/each}
                            </FieldSelect>
                        </div>
                        <div class="grid gap-2">
                            <span class="text-sm font-medium">Modalidade</span>
                            <Segmented name="modality" options={modalities} bind:value={modality} />
                        </div>
                        <div class="grid gap-2">
                            <Label for="triage_notes">Parecer da triagem</Label>
                            <FieldTextarea id="triage_notes" name="triage_notes" maxlength={5000} placeholder="Registro da análise inicial (visível só para você)" />
                            <InputError message={errors.triage_notes} />
                        </div>
                        <div class="grid grid-cols-2 gap-3 sm:grid-cols-[1.4fr_1fr_1fr] [&>*:first-child]:col-span-2 sm:[&>*:first-child]:col-span-1">
                            <div class="grid gap-2">
                                <Label for="date">Data <span class="text-primary">*</span></Label>
                                <Input id="date" name="date" type="date" min={todayValue()} />
                            </div>
                            <div class="grid gap-2">
                                <Label for="time">Horário <span class="text-primary">*</span></Label>
                                <Input id="time" name="time" type="time" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="planned_minutes">Minutos</Label>
                                <Input id="planned_minutes" name="planned_minutes" type="number" min={15} max={240} step={5} value={50} />
                            </div>
                        </div>
                        <InputError message={errors.date || errors.time || errors.planned_minutes} />
                        <div class="mt-auto flex flex-col gap-2 pt-4">
                            <Button type="submit" disabled={processing}>{processing ? 'Agendando…' : 'Agendar 1ª conversa'}</Button>
                            <Button type="button" variant="outline" onclick={() => (returnOpen = true)}>Devolver à coordenação</Button>
                        </div>
                    {/snippet}
                </Form>
            {:else}
                <p class="text-sm text-muted-foreground">Aguardando a triagem pela psicopedagoga do NAE.</p>
            {/if}
        </section>
    </div>
</div>

<Dialog bind:open={returnOpen}>
    <DialogContent>
        <Form {...giveBack.form(request.id)} class="space-y-4" onSuccess={() => (returnOpen = false)}>
            {#snippet children({ errors, processing })}
                <div class="space-y-1">
                    <DialogTitle>Devolver à coordenação</DialogTitle>
                    <DialogDescription>
                        A solicitação sai da fila de triagem. Explique o que precisa ser complementado.
                    </DialogDescription>
                </div>
                <div class="grid gap-2">
                    <Label for="return_notes">Motivo da devolução <span class="text-primary">*</span></Label>
                    <FieldTextarea id="return_notes" name="triage_notes" maxlength={5000} />
                    <InputError message={errors.triage_notes} />
                </div>
                <DialogFooter class="gap-2">
                    <DialogClose><Button type="button" variant="secondary">Cancelar</Button></DialogClose>
                    <Button type="submit" disabled={processing}>Devolver</Button>
                </DialogFooter>
            {/snippet}
        </Form>
    </DialogContent>
</Dialog>
