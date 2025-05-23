// resources/js/types/inertia-custom-props.d.ts

import { EventProps } from '@/types/front-end';
import { PageProps as InertiaPageProps } from '@inertiajs/core';

// Define the User type as it comes from your auth prop
export interface User {
    id: number;
    first_name: string;
    last_name: string;
    email: string; // <--- ADD THIS LINE
    role: string;
    contact_info: { avatar: string | undefined };
    // Add any other user properties you expect from your backend user object
}

// Define the Event type as it comes from your event prop
export interface EventContext {
    id: number;
    name: string;
    // Add any other event properties passed to the layout
}

// Extend Inertia's default PageProps
export interface CustomInertiaPageProps extends InertiaPageProps {
    auth: {
        user: User;
    };
    event?: EventContext;
    props: EventProps;
    client: string;
    userEndSessionDatetime?: string;
}

// Declare global `route` function if not already done by Ziggy
declare global {
    interface Window {
        Laravel: {
            csrfToken: string;
        };
        // Change `any` to `unknown`
        route: (name: string, params?: Record<string, unknown>) => string;
    }
}
