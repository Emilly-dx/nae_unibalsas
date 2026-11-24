<script module lang="ts">
    import { dashboard } from '@/routes';
    import { show } from '@/routes/attendances';
    import { index as agendaIndex } from '@/routes/agenda';

    export const layout = (props: { attendance: { id: number; student: { name: string } } }) => ({
        breadcrumbs: [
            { title: 'Painel', href: dashboard() },
            { title: 'Agenda', href: agendaIndex() },
            { title: props.attendance.student.name, href: show(props.attendance.id) },
        ],
    });
</script>

<script lang="ts">
    import { Form, Link } from '@inertiajs/svelte';
    import FileText from '@lucide/svelte/icons/file-text';
    import Lock from '@lucide/svelte/icons/lock';
    import AppHead from '@/components/AppHead.svelte';
    import FieldTextarea from '@/components/form/FieldTextarea.svelte';
    import InputError from '@/components/InputError.svelte';
    import { Button } from '@/components/ui/button';
    import {
        Dialog,
        DialogClose,
        DialogContent,
        DialogDescription,
        DialogFooter,
        DialogTitle,
        DialogTrigger,
    } from '@/components/ui/dialog';
    import { Label } from '@/components/ui/label';
    import { attendanceChipClass, attendanceLabel, attendanceStatusClass } from '@/lib/attendances';
    import { cn } from '@/lib/utils';
    import { cancel, edit as reschedule, finish } from '@/routes/attendances';
    import { edit as registerForm } from '@/routes/attendances/register';
    import { create as createReferral } from '@/routes/referrals';
    import Route from '@lucide/svelte/icons/route';
    import { show as showStudent } from '@/routes/students';
    import { show as showRequest } from '@/routes/triage/requests';
    import type { AttendanceSummary } from '@/types';

    type Attendance = AttendanceSummary & {
        started_at: string | null;
        ended_at: string | null;
        registered_at: string | null;
        cancellation_reason: string | null;
        support_request_id: number | null;
    };

    let {
        attendance,
        content,
        can,
    }: {
        attendance: Attendance;
        content: { demand: string | null; conduct: string | null; agreements: string | null; next_return_on: string | null } | null;
        can: { register: boolean; cancel: boolean; reschedule: boolean; finish: boolean; viewContent: boolean; viewStudent: boolean; viewRequest: boolean; refer: boolean };
    } = $props();

    let cancelOpen = $state(false);
</script>

