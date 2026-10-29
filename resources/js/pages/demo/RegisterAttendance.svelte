<script module lang="ts">
    import { dashboard } from '@/routes';

    export const layout = {
        breadcrumbs: [
            { title: 'Painel', href: dashboard() },
            { title: 'Triagem', href: '/triagem' },
            { title: 'Registrar atendimento', href: '/atendimentos/registrar' },
        ],
    };
</script>

<script lang="ts">
    import { Link, page, router } from '@inertiajs/svelte';
    import Lock from '@lucide/svelte/icons/lock';
    import { toast } from 'svelte-sonner';
    import AppHead from '@/components/AppHead.svelte';
    import DemoNotice from '@/components/demo/DemoNotice.svelte';
    import FieldTextarea from '@/components/demo/FieldTextarea.svelte';
    import Segmented from '@/components/demo/Segmented.svelte';
    import { Button } from '@/components/ui/button';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';
    import { demoStudents, registeredAttendances } from '@/lib/demo/data';
    import { getInitials } from '@/lib/initials';

    const initialStudent =
        typeof window === 'undefined'
            ? ''
            : (new URLSearchParams(window.location.search).get('estudante') ?? '');

    let student = $state(initialStudent);
    let date = $state(new Date().toISOString().slice(0, 10));
    let type = $state('psicopedagogico');
    let modality = $state('presencial');
    let demand = $state('');
    let createReferral = $state(false);
    let confidential = $state(true);
    let errors = $state<Record<string, string>>({});

    const studentInfo = $derived(demoStudents.find((item) => item.name === student));
    const lastAttendance = $derived(registeredAttendances.find((item) => item.student === student));

    function save(): void {
        errors = {};

        if (!studentInfo) errors.student = 'Selecione um estudante da lista.';
        if (!date) errors.date = 'Informe a data.';
        if (!demand.trim()) errors.demand = 'Descreva a demanda apresentada.';

        if (Object.keys(errors).length > 0) {
            toast.error('Preencha os campos obrigatórios.');
            return;
        }

        toast.success(`Atendimento de ${student} registrado.`);
        router.visit(createReferral ? '/encaminhamentos' : '/triagem?aba=atendimentos');
    }
</script>

<AppHead title="Registrar atendimento" />

