import React from 'react';

export const Stage: React.FC = () => {
    return (
        <div className="mx-auto mt-8 flex h-12 w-64 items-center justify-center rounded-lg border border-gray-400 bg-gray-200 font-medium text-gray-700">
            <span className="flex items-center justify-center gap-2">
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
                    <rect x="4" y="5" width="16" height="14" rx="2" />
                </svg>
                Stage
            </span>
        </div>
    );
};
