export interface EventProps {
    is_locked: boolean;
    is_mainenance: boolean;
    maintenance_expected_finish: Date;
    maintenance_title: string;
    maintenance_message: string;
    logo: string;
    logo_alt: string;
    favicon: string;
    primary_color: string;
    secondary_color: string;
    text_primary_color: string;
    text_secondary_color: string;
}
