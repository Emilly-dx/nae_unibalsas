<script module lang="ts">
    import { dashboard } from '@/routes';

    export const layout = {
        breadcrumbs: [
            { title: 'Painel', href: dashboard() },
            { title: 'Triagem', href: '/triagem' },
            { title: 'Nova solicitação', href: '/triagem/nova-solicitacao' },
        ],
    };
</script>

<script lang="ts">
    import { Link, router } from '@inertiajs/svelte';
    import Paperclip from '@lucide/svelte/icons/paperclip';
    import { toast } from 'svelte-sonner';
    import AppHead from '@/components/AppHead.svelte';
    import DemoNotice from '@/components/demo/DemoNotice.svelte';
    import FieldSelect from '@/components/demo/FieldSelect.svelte';
    import FieldTextarea from '@/components/demo/FieldTextarea.svelte';
    import Segmented from '@/components/demo/Segmented.svelte';
    import { Button } from '@/components/ui/button';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';
    import { demoStudents } from '@/lib/demo/data';

    const reasons = [
        'Dificuldades de aprendizagem',
        'Organização da rotina de estudos',
        'Questões emocionais',
        'Adaptação ao curso',
        'Avaliação adaptada',
        'Faltas frequentes ou risco de evasão',
        'Acessibilidade',
        'Outro',
    ];

    let student = $state('');
    let requester = $state('');
    let description = $state('');
    let aware = $state('sim');
    let priority = $state('normal');
    let professional = $state('');
    let notify = $state(true);
    let attachment = $state('');
    let errors = $state<Record<string, string>>({});

    const studentInfo = $derived(demoStudents.find((item) => item.name === student));

    function validate(): boolean {
        errors = {};

        if (!student) errors.student = 'Selecione o estudante.';
        if (!requester) errors.requester = 'Informe o solicitante.';
        if (!description) errors.description = 'Descreva a situação.';

        return Object.keys(errors).length === 0;
    }

    function submit(): void {
        if (!validate()) {
            toast.error('Preencha os campos obrigatórios.');
            return;
        }

        toast.success(`Solicitação de ${student} enviada para a fila de triagem.`);
        router.visit('/triagem');
    }

    function schedule(): void {
        if (!validate()) {
            toast.error('Preencha os campos obrigatórios.');
            return;
        }

        if (!professional) {
            errors = { ...errors, professional: 'Selecione o profissional.' };
            return;
        }

        toast.success(`1ª conversa de ${student} agendada com ${professional}.`);
        router.visit('/triagem?aba=agenda');
    }

    function giveBack(): void {
        toast.info('Solicitação devolvida à coordenação para complementação.');
        router.visit('/triagem');
    }
</script>

<AppHead title="Nova solicitação" />

