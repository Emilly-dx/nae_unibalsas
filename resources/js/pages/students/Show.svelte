<script module lang="ts">
    import { index, show } from '@/routes/students';
    import type { Student } from '@/types';

    export const layout = (props: { student: Student }) => ({
        breadcrumbs: [
            { title: 'Estudantes', href: index() },
            { title: props.student.name, href: show(props.student.id) },
        ],
    });
</script>

<script lang="ts">
    import { Form, Link, page } from '@inertiajs/svelte';
    import Download from '@lucide/svelte/icons/download';
    import FileText from '@lucide/svelte/icons/file-text';
    import History from '@lucide/svelte/icons/history';
    import Lock from '@lucide/svelte/icons/lock';
    import Pencil from '@lucide/svelte/icons/pencil';
    import Trash2 from '@lucide/svelte/icons/trash-2';
    import StudentController from '@/actions/App/Http/Controllers/Students/StudentController';
    import StudentDisabilityController from '@/actions/App/Http/Controllers/Students/StudentDisabilityController';
    import StudentDocumentController from '@/actions/App/Http/Controllers/Students/StudentDocumentController';
    import AppHead from '@/components/AppHead.svelte';
    import InputError from '@/components/InputError.svelte';
    import { Badge } from '@/components/ui/badge';
    import { Button } from '@/components/ui/button';
    import {
        Card,
        CardContent,
        CardDescription,
        CardHeader,
        CardTitle,
    } from '@/components/ui/card';
    import {
        Dialog,
        DialogClose,
        DialogContent,
        DialogDescription,
        DialogFooter,
        DialogTitle,
        DialogTrigger,
    } from '@/components/ui/dialog';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';
    import { can } from '@/lib/permissions';
    import { edit } from '@/routes/students';
    import { download } from '@/routes/students/documents';
    import type { StudentDisability, StudentDocument } from '@/types';

    let {
        student,
        disabilities,
        documents,
        canManageSensitive,
    }: {
        student: Student;
        disabilities: StudentDisability[];
        documents: StudentDocument[];
        canManageSensitive: boolean;
    } = $props();

    const auth = $derived(page.props.auth);
    const canEdit = $derived(can(auth, 'students.edit') && student.active);
    const canToggleStatus = $derived(can(auth, 'students.delete'));
    const canAddSensitive = $derived(canManageSensitive && student.active);

    function formatDate(iso: string | null): string {
        if (!iso) {
            return '—';
        }

        // Datas "YYYY-MM-DD" são montadas manualmente para não sofrer com fuso horário.
        const [year, month, day] = iso.slice(0, 10).split('-');

        return `${day}/${month}/${year}`;
    }

    function age(birthDate: string | null): string | null {
        if (!birthDate) {
            return null;
        }

        const [year, month, day] = birthDate.split('-').map(Number);
        const today = new Date();
        let years = today.getFullYear() - year;

        if (
            today.getMonth() + 1 < month ||
            (today.getMonth() + 1 === month && today.getDate() < day)
        ) {
            years--;
        }

        return `${years} anos`;
    }

    function formatSize(bytes: number): string {
        if (bytes < 1024 * 1024) {
            return `${Math.max(1, Math.round(bytes / 1024))} KB`;
        }

        return `${(bytes / (1024 * 1024)).toFixed(1).replace('.', ',')} MB`;
    }

    const textareaClass =
        'flex min-h-20 w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2';
</script>

