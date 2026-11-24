<?php

use App\Support\Registration;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Converte as matrículas já cadastradas com 8 dígitos para o formato oficial
 * AA.S.NNNNN. As que não têm 8 dígitos ficam como estão (corrigir pela edição).
 * Uma matrícula só é convertida se o novo valor ainda não existir.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('students')->orderBy('id')->select(['id', 'registration'])->each(function (object $student): void {
            $formatted = Registration::normalize((string) $student->registration);

            if ($formatted === $student->registration
                || DB::table('students')->where('registration', $formatted)->exists()) {
                return;
            }

            DB::table('students')->where('id', $student->id)->update(['registration' => $formatted]);
        });
    }

    public function down(): void
    {
        // Irreversível de forma segura: o formato com pontos é o oficial.
    }
};
