import { deconstructEventColorProps } from '@/types/deconstruct-front-end';
import { EventColorProps, EventProps } from '@/types/front-end';
import { InertiaLinkProps, Link } from '@inertiajs/react';
import { useEffect, useState, type ReactNode } from 'react';
import styled from 'styled-components';

interface ResponsiveNavLinkProps extends InertiaLinkProps {
    active?: boolean;
    eventProps: EventProps;
    children?: ReactNode; // Explicitly declare children
    className?: string; // Explicitly declare className
}

interface NavLinkEventProps extends EventColorProps {
    active: boolean;
}

const StyledLink = styled(Link)<{ $props: NavLinkEventProps }>`
    ${({ $props }) => `
    border-color:
        ${$props.active ? $props.text_primary_color : 'transparent'};
    color:
        ${$props.active ? $props.text_primary_color : $props.text_secondary_color};
    &:hover {
        border-color:  ${$props.text_primary_color};
        color: ${$props.text_primary_color};
    }
    &:focus {
        border-color: ${$props.text_primary_color};
        color: ${$props.text_primary_color};
    }
    `}
`;

export default function ResponsiveNavLink({
    eventProps,
    children,
    active = false,
    className = '',
    ...props
}: ResponsiveNavLinkProps) {
    const [navLinkProps, setNavLinkProps] = useState<NavLinkEventProps>(
        {} as NavLinkEventProps,
    );

    useEffect(() => {
        if (eventProps) {
            const eventColorProps = deconstructEventColorProps(eventProps);
            setNavLinkProps({ active, ...eventColorProps });
        }
    }, [eventProps, active]);

    return (
        <StyledLink
            {...props}
            $props={navLinkProps}
            className={`flex w-full items-start border-l-4 py-2 pe-4 ps-3 text-base font-medium transition duration-150 ease-in-out focus:outline-none ${className}`}
        >
            {children}
        </StyledLink>
    );
}
