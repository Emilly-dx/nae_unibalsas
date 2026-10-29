<?php

namespace App\Enums;

enum Modality: string
{
    case Presential = 'presential';
    case Distance = 'distance';

    public function label(): string
    {
        return match ($this) {
            self::Presential => 'Presencial',
            self::Distance => 'EAD',
        };
    }
}
