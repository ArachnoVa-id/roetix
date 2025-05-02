import React from 'react';

interface NotificationToastProps {
    message: string;
    visible: boolean;
}

export const NotificationToast: React.FC<NotificationToastProps> = ({
    message,
    visible,
}) => {
    if (!visible) return null;

    return (
        <div className="fixed bottom-4 right-4 z-50 flex items-center gap-2 rounded-md bg-green-100 px-4 py-2 text-green-800 shadow-lg">
            <svg
                xmlns="http://www.w3.org/2000/svg"
                width="20"
                height="20"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                strokeWidth="2"
                strokeLinecap="round"
                strokeLinejoin="round"
            >
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                <polyline points="22 4 12 14.01 9 11.01"></polyline>
            </svg>
            {message}
        </div>
    );
};
