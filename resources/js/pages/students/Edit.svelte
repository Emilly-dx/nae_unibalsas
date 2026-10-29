<script module lang="ts">
    import { edit, index, show } from '@/routes/students';
    import type { Student } from '@/types';

    export const layout = (props: { student: Student }) => ({
        breadcrumbs: [
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

    let {
        student,
        sexOptions,
        courses,
    }: {
        student: Student;
        sexOptions: SexOption[];
        courses: string[];
    } = $props();
</script>

<AppHead title={`Editar ${student.name}`} />

<div class="mx-auto flex w-full max-w-4xl flex-col gap-6 p-4">
    <Heading
        title="Editar estudante"
        description={`${student.name} · Matrícula ${student.registration}`}
    />

    <StudentForm
        action={StudentController.update.form(student.id)}
        cancelHref={show(student.id).url}
        {student}
        {sexOptions}
        {courses}
        submitLabel="Salvar alterações"
    />
</div>
