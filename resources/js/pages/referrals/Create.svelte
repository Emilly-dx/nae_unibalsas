<script module lang="ts">
    import { dashboard } from '@/routes';
    import { create, index } from '@/routes/referrals';

    export const layout = {
        breadcrumbs: [
            { title: 'Painel', href: dashboard() },
            { title: 'Encaminhamentos', href: index() },
            { title: 'Novo encaminhamento', href: create() },
        ],
    };
</script>

<script lang="ts">
    import { Form, Link } from '@inertiajs/svelte';
    import AppHead from '@/components/AppHead.svelte';
    import FieldSelect from '@/components/form/FieldSelect.svelte';
    import FieldTextarea from '@/components/form/FieldTextarea.svelte';
    import Segmented from '@/components/form/Segmented.svelte';
    import StudentCombobox from '@/components/form/StudentCombobox.svelte';
    import InputError from '@/components/InputError.svelte';
    import { Button } from '@/components/ui/button';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';
    import { todayValue } from '@/lib/attendances';
    import { show as showAttendance } from '@/routes/attendances';
    import { store } from '@/routes/referrals';
    import type { NumberOption, Option, StudentOption } from '@/types';

    let {
        students,
        professionals,
        externalSuggestions,
        kinds,
        selectedStudent,
        attendance,
    }: {
        students: StudentOption[];
        professionals: NumberOption[];
        externalSuggestions: string[];
        kinds: Option[];
        selectedStudent: number | null;
        attendance: { id: number; label: string } | null;
    } = $props();

    // svelte-ignore state_referenced_locally
    let studentId = $state<number | null>(selectedStudent);
    let kind = $state('internal');

    const cancelHref = $derived(attendance ? showAttendance(attendance.id).url : index().url);
</script>

<AppHead title="Novo encaminhamento" />

<Form {...store.form()} class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
    {#snippet children({ errors, processing })}
        <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight">Novo encaminhamento</h1>
                <p class="text-sm text-muted-foreground">
                    Interno: para outro profissional do NAE (ex.: psicopedagogia → psicologia). Externo: para um serviço fora do NAE.
                </p>
            </div>
            <div class="flex gap-2">
                <Button variant="outline" asChild>
                    {#snippet children(props)}<Link {...props} href={cancelHref}>Cancelar</Link>{/snippet}
                </Button>
                <Button type="submit" disabled={processing}>{processing ? 'Salvando…' : 'Encaminhar'}</Button>
            </div>
        </div>

        <section class="mx-auto w-full max-w-3xl space-y-5 rounded-xl border bg-card p-5">
            {#if attendance}
                <input type="hidden" name="attendance_id" value={attendance.id} />
                <p class="rounded-lg bg-muted/60 px-3 py-2 text-sm">
                    A partir do atendimento de <strong>{attendance.label}</strong>. Ele passará a constar como "Encaminhado".
                </p>
            {/if}

            <div class="grid gap-2">
                <Label for="student">Estudante <span class="text-primary">*</span></Label>
                <StudentCombobox {students} bind:value={studentId} disabled={attendance !== null} />
                <InputError message={errors.student_id || errors.attendance_id} />
            </div>

            <div class="grid gap-2 sm:w-1/2">
                <span class="text-sm font-medium">Tipo <span class="text-primary">*</span></span>
                <Segmented name="kind" options={kinds} bind:value={kind} />
            </div>

            {#if kind === 'internal'}
                <div class="grid gap-2">
                    <Label for="destination_user_id">Profissional de destino <span class="text-primary">*</span></Label>
                    <FieldSelect id="destination_user_id" name="destination_user_id">
                        <option value="">Selecione</option>
                        {#each professionals as option (option.value)}<option value={option.value}>{option.label}</option>{/each}
                    </FieldSelect>
                    <InputError message={errors.destination_user_id} />
                </div>
            {:else}
                <div class="grid gap-2">
                    <Label for="destination_name">Serviço de destino <span class="text-primary">*</span></Label>
                    <Input id="destination_name" name="destination_name" list="external-suggestions" maxlength={255} placeholder="Selecione ou digite o serviço" />
                    <datalist id="external-suggestions">
                        {#each externalSuggestions as option (option)}<option value={option}></option>{/each}
                    </datalist>
                    <InputError message={errors.destination_name} />
                </div>
            {/if}

            <div class="grid gap-2">
                <Label for="reason">Motivo <span class="text-primary">*</span></Label>
                <FieldTextarea id="reason" name="reason" maxlength={5000} placeholder="Por que o estudante está sendo encaminhado" />
                <InputError message={errors.reason} />
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="grid gap-2">
                    <Label for="referred_on">Data do encaminhamento <span class="text-primary">*</span></Label>
                    <Input id="referred_on" name="referred_on" type="date" max={todayValue()} value={todayValue()} />
                    <InputError message={errors.referred_on} />
                </div>
                <div class="grid gap-2">
                    <Label for="return_deadline">Prazo para retorno</Label>
                    <Input id="return_deadline" name="return_deadline" type="date" min={todayValue()} />
                    <InputError message={errors.return_deadline} />
                </div>
            </div>

            <p class="rounded-lg bg-muted/60 p-3 text-xs text-muted-foreground">
                O motivo é guardado criptografado e só aparece para você e, no encaminhamento interno, para o profissional de destino.
            </p>
        </section>
    {/snippet}
</Form>
