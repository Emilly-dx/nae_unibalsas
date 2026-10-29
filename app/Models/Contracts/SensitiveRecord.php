<?php

namespace App\Models\Contracts;

/**
 * Marca um registro com informação sensível (SRS §15.2).
 *
 * Pela RN-012, só o usuário que cadastrou o registro pode visualizá-lo.
 * Registros que implementam esta interface NÃO passam pelo bypass do
 * administrador no Gate::before (SRS §28.13 — pendente de validação).
 */
interface SensitiveRecord
{
    public function ownerId(): int;
}
