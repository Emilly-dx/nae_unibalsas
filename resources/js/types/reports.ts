export type Count = { label: string; value: number };

export type Report = {
    period: string;
    summary: { key: string; label: string; value: number; hint: string }[];
    byType: (Count & { key: string })[];
    monthly: {
        labels: string[];
        series: { key: string; label: string; values: number[] }[];
    };
    byProfessional: {
        name: string;
        total: number;
        psychopedagogical: number;
        psychological: number;
        listening: number;
        cancelled: number;
    }[];
    byCourse: { course: string; students: number; attendances: number }[];
    byModality: Count[];
    referrals: { internal: number; external: number; byStatus: Count[] };
    requests: { total: number; byReason: Count[]; byOrigin: Count[] };
};

export type ReportFilters = {
    de: string;
    ate: string;
    tipo: string;
    profissional: string;
    curso: string;
    modalidade: string;
};
