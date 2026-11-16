/**
 * DADOS FICTÍCIOS — protótipo navegável para validação com a equipe do NAE.
 *
 * Nada aqui vem do banco de dados. Estas estruturas demonstram os módulos
 * M6 (Encaminhamentos) e M8 (Relatórios) antes da implementação real, e
 * devem ser substituídas pelos dados do backend quando cada marco for
 * desenvolvido. (O M4 — Triagem + Atendimentos — já usa dados reais.)
 */

export type DemoStudent = {
    id: number;
    name: string;
    course: string;
    period: number;
};

export const demoStudents: DemoStudent[] = [
    {
        id: 1,
        name: 'Ana Beatriz S.',
        course: 'Análise e Des. de Sistemas',
        period: 3,
    },
    { id: 2, name: 'Carlos Eduardo M.', course: 'Direito', period: 1 },
    { id: 3, name: 'Fernanda L.', course: 'Enfermagem', period: 5 },
    { id: 4, name: 'João Pedro A.', course: 'Administração', period: 2 },
    { id: 5, name: 'Larissa C.', course: 'Pedagogia', period: 7 },
    { id: 6, name: 'Mateus R.', course: 'Ciências Contábeis', period: 4 },
    { id: 7, name: 'Paula V.', course: 'Direito', period: 6 },
    { id: 8, name: 'Rafael N.', course: 'Enfermagem', period: 2 },
    { id: 9, name: 'Igor T.', course: 'Enfermagem', period: 3 },
    { id: 10, name: 'Beatriz O.', course: 'Psicologia', period: 1 },
];

export type ReferralStatus =
    | 'concluido'
    | 'aguardando'
    | 'andamento'
    | 'enviado';

export const referralStatusLabels: Record<ReferralStatus, string> = {
    concluido: 'Concluído',
    aguardando: 'Aguardando retorno',
    andamento: 'Em andamento',
    enviado: 'Enviado',
};

export const referralStatusStyles: Record<ReferralStatus, string> = {
    concluido: 'bg-muted text-muted-foreground',
    aguardando:
        'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300',
    andamento: 'bg-blue-100 text-blue-700 dark:bg-blue-950 dark:text-blue-300',
    enviado: 'bg-rose-100 text-rose-700 dark:bg-rose-950 dark:text-rose-300',
};

export type Referral = {
    student: string;
    kind: 'interno' | 'externo';
    destination: string;
    reason: string;
    date: string;
    status: ReferralStatus;
};

export const referralDestinations = {
    interno: [
        'Coordenação do curso',
        'Docentes da turma',
        'CIA — Inclusão e Acessibilidade',
        'Psicologia do NAE',
    ],
    externo: [
        'Rede de saúde',
        'Clínica UNIBALSAS',
        'CAPS',
        'Outro serviço externo',
    ],
};

export const referrals: Referral[] = [
    {
        student: 'Ana Beatriz S.',
        kind: 'interno',
        destination: 'Coordenação do curso',
        reason: 'Solicitar avaliação adaptada',
        date: '07/09',
        status: 'concluido',
    },
    {
        student: 'João Pedro A.',
        kind: 'externo',
        destination: 'Rede de saúde',
        reason: 'Acompanhamento psicológico contínuo',
        date: '15/09',
        status: 'aguardando',
    },
    {
        student: 'Rafael N.',
        kind: 'interno',
        destination: 'CIA — Inclusão e Acessibilidade',
        reason: 'Recursos de acessibilidade em sala',
        date: '18/09',
        status: 'andamento',
    },
    {
        student: 'Larissa C.',
        kind: 'interno',
        destination: 'Docentes da turma',
        reason: 'Orientação sobre prazos de entrega',
        date: '22/09',
        status: 'enviado',
    },
    {
        student: 'Fernanda L.',
        kind: 'externo',
        destination: 'Rede de saúde',
        reason: 'Avaliação com especialista',
        date: '23/09',
        status: 'aguardando',
    },
    {
        student: 'Igor T.',
        kind: 'interno',
        destination: 'Coordenação do curso',
        reason: 'Adequação de avaliações',
        date: '25/09',
        status: 'enviado',
    },
    {
        student: 'Mateus R.',
        kind: 'interno',
        destination: 'Coordenação do curso',
        reason: 'Retorno sobre desempenho',
        date: '30/06',
        status: 'concluido',
    },
    {
        student: 'Paula V.',
        kind: 'interno',
        destination: 'CIA — Inclusão e Acessibilidade',
        reason: 'Apoio no período de provas',
        date: '26/09',
        status: 'andamento',
    },
];

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