<div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
    <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight">Registrar atendimento</h1>
            <p class="text-sm text-muted-foreground">Registro do atendimento feito pelo profissional do NAE.</p>
        </div>
        <div class="flex gap-2">
            <Button variant="outline" asChild>
                {#snippet children(props)}<Link {...props} href="/triagem">Cancelar</Link>{/snippet}
            </Button>
            <Button onclick={save}>Salvar atendimento</Button>
        </div>
    </div>

    <DemoNotice milestone="M4 — Atendimentos" />

    <div class="grid gap-6 lg:grid-cols-[1.7fr_1fr]">
        <section class="space-y-5 rounded-xl border bg-card p-5">
            <div>
                <h2 class="font-semibold">Atendimento</h2>
                <p class="text-xs text-muted-foreground">Dados do atendimento realizado.</p>
            </div>
            <div class="grid gap-2">
                <Label for="student">Estudante <span class="text-primary">*</span></Label>
                <Input id="student" list="demo-students" placeholder="Buscar por nome ou matrícula" bind:value={student} />
                <datalist id="demo-students">
                    {#each demoStudents as item (item.id)}<option value={item.name}></option>{/each}
                </datalist>
                {#if errors.student}<p class="text-xs text-destructive">{errors.student}</p>{/if}
            </div>
            <div class="grid gap-4 sm:grid-cols-[2fr_1fr_1fr]">
                <div class="grid gap-2">
                    <Label for="date">Data <span class="text-primary">*</span></Label>
                    <Input id="date" type="date" bind:value={date} />
                    {#if errors.date}<p class="text-xs text-destructive">{errors.date}</p>{/if}
                </div>
                <div class="grid gap-2"><Label for="start">Início</Label><Input id="start" type="time" /></div>
                <div class="grid gap-2"><Label for="end">Término</Label><Input id="end" type="time" /></div>
            </div>
            <div class="grid gap-2">
                <span class="text-sm font-medium">Tipo de atendimento <span class="text-primary">*</span></span>
                <Segmented
                    options={[
                        { value: 'psicopedagogico', label: 'Psicopedagógico' },
                        { value: 'psicologico', label: 'Psicológico' },
                        { value: 'escuta', label: 'Escuta e orientação' },
                    ]}
                    bind:value={type}
                />
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="grid gap-2">
                    <span class="text-sm font-medium">Modalidade</span>
                    <Segmented options={[{ value: 'presencial', label: 'Presencial' }, { value: 'online', label: 'Online' }]} bind:value={modality} />
                </div>
                <div class="grid gap-2">
                    <Label for="professional">Profissional</Label>
                    <Input id="professional" disabled value={page.props.auth.user.name} />
                    <p class="text-xs text-muted-foreground">Preenchido automaticamente</p>
                </div>
            </div>
            <div class="grid gap-2">
                <Label for="demand">Demanda apresentada <span class="text-primary">*</span></Label>
                <FieldTextarea id="demand" placeholder="O que o estudante trouxe para o atendimento" bind:value={demand} />
                {#if errors.demand}<p class="text-xs text-destructive">{errors.demand}</p>{/if}
            </div>
            <div class="grid gap-2">
                <Label for="conduct">Condução e observações</Label>
                <FieldTextarea id="conduct" placeholder="Como o atendimento foi conduzido" />
            </div>
            <div class="grid gap-2">
                <Label for="agreements">Combinados e próximos passos</Label>
                <FieldTextarea id="agreements" class="min-h-20" placeholder="O que ficou acordado com o estudante" />
            </div>
            <div class="grid items-end gap-4 sm:grid-cols-2">
                <div class="grid gap-2"><Label for="return">Próximo retorno</Label><Input id="return" type="date" /></div>
                <label class="flex items-center gap-2 pb-2 text-sm">
                    <input type="checkbox" class="size-4 accent-primary" bind:checked={createReferral} />
                    Gerar encaminhamento a partir deste atendimento
                </label>
            </div>
        </section>

        <div class="flex flex-col gap-6">
            <section class="rounded-xl border bg-card p-5">
                <h2 class="mb-4 font-semibold">Estudante</h2>
                {#if studentInfo}
                    <div class="flex items-center gap-3">
                        <span class="flex size-11 items-center justify-center rounded-lg bg-accent font-semibold text-accent-foreground">
                            {getInitials(studentInfo.name)}
                        </span>
                        <div>
                            <p class="font-medium">{studentInfo.name}</p>
                            <p class="text-xs text-muted-foreground">{studentInfo.course} · {studentInfo.period}º período</p>
                        </div>
                    </div>
                    <dl class="mt-4 grid grid-cols-[auto_1fr] gap-x-6 gap-y-2 text-sm">
                        <dt class="text-muted-foreground">Situação</dt><dd>Em acompanhamento</dd>
                        <dt class="text-muted-foreground">Atendimentos</dt><dd>4 no semestre</dd>
                        <dt class="text-muted-foreground">Último</dt><dd>{lastAttendance?.date ?? '—'}</dd>
                    </dl>
                    <Link href="/triagem?aba=atendimentos" class="mt-3 inline-block text-sm text-primary underline underline-offset-4">
                        Ver histórico completo
                    </Link>
                {:else}
                    <p class="text-sm text-muted-foreground">Selecione um estudante para ver o resumo.</p>
                {/if}
            </section>
            <section class="rounded-xl border bg-card p-5">
                <h2 class="mb-3 flex items-center gap-2 font-semibold"><Lock class="size-4" /> Sigilo</h2>
                <label class="flex items-start gap-2 text-sm">
                    <input type="checkbox" class="mt-0.5 size-4 accent-primary" bind:checked={confidential} />
                    Registro sigiloso: visível apenas para o profissional responsável
                </label>
                <p class="mt-3 text-xs text-muted-foreground">
                    Relatórios mostram só a contagem do atendimento, nunca o conteúdo.
                </p>
            </section>
        </div>
    </div>
</div>
