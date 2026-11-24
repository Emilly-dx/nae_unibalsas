<script module lang="ts">
    import { dashboard } from '@/routes';
    import { index, show } from '@/routes/referrals';

    export const layout = (props: { referral: { id: number; student: { name: string } } }) => ({
        breadcrumbs: [
            { title: 'Painel', href: dashboard() },
            { title: 'Encaminhamentos', href: index() },
            { title: props.referral.student.name, href: show(props.referral.id) },
        ],
    });
</script>

<script lang="ts">
    import { Form, Link } from '@inertiajs/svelte';
    import Lock from '@lucide/svelte/icons/lock';
    import AppHead from '@/components/AppHead.svelte';
    import FieldSelect from '@/components/form/FieldSelect.svelte';
    import FieldTextarea from '@/components/form/FieldTextarea.svelte';
    import InputError from '@/components/InputError.svelte';
    import { Button } from '@/components/ui/button';
    import { Label } from '@/components/ui/label';
    import { referralStatusClass } from '@/lib/attendances';
    import { cn } from '@/lib/utils';
    import { show as showAttendance } from '@/routes/attendances';
    import { cancel, reopen, status } from '@/routes/referrals';
    import { show as showStudent } from '@/routes/students';
    import type { Option, ReferralSummary } from '@/types';

    let {
        referral,
        content,
        statusOptions,
        can,
    }: {
        referral: ReferralSummary & { returned_at: string | null; attendance_label: string | null };
        content: { reason: string; return_notes: string | null } | null;
        statusOptions: Option[];
        can: { update: boolean; cancel: boolean; reopen: boolean; viewStudent: boolean; viewAttendance: boolean };
    } = $props();

    // svelte-ignore state_referenced_locally
    let nextStatus = $state(referral.status === 'sent' ? 'in_progress' : referral.status);
</script>

{#snippet item(label: string, value: string | null | undefined)}
    <div class="grid gap-0.5">
        <dt class="text-xs font-medium tracking-wide text-muted-foreground uppercase">{label}</dt>
        <dd class="text-sm">{value || '—'}</dd>
    </div>
{/snippet}

<AppHead title="Encaminhamento" />

<div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
    <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
        <div class="space-y-1">
            <div class="flex flex-wrap items-center gap-2">
                <h1 class="text-2xl font-semibold tracking-tight">Encaminhamento {referral.kind_label.toLowerCase()}</h1>
                <span class={cn('rounded-full px-2.5 py-0.5 text-xs font-medium', referralStatusClass(referral.status))}>
                    {referral.status_label}
                </span>
            </div>
            <p class="text-sm text-muted-foreground">
                {referral.student.name} · {referral.origin} → {referral.destination} · {referral.referred_on}
            </p>
        </div>
        <div class="flex flex-wrap gap-2">
            {#if can.cancel}
                <Form {...cancel.form(referral.id)} onBefore={() => confirm('Cancelar este encaminhamento? Ele continua no histórico como cancelado.')}>
                    {#snippet children({ processing })}
                        <Button type="submit" variant="outline" disabled={processing}>Cancelar encaminhamento</Button>
                    {/snippet}
                </Form>
            {/if}
            {#if can.reopen}
                <Form {...reopen.form(referral.id)}>
                    {#snippet children({ processing })}
                        <Button type="submit" disabled={processing}>Reabrir</Button>
                    {/snippet}
                </Form>
            {/if}
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-[1.6fr_1fr]">
        <div class="flex flex-col gap-6">
            <section class="space-y-4 rounded-xl border bg-card p-5">
                <h2 class="font-semibold">Motivo e retorno</h2>
                {#if content}
                    <div class="grid gap-1">
                        <p class="text-xs font-medium tracking-wide text-muted-foreground uppercase">Motivo</p>
                        <p class="rounded-lg bg-muted/60 p-3 text-sm whitespace-pre-line">{content.reason}</p>
                    </div>
                    <div class="grid gap-1">
                        <p class="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                            Retorno {referral.returned_at ? `(registrado em ${referral.returned_at})` : ''}
                        </p>
                        <p class="rounded-lg bg-muted/60 p-3 text-sm whitespace-pre-line">{content.return_notes || 'Ainda sem retorno.'}</p>
                    </div>
                {:else}
                    <p class="flex items-start gap-2 rounded-lg bg-muted/60 p-3 text-sm text-muted-foreground">
                        <Lock class="mt-0.5 size-4 shrink-0" />
                        Motivo e retorno são sigilosos: visíveis só para quem encaminhou e para o profissional de destino.
                    </p>
                {/if}
            </section>

            {#if can.update}
                <section class="rounded-xl border bg-card p-5">
                    <h2 class="mb-4 font-semibold">Atualizar situação</h2>
                    <Form {...status.form(referral.id)} class="space-y-4" options={{ preserveScroll: true }} resetOnSuccess>
                        {#snippet children({ errors, processing })}
                            <div class="grid gap-2 sm:w-1/2">
                                <Label for="status">Nova situação</Label>
                                <FieldSelect id="status" name="status" bind:value={nextStatus}>
                                    {#each statusOptions as option (option.value)}<option value={option.value}>{option.label}</option>{/each}
                                </FieldSelect>
                                <InputError message={errors.status} />
                            </div>
                            <div class="grid gap-2">
                                <Label for="return_notes">
                                    Retorno {#if nextStatus === 'completed'}<span class="text-primary">*</span>{:else}(opcional){/if}
                                </Label>
                                <FieldTextarea id="return_notes" name="return_notes" maxlength={5000}
                                    placeholder="O que o serviço ou profissional de destino informou" />
                                <InputError message={errors.return_notes} />
                            </div>
                            <Button type="submit" disabled={processing}>Salvar</Button>
                        {/snippet}
                    </Form>
                </section>
            {/if}
        </div>

        <section class="rounded-xl border bg-card p-5">
            <h2 class="mb-4 font-semibold">Detalhes</h2>
            <dl class="grid gap-3">
                <div class="grid gap-0.5">
                    <dt class="text-xs font-medium tracking-wide text-muted-foreground uppercase">Estudante</dt>
                    <dd class="text-sm">
                        {#if can.viewStudent}
                            <Link href={showStudent(referral.student.id).url} class="hover:underline">{referral.student.name}</Link>
                        {:else}
                            {referral.student.name}
                        {/if}
                        <span class="block text-xs text-muted-foreground">{referral.student.course} · {referral.student.period}º período</span>
                    </dd>
                </div>
                {@render item('Tipo', referral.kind_label)}
                {@render item('Encaminhado por', referral.origin)}
                {@render item('Destino', referral.destination)}
                {@render item('Data', referral.referred_on)}
                {@render item('Prazo para retorno', referral.return_deadline ? `${referral.return_deadline}${referral.overdue ? ' (vencido)' : ''}` : null)}
            </dl>
            {#if referral.attendance_id && can.viewAttendance}
                <Link href={showAttendance(referral.attendance_id).url} class="mt-4 inline-block text-sm text-primary underline underline-offset-4">
                    Atendimento de origem: {referral.attendance_label}
                </Link>
            {/if}
        </section>
    </div>
</div>
