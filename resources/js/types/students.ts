export type SexOption = {
    value: string;
    label: string;
};

export type StudentListItem = {
    id: number;
    name: string;
    registration: string;
    course: string;
    period: number;
    active: boolean;
};

export type Student = StudentListItem & {
    email: string | null;
    phone: string | null;
    birth_date: string | null;
    sex: string | null;
    sex_label: string | null;
    guardian_name: string | null;
    guardian_phone: string | null;
    guardian_relationship: string | null;
    created_at: string | null;
    updated_at: string | null;
};

export type StudentDisability = {
    id: number;
    cid_code: string;
    description: string | null;
    created_at: string | null;
};

export type StudentDocument = {
    id: number;
    title: string;
    original_name: string;
    mime_type: string;
    size: number;
    created_at: string | null;
};

export type Paginated<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    prev_page_url: string | null;
    next_page_url: string | null;
};
