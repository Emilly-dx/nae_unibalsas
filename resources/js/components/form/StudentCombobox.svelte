<script lang="ts">
    import Check from '@lucide/svelte/icons/check';
    import Search from '@lucide/svelte/icons/search';
    import X from '@lucide/svelte/icons/x';
    import { cn } from '@/lib/utils';
    import type { StudentOption } from '@/types';

    let {
        students,
        value = $bindable(null),
        name = 'student_id',
        id = 'student',
        disabled = false,
    }: {
        students: StudentOption[];
        value?: number | null;
        name?: string;
        id?: string;
        disabled?: boolean;
    } = $props();

    let query = $state('');
    let open = $state(false);
    let highlighted = $state(0);
    let searchInput: HTMLInputElement | undefined = $state();

    const selected = $derived(students.find((student) => student.id === value) ?? null);

    const normalize = (text: string): string =>
        text.normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase();

    const results = $derived.by(() => {
        const term = normalize(query.trim());
        const list = term
            ? students.filter(
                  (student) =>
                      normalize(student.name).includes(term) ||
                      student.registration.replace(/\D/g, '').includes(term.replace(/\D/g, '') || '\u0000'),
              )
            : students;

        return list.slice(0, 8);
    });

    function choose(student: StudentOption): void {
        // Tira o foco antes de trocar o campo pelo resumo: se o campo saísse
        // da tela ainda focado, o navegador dispararia "change" no meio da
        // atualização e o <Form> do Inertia tentaria mudar estado nesse momento.
        searchInput?.blur();
        open = false;
        value = student.id;
    }

    function clear(): void {
        query = '';
        highlighted = 0;
        value = null;
        open = true;
    }

    function onKeydown(event: KeyboardEvent): void {
        if (event.key === 'ArrowDown') {
            event.preventDefault();
            open = true;
            highlighted = Math.min(highlighted + 1, results.length - 1);
        } else if (event.key === 'ArrowUp') {
            event.preventDefault();
            highlighted = Math.max(highlighted - 1, 0);
        } else if (event.key === 'Enter' && open && results[highlighted]) {
            event.preventDefault();
            choose(results[highlighted]);
        } else if (event.key === 'Escape') {
            open = false;
        }
    }
</script>

<input type="hidden" {name} value={value ?? ''} />

{#if selected}
    <div class="flex items-center justify-between gap-2 rounded-md border bg-muted/40 px-3 py-2 text-sm">
        <div class="min-w-0">
            <p class="truncate font-medium">{selected.name}</p>
            <p class="truncate text-xs text-muted-foreground">
                {selected.registration} · {selected.course} · {selected.period}º período
            </p>
        </div>
        {#if !disabled}
            <button
                type="button"
                class="rounded p-1 text-muted-foreground hover:bg-muted hover:text-foreground"
                aria-label="Trocar estudante"
                onclick={clear}
            >
                <X class="size-4" />
            </button>
        {/if}
    </div>
{:else}
    <div class="relative">
        <Search class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
        <input
            bind:this={searchInput}
            {id}
            type="text"
            class="flex h-9 w-full rounded-md border border-input bg-background py-1 pr-3 pl-9 text-sm shadow-sm transition-colors placeholder:text-muted-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
            placeholder="Buscar por nome ou matrícula"
            autocomplete="off"
            role="combobox"
            aria-expanded={open}
            aria-controls="{id}-options"
            value={query}
            onfocus={() => (open = true)}
            oninput={(event) => {
                query = event.currentTarget.value;
                open = true;
                highlighted = 0;
            }}
            onblur={() => setTimeout(() => (open = false), 150)}
            onkeydown={onKeydown}
            {disabled}
        />
        {#if open}
            <ul
                id="{id}-options"
                role="listbox"
                class="absolute z-20 mt-1 max-h-72 w-full overflow-auto rounded-md border bg-popover p-1 text-sm shadow-md"
            >
                {#each results as student, index (student.id)}
                    <li role="option" aria-selected={index === highlighted}>
                        <button
                            type="button"
                            class={cn(
                                'flex w-full items-center justify-between gap-2 rounded px-2 py-1.5 text-left',
                                index === highlighted ? 'bg-accent text-accent-foreground' : 'hover:bg-muted',
                            )}
                            onmousedown={(event) => event.preventDefault()}
                            onclick={() => choose(student)}
                        >
                            <span class="min-w-0">
                                <span class="block truncate font-medium">{student.name}</span>
                                <span class="block truncate text-xs text-muted-foreground">
                                    {student.registration} · {student.course}
                                </span>
                            </span>
                            {#if student.id === value}<Check class="size-4" />{/if}
                        </button>
                    </li>
                {:else}
                    <li class="px-2 py-3 text-center text-muted-foreground">Nenhum estudante encontrado.</li>
                {/each}
            </ul>
        {/if}
    </div>
{/if}
