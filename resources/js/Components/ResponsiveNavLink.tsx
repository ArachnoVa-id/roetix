import { deconstructEventColorProps } from '@/types/deconstruct-front-end';
import { EventColorProps, EventProps } from '@/types/front-end';
import { InertiaLinkProps, Link } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import styled from 'styled-components';

interface ResponsiveNavLinkProps extends InertiaLinkProps {
    active: boolean;
    eventProps: EventProps;
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
    active = false,
    className = '',
    children,
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
            //     ${
            //     active
            //         ? 'border-indigo-400 bg-indigo-50 text-indigo-700 focus:border-indigo-700 focus:bg-indigo-100 focus:text-indigo-800'
            //         : 'border-transparent text-gray-600 hover:border-gray-300 hover:bg-gray-50 hover:text-gray-800 focus:border-gray-300 focus:bg-gray-50 focus:text-gray-800'
            // }
        >
            {children}
        </StyledLink>
    );
}
