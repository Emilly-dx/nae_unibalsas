import type { AttendanceSummary } from '@/types';

/** Cores por tipo de atendimento (blocos da agenda), claro e escuro. */
const blockStyles: Record<string, string> = {
    psychopedagogical:
        'border-l-rose-600 bg-rose-50 text-rose-800 hover:bg-rose-100 dark:bg-rose-950/60 dark:text-rose-200 dark:hover:bg-rose-950',
    psychological:
        'border-l-blue-600 bg-blue-50 text-blue-800 hover:bg-blue-100 dark:bg-blue-950/60 dark:text-blue-200 dark:hover:bg-blue-950',
    listening:
        'border-l-amber-600 bg-amber-50 text-amber-800 hover:bg-amber-100 dark:bg-amber-950/50 dark:text-amber-200 dark:hover:bg-amber-950',
};

const chipStyles: Record<string, string> = {
    psychopedagogical:
        'bg-rose-100 text-rose-700 dark:bg-rose-950 dark:text-rose-300',
    psychological:
        'bg-blue-100 text-blue-700 dark:bg-blue-950 dark:text-blue-300',
    listening:
        'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300',
};

const statusStyles: Record<string, string> = {
    scheduled: 'bg-blue-100 text-blue-700 dark:bg-blue-950 dark:text-blue-300',
    attended:
        'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300',
    completed: 'bg-muted text-muted-foreground',
    referred:
        'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300',
    cancelled: 'bg-muted text-muted-foreground line-through',
};

export const attendanceBlockClass = (attendance: AttendanceSummary): string =>
    attendance.is_first_conversation
        ? blockStyles.listening
        : (blockStyles[attendance.type] ?? blockStyles.listening);

export const attendanceChipClass = (attendance: AttendanceSummary): string =>
    attendance.is_first_conversation
        ? chipStyles.listening
        : (chipStyles[attendance.type] ?? chipStyles.listening);

export const attendanceStatusClass = (status: string): string =>
    statusStyles[status] ?? statusStyles.completed;

export const attendanceLabel = (attendance: AttendanceSummary): string =>
    attendance.is_first_conversation ? '1ª conversa' : attendance.type_label;

/** Data de hoje no fuso local, no formato YYYY-MM-DD (para inputs de data). */
export function todayValue(): string {
    const now = new Date();

    return `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')}`;
}

const referralStatusStyles: Record<string, string> = {
    sent: 'bg-rose-100 text-rose-700 dark:bg-rose-950 dark:text-rose-300',
    in_progress:
        'bg-blue-100 text-blue-700 dark:bg-blue-950 dark:text-blue-300',
    awaiting_return:
        'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300',
    completed: 'bg-muted text-muted-foreground',
    cancelled: 'bg-muted text-muted-foreground line-through',
};

export const referralStatusClass = (status: string): string =>
    referralStatusStyles[status] ?? referralStatusStyles.completed;
