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
            'file.max' => 'O arquivo não pode ter mais que 10 MB.',
            'integer' => 'O campo :attribute deve ser um número inteiro.',
            'between' => 'O campo :attribute deve estar entre :min e :max.',
            'email' => 'Informe um e-mail válido.', 'digits_between' => 'O campo :attribute deve ter entre :min e :max dígitos (DDD + número).',
            'date' => 'Informe uma data válida.',
            'before' => 'A :attribute deve ser anterior a hoje.',
            'unique' => 'Já existe um cadastro com esta :attribute.',
            'enum' => 'O valor selecionado para :attribute é inválido.',
            'exists' => 'O :attribute selecionado é inválido.',
            'after' => 'O campo :attribute deve ser posterior a :date.',
            'in' => 'O valor selecionado para :attribute é inválido.',
            'regex' => 'O formato do campo :attribute é inválido.',
            'registration.regex' => 'Informe a matrícula no formato 00.0.00000 (ano, semestre e número).',
            'course.in' => 'Selecione um curso da lista.',
            'file' => 'Selecione um arquivo válido.',
            'mimes' => 'O arquivo deve ser do tipo: :values.',
            // O PHP recusou o arquivo antes de chegar ao sistema (quase sempre: maior que o upload_max_filesize do php.ini).
            'uploaded' => 'Não foi possível enviar o arquivo. Verifique se ele tem até 10 MB; se tiver, o limite de upload do PHP precisa ser aumentado.',
            'document.max' => 'O arquivo não pode ter mais que 10 MB.',
            'array' => 'O campo :attribute é inválido.',
            'boolean' => 'O campo :attribute é inválido.',
            'consent.accepted' => 'É necessário registrar o consentimento do estudante (ou responsável) para o tratamento dos dados pelo NAE.',
            'prohibited' => 'Você não tem permissão para enviar :attribute.',
        ];
    }
}
