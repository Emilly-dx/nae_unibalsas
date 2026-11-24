<script module lang="ts">
    import { index, show } from '@/routes/students';
    import type { Student } from '@/types';

    export const layout = (props: { student: Student }) => ({
        breadcrumbs: [
            { title: 'Painel', href: '/dashboard' },
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
    import { attendanceChipClass, attendanceLabel, attendanceStatusClass } from '@/lib/attendances';
    import { cn } from '@/lib/utils';
    import { create as scheduleAttendance, show as showAttendance } from '@/routes/attendances';
    import { create as registerAttendance } from '@/routes/attendances/register';
    import { create as createRequest, show as showRequest } from '@/routes/triage/requests';
    import FieldSelect from '@/components/form/FieldSelect.svelte';
    import { referralStatusClass } from '@/lib/attendances';
    import { create as createReferral, show as showReferral } from '@/routes/referrals';
    import { close as closeFollowUp } from '@/routes/students/follow-up';
    import type { AttendanceSummary, Option, ReferralSummary, StudentDisability, StudentDocument } from '@/types';

    let {
        student,
        disabilities,
        documents,
        canManageSensitive,
        history,
        followUp,
        can: allowed,
    }: {
        student: Student;
        disabilities: StudentDisability[];
        documents: StudentDocument[];
        canManageSensitive: boolean;
        history: {
            attendances: (AttendanceSummary & { excerpt: string | null })[] | null;
            requests: { id: number; date: string | null; date_iso: string | null; origin: string; status_label: string }[] | null;
            referrals: ReferralSummary[] | null;
        };
        followUp: {
            status: string;
            status_label: string;
            started_at: string | null;
            last_at: string | null;
            attendances: number;
            professionals: string[];
            open_referrals: number;
            closed_at: string | null;
            closure_reason: string | null;
            closed_by: string | null;
            canClose: boolean;
            closureReasons: Option[];
        } | null;
        can: { createRequest: boolean; schedule: boolean; register: boolean; refer: boolean };
    } = $props();

    type TimelineItem =
        | { kind: 'request'; sort: string; data: NonNullable<typeof history.requests>[number] }
        | { kind: 'attendance'; sort: string; data: NonNullable<typeof history.attendances>[number] }
        | { kind: 'referral'; sort: string; data: ReferralSummary };

    // Linha do tempo (RF-031): solicitações, atendimentos e encaminhamentos, do mais recente ao mais antigo.
    const timeline = $derived.by((): TimelineItem[] => {
        const items: TimelineItem[] = [
            ...(history.requests ?? []).map((data) => ({ kind: 'request' as const, sort: data.date_iso ?? '', data })),
            ...(history.attendances ?? []).map((data) => ({ kind: 'attendance' as const, sort: data.scheduled_at, data })),
            ...(history.referrals ?? []).map((data) => ({ kind: 'referral' as const, sort: data.referred_on_iso, data })),
        ];

        return items.sort((a, b) => b.sort.localeCompare(a.sort));
    });

    let closeOpen = $state(false);

    const followUpClass: Record<string, string> = {
        none: 'bg-muted text-muted-foreground',
        waiting: 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300',
        active: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300',
        closed: 'bg-muted text-muted-foreground',
    };

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

<div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
    <!-- Cabeçalho -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div class="space-y-1">
            <div class="flex flex-wrap items-center gap-2">
                <h1 class="text-xl font-semibold tracking-tight">{student.social_name || student.name}</h1>
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
                        {#if student.social_name}{@render field('Nome civil', student.name)}{/if}
                        {@render field('Curso', student.course)}
                        {@render field('Período', `${student.period}º`)}
                        {@render field('Turno', student.shift_label)}
                        {@render field('Modalidade', student.modality_label)}
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
                    <CardTitle>Contato de emergência</CardTitle>
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
                            Nenhum contato de emergência informado.
                        </p>
                    {/if}
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Apoio e acessibilidade</CardTitle>
                </CardHeader>
                <CardContent class="space-y-3 text-sm">
                    <dl class="grid gap-4 sm:grid-cols-2">
                        {@render field('Necessita de recursos de acessibilidade', student.needs_accessibility ? 'Sim' : 'Não')}
                        {@render field('Acompanhamento externo', student.has_external_follow_up ? 'Sim' : 'Não')}
                    </dl>
                    {#if student.accessibility_resource_labels.length > 0}
                        <div class="flex flex-wrap gap-1.5">
                            {#each student.accessibility_resource_labels as resource (resource)}
                                <span class="rounded-full bg-blue-100 px-2 py-0.5 text-xs text-blue-700 dark:bg-blue-950 dark:text-blue-300">{resource}</span>
                            {/each}
                        </div>
                    {/if}
                    <p class="text-xs text-muted-foreground">
                        {student.consent_at
                            ? `Consentimento (LGPD) registrado em ${formatDate(student.consent_at)}.`
                            : 'Consentimento (LGPD) ainda não registrado — edite o cadastro para registrar.'}
                    </p>
                </CardContent>
            </Card>

            {#if followUp}
                <Card>
                    <CardHeader>
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <CardTitle>Acompanhamento</CardTitle>
                            <span class={cn('rounded-full px-2.5 py-0.5 text-xs font-medium', followUpClass[followUp.status])}>
                                {followUp.status_label}
                            </span>
                        </div>
                        <CardDescription>
                            O acompanhamento é o conjunto dos atendimentos do estudante. Um novo atendimento reabre um acompanhamento encerrado.
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <dl class="grid gap-4 sm:grid-cols-4">
                            {@render field('Início', followUp.started_at)}
                            {@render field('Último atendimento', followUp.last_at)}
                            {@render field('Atendimentos', String(followUp.attendances))}
                            {@render field('Encaminhamentos em aberto', String(followUp.open_referrals))}
                        </dl>
                        {#if followUp.professionals.length > 0}
                            <p class="text-sm"><span class="text-muted-foreground">Profissionais:</span> {followUp.professionals.join(', ')}</p>
                        {/if}
                        {#if followUp.status === 'closed'}
                            <p class="rounded-lg bg-muted/60 p-3 text-sm">
                                Encerrado em {followUp.closed_at}{followUp.closed_by ? ` por ${followUp.closed_by}` : ''} — {followUp.closure_reason}.
                            </p>
                        {/if}
                        {#if followUp.canClose}
                            {#if closeOpen}
                                <Form {...closeFollowUp.form(student.id)} class="flex flex-col gap-3 rounded-lg border p-3 sm:flex-row sm:items-end" onSuccess={() => (closeOpen = false)}>
                                    {#snippet children({ errors, processing })}
                                        <div class="grid flex-1 gap-2">
                                            <Label for="closure_reason">Motivo do encerramento</Label>
                                            <FieldSelect id="closure_reason" name="reason">
                                                <option value="">Selecione</option>
                                                {#each followUp.closureReasons as option (option.value)}<option value={option.value}>{option.label}</option>{/each}
                                            </FieldSelect>
                                            <InputError message={errors.reason} />
                                        </div>
                                        <div class="flex gap-2">
                                            <Button type="button" variant="ghost" onclick={() => (closeOpen = false)}>Voltar</Button>
                                            <Button type="submit" variant="destructive" disabled={processing}>Encerrar</Button>
                                        </div>
                                    {/snippet}
                                </Form>
                            {:else}
                                <Button variant="outline" size="sm" onclick={() => (closeOpen = true)}>Encerrar acompanhamento</Button>
                            {/if}
                        {/if}
                    </CardContent>
                </Card>
            {/if}

            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <History class="size-4" />
                        Linha do tempo
                    </CardTitle>
                    <CardDescription>
                        Solicitações, atendimentos e encaminhamentos. O conteúdo é sigiloso; você vê o resumo só dos atendimentos que registrou.
                    </CardDescription>
                </CardHeader>
                <CardContent class="space-y-4">
                    {#if allowed.createRequest || allowed.schedule || allowed.register || allowed.refer}
                        <div class="flex flex-wrap gap-2">
                            {#if allowed.createRequest}
                                <Button variant="outline" size="sm" asChild>
                                    {#snippet children(props)}<Link {...props} href={createRequest({ query: { estudante: student.id } }).url}>Nova solicitação</Link>{/snippet}
                                </Button>
                            {/if}
                            {#if allowed.schedule}
                                <Button variant="outline" size="sm" asChild>
                                    {#snippet children(props)}<Link {...props} href={scheduleAttendance({ query: { estudante: student.id } }).url}>Agendar</Link>{/snippet}
                                </Button>
                            {/if}
                            {#if allowed.refer}
                                <Button variant="outline" size="sm" asChild>
                                    {#snippet children(props)}<Link {...props} href={createReferral({ query: { estudante: student.id } }).url}>Encaminhar</Link>{/snippet}
                                </Button>
                            {/if}
                            {#if allowed.register}
                                <Button size="sm" asChild>
                                    {#snippet children(props)}<Link {...props} href={registerAttendance({ query: { estudante: student.id } }).url}>Registrar atendimento</Link>{/snippet}
                                </Button>
                            {/if}
                        </div>
                    {/if}

                    {#if history.attendances === null && history.requests === null && history.referrals === null}
                        <p class="text-sm text-muted-foreground">Você não tem acesso ao histórico do estudante.</p>
                    {:else if timeline.length === 0}
                        <p class="rounded-md border border-dashed p-4 text-sm text-muted-foreground">Nada registrado ainda.</p>
                    {:else}
                        <ol class="relative space-y-4 border-l pl-5">
                            {#each timeline as entry (entry.kind + entry.data.id)}
                                <li class="relative">
                                    {#if entry.kind === 'attendance'}
                                        {@const attendance = entry.data}
                                        <span class="absolute top-1.5 -left-[25px] size-2.5 rounded-full bg-primary"></span>
                                        <div class="flex flex-wrap items-center gap-2">
                                            <Link href={showAttendance(attendance.id).url} class="text-sm font-medium hover:underline">
                                                {attendance.date} às {attendance.time}
                                            </Link>
                                            <span class={cn('rounded-full px-2 py-0.5 text-xs', attendanceChipClass(attendance))}>{attendanceLabel(attendance)}</span>
                                            <span class={cn('rounded-full px-2 py-0.5 text-xs', attendanceStatusClass(attendance.status))}>{attendance.status_label}</span>
                                        </div>
                                        <p class="text-xs text-muted-foreground">{attendance.professional} · {attendance.modality_label}</p>
                                        {#if attendance.excerpt}
                                            <p class="mt-1 rounded-md bg-muted/60 px-2 py-1 text-xs">{attendance.excerpt}</p>
                                        {/if}
                                    {:else if entry.kind === 'referral'}
                                        {@const referral = entry.data}
                                        <span class="absolute top-1.5 -left-[25px] size-2.5 rounded-full bg-blue-600"></span>
                                        <div class="flex flex-wrap items-center gap-2">
                                            <Link href={showReferral(referral.id).url} class="text-sm font-medium hover:underline">
                                                {referral.referred_on} · Encaminhamento {referral.kind_label.toLowerCase()}
                                            </Link>
                                            <span class={cn('rounded-full px-2 py-0.5 text-xs', referralStatusClass(referral.status))}>{referral.status_label}</span>
                                        </div>
                                        <p class="text-xs text-muted-foreground">{referral.origin} → {referral.destination}</p>
                                    {:else}
                                        {@const request = entry.data}
                                        <span class="absolute top-1.5 -left-[25px] size-2.5 rounded-full bg-amber-500"></span>
                                        <Link href={showRequest(request.id).url} class="text-sm font-medium hover:underline">
                                            {request.date} · Solicitação de apoio
                                        </Link>
                                        <p class="text-xs text-muted-foreground">{request.origin} · {request.status_label}</p>
                                    {/if}
                                </li>
                            {/each}
                        </ol>
                    {/if}
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
