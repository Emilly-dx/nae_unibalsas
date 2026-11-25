<script module lang="ts">
    import { dashboard } from '@/routes';
    import { index } from '@/routes/triage';

    export const layout = {
        breadcrumbs: [
            { title: 'Painel', href: dashboard() },
            { title: 'Triagem e atendimentos', href: index() },
            { title: 'Registrar atendimento', href: index() },
        ],
    };
</script>

<script lang="ts">
    import { Form, Link } from '@inertiajs/svelte';
    import Lock from '@lucide/svelte/icons/lock';
    import AppHead from '@/components/AppHead.svelte';
    import FieldTextarea from '@/components/form/FieldTextarea.svelte';
    import Segmented from '@/components/form/Segmented.svelte';
    import StudentCombobox from '@/components/form/StudentCombobox.svelte';
    import InputError from '@/components/InputError.svelte';
    import { Button } from '@/components/ui/button';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';
    import { todayValue } from '@/lib/attendances';
    import { getInitials } from '@/lib/initials';
    import { can } from '@/lib/permissions';
    import { page } from '@inertiajs/svelte';
    import { show } from '@/routes/attendances';
    import { store, update } from '@/routes/attendances/register';
    import { show as showStudent } from '@/routes/students';
    import type { AttendanceSummary, Option, StudentOption } from '@/types';

    type Scheduled = AttendanceSummary & {
        date_value: string;
        start_time: string;
        end_time: string;
        modality: string;
        demand: string | null;
        conduct: string | null;
        agreements: string | null;
        next_return_on: string | null;
    };

    let {
        attendance,
        students,
        types,
        modalities,
        studentSummaries,
        selectedStudent,
        professional,
    }: {
        attendance: Scheduled | null;
        students: StudentOption[];
        types: Option[];
        modalities: Option[];
        studentSummaries: Record<number, { count: number; last: string | null }>;
        selectedStudent: number | null;
        professional: string;
    } = $props();

    // svelte-ignore state_referenced_locally
    let studentId = $state<number | null>(selectedStudent);
    // svelte-ignore state_referenced_locally
    let type = $state(attendance?.type ?? 'psychopedagogical');
    // svelte-ignore state_referenced_locally
    let modality = $state(attendance?.modality ?? 'presential');

    const canRefer = $derived(can(page.props.auth, 'referrals.create'));
    const action = $derived(attendance ? update.form(attendance.id) : store.form());
    const cancelHref = $derived(attendance ? show(attendance.id).url : index().url);
    const student = $derived(students.find((item) => item.id === studentId) ?? null);
    const summary = $derived(studentId ? studentSummaries[studentId] : undefined);
</script>

<AppHead title="Registrar atendimento" />

