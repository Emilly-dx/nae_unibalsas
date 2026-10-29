<script lang="ts">
    import { Form, Link } from '@inertiajs/svelte';
    import FileText from '@lucide/svelte/icons/file-text';
    import type { Snippet } from 'svelte';
    import InputError from '@/components/InputError.svelte';
    import FieldSelect from '@/components/demo/FieldSelect.svelte';
    import Segmented from '@/components/demo/Segmented.svelte';
    import { Button } from '@/components/ui/button';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';
    import type { SexOption, Student } from '@/types';
    import type { RouteFormDefinition } from '@/wayfinder';

    type Option = { value: string; label: string };

    let {
        action,
        cancelHref,
        sexOptions,
        shiftOptions,
        accessibilityOptions,
        courses = [],
        student = null,
        canAttachDocument = false,
        submitLabel = 'Salvar',
        header,
    }: {
        action: RouteFormDefinition<'post'>;
        cancelHref: string;
        sexOptions: SexOption[];
        shiftOptions: Option[];
        accessibilityOptions: Option[];
        courses?: string[];
        student?: Student | null;
        canAttachDocument?: boolean;
        submitLabel?: string;
        header: Snippet<[Snippet]>;
    } = $props();

    // svelte-ignore state_referenced_locally
    let modality = $state(student?.modality ?? 'presential');
    // svelte-ignore state_referenced_locally
    let needsAccessibility = $state(student?.needs_accessibility ? '1' : '0');
    // svelte-ignore state_referenced_locally
    let externalFollowUp = $state(student?.has_external_follow_up ? '1' : '0');
    let documentName = $state('');

    const sections = [
        { id: 'dados-pessoais', label: 'Dados pessoais' },
        { id: 'dados-academicos', label: 'Dados acadêmicos' },
        { id: 'contato', label: 'Contato' },
        { id: 'apoio', label: 'Apoio e acessibilidade' },
        { id: 'consentimento', label: 'Consentimento' },
    ];

    function scrollTo(id: string): void {
        document.getElementById(id)?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
</script>

{#snippet sectionTitle(number: number, title: string, description: string)}
    <div class="mb-4 flex items-start gap-3">
        <span class="flex size-7 shrink-0 items-center justify-center rounded-full bg-accent text-sm font-semibold text-accent-foreground">
            {number}
        </span>
        <div>
            <h2 class="font-semibold">{title}</h2>
            <p class="text-xs text-muted-foreground">{description}</p>
        </div>
    </div>
{/snippet}

{#snippet required()}<span class="text-primary">*</span>{/snippet}

<Form {...action} class="flex flex-col gap-6" options={{ preserveScroll: true }}>
    {#snippet children({ errors, processing })}
        {#snippet actions()}
            <div class="flex gap-2">
                <Button variant="outline" asChild>
                    {#snippet children(props)}<Link {...props} href={cancelHref}>Cancelar</Link>{/snippet}
                </Button>
                <Button type="submit" disabled={processing}>{processing ? 'Salvando…' : submitLabel}</Button>
            </div>
        {/snippet}

        {@render header(actions)}

        {#if Object.keys(errors).length > 0}
            <p class="rounded-lg border border-destructive/30 bg-destructive/5 px-4 py-2 text-sm text-destructive">
                Verifique os campos destacados abaixo.
            </p>
        {/if}

        <div class="grid gap-6 lg:grid-cols-[200px_1fr]">
            <nav class="hidden lg:block" aria-label="Seções do cadastro">
                <ol class="sticky top-4 space-y-1">
                    {#each sections as section, index (section.id)}
                        <li>
                            <button
                                type="button"
                                class="flex w-full items-center gap-2 rounded-md px-2 py-1.5 text-left text-sm text-muted-foreground hover:bg-muted hover:text-foreground"
                                onclick={() => scrollTo(section.id)}
                            >
                                <span class="flex size-5 items-center justify-center rounded-full border text-xs">{index + 1}</span>
                                {section.label}
                            </button>
                        </li>
                    {/each}
                </ol>
            </nav>

            <div class="flex min-w-0 flex-col gap-6">
                <section id="dados-pessoais" class="scroll-mt-4 rounded-xl border bg-card p-5">
                    {@render sectionTitle(1, 'Dados pessoais', 'Identificação do estudante.')}
                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="grid gap-2 md:col-span-2">
                            <Label for="name">Nome completo {@render required()}</Label>
                            <Input id="name" name="name" value={student?.name ?? ''} placeholder="Nome como consta na matrícula" maxlength={255} />
                            <InputError message={errors.name} />
                        </div>
                        <div class="grid gap-2">
                            <Label for="social_name">Nome social</Label>
                            <Input id="social_name" name="social_name" value={student?.social_name ?? ''} placeholder="Opcional" maxlength={255} />
                            <InputError message={errors.social_name} />
                        </div>
                        <div class="grid gap-2">
                            <Label for="birth_date">Data de nascimento {@render required()}</Label>
                            <Input id="birth_date" name="birth_date" type="date" value={student?.birth_date ?? ''} />
                            <InputError message={errors.birth_date} />
                        </div>
                        <div class="grid gap-2">
                            <Label for="sex">Sexo</Label>
                            <FieldSelect id="sex" name="sex" value={student?.sex ?? ''}>
                                <option value="">Não informado</option>
                                {#each sexOptions as option (option.value)}<option value={option.value}>{option.label}</option>{/each}
                            </FieldSelect>
                            <InputError message={errors.sex} />
                        </div>
                    </div>
                </section>

                <section id="dados-academicos" class="scroll-mt-4 rounded-xl border bg-card p-5">
                    {@render sectionTitle(2, 'Dados acadêmicos', 'Vínculo com a UNIBALSAS.')}
                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="grid gap-2">
                            <Label for="registration">Matrícula {@render required()}</Label>
                            <Input id="registration" name="registration" value={student?.registration ?? ''} placeholder="000000" maxlength={30} />
                            <InputError message={errors.registration} />
                        </div>
                        <div class="grid gap-2">
                            <Label for="course">Curso {@render required()}</Label>
                            <Input id="course" name="course" list="course-options" value={student?.course ?? ''} placeholder="Selecione ou digite o curso" maxlength={255} />
                            <datalist id="course-options">
                                {#each courses as course (course)}<option value={course}></option>{/each}
                            </datalist>
                            <InputError message={errors.course} />
                        </div>
                        <div class="grid gap-2">
                            <Label for="period">Período {@render required()}</Label>
                            <FieldSelect id="period" name="period" value={student?.period ? String(student.period) : ''}>
                                <option value="">Selecione</option>
                                {#each Array.from({ length: 12 }, (_, index) => index + 1) as period (period)}
                                    <option value={String(period)}>{period}º período</option>
                                {/each}
                            </FieldSelect>
                            <InputError message={errors.period} />
                        </div>
                        <div class="grid gap-2">
                            <Label for="shift">Turno</Label>
                            <FieldSelect id="shift" name="shift" value={student?.shift ?? ''}>
                                <option value="">Selecione</option>
                                {#each shiftOptions as option (option.value)}<option value={option.value}>{option.label}</option>{/each}
                            </FieldSelect>
                            <InputError message={errors.shift} />
                        </div>
                        <div class="grid gap-2 md:col-span-2">
                            <span class="text-sm font-medium">Modalidade</span>
                            <Segmented
                                class="md:w-1/2"
                                name="modality"
                                options={[{ value: 'presential', label: 'Presencial' }, { value: 'distance', label: 'EAD' }]}
                                bind:value={modality}
                            />
                        </div>
                    </div>
                </section>

                <section id="contato" class="scroll-mt-4 rounded-xl border bg-card p-5">
                    {@render sectionTitle(3, 'Contato', 'Como o NAE fala com o estudante.')}
                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="grid gap-2">
                            <Label for="email">E-mail institucional {@render required()}</Label>
                            <Input id="email" name="email" type="email" value={student?.email ?? ''} placeholder="nome@unibalsas.edu.br" maxlength={255} />
                            <InputError message={errors.email} />
                        </div>
                        <div class="grid gap-2">
                            <Label for="phone">Telefone / WhatsApp {@render required()}</Label>
                            <Input id="phone" name="phone" type="tel" value={student?.phone ?? ''} placeholder="(99) 99999-9999" maxlength={20} />
                            <InputError message={errors.phone} />
                        </div>
                        <div class="grid gap-2">
                            <Label for="guardian_name">Contato de emergência</Label>
                            <Input id="guardian_name" name="guardian_name" value={student?.guardian_name ?? ''} placeholder="Nome" maxlength={255} />
                            <InputError message={errors.guardian_name} />
                        </div>
                        <div class="grid gap-2">
                            <Label for="guardian_phone">Telefone de emergência</Label>
                            <Input id="guardian_phone" name="guardian_phone" type="tel" value={student?.guardian_phone ?? ''} placeholder="(99) 99999-9999" maxlength={20} />
                            <InputError message={errors.guardian_phone} />
                        </div>
                        <div class="grid gap-2">
                            <Label for="guardian_relationship">Parentesco</Label>
                            <Input id="guardian_relationship" name="guardian_relationship" value={student?.guardian_relationship ?? ''} placeholder="Ex.: mãe, pai, cônjuge" maxlength={50} />
                            <InputError message={errors.guardian_relationship} />
                        </div>
                    </div>
                </section>

                <section id="apoio" class="scroll-mt-4 rounded-xl border bg-card p-5">
                    {@render sectionTitle(4, 'Apoio e acessibilidade', 'Informações para planejar o acompanhamento, com o CIA quando necessário.')}
                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="grid gap-2">
                            <span class="text-sm font-medium">Necessita de recursos de acessibilidade?</span>
                            <Segmented name="needs_accessibility" options={[{ value: '0', label: 'Não' }, { value: '1', label: 'Sim' }]} bind:value={needsAccessibility} />
                        </div>
                        <div class="grid gap-2">
                            <span class="text-sm font-medium">Já possui acompanhamento externo?</span>
                            <Segmented name="has_external_follow_up" options={[{ value: '0', label: 'Não' }, { value: '1', label: 'Sim' }]} bind:value={externalFollowUp} />
                        </div>
                    </div>

                    {#if needsAccessibility === '1'}
                        <fieldset class="mt-4 space-y-2">
                            <legend class="text-sm font-medium">Recursos necessários</legend>
                            <div class="grid gap-2 sm:grid-cols-2 xl:grid-cols-3">
                                {#each accessibilityOptions as option (option.value)}
                                    <label class="flex items-center gap-2 text-sm">
                                        <input
                                            type="checkbox"
                                            name="accessibility_resources[]"
                                            value={option.value}
                                            class="size-4 accent-primary"
                                            checked={student?.accessibility_resources.includes(option.value) ?? false}
                                        />
                                        {option.label}
                                    </label>
                                {/each}
                            </div>
                            <InputError message={errors.accessibility_resources} />
                        </fieldset>
                    {/if}

                    {#if canAttachDocument}
                        <div class="mt-4 space-y-2">
                            <label class="flex cursor-pointer items-center justify-between gap-3 rounded-lg border border-dashed p-4">
                                <span class="flex items-center gap-3">
                                    <FileText class="size-4 text-muted-foreground" />
                                    <span>
                                        <span class="block text-sm font-medium">Laudo ou documento de apoio (opcional)</span>
                                        <span class="block text-xs text-muted-foreground">
                                            {documentName || 'PDF ou imagem, até 10 MB · visível só para você'}
                                        </span>
                                    </span>
                                </span>
                                <span class="rounded-md border px-3 py-1.5 text-sm">Selecionar arquivo</span>
                                <input
                                    type="file"
                                    name="document"
                                    class="sr-only"
                                    accept=".pdf,.jpg,.jpeg,.png"
                                    onchange={(event) => (documentName = event.currentTarget.files?.[0]?.name ?? '')}
                                />
                            </label>
                            <InputError message={errors.document} />
                        </div>
                    {/if}
                </section>

                <section id="consentimento" class="scroll-mt-4 rounded-xl border bg-card p-5">
                    {@render sectionTitle(5, 'Consentimento', 'Tratamento de dados pessoais (LGPD).')}
                    {#if student?.consent_at}
                        <p class="text-sm text-muted-foreground">
                            Consentimento registrado em {new Date(student.consent_at).toLocaleDateString('pt-BR')}.
                        </p>
                    {:else}
                        <label class="flex items-start gap-3 text-sm">
                            <input type="checkbox" name="consent" value="1" class="mt-0.5 size-4 accent-primary" />
                            <span>
                                O estudante (ou seu responsável) foi informado e autorizou o registro e o
                                tratamento dos seus dados pelo NAE, para fins de acompanhamento acadêmico.
                                {#if !student}{@render required()}{/if}
                            </span>
                        </label>
                        <InputError class="mt-2" message={errors.consent} />
                    {/if}
                </section>

                <div class="flex justify-end">{@render actions()}</div>
            </div>
        </div>
    {/snippet}
</Form>
