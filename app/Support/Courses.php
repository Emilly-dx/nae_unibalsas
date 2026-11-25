<?php

namespace App\Support;

use App\Enums\Modality;

/**
 * Cursos de graduação da UNIBALSAS (config/nae.php), com a modalidade.
 */
class Courses
{
    /**
     * @return array<string, Modality>
     */
    public static function all(): array
    {
        /** @var array<string, string> $courses */
        $courses = config('nae.courses', []);

        return array_map(fn (string $modality): Modality => Modality::from($modality), $courses);
    }

    /**
     * @return list<string>
     */
    public static function names(): array
    {
        return array_keys(self::all());
    }

    /**
     * Opções para o formulário, agrupadas pela modalidade.
     *
     * @return list<array{value: string, label: string, modality: string}>
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::all() as $name => $modality) {
            $options[] = ['value' => $name, 'label' => $name, 'modality' => $modality->value];
        }

        return $options;
    }
}
