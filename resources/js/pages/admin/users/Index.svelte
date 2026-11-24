<script module lang="ts">
    import { dashboard } from '@/routes';
    import { index } from '@/routes/admin/users';

    export const layout = {
        breadcrumbs: [
            { title: 'Painel', href: dashboard() },
            { title: 'Usuários', href: index() },
        ],
    };
</script>

<script lang="ts">
    import { Link, router } from '@inertiajs/svelte';
    import Plus from '@lucide/svelte/icons/plus';
    import Search from '@lucide/svelte/icons/search';
    import UserCog from '@lucide/svelte/icons/user-cog';
    import AppHead from '@/components/AppHead.svelte';
    import Pager from '@/components/admin/Pager.svelte';
    import FieldSelect from '@/components/form/FieldSelect.svelte';
    import { Badge } from '@/components/ui/badge';
    import { Button } from '@/components/ui/button';
    import { Input } from '@/components/ui/input';
    import { getInitials } from '@/lib/initials';
    import { create, deactivate, edit, reactivate } from '@/routes/admin/users';
    import type { Option, Paginated } from '@/types';

    type UserRow = {
        id: number;
        name: string;
        email: string;
        roles: string[];
        active: boolean;
        last_login: string | null;
        is_me: boolean;
    };

    let {
        users,
        filters,
        roles,
        can,
    }: {
        users: Paginated<UserRow>;
        filters: { busca: string; perfil: string; situacao: string };
        roles: Option[];
        can: { create: boolean; edit: boolean; deactivate: boolean };
    } = $props();

    // svelte-ignore state_referenced_locally
    let form = $state({ ...filters });
    let debounce: ReturnType<typeof setTimeout> | undefined;

    function apply(): void {
        clearTimeout(debounce);
        const query = Object.fromEntries(Object.entries(form).filter(([, value]) => value !== ''));
        router.get(index().url, query, { preserveState: true, preserveScroll: true, replace: true });
    }

    function onSearch(): void {
        clearTimeout(debounce);
        debounce = setTimeout(apply, 350);
    }

    function toggle(user: UserRow): void {
        const message = user.active
            ? `Desativar a conta de ${user.name}? A pessoa não conseguirá mais entrar, mas tudo o que ela registrou continua no sistema.`
            : `Reativar a conta de ${user.name}?`;

        if (!window.confirm(message)) {
            return;
        }

        router.patch((user.active ? deactivate(user.id) : reactivate(user.id)).url, {}, { preserveScroll: true });
    }
</script>

<AppHead title="Usuários" />

<div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
    <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight">Usuários</h1>
            <p class="text-sm text-muted-foreground">Contas da equipe do NAE e os perfis de acesso de cada pessoa.</p>
        </div>
        {#if can.create}
            <Button asChild>
                {#snippet children(props)}
                    <Link {...props} href={create().url}><Plus class="size-4" /> Nova conta</Link>
                {/snippet}
            </Button>
        {/if}
    </div>

    <div class="grid gap-3 rounded-xl border bg-card p-4 md:grid-cols-[2fr_1fr_1fr]">
        <div class="relative">
            <Search class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
            <Input type="search" class="pl-9" placeholder="Nome ou e-mail" aria-label="Pesquisar" bind:value={form.busca} oninput={onSearch} />
        </div>
        <FieldSelect aria-label="Perfil" bind:value={form.perfil} onchange={apply}>
            <option value="">Todos os perfis</option>
            {#each roles as role (role.value)}<option value={role.value}>{role.label}</option>{/each}
        </FieldSelect>
        <FieldSelect aria-label="Situação" bind:value={form.situacao} onchange={apply}>
            <option value="ativos">Contas ativas</option>
            <option value="inativos">Contas desativadas</option>
            <option value="todos">Todas</option>
        </FieldSelect>
    </div>

    <section class="overflow-hidden rounded-xl border bg-card">
        {#if users.data.length === 0}
            <div class="flex flex-col items-center gap-2 p-10 text-center text-sm text-muted-foreground">
                <UserCog class="size-7" />
                Nenhuma conta encontrada.
            </div>
        {:else}
            <div class="overflow-x-auto">
                <table class="w-full min-w-[720px] text-sm">
                    <thead class="bg-muted/50 text-left text-xs text-muted-foreground">
                        <tr>
                            <th class="px-5 py-2.5 font-medium">Pessoa</th>
                            <th class="px-4 py-2.5 font-medium">Perfis</th>
                            <th class="px-4 py-2.5 font-medium">Último acesso</th>
                            <th class="px-4 py-2.5 font-medium">Situação</th>
                            <th class="px-5 py-2.5"><span class="sr-only">Ações</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        {#each users.data as user (user.id)}
                            <tr class={user.active ? '' : 'text-muted-foreground'}>
                                <td class="px-5 py-3">
                                    <div class="flex items-center gap-3">
                                        <span class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-accent text-xs font-semibold text-accent-foreground">
                                            {getInitials(user.name)}
                                        </span>
                                        <div class="min-w-0">
                                            <p class="truncate font-medium">{user.name}{user.is_me ? ' (você)' : ''}</p>
                                            <p class="truncate text-xs text-muted-foreground">{user.email}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex flex-wrap gap-1">
                                        {#each user.roles as role (role)}<Badge variant="secondary">{role}</Badge>{:else}<span class="text-xs">Sem perfil</span>{/each}
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-xs">{user.last_login ?? 'Nunca entrou'}</td>
                                <td class="px-4 py-3">
                                    {#if user.active}<Badge variant="secondary">Ativa</Badge>{:else}<Badge variant="outline">Desativada</Badge>{/if}
                                </td>
                                <td class="px-5 py-3">
                                    <div class="flex justify-end gap-3 text-xs">
                                        {#if can.edit}
                                            <Link href={edit(user.id).url} class="text-primary underline-offset-4 hover:underline">Editar</Link>
                                        {/if}
                                        {#if can.deactivate && !user.is_me}
                                            <button type="button" class="text-muted-foreground underline-offset-4 hover:text-foreground hover:underline" onclick={() => toggle(user)}>
                                                {user.active ? 'Desativar' : 'Reativar'}
                                            </button>
                                        {/if}
                                    </div>
                                </td>
                            </tr>
                        {/each}
                    </tbody>
                </table>
            </div>
            <Pager page={users} />
        {/if}
    </section>

    <p class="text-xs text-muted-foreground">
        Contas não são excluídas: desativar impede o acesso e mantém o nome de quem fez cada registro e a trilha de auditoria.
    </p>
</div>
