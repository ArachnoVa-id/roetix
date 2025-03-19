import { AlertTriangle, CheckCircle, X } from 'lucide-react';
import React, { useEffect, useState } from 'react';

interface ToasterProps {
    message: string;
    type: 'success' | 'error';
    isVisible: boolean;
    onClose: () => void;
    duration?: number;
}

const Toaster: React.FC<ToasterProps> = ({
    message,
    type,
    isVisible,
    onClose,
    duration = 3000,
}) => {
    const [isClosing, setIsClosing] = useState(false);

    useEffect(() => {
        if (isVisible) {
            // Reset closing state when toaster becomes visible
            setIsClosing(false);

            // Auto-dismiss after duration
            const timer = setTimeout(() => {
                setIsClosing(true);
                setTimeout(onClose, 300); // Allow time for animation
            }, duration);

            return () => clearTimeout(timer);
        }
    }, [isVisible, duration, onClose]);

    if (!isVisible) return null;

    return (
        <div
            className={`fixed right-4 top-4 z-50 flex transition-all duration-300 ${isClosing ? 'translate-x-8 opacity-0' : 'opacity-100'}`}
        >
            <div
                className={`flex min-w-72 items-center rounded-md px-4 py-3 shadow-lg ${
                    type === 'success'
                        ? 'bg-black text-white'
                        : 'border border-red-200 bg-red-50 text-red-700'
                }`}
            >
                {type === 'success' ? (
                    <CheckCircle className="mr-3 h-5 w-5 text-green-400" />
                ) : (
                    <AlertTriangle className="mr-3 h-5 w-5 text-red-500" />
                )}

                <p className="flex-1 text-sm font-medium">{message}</p>

                <button
                    onClick={() => {
                        setIsClosing(true);
                        setTimeout(onClose, 300);
                    }}
                    className="ml-4 rounded-full p-1 hover:bg-black/10 focus:outline-none"
                    aria-label="Close notification"
                    title="Close"
                >
                    <X className="h-4 w-4" />
                </button>
            </div>
        </div>
    );
};

export default Toaster;
