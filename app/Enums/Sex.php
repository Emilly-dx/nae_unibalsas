<?php

namespace App\Enums;

/**
 * Sexo do estudante (SRS §9.2). Valores a validar com o NAE.
 */
enum Sex: string
{
    case Female = 'female';
    case Male = 'male';
    case NotInformed = 'not_informed';

    public function label(): string
    {
        return match ($this) {
            self::Female => 'Feminino',
            self::Male => 'Masculino',
            self::NotInformed => 'Prefere não informar',
        };
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $sex): array => ['value' => $sex->value, 'label' => $sex->label()],
            self::cases(),
        );
    }
}
