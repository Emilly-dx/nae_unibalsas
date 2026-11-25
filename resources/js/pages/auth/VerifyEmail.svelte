<script module lang="ts">
    export const layout = {
        title: 'Confirme seu e-mail',
        description:
            'Clique no link que enviamos para o seu e-mail para confirmar o endereço.',
    };
</script>

<script lang="ts">
    import { Form } from '@inertiajs/svelte';
    import AppHead from '@/components/AppHead.svelte';
    import TextLink from '@/components/TextLink.svelte';
    import { Button } from '@/components/ui/button';
    import { Spinner } from '@/components/ui/spinner';
    import { logout } from '@/routes';
    import { send } from '@/routes/verification';

    let {
        status = '',
    }: {
        status?: string;
    } = $props();
</script>

<AppHead title="Confirmar e-mail" />

{#if status === 'verification-link-sent'}
    <div class="mb-4 text-center text-sm font-medium text-green-600">
        Enviamos um novo link de confirmação para o seu e-mail.
    </div>
{/if}

<Form {...send.form()} class="space-y-6 text-center">
    {#snippet children({ processing })}
        <Button type="submit" disabled={processing} variant="secondary">
            {#if processing}<Spinner />{/if}
            Reenviar e-mail de confirmação
        </Button>

        <TextLink href={logout()} as="button" class="mx-auto block text-sm">
            Sair
        </TextLink>
    {/snippet}
</Form>
