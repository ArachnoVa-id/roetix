export interface EventTicketLimitProps {
    ticket_limit: number;
}

export interface EventColorProps {
    primary_color: string;
    secondary_color: string;
    text_primary_color: string;
    text_secondary_color: string;
}

export interface EventMaintenanceProps {
    is_mainenance: boolean; // Typo: should be is_maintenance
    maintenance_expected_finish: Date | string; // Date might be string from JSON
    maintenance_title: string;
    maintenance_message: string;
}

export interface EventPasswordProps {
    is_locked: boolean;
    locked_password: string; // Note: Sending passwords to frontend is a security risk
}

export interface EventLogoProps {
    logo: string;
    logo_alt: string;
    favicon: string;
    texture: string;
}

export interface EventMiscProps {
    contact_person: string;
}

export interface EventProps
    extends EventTicketLimitProps,
        EventColorProps,
        EventMaintenanceProps,
        EventPasswordProps,
        EventLogoProps,
        EventMiscProps {}

export interface Event {
    name: string;
    slug: string;
    category: string;
    start_date: Date;
    end_date: Date;
    location: string;
    status: string;
}

// This is your detailed Event model, likely used on other pages
export interface FullEvent {
    // Renamed to avoid conflict with global Event if any
    name: string;
    slug: string;
    category: string;
    start_date: Date | string; // Date might be string from JSON
    end_date: Date | string; // Date might be string from JSON
    location: string;
    status: string;
}

// Contextual Event object passed to specific pages like ScanTicketPage
export interface EventContext {
    id: number;
    name: string;
    slug: string;
}

// export interface UserType {
//     id: number | string;
//     first_name: string;
//     last_name: string;
//     email: string;
//     role: string;
//     contact_info?: {
//         avatar?: string;
//     };
// }

// export interface AuthProps {
//     user: UserType;
// }

export type HttpVerb =
    | 'GET'
    | 'POST'
    | 'PUT'
    | 'PATCH'
    | 'DELETE'
    | 'OPTIONS'
    | 'HEAD';

// Basic Ziggy config structure. You can make this more specific.
// The actual `Ziggy` object from `php artisan ziggy:generate` is more complex.
export interface ZiggyConfig {
    url: string;
    port: number | null;
    // Perbaiki di sini: defaults tidak boleh null atau undefined menurut RawParameterValue dari ziggy-js
    defaults: Record<string, string | number | boolean>;
    routes: Record<string, ZiggyRoute>;
    location: string | URL;
    version?: string;
}

export interface ZiggyRoute {
    uri: string;
    methods: HttpVerb[];
    domain?: string | null;
    bindings?: Record<string, string>;
    wheres?: Record<string, string | number>;
    middleware?: string[];
    name?: string;
}

// API response types
export interface ApiSuccessResponse {
    message: string;
}

export interface ApiErrorResponse {
    message: string;
    errors?: Record<string, string[]>;
}

// // Global Inertia PageProps
// declare module '@inertiajs/core' {
//     interface PageProps {
//         auth: AuthProps;
//         props: EventProps;
//         client: string;
//         event: EventContext;
//         appName: string;
//         userEndSessionDatetime?: string;
//         ziggy: ZiggyConfig;
//         errors: Record<string, string>;
//         [key: string]: unknown;
//     }
// }
