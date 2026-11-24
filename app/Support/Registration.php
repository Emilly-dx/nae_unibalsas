<?php

namespace App\Support;

/**
 * Matrícula da UNIBALSAS no formato oficial AA.S.NNNNN
 * (ano de ingresso, semestre e número), ex.: 24.1.08965.
 */
class Registration
{
    public const string PATTERN = '/^\d{2}\.\d\.\d{5}$/';

    /**
     * Aceita a matrícula com ou sem pontos e devolve no formato oficial.
     * Valores que não têm 8 dígitos voltam como foram digitados, para a
     * validação apontar o erro.
     */
    public static function normalize(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);
        $digits = self::digits($value);

        if (strlen($digits) !== 8) {
            return $value;
        }

        return substr($digits, 0, 2).'.'.substr($digits, 2, 1).'.'.substr($digits, 3);
    }

    public static function digits(string $value): string
    {
        return (string) preg_replace('/\D+/', '', $value);
    }
}
