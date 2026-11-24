/** Telefone com DDD: (99) 99999-9999 ou (99) 9999-9999. Aceita no máximo 11 dígitos. */
export function formatPhone(value: string | null | undefined): string {
    const digits = (value ?? '').replace(/\D/g, '').slice(0, 11);

    if (digits.length === 0) {
        return '';
    }

    if (digits.length <= 2) {
        return `(${digits}`;
    }

    const ddd = digits.slice(0, 2);
    const rest = digits.slice(2);
    const split = digits.length === 11 ? 5 : 4;

    return rest.length > split
        ? `(${ddd}) ${rest.slice(0, split)}-${rest.slice(split)}`
        : `(${ddd}) ${rest}`;
}

/** Matrícula no formato do portal: 24.1.08965. Aceita no máximo 8 dígitos. */
export function formatRegistration(value: string | null | undefined): string {
    const digits = (value ?? '').replace(/\D/g, '').slice(0, 8);

    return [digits.slice(0, 2), digits.slice(2, 3), digits.slice(3)]
        .filter(Boolean)
        .join('.');
}

/** Para usar em oninput: reescreve o valor do campo já formatado. */
export function maskInput(format: (value: string) => string) {
    return (event: Event & { currentTarget: HTMLInputElement }): void => {
        event.currentTarget.value = format(event.currentTarget.value);
    };
}
