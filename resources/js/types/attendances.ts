export type Option = { value: string; label: string };
export type NumberOption = { value: number; label: string };

export type StudentOption = {
    id: number;
    name: string;
    registration: string;
    course: string;
    period: number;
};

export type AttendanceSummary = {
    id: number;
    student: { id: number; name: string; course: string; period: number };
    professional: string;
    professional_id: number;
    type: string;
    type_label: string;
    status: string;
    status_label: string;
    modality_label: string;
    is_first_conversation: boolean;
    scheduled_at: string;
    date: string;
    time: string;
    planned_minutes: number;
    duration_minutes: number | null;
};

export type AgendaEvent = AttendanceSummary & {
    day: number;
    start: number;
    hours: number;
};

export type Week = {
    label: string;
    previous: string;
    next: string;
    current: string;
    isCurrent: boolean;
    days: { label: string; isToday: boolean }[];
    events: AgendaEvent[];
};

export type QueueItem = {
    id: number;
    student: string;
    reasons: string[];
    origin: string;
    priority: 'normal' | 'high';
    date: string | null;
};

export type ReferralSummary = {
    id: number;
    student: { id: number; name: string; course: string; period: number };
    kind: 'internal' | 'external';
    kind_label: string;
    origin: string;
    destination: string;
    status: string;
    status_label: string;
    referred_on: string;
    referred_on_iso: string;
    return_deadline: string | null;
    overdue: boolean;
    attendance_id: number | null;
};
