<?php

namespace App\Http\Requests\Concerns;

/**
 * Mensagens de validação em português para os módulos do NAE.
 * (O projeto ainda não tem arquivos de tradução em lang/pt_BR.)
 */
trait HasPortugueseMessages
{
    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'required' => 'O campo :attribute é obrigatório.',
            'string' => 'O campo :attribute deve ser um texto.',
            'max' => 'O campo :attribute não pode ter mais que :max caracteres.',
            'file.max' => 'O arquivo não pode ter mais que :max KB.',
            'integer' => 'O campo :attribute deve ser um número inteiro.',
            'between' => 'O campo :attribute deve estar entre :min e :max.',
            'email' => 'Informe um e-mail válido.',
            'date' => 'Informe uma data válida.',
            'before' => 'A :attribute deve ser anterior a hoje.',
            'unique' => 'Já existe um cadastro com esta :attribute.',
            'enum' => 'O valor selecionado para :attribute é inválido.',
            'regex' => 'O formato do campo :attribute é inválido.',
            'file' => 'Selecione um arquivo válido.',
            'mimes' => 'O arquivo deve ser do tipo: :values.',
        ];
    }
}
