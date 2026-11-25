<script module lang="ts">
    import { dashboard } from '@/routes';
    import { index } from '@/routes/triage';
    import { create } from '@/routes/triage/requests';

    export const layout = {
        breadcrumbs: [
            { title: 'Painel', href: dashboard() },
            { title: 'Triagem', href: index() },
            { title: 'Nova solicitação', href: create() },
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
    import { store } from '@/routes/triage/requests';
    import type { Option, StudentOption } from '@/types';

    let {
        students,
        origins,
        reasons,
        selectedStudent,
    }: {
        students: StudentOption[];
        origins: Option[];
        reasons: Option[];
        selectedStudent: number | null;
    } = $props();

    // svelte-ignore state_referenced_locally
    let studentId = $state<number | null>(selectedStudent);
    let aware = $state('1');
</script>

<AppHead title="Nova solicitação" />

<Form {...store.form()} class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
    {#snippet children({ errors, processing })}
        <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight">Formulário de Solicitação do Apoio do NAE</h1>
                <p class="text-sm text-muted-foreground">
                    A coordenação envia a solicitação; o NAE faz a triagem e agenda a primeira conversa.
                </p>
            </div>
            <div class="flex gap-2">
                <Button variant="outline" asChild>
                    {#snippet children(props)}<Link {...props} href={index().url}>Cancelar</Link>{/snippet}
                </Button>
                <Button type="submit" disabled={processing}>{processing ? 'Enviando…' : 'Enviar solicitação'}</Button>
            </div>
        </div>

        <section class="mx-auto w-full max-w-4xl space-y-5 rounded-xl border bg-card p-5">
            <div>
                <h2 class="font-semibold">Dados da solicitação</h2>
                <p class="text-xs text-muted-foreground">Preenchido a partir do formulário enviado pela coordenação do curso.</p>
            </div>

            <div class="grid gap-2">
                <Label for="student">Estudante <span class="text-primary">*</span></Label>
                <StudentCombobox {students} bind:value={studentId} />
                <InputError message={errors.student_id} />
                {#if students.length === 0}
                    <p class="text-xs text-muted-foreground">Nenhum estudante ativo cadastrado. Cadastre o estudante antes.</p>
                {/if}
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="grid gap-2">
                    <Label for="requester_name">Solicitante <span class="text-primary">*</span></Label>
                    <Input id="requester_name" name="requester_name" placeholder="Nome do(a) coordenador(a) ou docente" maxlength={255} />
                    <InputError message={errors.requester_name} />
                </div>
                <div class="grid gap-2">
                    <Label for="requester_email">E-mail do solicitante</Label>
                    <Input id="requester_email" name="requester_email" type="email" placeholder="nome@unibalsas.edu.br" maxlength={255} />
                    <InputError message={errors.requester_email} />
                </div>
                <div class="grid gap-2">
                    <Label for="origin">Origem <span class="text-primary">*</span></Label>
                    <FieldSelect id="origin" name="origin" value="course_coordination">
                        {#each origins as option (option.value)}<option value={option.value}>{option.label}</option>{/each}
                    </FieldSelect>
                    <InputError message={errors.origin} />
                </div>
            </div>

            <fieldset class="space-y-2">
                <legend class="text-sm font-medium">Motivo da solicitação</legend>
                <div class="grid gap-2 sm:grid-cols-2">
                    {#each reasons as reason (reason.value)}
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" name="reasons[]" value={reason.value} class="size-4 accent-primary" />
                            {reason.label}
                        </label>
                    {/each}
                </div>
                <InputError message={errors.reasons} />
            </fieldset>

            <div class="grid gap-2">
                <Label for="description">Descrição da situação <span class="text-primary">*</span></Label>
                <FieldTextarea id="description" name="description" maxlength={5000} placeholder="Descreva o que foi observado e o que já foi feito" />
                <InputError message={errors.description} />
            </div>

            <div class="grid gap-2 sm:w-1/2">
                <span class="text-sm font-medium">O estudante está ciente desta solicitação?</span>
                <Segmented name="student_aware" options={[{ value: '1', label: 'Sim' }, { value: '0', label: 'Não' }]} bind:value={aware} />
            </div>

            <p class="rounded-lg bg-muted/60 p-3 text-xs text-muted-foreground">
                A descrição é guardada criptografada e só é vista pela equipe de triagem do NAE.
            </p>
        </section>
    {/snippet}
</Form>
