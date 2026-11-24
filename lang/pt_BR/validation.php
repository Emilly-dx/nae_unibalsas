<?php

/*
| Mensagens de validação em português. As regras que não estão aqui usam
| as mensagens em inglês do Laravel; os formulários do NAE definem as
| próprias mensagens (HasPortugueseMessages).
*/
return [
    'required' => 'O campo :attribute é obrigatório.',
    'email' => 'Informe um e-mail válido.',
    'confirmed' => 'A confirmação do campo :attribute não confere.',
    'current_password' => 'A senha atual está incorreta.',
    'string' => 'O campo :attribute deve ser um texto.',
    'max' => [
        'string' => 'O campo :attribute não pode ter mais que :max caracteres.',
    ],
    'min' => [
        'string' => 'O campo :attribute deve ter pelo menos :min caracteres.',
    ],
    'unique' => 'Este :attribute já está em uso.',
    'password' => [
        'letters' => 'A senha deve conter pelo menos uma letra.',
        'mixed' => 'A senha deve conter letras maiúsculas e minúsculas.',
        'numbers' => 'A senha deve conter pelo menos um número.',
        'symbols' => 'A senha deve conter pelo menos um símbolo.',
        'uncompromised' => 'Esta senha apareceu em um vazamento de dados. Escolha outra.',
    ],
    'attributes' => [
        'email' => 'e-mail',
        'password' => 'senha',
        'current_password' => 'senha atual',
        'name' => 'nome',
    ],
];
