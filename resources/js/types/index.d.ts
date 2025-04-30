export interface User {
    id: number;
    first_name: string;
    last_name: string;
    email: string;
    role: string;
    email_verified_at?: string;
    contact_info: ContactInfo;
}

export interface ContactInfo {
    nickname?: string;
    fullname?: string;
    avatar?: string;
    phone_number?: string;
    email?: string;
    whatsapp_number?: string;
    instagram?: string;
    birth_date?: string;
    gender?: string;
    address?: string;
}

export type PageProps<
    T extends Record<string, unknown> = Record<string, unknown>,
> = T & {
    auth: {
        user: User;
    };
};
