import { Button } from '@/Components/ui/button';
import React, { ReactNode } from 'react';

interface EditorButtonProps {
    children: ReactNode;
    onClick: () => void;
    isActive?: boolean;
    icon?: ReactNode;
    variant?: string;
    className?: string;
    // [x: string]: any; // For additional props
}

export const EditorButton: React.FC<EditorButtonProps> = ({
    children,
    onClick,
    isActive = false,
    icon = null,
    variant = 'outline',
    className = '',
    ...props
}) => {
    const baseClass =
        'flex items-center justify-start gap-2 py-2 pl-3 pr-2 text-left transition-all';

    let activeClass = '';
    if (isActive) {
        activeClass =
            'border-blue-500 bg-blue-50 text-blue-700 hover:bg-blue-100';
    } else {
        activeClass = 'border-gray-200 bg-white hover:bg-gray-50';
    }

    return (
        <Button
            variant={variant}
            onClick={onClick}
            className={`${baseClass} ${activeClass} ${className}`}
            {...props}
        >
            {icon && (
                <div
                    className={`flex h-7 w-7 items-center justify-center rounded-full ${isActive ? 'bg-blue-600' : 'bg-gray-100'}`}
                >
                    {React.cloneElement(icon as React.ReactElement, {
                        size: 14,
                        className: isActive ? 'text-white' : 'text-gray-500',
                    })}
                </div>
            )}
            <span>{children}</span>
        </Button>
    );
};
