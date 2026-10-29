<script lang="ts">
    import { Form, Link } from '@inertiajs/svelte';
    import type { RouteFormDefinition } from '@/wayfinder';
    import InputError from '@/components/InputError.svelte';
    import { Button } from '@/components/ui/button';
    import {
        Card,
        CardContent,
        CardDescription,
        CardHeader,
        CardTitle,
    } from '@/components/ui/card';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';
    import type { SexOption, Student } from '@/types';

    let {
        action,
        cancelHref,
        sexOptions,
        courses = [],
        student = null,
        submitLabel = 'Salvar',
    }: {
        action: RouteFormDefinition<'post'>;
        cancelHref: string;
        sexOptions: SexOption[];
        courses?: string[];
        student?: Student | null;
        submitLabel?: string;
    } = $props();

    const selectClass =
        'flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50';
</script>

<Form {...action} class="space-y-6" options={{ preserveScroll: true }}>
    {#snippet children({ errors, processing })}
        <Card>
            <CardHeader>
                <CardTitle>Dados do estudante</CardTitle>
                <CardDescription>
                    Campos marcados com * são obrigatórios.
                </CardDescription>
            </CardHeader>
            <CardContent class="grid gap-4 md:grid-cols-2">
                <div class="grid gap-2 md:col-span-2">
                    <Label for="name">Nome completo *</Label>
                    <Input
                        id="name"
                        name="name"
                        value={student?.name ?? ''}
                        required
                        maxlength={255}
                        autocomplete="off"
                    />
                    <InputError message={errors.name} />
                </div>

                <div class="grid gap-2">
                    <Label for="registration">Matrícula *</Label>
                    <Input
                        id="registration"
                        name="registration"
                        value={student?.registration ?? ''}
                        required
                        maxlength={30}
                        autocomplete="off"
                    />
                    <InputError message={errors.registration} />
                </div>

                <div class="grid gap-2">
                    <Label for="period">Período *</Label>
                    <Input
                        id="period"
                        name="period"
                        type="number"
                        min={1}
                        max={12}
                        value={student?.period ?? ''}
                        required
                    />
                    <InputError message={errors.period} />
                </div>

                <div class="grid gap-2 md:col-span-2">
                    <Label for="course">Curso *</Label>
                    <Input
                        id="course"
                        name="course"
                        list="course-options"
                        value={student?.course ?? ''}
                        required
                        maxlength={255}
                        autocomplete="off"
                    />
                    <datalist id="course-options">
                        {#each courses as course (course)}
                            <option value={course}></option>
                        {/each}
                    </datalist>
                    <InputError message={errors.course} />
                </div>

                <div class="grid gap-2">
                    <Label for="birth_date">Data de nascimento</Label>
                    <Input
                        id="birth_date"
                        name="birth_date"
                        type="date"
                        value={student?.birth_date ?? ''}
                    />
                    <InputError message={errors.birth_date} />
                </div>

                <div class="grid gap-2">
                    <Label for="sex">Sexo</Label>
                    <select
                        id="sex"
                        name="sex"
                        class={selectClass}
                        value={student?.sex ?? ''}
                    >
                        <option value="">Não informado</option>
                        {#each sexOptions as option (option.value)}
                            <option value={option.value}>{option.label}</option>
                        {/each}
                    </select>
                    <InputError message={errors.sex} />
                </div>
            </CardContent>
        </Card>

        <Card>
            <CardHeader>
                <CardTitle>Contato</CardTitle>
            </CardHeader>
            <CardContent class="grid gap-4 md:grid-cols-2">
                <div class="grid gap-2">
                    <Label for="email">E-mail</Label>
                    <Input
                        id="email"
                        name="email"
                        type="email"
                        value={student?.email ?? ''}
                        maxlength={255}
                    />
                    <InputError message={errors.email} />
                </div>

                <div class="grid gap-2">
                    <Label for="phone">Telefone</Label>
                    <Input
                        id="phone"
                        name="phone"
                        type="tel"
                        value={student?.phone ?? ''}
                        maxlength={20}
                        placeholder="(99) 99999-9999"
                    />
                    <InputError message={errors.phone} />
                </div>
            </CardContent>
        </Card>

        <Card>
            <CardHeader>
                <CardTitle>Responsável</CardTitle>
                <CardDescription>
                    Preencha quando houver um responsável pelo estudante.
                </CardDescription>
            </CardHeader>
            <CardContent class="grid gap-4 md:grid-cols-3">
                <div class="grid gap-2 md:col-span-3">
                    <Label for="guardian_name">Nome do responsável</Label>
                    <Input
                        id="guardian_name"
                        name="guardian_name"
                        value={student?.guardian_name ?? ''}
                        maxlength={255}
                    />
                    <InputError message={errors.guardian_name} />
                </div>

                <div class="grid gap-2 md:col-span-2">
                    <Label for="guardian_phone">Telefone do responsável</Label>
                    <Input
                        id="guardian_phone"
                        name="guardian_phone"
                        type="tel"
                        value={student?.guardian_phone ?? ''}
                        maxlength={20}
                    />
                    <InputError message={errors.guardian_phone} />
                </div>

                <div class="grid gap-2">
                    <Label for="guardian_relationship">Parentesco</Label>
                    <Input
                        id="guardian_relationship"
                        name="guardian_relationship"
                        value={student?.guardian_relationship ?? ''}
                        maxlength={50}
                        placeholder="Ex.: mãe, pai, tutor(a)"
                    />
                    <InputError message={errors.guardian_relationship} />
                </div>
            </CardContent>
        </Card>

        <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
            <Button variant="outline" asChild>
                {#snippet children(props)}
                    <Link {...props} href={cancelHref}>Cancelar</Link>
                {/snippet}
            </Button>
            <Button type="submit" disabled={processing}>
                {processing ? 'Salvando…' : submitLabel}
            </Button>
        </div>
    {/snippet}
</Form>
