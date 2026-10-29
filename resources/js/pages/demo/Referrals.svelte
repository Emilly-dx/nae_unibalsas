<script module lang="ts">
    import { dashboard } from '@/routes';

    export const layout = {
        breadcrumbs: [
            { title: 'Painel', href: dashboard() },
            { title: 'Encaminhamentos', href: '/encaminhamentos' },
        ],
    };
</script>

<script lang="ts">
    import Download from '@lucide/svelte/icons/download';
    import { toast } from 'svelte-sonner';
    import AppHead from '@/components/AppHead.svelte';
    import DemoNotice from '@/components/demo/DemoNotice.svelte';
    import FieldSelect from '@/components/demo/FieldSelect.svelte';
    import FieldTextarea from '@/components/demo/FieldTextarea.svelte';
    import Segmented from '@/components/demo/Segmented.svelte';
    import { Button } from '@/components/ui/button';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';
    import type { Referral, ReferralStatus } from '@/lib/demo/data';
    import {
        demoStudents,
        referralDestinations,
        referrals as initialReferrals,
        referralStatusLabels,
        referralStatusStyles,
    } from '@/lib/demo/data';
    import { cn } from '@/lib/utils';

    let list = $state<Referral[]>([...initialReferrals]);
    let kindFilter = $state('todos');
    let statusFilter = $state('');

    let student = $state('');
    let kind = $state<'interno' | 'externo'>('interno');
    let destination = $state(referralDestinations.interno[0]);
    let reason = $state('');
    let errors = $state<Record<string, string>>({});

    const filtered = $derived(
        list.filter(
            (item) =>
                (kindFilter === 'todos' || item.kind === kindFilter.slice(0, -1)) &&
                (statusFilter === '' || item.status === statusFilter),
        ),
    );

    function changeKind(value: string): void {
        kind = value as 'interno' | 'externo';
        destination = referralDestinations[kind][0];
    }

    function reset(): void {
        student = '';
        reason = '';
        errors = {};
        changeKind('interno');
    }

    function save(): void {
        errors = {};

        if (!demoStudents.some((item) => item.name === student)) errors.student = 'Selecione um estudante da lista.';
        if (!reason.trim()) errors.reason = 'Descreva o motivo.';

        if (Object.keys(errors).length > 0) {
            return;
        }

        const today = new Date();
        list = [
            {
                student,
                kind,
                destination,
                reason,
                date: `${String(today.getDate()).padStart(2, '0')}/${String(today.getMonth() + 1).padStart(2, '0')}`,
                status: 'enviado',
            },
            ...list,
        ];
        toast.success(`Encaminhamento ${kind} de ${student} registrado.`);
        reset();
    }

    const statuses = Object.entries(referralStatusLabels) as [ReferralStatus, string][];
</script>

<AppHead title="Encaminhamentos" />

<div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
    <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight">Encaminhamentos</h1>
            <p class="text-sm text-muted-foreground">
                Encaminhamentos internos e externos ao NAE, com acompanhamento do retorno.
            </p>
        </div>
        <Button variant="outline" onclick={() => toast.info('A exportação será implementada no módulo de relatórios (M8).')}>
            <Download class="size-4" /> Exportar
        </Button>
    </div>

    <DemoNotice milestone="M6 — Encaminhamentos" />

    <div class="flex flex-wrap gap-3">
        <Segmented
            class="w-full sm:w-72"
            options={[
                { value: 'todos', label: 'Todos' },
                { value: 'internos', label: 'Internos' },
                { value: 'externos', label: 'Externos' },
            ]}
            bind:value={kindFilter}
        />
        <FieldSelect class="h-10 w-auto" bind:value={statusFilter} aria-label="Status">
            <option value="">Todos os status</option>
            {#each statuses as [value, label] (value)}<option {value}>{label}</option>{/each}
        </FieldSelect>
        <FieldSelect class="h-10 w-auto" aria-label="Semestre">
            <option>2º semestre 2026</option>
            <option>1º semestre 2026</option>
        </FieldSelect>
    </div>

    <div class="grid gap-6 xl:grid-cols-[1.8fr_1fr]">
        <section class="overflow-hidden rounded-xl border bg-card">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[680px] text-sm">
                    <thead class="bg-muted/50 text-left text-muted-foreground">
                        <tr>
                            <th class="px-5 py-3 font-medium">Estudante</th>
                            <th class="px-3 py-3 font-medium">Tipo</th>
                            <th class="px-3 py-3 font-medium">Destino</th>
                            <th class="px-3 py-3 font-medium">Motivo</th>
                            <th class="px-3 py-3 font-medium">Data</th>
                            <th class="px-3 py-3 font-medium">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        {#each filtered as item, index (item.student + item.date + index)}
                            <tr class="border-t">
                                <td class="px-5 py-3 font-medium">{item.student}</td>
                                <td class="px-3 py-3 capitalize">{item.kind}</td>
                                <td class="px-3 py-3">{item.destination}</td>
                                <td class="px-3 py-3">{item.reason}</td>
                                <td class="px-3 py-3 tabular-nums">{item.date}</td>
                                <td class="px-3 py-3">
                                    <span class={cn('rounded-full px-2.5 py-0.5 text-xs font-medium whitespace-nowrap', referralStatusStyles[item.status])}>
                                        {referralStatusLabels[item.status]}
                                    </span>
                                </td>
                            </tr>
                        {:else}
                            <tr><td colspan="6" class="px-5 py-8 text-center text-muted-foreground">Nenhum encaminhamento com esses filtros.</td></tr>
                        {/each}
                    </tbody>
                </table>
            </div>
        </section>

        <section class="flex flex-col gap-4 rounded-xl border bg-card p-5">
            <div>
                <h2 class="font-semibold">Novo encaminhamento</h2>
                <p class="text-xs text-muted-foreground">Registre para onde o estudante foi encaminhado.</p>
            </div>
            <div class="grid gap-2">
                <Label for="ref-student">Estudante</Label>
                <Input id="ref-student" list="ref-students" placeholder="Buscar estudante" bind:value={student} />
                <datalist id="ref-students">
                    {#each demoStudents as item (item.id)}<option value={item.name}></option>{/each}
                </datalist>
                {#if errors.student}<p class="text-xs text-destructive">{errors.student}</p>{/if}
            </div>
            <div class="grid gap-2">
                <span class="text-sm font-medium">Tipo</span>
                <Segmented
                    options={[{ value: 'interno', label: 'Interno' }, { value: 'externo', label: 'Externo' }]}
                    bind:value={() => kind, changeKind}
                />
            </div>
            <div class="grid gap-2">
                <Label for="ref-destination">Destino</Label>
                <FieldSelect id="ref-destination" bind:value={destination}>
                    {#each referralDestinations[kind] as option (option)}<option>{option}</option>{/each}
                </FieldSelect>
            </div>
            <div class="grid gap-2">
                <Label for="ref-reason">Motivo</Label>
                <FieldTextarea id="ref-reason" placeholder="Descreva o motivo do encaminhamento" bind:value={reason} />
                {#if errors.reason}<p class="text-xs text-destructive">{errors.reason}</p>{/if}
            </div>
            <div class="grid gap-2"><Label for="ref-deadline">Prazo para retorno</Label><Input id="ref-deadline" type="date" /></div>
            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" class="size-4 accent-primary" checked /> Informar o estudante sobre o encaminhamento
            </label>
            <div class="mt-auto grid grid-cols-2 gap-2 pt-4">
                <Button variant="outline" onclick={reset}>Cancelar</Button>
                <Button onclick={save}>Salvar</Button>
            </div>
        </section>
    </div>
</div>
