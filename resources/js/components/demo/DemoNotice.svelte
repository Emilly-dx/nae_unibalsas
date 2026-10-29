<script lang="ts">
    import FlaskConical from '@lucide/svelte/icons/flask-conical';

    let { milestone }: { milestone: string } = $props();
</script>

<div
    class="flex items-start gap-2 rounded-lg border border-dashed border-amber-300 bg-amber-50 px-3 py-2 text-xs text-amber-900 dark:border-amber-400/30 dark:bg-amber-500/10 dark:text-amber-100"
    role="note"
>
    <FlaskConical class="mt-px size-3.5 shrink-0" />
    <p>
        <strong>Protótipo ({milestone}).</strong> Tela de demonstração com dados
        fictícios para validação com a equipe do NAE. Nada é salvo.
    </p>
</div>
