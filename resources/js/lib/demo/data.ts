/**
 * DADOS FICTÍCIOS — protótipo navegável para validação com a equipe do NAE.
 *
 * Nada aqui vem do banco de dados. Estas estruturas demonstram os módulos
 * M8 (Relatórios) antes da implementação real, e
 * devem ser substituídas pelos dados do backend quando cada marco for
 * desenvolvido. (M4 e M6 já usam dados reais.)
 */

/** Indicadores no formato do relatório anual de atividades do NAE (2026). */
export const reportIndicators: { label: string; value: number }[] = [
    { label: 'Atendimentos psicopedagógicos', value: 214 },
    { label: 'Atendimentos psicológicos', value: 136 },
    { label: 'Escutas e orientações', value: 98 },
    { label: 'Encaminhamentos internos e externos', value: 57 },
    { label: 'Avaliações adaptadas acompanhadas', value: 41 },
    { label: 'Alunos acompanhados pelo NAE', value: 129 },
    { label: 'Reuniões com coordenações e docentes', value: 33 },
    { label: 'Ações coletivas realizadas', value: 18 },
    { label: 'Estudantes alcançados em ações coletivas', value: 1240 },
];

export const months = [
    'Jan',
    'Fev',
    'Mar',
    'Abr',
    'Mai',
    'Jun',
    'Jul',
    'Ago',
    'Set',
    'Out',
    'Nov',
    'Dez',
];

/** Atendimentos por mês: [psicopedagógico, psicológico, escuta]. Out–Dez ainda sem dados. */
export const monthlyAttendances: [number, number, number][] = [
    [6, 4, 4],
    [10, 6, 6],
    [12, 8, 6],
    [10, 8, 8],
    [14, 8, 6],
    [8, 6, 4],
    [6, 4, 4],
    [12, 8, 8],
    [14, 10, 8],
    [0, 0, 0],
    [0, 0, 0],
    [0, 0, 0],
];

export const collectiveActions: {
    name: string;
    description: string;
    reached: number;
}[] = [
    {
        name: 'UpGrade Acadêmico',
        description: 'Encontros temáticos com horas complementares',
        reached: 420,
    },
    {
        name: 'Tô na Unibalsas',
        description: 'Acolhimento de calouros (presencial e EAD)',
        reached: 510,
    },
    {
        name: 'P.A.T.A.S.',
        description: 'Acolhimento com terapia assistida por animais',
        reached: 160,
    },
    {
        name: 'Programa de Mentoria UB',
        description: 'Mentoria entre estudantes',
        reached: 95,
    },
    {
        name: 'Encontro de líderes de turma',
        description: 'Líderes e vice-líderes',
        reached: 55,
    },
];
