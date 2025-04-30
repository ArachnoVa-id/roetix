import { Button } from '@/Components/ui/button';
import { Link } from '@inertiajs/react';
import React from 'react';

interface EmptyStateProps {
    title?: string;
    description?: string;
    actionLink?: string | null;
    actionText?: string;
}

export default function EmptyState({
    title = 'No data found',
    description = 'There are no items to display at this time.',
    actionLink = null,
    actionText = 'Go Back',
}: EmptyStateProps): React.ReactElement {
    return (
        <div className="flex flex-col items-center justify-center py-12 text-center">
            <svg
                className="mb-4 h-20 w-20 text-gray-400"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
                xmlns="http://www.w3.org/2000/svg"
            >
                <path
                    strokeLinecap="round"
                    strokeLinejoin="round"
                    strokeWidth={1.5}
                    d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M12 13h.01M12 18a6 6 0 100-12 6 6 0 000 12z"
                />
            </svg>
            <h3 className="mb-1 text-lg font-semibold">{title}</h3>
            <p className="mb-6 text-gray-500">{description}</p>

            {actionLink && (
                <Link href={actionLink}>
                    <Button>{actionText}</Button>
                </Link>
            )}
        </div>
    );
}
