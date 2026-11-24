<script module lang="ts">
    import { dashboard } from '@/routes';
    import { index as agendaIndex } from '@/routes/agenda';
    import { edit, show } from '@/routes/attendances';

    export const layout = (props: { attendance: { id: number; student: { name: string } } }) => ({
        breadcrumbs: [
            { title: 'Painel', href: dashboard() },
            { title: 'Agenda', href: agendaIndex() },
            { title: props.attendance.student.name, href: show(props.attendance.id) },
            { title: 'Remarcar', href: edit(props.attendance.id) },
        ],
    });
</script>

<script lang="ts">
    import { Form, Link } from '@inertiajs/svelte';
    import AppHead from '@/components/AppHead.svelte';
    import FieldSelect from '@/components/form/FieldSelect.svelte';
    import Segmented from '@/components/form/Segmented.svelte';
    import InputError from '@/components/InputError.svelte';
    import { Button } from '@/components/ui/button';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';
    import { todayValue } from '@/lib/attendances';
    import { update } from '@/routes/attendances';
    import type { AttendanceSummary, NumberOption, Option } from '@/types';

    let {
        attendance,
        professionals,
        types,
        modalities,
    }: {
        attendance: AttendanceSummary & { date_value: string; modality: string };
        professionals: NumberOption[];
        types: Option[];
        modalities: Option[];
    } = $props();

    // svelte-ignore state_referenced_locally
    let type = $state(attendance.type);
    // svelte-ignore state_referenced_locally
    let modality = $state(attendance.modality);
</script>

<AppHead title="Remarcar atendimento" />

<Form {...update.form(attendance.id)} class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
    {#snippet children({ errors, processing })}
        <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight">Remarcar atendimento</h1>
                <p class="text-sm text-muted-foreground">
                    {attendance.student.name} · hoje marcado para {attendance.date} às {attendance.time} com {attendance.professional}
                </p>
            </div>
            <div class="flex gap-2">
                <Button variant="outline" asChild>
                    {#snippet children(props)}<Link {...props} href={show(attendance.id).url}>Cancelar</Link>{/snippet}
                </Button>
                <Button type="submit" disabled={processing}>{processing ? 'Salvando…' : 'Salvar nova data'}</Button>
            </div>
        </div>

        <section class="mx-auto w-full max-w-3xl space-y-5 rounded-xl border bg-card p-5">
            <div class="grid gap-2">
                <Label for="professional_id">Profissional <span class="text-primary">*</span></Label>
                <FieldSelect id="professional_id" name="professional_id" value={String(attendance.professional_id)}>
                    {#each professionals as option (option.value)}<option value={String(option.value)}>{option.label}</option>{/each}
                </FieldSelect>
                <InputError message={errors.professional_id} />
            </div>
            <div class="grid gap-2">
                <span class="text-sm font-medium">Tipo de atendimento</span>
                <Segmented name="type" options={types} bind:value={type} />
            </div>
            <div class="grid gap-2 sm:w-1/2">
                <span class="text-sm font-medium">Modalidade</span>
                <Segmented name="modality" options={modalities} bind:value={modality} />
            </div>
            <div class="grid gap-4 sm:grid-cols-3">
                <div class="grid gap-2">
                    <Label for="date">Nova data <span class="text-primary">*</span></Label>
                    <Input id="date" name="date" type="date" min={todayValue()} value={attendance.date_value} />
                    <InputError message={errors.date} />
                </div>
                <div class="grid gap-2">
                    <Label for="time">Horário <span class="text-primary">*</span></Label>
                    <Input id="time" name="time" type="time" value={attendance.time} />
                    <InputError message={errors.time} />
                </div>
                <div class="grid gap-2">
                    <Label for="planned_minutes">Duração (min)</Label>
                    <Input id="planned_minutes" name="planned_minutes" type="number" min={15} max={240} step={5} value={attendance.planned_minutes} />
                    <InputError message={errors.planned_minutes} />
                </div>
            </div>
            <p class="rounded-lg bg-muted/60 p-3 text-xs text-muted-foreground">
                O sistema não deixa marcar dois atendimentos no mesmo horário para o mesmo profissional.
            </p>
        </section>
    {/snippet}
</Form>