<Form {...action} class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6" options={{ preserveScroll: true }}>
    {#snippet children({ errors, processing })}
        <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight">Registrar atendimento</h1>
                <p class="text-sm text-muted-foreground">
                    {attendance
                        ? `Agendado para ${attendance.date} às ${attendance.time}.`
                        : 'Registro de um atendimento realizado sem agendamento prévio.'}
                </p>
            </div>
            <div class="flex gap-2">
                <Button variant="outline" asChild>
                    {#snippet children(props)}<Link {...props} href={cancelHref}>Cancelar</Link>{/snippet}
                </Button>
                <Button type="submit" disabled={processing}>{processing ? 'Salvando…' : 'Salvar atendimento'}</Button>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-[1.7fr_1fr]">
            <section class="space-y-5 rounded-xl border bg-card p-5">
                <div>
                    <h2 class="font-semibold">Atendimento</h2>
                    <p class="text-xs text-muted-foreground">Dados do atendimento realizado.</p>
                </div>
                <div class="grid gap-2">
                    <Label for="student">Estudante <span class="text-primary">*</span></Label>
                    {#if attendance}
                        <p class="rounded-md border bg-muted/40 px-3 py-2 text-sm font-medium">{attendance.student.name}</p>
                    {:else}
                        <StudentCombobox {students} bind:value={studentId} />
                        <InputError message={errors.student_id} />
                    {/if}
                </div>
                <div class="grid items-start gap-4 sm:grid-cols-[2fr_1fr_1fr]">
                    <div class="grid gap-2">
                        <Label for="date">Data <span class="text-primary">*</span></Label>
                        <Input id="date" name="date" type="date" max={todayValue()} value={attendance?.date_value ?? todayValue()} />
                        <InputError message={errors.date} />
                    </div>
                    <div class="grid gap-2">
                        <Label for="start_time">Início {#if !attendance}<span class="text-primary">*</span>{/if}</Label>
                        <Input id="start_time" name="start_time" type="time" value={attendance?.start_time ?? ''} />
                        <InputError message={errors.start_time} />
                    </div>
                    <div class="grid gap-2">
                        <Label for="end_time">Término</Label>
                        <Input id="end_time" name="end_time" type="time" value={attendance?.end_time ?? ''} />
                        <InputError message={errors.end_time} />
                    </div>
                </div>
                <div class="grid gap-2">
                    <span class="text-sm font-medium">Tipo de atendimento <span class="text-primary">*</span></span>
                    <Segmented name="type" options={types} bind:value={type} />
                    <InputError message={errors.type} />
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="grid gap-2">
                        <span class="text-sm font-medium">Modalidade</span>
                        <Segmented name="modality" options={modalities} bind:value={modality} />
                    </div>
                    <div class="grid gap-2">
                        <Label for="professional">Profissional</Label>
                        <Input id="professional" disabled value={professional} />
                        <p class="text-xs text-muted-foreground">Preenchido com o usuário logado</p>
                    </div>
                </div>
                <div class="grid gap-2">
                    <Label for="demand">Demanda apresentada <span class="text-primary">*</span></Label>
                    <FieldTextarea id="demand" name="demand" maxlength={10000} value={attendance?.demand ?? ''} placeholder="O que o estudante trouxe para o atendimento" />
                    <InputError message={errors.demand} />
                </div>
                <div class="grid gap-2">
                    <Label for="conduct">Condução e observações</Label>
                    <FieldTextarea id="conduct" name="conduct" maxlength={10000} value={attendance?.conduct ?? ''} placeholder="Como o atendimento foi conduzido" />
                    <InputError message={errors.conduct} />
                </div>
                <div class="grid gap-2">
                    <Label for="agreements">Combinados e próximos passos</Label>
                    <FieldTextarea id="agreements" name="agreements" class="min-h-20" maxlength={5000} value={attendance?.agreements ?? ''} placeholder="O que ficou acordado com o estudante" />
                    <InputError message={errors.agreements} />
                </div>
                <div class="grid items-end gap-4 sm:grid-cols-2">
                    <div class="grid gap-2">
                        <Label for="next_return_on">Próximo retorno</Label>
                        <Input id="next_return_on" name="next_return_on" type="date" value={attendance?.next_return_on ?? ''} />
                        <InputError message={errors.next_return_on} />
                    </div>
                    {#if canRefer}
                        <label class="flex items-center gap-2 pb-2 text-sm">
                            <input type="checkbox" name="refer_after" value="1" class="size-4 accent-primary" />
                            Gerar encaminhamento a partir deste atendimento
                        </label>
                    {/if}
                </div>
            </section>

            <div class="flex flex-col gap-6">
                <section class="rounded-xl border bg-card p-5">
                    <h2 class="mb-4 font-semibold">Estudante</h2>
                    {#if attendance || student}
                        {@const name = attendance?.student.name ?? student?.name ?? ''}
                        {@const id = attendance?.student.id ?? student?.id ?? 0}
                        <div class="flex items-center gap-3">
                            <span class="flex size-11 items-center justify-center rounded-lg bg-accent font-semibold text-accent-foreground">
                                {getInitials(name)}
                            </span>
                            <div>
                                <p class="font-medium">{name}</p>
                                <p class="text-xs text-muted-foreground">
                                    {attendance?.student.course ?? student?.course} · {attendance?.student.period ?? student?.period}º período
                                </p>
                            </div>
                        </div>
                        <dl class="mt-4 grid grid-cols-[auto_1fr] gap-x-6 gap-y-2 text-sm">
                            <dt class="text-muted-foreground">Atendimentos</dt><dd>{summary?.count ?? 0} no semestre</dd>
                            <dt class="text-muted-foreground">Último</dt><dd>{summary?.last ?? '—'}</dd>
                        </dl>
                        <Link href={showStudent(id).url} class="mt-3 inline-block text-sm text-primary underline underline-offset-4">
                            Ver histórico completo
                        </Link>
                    {:else}
                        <p class="text-sm text-muted-foreground">Selecione um estudante para ver o resumo.</p>
                    {/if}
                </section>
                <section class="rounded-xl border bg-card p-5">
                    <h2 class="mb-3 flex items-center gap-2 font-semibold"><Lock class="size-4" /> Sigilo</h2>
                    <p class="text-sm">
                        Registro sigiloso: demanda, condução e combinados são visíveis apenas para você, profissional responsável.
                    </p>
                    <p class="mt-3 text-xs text-muted-foreground">
                        O conteúdo é guardado criptografado. Relatórios e históricos mostram só data, tipo e profissional, nunca o conteúdo.
                    </p>
                </section>
            </div>
        </div>
    {/snippet}
</Form>
