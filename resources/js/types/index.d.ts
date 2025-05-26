import { EventProps } from '@/types/front-end'; // Assuming this path is correct
import { PageProps as InertiaPageProps } from '@inertiajs/core';
import { Config } from 'ziggy-js';

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
    avatar?: string | null; // Accepts string or null
    phone_number?: string;
    email?: string;
    whatsapp_number?: string;
    instagram?: string;
    birth_date?: string;
    gender?: string;
    address?: string;
}

export interface EventContext {
    id: number;
    name: string;
    slug: string;
    location: string;
    // Add other event properties if passed from Laravel
}

declare module '@inertiajs/core' {
    interface PageProps extends InertiaPageProps {
        appName: string;
        auth: {
            user: User;
        };
        event?: EventContext; // Optional event context
        props: EventProps; // Your event-specific styling/logo props
        client: string; // The client subdomain
        userEndSessionDatetime?: string; // Optional countdown
        ziggy: Config; // Assuming Ziggy is used
        // Add any other top-level props you pass from Laravel
    }
}

declare global {
    interface Window {
        Laravel: {
            csrfToken: string;
        };
        route: (name: string, params?: Record<string, unknown>) => string;
    }
}
