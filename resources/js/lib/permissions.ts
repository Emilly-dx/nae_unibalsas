import type { Auth } from '@/types';

/**
 * Verificação de permissão APENAS para a interface (mostrar/ocultar
 * botões e menus). A autorização real é sempre feita no backend.
 */
export function can(auth: Auth, permission: string): boolean {
    return (
        auth.roles.includes('administrator') ||
        auth.permissions.includes(permission)
    );
}