{#snippet field(label: string, value: string | number | null | undefined)}
    <div class="grid gap-1">
        <dt class="text-xs font-medium tracking-wide text-muted-foreground uppercase">
            {label}
        </dt>
        <dd class="text-sm">{value || '—'}</dd>
    </div>
{/snippet}

<AppHead title={student.name} />

<div class="flex h-full flex-1 flex-col gap-6 p-4">
    <!-- Cabeçalho -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div class="space-y-1">
            <div class="flex flex-wrap items-center gap-2">
                <h1 class="text-xl font-semibold tracking-tight">{student.name}</h1>
                {#if student.active}
                    <Badge variant="secondary">Ativo</Badge>
                {:else}
                    <Badge variant="outline">Inativo</Badge>
                {/if}
            </div>
            <p class="text-sm text-muted-foreground">
                Matrícula {student.registration} · {student.course} ·
                {student.period}º período
            </p>
        </div>

        <div class="flex flex-wrap gap-2">
            {#if canEdit}
                <Button variant="outline" asChild>
                    {#snippet children(props)}
                        <Link {...props} href={edit(student.id).url}>
                            <Pencil class="size-4" />
                            Editar
                        </Link>
                    {/snippet}
                </Button>
            {/if}

            {#if canToggleStatus && student.active}
                <Dialog>
                    <DialogTrigger>
                        <Button variant="outline">Inativar</Button>
                    </DialogTrigger>
                    <DialogContent>
                        <Form {...StudentController.destroy.form(student.id)}>
                            {#snippet children({ processing })}
                                <div class="space-y-3">
                                    <DialogTitle>Inativar {student.name}?</DialogTitle>
                                    <DialogDescription>
                                        O estudante deixa de aparecer na lista de
                                        ativos, mas nenhum dado é apagado. Ele pode
                                        ser reativado a qualquer momento.
                                    </DialogDescription>
                                </div>
                                <DialogFooter class="mt-6 gap-2">
                                    <DialogClose>
                                        <Button variant="secondary">Cancelar</Button>
                                    </DialogClose>
                                    <Button
                                        type="submit"
                                        variant="destructive"
                                        disabled={processing}
                                    >
                                        Inativar
                                    </Button>
                                </DialogFooter>
                            {/snippet}
                        </Form>
                    </DialogContent>
                </Dialog>
            {/if}

            {#if canToggleStatus && !student.active}
                <Form {...StudentController.restore.form(student.id)}>
                    {#snippet children({ processing })}
                        <Button type="submit" disabled={processing}>Reativar</Button>
                    {/snippet}
                </Form>
            {/if}
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <!-- Coluna principal -->
        <div class="flex flex-col gap-6 lg:col-span-2">
            <Card>
                <CardHeader>
                    <CardTitle>Dados cadastrais</CardTitle>
                </CardHeader>
                <CardContent>
                    <dl class="grid gap-4 sm:grid-cols-2">
                        {@render field('Curso', student.course)}
                        {@render field('Período', `${student.period}º`)}
                        {@render field(
                            'Data de nascimento',
                            student.birth_date
                                ? `${formatDate(student.birth_date)} (${age(student.birth_date)})`
                                : null,
                        )}
                        {@render field('Sexo', student.sex_label)}
                        {@render field('E-mail', student.email)}
                        {@render field('Telefone', student.phone)}
                    </dl>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Responsável</CardTitle>
                </CardHeader>
                <CardContent>
                    {#if student.guardian_name}
                        <dl class="grid gap-4 sm:grid-cols-3">
                            {@render field('Nome', student.guardian_name)}
                            {@render field('Telefone', student.guardian_phone)}
                            {@render field('Parentesco', student.guardian_relationship)}
                        </dl>
                    {:else}
                        <p class="text-sm text-muted-foreground">
                            Nenhum responsável informado.
                        </p>
                    {/if}
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <History class="size-4" />
                        Histórico de atendimentos
                    </CardTitle>
                    <CardDescription>
                        Atendimentos, encaminhamentos e acompanhamento do estudante.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <p class="rounded-md border border-dashed p-4 text-sm text-muted-foreground">
                        O histórico será exibido aqui quando o módulo de
                        atendimentos (M4/M5) estiver disponível.
                    </p>
                </CardContent>
            </Card>
        </div>

        <!-- Dados sensíveis -->
        <div class="flex flex-col gap-6">
            {#if canManageSensitive}
                <div
                    class="flex gap-2 rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900 dark:border-amber-300/20 dark:bg-amber-500/10 dark:text-amber-100"
                >
                    <Lock class="mt-0.5 size-4 shrink-0" />
                    <p>
                        Informações sensíveis. Você vê apenas os registros que
                        você mesmo cadastrou.
                    </p>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>CID</CardTitle>
                        <CardDescription>
                            Relacionado à deficiência/acessibilidade.
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        {#if disabilities.length === 0}
                            <p class="text-sm text-muted-foreground">
                                Nenhum CID registrado por você.
                            </p>
                        {:else}
                            <ul class="divide-y rounded-md border">
                                {#each disabilities as disability (disability.id)}
                                    <li class="flex items-start justify-between gap-3 p-3">
                                        <div class="min-w-0">
                                            <p class="font-mono text-sm font-semibold">
                                                {disability.cid_code}
                                            </p>
                                            {#if disability.description}
                                                <p class="text-sm break-words text-muted-foreground">
                                                    {disability.description}
                                                </p>
                                            {/if}
                                            <p class="text-xs text-muted-foreground">
                                                Registrado em {formatDate(disability.created_at)}
                                            </p>
                                        </div>
                                        <Form
                                            {...StudentDisabilityController.destroy.form([
                                                student.id,
                                                disability.id,
                                            ])}
                                            options={{ preserveScroll: true }}
                                            onBefore={() =>
                                                confirm('Remover este CID? O registro é mantido no histórico do sistema.')}
                                        >
                                            {#snippet children({ processing })}
                                                <Button
                                                    type="submit"
                                                    variant="ghost"
                                                    size="icon"
                                                    disabled={processing}
                                                    aria-label="Remover CID {disability.cid_code}"
                                                >
                                                    <Trash2 class="size-4" />
                                                </Button>
                                            {/snippet}
                                        </Form>
                                    </li>
                                {/each}
                            </ul>
                        {/if}

                        {#if canAddSensitive}
                            <Form
                                {...StudentDisabilityController.store.form(student.id)}
                                class="space-y-3"
                                options={{ preserveScroll: true }}
                                resetOnSuccess
                            >
                                {#snippet children({ errors, processing })}
                                    <div class="grid gap-2">
                                        <Label for="cid_code">Código CID</Label>
                                        <Input
                                            id="cid_code"
                                            name="cid_code"
                                            placeholder="Ex.: F84.0"
                                            maxlength={10}
                                            class="font-mono uppercase"
                                            required
                                        />
                                        <InputError message={errors.cid_code} />
                                    </div>
                                    <div class="grid gap-2">
                                        <Label for="description">Descrição (opcional)</Label>
                                        <textarea
                                            id="description"
                                            name="description"
                                            maxlength={1000}
                                            class={textareaClass}
                                        ></textarea>
                                        <InputError message={errors.description} />
                                    </div>
                                    <Button type="submit" class="w-full" disabled={processing}>
                                        Registrar CID
                                    </Button>
                                {/snippet}
                            </Form>
                        {/if}
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Documentos</CardTitle>
                        <CardDescription>
                            Laudos, relatórios e outros anexos (PDF, imagem ou Word, até 10 MB).
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        {#if documents.length === 0}
                            <p class="text-sm text-muted-foreground">
                                Nenhum documento anexado por você.
                            </p>
                        {:else}
                            <ul class="divide-y rounded-md border">
                                {#each documents as document (document.id)}
                                    <li class="flex items-center justify-between gap-2 p-3">
                                        <div class="flex min-w-0 items-start gap-2">
                                            <FileText class="mt-0.5 size-4 shrink-0 text-muted-foreground" />
                                            <div class="min-w-0">
                                                <p class="truncate text-sm font-medium">
                                                    {document.title}
                                                </p>
                                                <p class="truncate text-xs text-muted-foreground">
                                                    {document.original_name} ·
                                                    {formatSize(document.size)} ·
                                                    {formatDate(document.created_at)}
                                                </p>
                                            </div>
                                        </div>
                                        <div class="flex shrink-0">
                                            <Button variant="ghost" size="icon" asChild>
                                                {#snippet children(props)}
                                                    <a
                                                        {...props}
                                                        href={download([student.id, document.id]).url}
                                                        aria-label="Baixar {document.title}"
                                                    >
                                                        <Download class="size-4" />
                                                    </a>
                                                {/snippet}
                                            </Button>
                                            <Form
                                                {...StudentDocumentController.destroy.form([
                                                    student.id,
                                                    document.id,
                                                ])}
                                                options={{ preserveScroll: true }}
                                                onBefore={() =>
                                                    confirm('Remover este documento? O arquivo é mantido no histórico do sistema.')}
                                            >
                                                {#snippet children({ processing })}
                                                    <Button
                                                        type="submit"
                                                        variant="ghost"
                                                        size="icon"
                                                        disabled={processing}
                                                        aria-label="Remover {document.title}"
                                                    >
                                                        <Trash2 class="size-4" />
                                                    </Button>
                                                {/snippet}
                                            </Form>
                                        </div>
                                    </li>
                                {/each}
                            </ul>
                        {/if}

                        {#if canAddSensitive}
                            <Form
                                {...StudentDocumentController.store.form(student.id)}
                                class="space-y-3"
                                options={{ preserveScroll: true }}
                                resetOnSuccess
                            >
                                {#snippet children({ errors, processing, progress })}
                                    <div class="grid gap-2">
                                        <Label for="title">Título</Label>
                                        <Input
                                            id="title"
                                            name="title"
                                            placeholder="Ex.: Laudo neurológico"
                                            maxlength={255}
                                            required
                                        />
                                        <InputError message={errors.title} />
                                    </div>
                                    <div class="grid gap-2">
                                        <Label for="file">Arquivo</Label>
                                        <Input
                                            id="file"
                                            name="file"
                                            type="file"
                                            accept=".pdf,.jpg,.jpeg,.png,.doc,.docx"
                                            required
                                        />
                                        <InputError message={errors.file} />
                                    </div>
                                    {#if progress}
                                        <progress
                                            class="h-2 w-full"
                                            value={progress.percentage}
                                            max="100"
                                        ></progress>
                                    {/if}
                                    <Button type="submit" class="w-full" disabled={processing}>
                                        Anexar documento
                                    </Button>
                                {/snippet}
                            </Form>
                        {/if}
                    </CardContent>
                </Card>
            {:else}
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Lock class="size-4" />
                            Dados sensíveis
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p class="text-sm text-muted-foreground">
                            CID e documentos são restritos aos profissionais
                            autorizados.
                        </p>
                    </CardContent>
                </Card>
            {/if}
        </div>
    </div>
</div>
