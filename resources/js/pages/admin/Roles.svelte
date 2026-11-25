<script module lang="ts">
    import { dashboard } from '@/routes';
    import { index } from '@/routes/admin/roles';

    export const layout = {
        breadcrumbs: [
            { title: 'Painel', href: dashboard() },
            { title: 'Perfis de acesso', href: index() },
        ],
    };
</script>

<script lang="ts">
    import { router } from '@inertiajs/svelte';
    import Lock from '@lucide/svelte/icons/lock';
    import AppHead from '@/components/AppHead.svelte';
    import { Button } from '@/components/ui/button';
    import { cn } from '@/lib/utils';
    import { update } from '@/routes/admin/roles';

    type Role = { id: number; name: string; slug: string; users: number; permissions: number[]; locked: boolean };
    type Module = { name: string; permissions: { id: number; name: string; slug: string }[] };

    let { roles, modules, canManage }: { roles: Role[]; modules: Module[]; canManage: boolean } = $props();

    // svelte-ignore state_referenced_locally
    let selectedId = $state(roles.find((role) => !role.locked)?.id ?? roles[0]?.id);
    const selected = $derived(roles.find((role) => role.id === selectedId) ?? roles[0]);

    // Permissões marcadas (rascunho), por perfil.
    // svelte-ignore state_referenced_locally
    let drafts = $state<Record<number, number[]>>(Object.fromEntries(roles.map((role) => [role.id, [...role.permissions]])));
    let saving = $state(false);

    const checked = $derived(new Set(drafts[selected.id] ?? []));
    const dirty = $derived(
        [...checked].sort().join(',') !== [...selected.permissions].sort().join(','),
    );
    const editable = $derived(canManage && !selected.locked);

    function toggle(id: number): void {
        const current = new Set(drafts[selected.id]);

        if (current.has(id)) {
            current.delete(id);
        } else {
            current.add(id);
        }

        drafts[selected.id] = [...current];
    }

    function reset(): void {
        drafts[selected.id] = [...selected.permissions];
    }

    function save(): void {
        saving = true;
        router.put(
            update(selected.id).url,
            { permissions: drafts[selected.id] },
            { preserveScroll: true, preserveState: true, onFinish: () => (saving = false) },
        );
    }
</script>

<AppHead title="Perfis de acesso" />

<div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
    <div>
        <h1 class="text-2xl font-semibold tracking-tight">Perfis de acesso</h1>
        <p class="text-sm text-muted-foreground">O que cada perfil pode fazer na plataforma. As mudanças valem na hora para todas as pessoas do perfil.</p>
    </div>

    <div class="grid gap-6 lg:grid-cols-[260px_1fr]">
        <nav class="flex gap-2 overflow-x-auto lg:flex-col" aria-label="Perfis">
            {#each roles as role (role.id)}
                <button
                    type="button"
                    class={cn(
                        'flex min-w-48 items-center justify-between gap-3 rounded-lg border px-4 py-3 text-left text-sm transition-colors hover:bg-muted/50',
                        role.id === selected.id && 'border-primary bg-accent/50',
                    )}
                    onclick={() => (selectedId = role.id)}
                >
                    <span>
                        <span class="block font-medium">{role.name}</span>
                        <span class="text-xs text-muted-foreground">{role.users} {role.users === 1 ? 'pessoa' : 'pessoas'}</span>
                    </span>
                    {#if role.locked}<Lock class="size-3.5 text-muted-foreground" />{/if}
                </button>
            {/each}
        </nav>

        <section class="overflow-hidden rounded-xl border bg-card">
            <header class="flex flex-wrap items-center justify-between gap-3 border-b px-5 py-4">
                <div>
                    <h2 class="font-semibold">{selected.name}</h2>
                    <p class="text-xs text-muted-foreground">
                        {#if selected.locked}
                            Acesso administrativo completo, exceto ao conteúdo sigiloso (que só o profissional autor vê). Não é editável.
                        {:else}
                            {checked.size} {checked.size === 1 ? 'permissão marcada' : 'permissões marcadas'}
                        {/if}
                    </p>
                </div>
                {#if editable}
                    <div class="flex gap-2">
                        <Button variant="ghost" disabled={!dirty || saving} onclick={reset}>Desfazer</Button>
                        <Button disabled={!dirty || saving} onclick={save}>{saving ? 'Salvando…' : 'Salvar permissões'}</Button>
                    </div>
                {/if}
            </header>
            <div class="divide-y">
                {#each modules as module (module.name)}
                    <div class="grid gap-3 px-5 py-4 md:grid-cols-[180px_1fr]">
                        <p class="text-sm font-medium">{module.name}</p>
                        <div class="grid gap-2 sm:grid-cols-2">
                            {#each module.permissions as permission (permission.id)}
                                <label class={cn('flex items-start gap-2 text-sm', editable ? 'cursor-pointer' : 'cursor-default')}>
                                    <input
                                        type="checkbox"
                                        class="mt-0.5 size-4 shrink-0 accent-primary"
                                        checked={selected.locked || checked.has(permission.id)}
                                        disabled={!editable}
                                        onchange={() => toggle(permission.id)}
                                    />
                                    <span>
                                        {permission.name}
                                        <span class="block font-mono text-[11px] text-muted-foreground">{permission.slug}</span>
                                    </span>
                                </label>
                            {/each}
                        </div>
                    </div>
                {/each}
            </div>
        </section>
    </div>
</div>
