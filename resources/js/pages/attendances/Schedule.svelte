<script module lang="ts">
    import { dashboard } from '@/routes';
    import { create } from '@/routes/attendances';
    import { index } from '@/routes/triage';

    export const layout = {
        breadcrumbs: [
            { title: 'Painel', href: dashboard() },
            { title: 'Triagem e atendimentos', href: index() },
            { title: 'Agendar atendimento', href: create() },
        ],
    };
</script>

<script lang="ts">
    import { Form, Link } from '@inertiajs/svelte';
    import AppHead from '@/components/AppHead.svelte';
    import FieldSelect from '@/components/form/FieldSelect.svelte';
    import Segmented from '@/components/form/Segmented.svelte';
    import StudentCombobox from '@/components/form/StudentCombobox.svelte';
    import InputError from '@/components/InputError.svelte';
    import { Button } from '@/components/ui/button';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';
    import { todayValue } from '@/lib/attendances';
    import { store } from '@/routes/attendances';
    import type { NumberOption, Option, StudentOption } from '@/types';

    let {
        students,
        professionals,
        types,
        modalities,
        selectedStudent,
    }: {
        students: StudentOption[];
        professionals: NumberOption[];
        types: Option[];
        modalities: Option[];
        selectedStudent: number | null;
    } = $props();

    // svelte-ignore state_referenced_locally
    let studentId = $state<number | null>(selectedStudent);
    let type = $state('psychopedagogical');
    let modality = $state('presential');
</script>

<AppHead title="Agendar atendimento" />

<Form {...store.form()} class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
    {#snippet children({ errors, processing })}
        <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight">Agendar atendimento</h1>
                <p class="text-sm text-muted-foreground">O agendamento representa um atendimento que ainda vai acontecer.</p>
            </div>
            <div class="flex gap-2">
                <Button variant="outline" asChild>
                    {#snippet children(props)}<Link {...props} href={index({ query: { aba: 'agenda' } }).url}>Cancelar</Link>{/snippet}
                </Button>
                <Button type="submit" disabled={processing}>{processing ? 'Agendando…' : 'Agendar'}</Button>
            </div>
        </div>

        <section class="mx-auto w-full max-w-3xl space-y-5 rounded-xl border bg-card p-5">
            <div class="grid gap-2">
                <Label for="student">Estudante <span class="text-primary">*</span></Label>
                <StudentCombobox {students} bind:value={studentId} />
                <InputError message={errors.student_id} />
            </div>
            <div class="grid gap-2">
                <Label for="professional_id">Profissional <span class="text-primary">*</span></Label>
                <FieldSelect id="professional_id" name="professional_id">
                    <option value="">Selecione</option>
                    {#each professionals as option (option.value)}<option value={option.value}>{option.label}</option>{/each}
                </FieldSelect>
                <InputError message={errors.professional_id} />
            </div>
            <div class="grid gap-2">
                <span class="text-sm font-medium">Tipo de atendimento <span class="text-primary">*</span></span>
                <Segmented name="type" options={types} bind:value={type} />
                <InputError message={errors.type} />
            </div>
            <div class="grid gap-2 sm:w-1/2">
                <span class="text-sm font-medium">Modalidade</span>
                <Segmented name="modality" options={modalities} bind:value={modality} />
            </div>
            <div class="grid gap-4 sm:grid-cols-3">
                <div class="grid gap-2">
                    <Label for="date">Data <span class="text-primary">*</span></Label>
                    <Input id="date" name="date" type="date" min={todayValue()} />
                    <InputError message={errors.date} />
                </div>
                <div class="grid gap-2">
                    <Label for="time">Horário <span class="text-primary">*</span></Label>
                    <Input id="time" name="time" type="time" />
                    <InputError message={errors.time} />
                </div>
                <div class="grid gap-2">
                    <Label for="planned_minutes">Duração prevista (min)</Label>
                    <Input id="planned_minutes" name="planned_minutes" type="number" min={15} max={240} step={5} value={50} />
                    <InputError message={errors.planned_minutes} />
                </div>
            </div>
        </section>
    {/snippet}
</Form>
