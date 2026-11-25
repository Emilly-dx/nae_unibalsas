<?php

return [
    /*
    | Senha das contas criadas pelo DemoDataSeeder (dados fictícios).
    | Fora do computador local, defina uma senha forte em NAE_DEMO_PASSWORD.
    */
    'demo_password' => env('NAE_DEMO_PASSWORD', 'password'),

    /*
    | Disco dos laudos e documentos dos estudantes (dados sensíveis).
    | Precisa ser PRIVADO. Local: "local". Hospedagem: o nome do bucket privado.
    */
    'documents_disk' => env('NAE_DOCUMENTS_DISK', 'local'),

    /*
    | Cursos de graduação da UNIBALSAS e a modalidade de cada um
    | (presential, hybrid = semipresencial, distance = EAD).
    | Fonte: Vestibular UNIBALSAS 2027.1 e cursos EAD. Para incluir um curso
    | (ou um que saiu de oferta mas ainda tem alunos), acrescente aqui.
    */
    'courses' => [
        'Agronomia' => 'presential',
        'Análise e Desenvolvimento de Sistemas' => 'presential',
        'Biomedicina' => 'hybrid',
        'Ciências Contábeis' => 'presential',
        'Direito' => 'presential',
        'Enfermagem' => 'presential',
        'Estética e Cosmética' => 'presential',
        'Medicina Veterinária' => 'presential',
        'Odontologia' => 'presential',
        'Psicologia' => 'presential',
        'Produção Publicitária' => 'presential',
        'Administração' => 'distance',
        'Gestão Comercial' => 'distance',
        'Gestão de Agronegócios' => 'distance',
        'Gestão de Recursos Humanos' => 'distance',
        'Gestão Financeira' => 'distance',
        'Logística' => 'distance',
    ],

];
