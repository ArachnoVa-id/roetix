import React, { ReactNode } from 'react';

interface CardProps {
    title: string;
    icon: ReactNode;
    children: ReactNode;
    className?: string;
    headerClassName?: string;
}

export const Card: React.FC<CardProps> = ({
    title,
    icon,
    children,
    className = '',
    headerClassName = '',
}) => {
    return (
        <div
            className={`mb-6 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm ${className}`}
        >
            <div
                className={`border-b border-gray-200 bg-gray-50 p-3 ${headerClassName}`}
            >
                <h3 className="flex items-center gap-2 font-medium text-gray-700">
                    {icon}
                    {title}
                </h3>
            </div>
            <div className="p-3">{children}</div>
        </div>
    );
};
