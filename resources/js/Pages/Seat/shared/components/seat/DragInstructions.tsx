import React from 'react';

export const DragInstructions: React.FC = () => {
    return (
        <div className="mb-6 overflow-hidden rounded-xl border border-amber-100 bg-amber-50 shadow-sm">
            <div className="flex items-start gap-3 p-4">
                <div className="rounded-full bg-amber-100 p-2 text-amber-600">
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
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="8" x2="12" y2="12"></line>
                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                    </svg>
                </div>
                <div>
                    <p className="font-medium text-amber-800">
                        Drag Selection Mode
                    </p>
                    <p className="mt-1 text-sm text-amber-700">
                        Click and drag to select multiple seats at once. Hold{' '}
                        <kbd className="mx-1 rounded bg-amber-100 px-1.5 py-0.5 text-xs font-semibold">
                            Shift
                        </kbd>{' '}
                        to add to existing selection. Press{' '}
                        <kbd className="mx-1 rounded bg-amber-100 px-1.5 py-0.5 text-xs font-semibold">
                            Esc
                        </kbd>{' '}
                        to cancel.
                    </p>
                </div>
            </div>
        </div>
    );
};
