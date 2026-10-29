/**
 * DADOS FICTÍCIOS — protótipo navegável para validação com a equipe do NAE.
 *
 * Nada aqui vem do banco de dados. Estas estruturas servem para demonstrar
 * o fluxo dos módulos M4 (Triagem + Atendimentos), M6 (Encaminhamentos)
 * e M8 (Relatórios) antes da implementação real, e devem ser substituídas
 * pelos dados do backend quando cada marco for desenvolvido.
 */

export type AttendanceType =
    | 'psicopedagogico'
    | 'psicologico'
    | 'escuta'
    | 'primeira_conversa'
    | 'avaliacao_adaptada';

export const attendanceTypeLabels: Record<AttendanceType, string> = {
    psicopedagogico: 'Psicopedagógico',
    psicologico: 'Psicológico',
    escuta: 'Escuta e orientação',
    primeira_conversa: '1ª conversa',
    avaliacao_adaptada: 'Avaliação adaptada',
};

/** Classes de cor por tipo (chips e blocos da agenda), claro e escuro. */
export const attendanceTypeStyles: Record<AttendanceType, string> = {
    psicopedagogico:
        'border-l-rose-600 bg-rose-50 text-rose-800 dark:bg-rose-950/60 dark:text-rose-200',
    psicologico:
        'border-l-blue-600 bg-blue-50 text-blue-800 dark:bg-blue-950/60 dark:text-blue-200',
    escuta: 'border-l-amber-600 bg-amber-50 text-amber-800 dark:bg-amber-950/50 dark:text-amber-200',
    primeira_conversa:
        'border-l-amber-600 bg-amber-50 text-amber-800 dark:bg-amber-950/50 dark:text-amber-200',
    avaliacao_adaptada:
        'border-l-rose-600 bg-rose-50 text-rose-800 dark:bg-rose-950/60 dark:text-rose-200',
};

export const attendanceChipStyles: Record<AttendanceType, string> = {
    psicopedagogico:
        'bg-rose-100 text-rose-700 dark:bg-rose-950 dark:text-rose-300',
    psicologico:
        'bg-blue-100 text-blue-700 dark:bg-blue-950 dark:text-blue-300',
    escuta: 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300',
    primeira_conversa:
        'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300',
    avaliacao_adaptada:
        'bg-rose-100 text-rose-700 dark:bg-rose-950 dark:text-rose-300',
};

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

export function demoStudent(name: string): DemoStudent {
    return (
        demoStudents.find((student) => student.name === name) ?? demoStudents[0]
    );
}

export const todayAgenda: {
    time: string;
    student: string;
    type: AttendanceType;
}[] = [
    { time: '09:00', student: 'Ana Beatriz S.', type: 'psicopedagogico' },
    { time: '10:30', student: 'Larissa C.', type: 'psicopedagogico' },
    { time: '14:00', student: 'Paula V.', type: 'primeira_conversa' },
    { time: '15:00', student: 'Rafael N.', type: 'psicologico' },
];

export type TriageRequest = {
    id: number;
    student: string;
    reason: string;
    origin: string;
    date: string;
    priority: 'normal' | 'prioritaria';
    description: string;
    requester: string;
};

export const triageQueue: TriageRequest[] = [
    {
        id: 1,
        student: 'Carlos Eduardo M.',
        reason: 'Dificuldade de adaptação ao curso (calouro)',
        origin: 'Coordenação de Direito',
        date: '25/09',
        priority: 'normal',
        requester: 'Coordenação de Direito',
        description:
            'Estudante relata dificuldade em acompanhar o ritmo das disciplinas e em se integrar à turma.',
    },
    {
        id: 2,
        student: 'Paula V.',
        reason: 'Ansiedade no período de avaliações',
        origin: 'Procura espontânea',
        date: '26/09',
        priority: 'prioritaria',
        requester: 'A própria estudante',
        description:
            'Procurou o NAE relatando crises de ansiedade nas semanas de prova.',
    },
    {
        id: 3,
        student: 'Igor T.',
        reason: 'Solicitação de avaliação adaptada',
        origin: 'Coordenação de Enfermagem',
        date: '27/09',
        priority: 'normal',
        requester: 'Coordenação de Enfermagem',
        description:
            'Coordenação solicita orientação sobre adaptação das avaliações do semestre.',
    },
    {
        id: 4,
        student: 'Beatriz O.',
        reason: 'Organização da rotina de estudos',
        origin: 'Docente da turma',
        date: '28/09',
        priority: 'normal',
        requester: 'Docente da turma',
        description:
            'Docente observou atrasos recorrentes nas entregas e dificuldade de organização.',
    },
];

export const weekDays = ['Seg 28', 'Ter 29', 'Qua 30', 'Qui 01', 'Sex 02'];
export const weekLabel = '28 set – 02 out 2026';
export const agendaHours = [8, 9, 10, 11, 12, 13, 14, 15, 16, 17];

export type AgendaEvent = {
    day: number;
    start: number;
    duration: number;
    student: string;
    type: AttendanceType;
};

export const weekAgenda: AgendaEvent[] = [
    {
        day: 0,
        start: 9,
        duration: 1,
        student: 'Ana Beatriz S.',
        type: 'psicopedagogico',
    },
    {
        day: 0,
        start: 14,
        duration: 1,
        student: 'Paula V.',
        type: 'primeira_conversa',
    },
    {
        day: 1,
        start: 8,
        duration: 1,
        student: 'Fernanda L.',
        type: 'psicologico',
    },
    {
        day: 1,
        start: 10,
        duration: 1.5,
        student: 'Larissa C.',
        type: 'psicopedagogico',
    },
    {
        day: 2,
        start: 13,
        duration: 1,
        student: 'Carlos Eduardo M.',
        type: 'primeira_conversa',
    },
    {
        day: 2,
        start: 15,
        duration: 1,
        student: 'Rafael N.',
        type: 'psicologico',
    },
    {
        day: 3,
        start: 9,
        duration: 1,
        student: 'Fernanda L.',
        type: 'psicologico',
    },
    {
        day: 3,
        start: 16,
        duration: 1,
        student: 'Igor T.',
        type: 'avaliacao_adaptada',
    },
    { day: 4, start: 10, duration: 1, student: 'Beatriz O.', type: 'escuta' },
];

export const registeredAttendances: {
    date: string;
    student: string;
    type: AttendanceType;
    professional: string;
    modality: 'Presencial' | 'Online';
}[] = [
    {
        date: '24/09/2026',
        student: 'Larissa C.',
        type: 'psicopedagogico',
        professional: 'Psicopedagoga do NAE',
        modality: 'Presencial',
    },
    {
        date: '22/09/2026',
        student: 'Fernanda L.',
        type: 'psicologico',
        professional: 'Psicóloga do NAE',
        modality: 'Presencial',
    },
    {
        date: '18/09/2026',
        student: 'Rafael N.',
        type: 'psicologico',
        professional: 'Psicóloga do NAE',
        modality: 'Online',
    },
    {
        date: '15/09/2026',
        student: 'João Pedro A.',
        type: 'escuta',
        professional: 'Pedagoga do NAE',
        modality: 'Presencial',
    },
    {
        date: '07/09/2026',
        student: 'Ana Beatriz S.',
        type: 'avaliacao_adaptada',
        professional: 'Psicopedagoga do NAE',
        modality: 'Presencial',
    },
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
