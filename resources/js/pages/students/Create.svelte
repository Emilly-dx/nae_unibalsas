<script module lang="ts">
    import { create, index } from '@/routes/students';

    export const layout = {
        breadcrumbs: [
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

    let {
        sexOptions,
        courses,
    }: {
        sexOptions: SexOption[];
        courses: string[];
    } = $props();
</script>

<AppHead title="Novo estudante" />

<div class="mx-auto flex w-full max-w-4xl flex-col gap-6 p-4">
    <Heading
        title="Novo estudante"
        description="Cadastre um estudante encaminhado ao NAE."
    />

    <StudentForm
        action={StudentController.store.form()}
        cancelHref={index().url}
        {sexOptions}
        {courses}
        submitLabel="Cadastrar estudante"
    />
</div>
