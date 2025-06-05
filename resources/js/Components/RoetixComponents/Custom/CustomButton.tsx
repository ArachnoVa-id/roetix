import { ReactNode } from 'react';
import { twMerge } from 'tailwind-merge';

interface CustomButtonProps {
    children: ReactNode;
    className?: string;
    onClick?: () => void;
    type?: 'button' | 'submit' | 'reset';
    href?: string;
    disabled?: boolean;
}

export default function CustomButton({
    children,
    className,
    onClick,
    type = 'button',
    href,
    disabled = false,
}: CustomButtonProps) {
    const baseClass =
        'flex items-center justify-center space-x-2 text-white rounded-lg font-semibold transition duration-200';

    const disabledClass = disabled
        ? 'bg-gray-400 cursor-not-allowed hover:bg-gray-400'
        : 'cursor-pointer';

    const mergedClass = twMerge(baseClass, disabledClass, className);

    if (href && !disabled) {
        return (
            <a
                href={href}
                className={mergedClass}
                target="_blank"
                rel="noreferrer"
            >
                {children}
            </a>
        );
    }

    return (
        <button
            type={type}
            className={mergedClass}
            onClick={onClick}
            disabled={disabled}
        >
            {children}
        </button>
    );
}
