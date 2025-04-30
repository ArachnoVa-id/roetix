import React from 'react';

interface SaveButtonProps {
    onClick: () => void;
    isDisabled: boolean;
    hasChanges: boolean;
    label?: string;
}

export const SaveButton: React.FC<SaveButtonProps> = ({
    onClick,
    isDisabled,
    hasChanges,
    label = 'Save Layout',
}) => {
    return (
        <button
            onClick={onClick}
            className="flex w-full items-center justify-center gap-2 rounded-lg bg-gradient-to-r from-blue-600 to-blue-700 px-4 py-3 font-medium text-white shadow-sm transition-all hover:from-blue-700 hover:to-blue-800 hover:shadow-md disabled:cursor-not-allowed disabled:opacity-50"
            disabled={isDisabled || !hasChanges}
        >
            <svg
                xmlns="http://www.w3.org/2000/svg"
                width="18"
                height="18"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                strokeWidth="2"
                strokeLinecap="round"
                strokeLinejoin="round"
            >
                <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                <polyline points="17 21 17 13 7 13 7 21"></polyline>
                <polyline points="7 3 7 8 15 8"></polyline>
            </svg>
            {isDisabled ? 'Saving...' : hasChanges ? label : 'No Changes'}
        </button>
    );
};
