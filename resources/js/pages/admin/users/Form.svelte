<script module lang="ts">
    import { dashboard } from '@/routes';
    import { index } from '@/routes/admin/users';

    export const layout = (props: { user: { name: string } | null }) => ({
        breadcrumbs: [
            { title: 'Painel', href: dashboard() },
            { title: 'Usuários', href: index() },
            { title: props.user ? props.user.name : 'Nova conta', href: index() },
        ],
    });
</script>

<script lang="ts">
    import { Form, Link, router } from '@inertiajs/svelte';
    import KeyRound from '@lucide/svelte/icons/key-round';
    import AppHead from '@/components/AppHead.svelte';
    import InputError from '@/components/InputError.svelte';
    import PasswordInput from '@/components/PasswordInput.svelte';
    import { Button } from '@/components/ui/button';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';
    import { passwordLink, store, update } from '@/routes/admin/users';

    type RoleOption = { value: number; label: string; slug: string };

    let {
        user,
        roles,
        canAssignRoles,
    }: {
        user: { id: number; name: string; email: string; roles: number[]; active: boolean; is_me: boolean } | null;
        roles: RoleOption[];
        canAssignRoles: boolean;
    } = $props();

    const descriptions: Record<string, string> = {
        administrator: 'Contas, perfis e auditoria. Não vê conteúdo sigiloso dos atendimentos.',
        coordinator: 'Consulta estudantes, atendimentos e encaminhamentos; relatórios.',
        psychopedagogue: 'Triagem, cadastro de estudantes, atendimentos e encaminhamentos.',
        psychologist: 'Atendimentos e encaminhamentos dos estudantes recebidos.',
        pedagogue: 'Atendimentos e encaminhamentos dos estudantes recebidos.',
        assistant: 'Registra solicitações de apoio e agenda atendimentos.',
    };

    const action = $derived(user ? update.form(user.id) : store.form());

    function sendLink(): void {
        if (user && window.confirm(`Enviar para ${user.email} um link para criar uma nova senha?`)) {
            router.post(passwordLink(user.id).url, {}, { preserveScroll: true });
        }
    }
</script>

<AppHead title={user ? 'Editar conta' : 'Nova conta'} />

<Form {...action} class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
    {#snippet children({ errors, processing })}
        <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight">{user ? 'Editar conta' : 'Nova conta'}</h1>
                <p class="text-sm text-muted-foreground">
                    {user ? 'Dados de acesso e perfis desta pessoa.' : 'Conta para alguém da equipe do NAE acessar a plataforma.'}
                </p>
            </div>
            <div class="flex gap-2">
                <Button variant="outline" asChild>
                    {#snippet children(props)}<Link {...props} href={index().url}>Cancelar</Link>{/snippet}
                </Button>
                <Button type="submit" disabled={processing}>{processing ? 'Salvando…' : 'Salvar'}</Button>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-[1.3fr_1fr]">
            <section class="space-y-5 rounded-xl border bg-card p-5">
                <h2 class="font-semibold">Dados de acesso</h2>
                <div class="grid gap-2">
                    <Label for="name">Nome completo <span class="text-primary">*</span></Label>
                    <Input id="name" name="name" value={user?.name ?? ''} maxlength={255} autocomplete="off" />
                    <InputError message={errors.name} />
                </div>
                <div class="grid gap-2">
                    <Label for="email">E-mail <span class="text-primary">*</span></Label>
                    <Input id="email" name="email" type="email" value={user?.email ?? ''} maxlength={255} placeholder="nome@unibalsas.edu.br" autocomplete="off" />
                    <InputError message={errors.email} />
                </div>
                {#if user}
                    <div class="flex flex-wrap items-center justify-between gap-3 rounded-lg bg-muted/60 p-3 text-sm">
                        <span class="text-muted-foreground">A senha é pessoal: a administração não vê nem altera a senha de ninguém.</span>
                        {#if user.active}
                            <Button type="button" variant="outline" size="sm" onclick={sendLink}>
                                <KeyRound class="size-4" /> Enviar link de nova senha
                            </Button>
                        {/if}
                    </div>
                {:else}
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="grid gap-2">
                            <Label for="password">Senha provisória <span class="text-primary">*</span></Label>
                            <PasswordInput id="password" name="password" autocomplete="new-password" />
                            <InputError message={errors.password} />
                        </div>
                        <div class="grid gap-2">
                            <Label for="password_confirmation">Repita a senha <span class="text-primary">*</span></Label>
                            <PasswordInput id="password_confirmation" name="password_confirmation" autocomplete="new-password" />
                        </div>
                    </div>
                    <p class="text-xs text-muted-foreground">
                        Entregue a senha provisória pessoalmente e peça para a pessoa trocá-la em Configurações → Segurança no primeiro acesso.
                    </p>
                {/if}
            </section>

            <section class="space-y-4 rounded-xl border bg-card p-5">
                <div>
                    <h2 class="font-semibold">Perfis de acesso</h2>
                    <p class="text-xs text-muted-foreground">Dê só o necessário para o trabalho de cada pessoa (menor privilégio).</p>
                </div>
                {#if canAssignRoles}
                    <div class="grid gap-2">
                        {#each roles as role (role.value)}
                            <label class="flex cursor-pointer items-start gap-3 rounded-lg border p-3 text-sm transition-colors hover:bg-muted/40 has-[:checked]:border-primary has-[:checked]:bg-accent/40">
                                <input
                                    type="checkbox"
                                    name="roles[]"
                                    value={role.value}
                                    checked={user?.roles.includes(role.value) ?? false}
                                    class="mt-0.5 size-4 accent-primary"
                                />
                                <span>
                                    <span class="font-medium">{role.label}</span>
                                    {#if descriptions[role.slug]}<span class="block text-xs text-muted-foreground">{descriptions[role.slug]}</span>{/if}
                                </span>
                            </label>
                        {/each}
                    </div>
                    <InputError message={errors.roles} />
                {:else}
                    <p class="text-sm text-muted-foreground">Você não tem permissão para alterar perfis de acesso.</p>
                {/if}
            </section>
        </div>
    {/snippet}
</Form>
