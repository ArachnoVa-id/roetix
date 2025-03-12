export interface EventColorProps {
    primary_color: string;
    secondary_color: string;
    text_primary_color: string;
    text_secondary_color: string;
}

export interface EventMaintenanceProps {
    is_mainenance: boolean;
    maintenance_expected_finish: Date;
    maintenance_title: string;
    maintenance_message: string;
}

export interface EventPasswordProps {
    is_locked: boolean;
    locked_password: string;
}

export interface EventLogoProps {
    logo: string;
    logo_alt: string;
    favicon: string;
}

export interface EventProps
    extends EventColorProps,
        EventMaintenanceProps,
        EventPasswordProps,
        EventLogoProps {}

export interface Event {
    name: string;
    slug: string;
    category: string;
    start_date: Date;
    end_date: Date;
    location: string;
    status: string;
}
