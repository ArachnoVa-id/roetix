import {
    EventColorProps,
    EventLogoProps,
    EventMaintenanceProps,
    EventPasswordProps,
    EventProps,
} from './front-end';

export const deconstructEventColorProps = (
    eventProps: EventProps,
): EventColorProps => {
    return {
        primary_color: eventProps.primary_color,
        secondary_color: eventProps.secondary_color,
        text_primary_color: eventProps.text_primary_color,
        text_secondary_color: eventProps.text_secondary_color,
    };
};

export const deconstructEventMaintenanceProps = (
    eventProps: EventProps,
): EventMaintenanceProps => {
    return {
        is_mainenance: eventProps.is_mainenance,
        maintenance_expected_finish: new Date(
            eventProps.maintenance_expected_finish,
        ),
        maintenance_title: eventProps.maintenance_title,
        maintenance_message: eventProps.maintenance_message,
    };
};

export const deconstructEventPasswordProps = (
    eventProps: EventProps,
): EventPasswordProps => {
    return {
        is_locked: eventProps.is_locked,
        locked_password: eventProps.locked_password,
    };
};

export const deconstructEventLogoProps = (
    eventProps: EventProps,
): EventLogoProps => {
    return {
        logo: eventProps.logo,
        logo_alt: eventProps.logo_alt,
        favicon: eventProps.favicon,
        texture: eventProps.texture,
    };
};