<div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
    <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight">Formulário de Solicitação do Apoio do NAE</h1>
            <p class="text-sm text-muted-foreground">
                A coordenação envia a solicitação; o NAE faz a triagem e agenda a primeira conversa.
            </p>
        </div>
        <div class="flex gap-2">
            <Button variant="outline" asChild>
                {#snippet children(props)}<Link {...props} href="/triagem">Cancelar</Link>{/snippet}
            </Button>
            <Button onclick={submit}>Enviar solicitação</Button>
        </div>
    </div>

    <DemoNotice milestone="M4 — Triagem" />

    <div class="grid gap-6 lg:grid-cols-[1.7fr_1fr]">
        <section class="space-y-5 rounded-xl border bg-card p-5">
            <div>
                <h2 class="font-semibold">Dados da solicitação</h2>
                <p class="text-xs text-muted-foreground">Preenchido pela coordenação do curso.</p>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="grid gap-2">
                    <Label for="student">Estudante <span class="text-primary">*</span></Label>
                    <Input id="student" list="demo-students" placeholder="Buscar por nome ou matrícula" bind:value={student} />
                    <datalist id="demo-students">
                        {#each demoStudents as item (item.id)}<option value={item.name}></option>{/each}
                    </datalist>
                    {#if errors.student}<p class="text-xs text-destructive">{errors.student}</p>{/if}
                </div>
                <div class="grid gap-2">
                    <Label for="course">Curso e período</Label>
                    <Input
                        id="course"
                        disabled
                        placeholder="Preenchido a partir do estudante"
                        value={studentInfo ? `${studentInfo.course} · ${studentInfo.period}º período` : ''}
                    />
                </div>
                <div class="grid gap-2">
                    <Label for="requester">Solicitante <span class="text-primary">*</span></Label>
                    <Input id="requester" placeholder="Nome do(a) coordenador(a)" bind:value={requester} />
                    {#if errors.requester}<p class="text-xs text-destructive">{errors.requester}</p>{/if}
                </div>
                <div class="grid gap-2">
                    <Label for="requester-email">E-mail do solicitante</Label>
                    <Input id="requester-email" type="email" placeholder="nome@unibalsas.edu.br" />
                </div>
            </div>

            <fieldset class="space-y-2">
                <legend class="text-sm font-medium">Motivo da solicitação</legend>
                <div class="grid gap-2 sm:grid-cols-2">
                    {#each reasons as reason (reason)}
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" class="size-4 accent-primary" /> {reason}
                        </label>
                    {/each}
                </div>
            </fieldset>

            <div class="grid gap-2">
                <Label for="description">Descrição da situação <span class="text-primary">*</span></Label>
                <FieldTextarea id="description" placeholder="Descreva o que foi observado e o que já foi feito" bind:value={description} />
                {#if errors.description}<p class="text-xs text-destructive">{errors.description}</p>{/if}
            </div>

            <div class="grid gap-2">
                <span class="text-sm font-medium">O estudante está ciente desta solicitação?</span>
                <Segmented options={[{ value: 'sim', label: 'Sim' }, { value: 'nao', label: 'Não' }]} bind:value={aware} />
            </div>

            <label class="flex cursor-pointer items-center justify-between gap-3 rounded-lg border border-dashed p-4">
                <span class="flex items-center gap-3">
                    <Paperclip class="size-4 text-muted-foreground" />
                    <span>
                        <span class="block text-sm font-medium">Anexos (opcional)</span>
                        <span class="block text-xs text-muted-foreground">{attachment || 'PDF ou imagem, até 10 MB'}</span>
                    </span>
                </span>
                <span class="rounded-md border px-3 py-1.5 text-sm">Selecionar arquivo</span>
                <input
                    type="file"
                    class="sr-only"
                    accept=".pdf,.jpg,.jpeg,.png"
                    onchange={(event) => (attachment = event.currentTarget.files?.[0]?.name ?? '')}
                />
            </label>
        </section>

        <section class="flex flex-col gap-4 rounded-xl border bg-card p-5">
            <div class="flex items-start justify-between gap-2">
                <div>
                    <h2 class="font-semibold">Triagem</h2>
                    <p class="text-xs text-muted-foreground">Uso exclusivo da equipe do NAE.</p>
                </div>
                <span class="rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-800 dark:bg-amber-950 dark:text-amber-300">
                    Aguardando triagem
                </span>
            </div>
            <div class="grid gap-2">
                <span class="text-sm font-medium">Prioridade</span>
                <Segmented options={[{ value: 'normal', label: 'Normal' }, { value: 'prioritaria', label: 'Prioritária' }]} bind:value={priority} />
            </div>
            <div class="grid gap-2">
                <Label for="professional">Profissional designado <span class="text-primary">*</span></Label>
                <FieldSelect id="professional" bind:value={professional}>
                    <option value="">Selecione</option>
                    <option>Psicopedagoga do NAE</option>
                    <option>Psicóloga do NAE</option>
                    <option>Pedagoga do NAE</option>
                </FieldSelect>
                {#if errors.professional}<p class="text-xs text-destructive">{errors.professional}</p>{/if}
            </div>
            <div class="grid gap-2">
                <Label for="opinion">Parecer da triagem</Label>
                <FieldTextarea id="opinion" placeholder="Registro da análise inicial" />
            </div>
            <div class="grid grid-cols-[1.4fr_1fr] gap-3">
                <div class="grid gap-2"><Label for="first-date">Data da 1ª conversa</Label><Input id="first-date" type="date" /></div>
                <div class="grid gap-2"><Label for="first-time">Horário</Label><Input id="first-time" type="time" /></div>
            </div>
            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" class="size-4 accent-primary" bind:checked={notify} />
                Notificar a coordenação e o estudante
            </label>
            <div class="mt-auto flex flex-col gap-2 pt-4">
                <Button onclick={schedule}>Agendar 1ª conversa</Button>
                <Button variant="outline" onclick={giveBack}>Devolver à coordenação</Button>
            </div>
        </section>
    </div>
</div>
