<script module lang="ts">
    import { edit, index, show } from '@/routes/students';
    import type { Student } from '@/types';

    export const layout = (props: { student: Student }) => ({
        breadcrumbs: [
            { title: 'Painel', href: '/dashboard' },
            { title: 'Estudantes', href: index() },
            { title: props.student.name, href: show(props.student.id) },
            { title: 'Editar', href: edit(props.student.id) },
        ],
    });
</script>

<script lang="ts">
    import StudentController from '@/actions/App/Http/Controllers/Students/StudentController';
    import AppHead from '@/components/AppHead.svelte';
    import Heading from '@/components/Heading.svelte';
    import StudentForm from '@/components/students/StudentForm.svelte';
    import type { SexOption } from '@/types';

    type Option = { value: string; label: string };

    let {
        student,
        sexOptions,
        shiftOptions,
        accessibilityOptions,
        courses,
    }: {
        student: Student;
        sexOptions: SexOption[];
        shiftOptions: Option[];
        accessibilityOptions: Option[];
        courses: string[];
    } = $props();
</script>

<AppHead title={`Editar ${student.name}`} />

<div class="flex w-full flex-col gap-6 p-4 md:p-6">
    <StudentForm
        action={StudentController.update.form(student.id)}
        cancelHref={show(student.id).url}
        {student}
        {sexOptions}
        {shiftOptions}
        {accessibilityOptions}
        {courses}
        submitLabel="Salvar alterações"
    >
        {#snippet header(actions)}
            <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
                <Heading
                    title="Editar estudante"
                    description={`${student.name} · Matrícula ${student.registration}`}
                />
                {@render actions()}
            </div>
        {/snippet}
    </StudentForm>
</div>