{#snippet item(label: string, value: string | number | null | undefined)}
    <div class="grid gap-0.5">
        <dt class="text-xs font-medium tracking-wide text-muted-foreground uppercase">{label}</dt>
        <dd class="text-sm">{value || '—'}</dd>
    </div>
{/snippet}

{#snippet block(label: string, value: string | null)}
    <div class="grid gap-1">
        <p class="text-xs font-medium tracking-wide text-muted-foreground uppercase">{label}</p>
        <p class="rounded-lg bg-muted/60 p-3 text-sm whitespace-pre-line">{value || '—'}</p>
    </div>
{/snippet}

<AppHead title="Atendimento" />

<div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
    <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
        <div class="space-y-1">
            <div class="flex flex-wrap items-center gap-2">
                <h1 class="text-2xl font-semibold tracking-tight">{attendanceLabel(attendance)}</h1>
                <span class={cn('rounded-full px-2.5 py-0.5 text-xs font-medium', attendanceStatusClass(attendance.status))}>
                    {attendance.status_label}
                </span>
            </div>
            <p class="text-sm text-muted-foreground">
                {attendance.student.name} · {attendance.date} às {attendance.time} · {attendance.professional}
            </p>
        </div>
        <div class="flex flex-wrap gap-2">
            {#if can.reschedule}
                <Button variant="outline" asChild>
                    {#snippet children(props)}<Link {...props} href={reschedule(attendance.id).url}>Remarcar</Link>{/snippet}
                </Button>
            {/if}
            {#if can.cancel}
                <Dialog bind:open={cancelOpen}>
                    <DialogTrigger><Button variant="outline">Cancelar agendamento</Button></DialogTrigger>
                    <DialogContent>
                        <Form {...cancel.form(attendance.id)} class="space-y-4" onSuccess={() => (cancelOpen = false)}>
                            {#snippet children({ errors, processing })}
                                <div class="space-y-1">
                                    <DialogTitle>Cancelar agendamento?</DialogTitle>
                                    <DialogDescription>O registro continua no histórico como cancelado.</DialogDescription>
                                </div>
                                <div class="grid gap-2">
                                    <Label for="cancellation_reason">Motivo (opcional)</Label>
                                    <FieldTextarea id="cancellation_reason" name="cancellation_reason" class="min-h-16" maxlength={255} />
                                    <InputError message={errors.cancellation_reason} />
                                </div>
                                <DialogFooter class="gap-2">
                                    <DialogClose><Button type="button" variant="secondary">Voltar</Button></DialogClose>
                                    <Button type="submit" variant="destructive" disabled={processing}>Cancelar agendamento</Button>
                                </DialogFooter>
                            {/snippet}
                        </Form>
                    </DialogContent>
                </Dialog>
            {/if}
            {#if can.finish}
                <Form {...finish.form(attendance.id)}>
                    {#snippet children({ processing })}
                        <Button type="submit" variant="outline" disabled={processing}>Concluir</Button>
                    {/snippet}
                </Form>
            {/if}
            {#if can.refer}
                <Button variant="outline" asChild>
                    {#snippet children(props)}
                        <Link {...props} href={createReferral({ query: { atendimento: attendance.id } }).url}>
                            <Route class="size-4" /> Encaminhar
                        </Link>
                    {/snippet}
                </Button>
            {/if}
            {#if can.register}
                <Button asChild>
                    {#snippet children(props)}
                        <Link {...props} href={registerForm(attendance.id).url}>
                            <FileText class="size-4" />
                            {attendance.status === 'scheduled' ? 'Registrar atendimento' : 'Editar registro'}
                        </Link>
                    {/snippet}
                </Button>
            {/if}
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-[1.6fr_1fr]">
        <section class="space-y-5 rounded-xl border bg-card p-5">
            <h2 class="font-semibold">Registro</h2>
            {#if attendance.status === 'cancelled'}
                <p class="text-sm text-muted-foreground">
                    Agendamento cancelado.{attendance.cancellation_reason ? ` Motivo: ${attendance.cancellation_reason}` : ''}
                </p>
            {:else if !attendance.registered_at}
                <p class="text-sm text-muted-foreground">
                    Atendimento ainda não registrado.
                    {can.register ? 'Depois de atender, use "Registrar atendimento".' : 'O registro é feito pelo profissional responsável.'}
                </p>
            {:else if content}
                {@render block('Demanda apresentada', content.demand)}
                {@render block('Condução e observações', content.conduct)}
                {@render block('Combinados e próximos passos', content.agreements)}
                <dl>{@render item('Próximo retorno', content.next_return_on)}</dl>
            {:else}
                <p class="flex items-start gap-2 rounded-lg bg-muted/60 p-3 text-sm text-muted-foreground">
                    <Lock class="mt-0.5 size-4 shrink-0" />
                    O conteúdo deste atendimento é sigiloso e visível apenas para o profissional responsável.
                </p>
            {/if}
        </section>

        <section class="rounded-xl border bg-card p-5">
            <h2 class="mb-4 font-semibold">Detalhes</h2>
            <dl class="grid gap-3">
                <div class="grid gap-0.5">
                    <dt class="text-xs font-medium tracking-wide text-muted-foreground uppercase">Estudante</dt>
                    <dd class="text-sm">
                        {#if can.viewStudent}
                            <Link href={showStudent(attendance.student.id).url} class="hover:underline">{attendance.student.name}</Link>
                        {:else}
                            {attendance.student.name}
                        {/if}
                        <span class="block text-xs text-muted-foreground">{attendance.student.course} · {attendance.student.period}º período</span>
                    </dd>
                </div>
                <div class="grid gap-0.5">
                    <dt class="text-xs font-medium tracking-wide text-muted-foreground uppercase">Tipo</dt>
                    <dd><span class={cn('rounded-full px-2.5 py-0.5 text-xs font-medium', attendanceChipClass(attendance))}>{attendanceLabel(attendance)}</span></dd>
                </div>
                {@render item('Profissional', attendance.professional)}
                {@render item('Modalidade', attendance.modality_label)}
                {@render item('Agendado para', `${attendance.date} às ${attendance.time} (${attendance.planned_minutes} min)`)}
                {#if attendance.started_at}
                    {@render item('Realizado', `${attendance.started_at} – ${attendance.ended_at ?? '?'}${attendance.duration_minutes ? ` (${attendance.duration_minutes} min)` : ''}`)}
                {/if}
                {@render item('Registrado em', attendance.registered_at)}
            </dl>
            {#if attendance.support_request_id && can.viewRequest}
                <Link href={showRequest(attendance.support_request_id).url} class="mt-4 inline-block text-sm text-primary underline underline-offset-4">
                    Ver solicitação de origem
                </Link>
            {/if}
        </section>
    </div>
</div>
