export interface User {
    id: number;
    first_name: string;
    last_name: string;
    email: string;
    email_verified_at?: string;
    contact_info: ContactInfo;
}

export interface ContactInfo {
    phone_number?: string;
    whatsapp_number?: string;
    instagram?: string;
}

export type PageProps<
    T extends Record<string, unknown> = Record<string, unknown>,
> = T & {
    auth: {
        user: User;
    };
};
