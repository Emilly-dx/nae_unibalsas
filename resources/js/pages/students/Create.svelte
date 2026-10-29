<script module lang="ts">
    import { create, index } from '@/routes/students';

    export const layout = {
        breadcrumbs: [
            { title: 'Painel', href: '/dashboard' },
            { title: 'Estudantes', href: index() },
            { title: 'Novo estudante', href: create() },
        ],
    };
</script>

<script lang="ts">
    import StudentController from '@/actions/App/Http/Controllers/Students/StudentController';
    import AppHead from '@/components/AppHead.svelte';
    import Heading from '@/components/Heading.svelte';
    import StudentForm from '@/components/students/StudentForm.svelte';
    import type { SexOption } from '@/types';

    type Option = { value: string; label: string };

    let {
        sexOptions,
        shiftOptions,
        accessibilityOptions,
        courses,
        canAttachDocument,
    }: {
        sexOptions: SexOption[];
        shiftOptions: Option[];
        accessibilityOptions: Option[];
        courses: string[];
        canAttachDocument: boolean;
    } = $props();
</script>

<AppHead title="Novo estudante" />

<div class="flex w-full flex-col gap-6 p-4 md:p-6">
    <StudentForm
        action={StudentController.store.form()}
        cancelHref={index().url}
        {sexOptions}
        {shiftOptions}
        {accessibilityOptions}
        {courses}
        {canAttachDocument}
        submitLabel="Salvar cadastro"
    >
        {#snippet header(actions)}
            <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
                <Heading
                    title="Novo estudante"
                    description="Cadastro do estudante atendido pelo NAE. Campos com * são obrigatórios."
                />
                {@render actions()}
            </div>
        {/snippet}
    </StudentForm>
</div>
