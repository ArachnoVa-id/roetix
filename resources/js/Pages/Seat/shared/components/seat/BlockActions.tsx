import { Button } from '@/Components/ui/button';
import { Plus, Trash2 } from 'lucide-react';
import React from 'react';

interface BlockActionsProps {
    onAddSeats: () => void;
    onDeleteSeats: () => void;
    onClearSelection: () => void;
    blockedAreasCount: number;
}

export const BlockActions: React.FC<BlockActionsProps> = ({
    onAddSeats,
    onDeleteSeats,
    onClearSelection,
    blockedAreasCount,
}) => {
    return (
        <div className="mb-6 overflow-hidden rounded-xl border border-blue-300 bg-gradient-to-r from-blue-50 to-indigo-50 shadow-sm">
            <div className="border-b border-blue-200 bg-blue-100/50 p-3">
                <div className="flex items-center gap-2 font-medium text-blue-800">
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        width="16"
                        height="16"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        strokeWidth="2"
                        strokeLinecap="round"
                        strokeLinejoin="round"
                    >
                        <path d="M3 3h18v18H3z"></path>
                    </svg>
                    {blockedAreasCount > 1
                        ? `${blockedAreasCount} Areas Selected`
                        : 'Area Selected'}
                </div>
            </div>
            <div className="p-3">
                <div className="grid grid-cols-1 gap-2">
                    <Button
                        variant="outline"
                        onClick={onAddSeats}
                        className="flex items-center justify-center gap-2 border-green-500 bg-white py-2 text-green-600 shadow-sm transition-colors hover:bg-green-50"
                    >
                        <Plus size={16} />
                        <span>Add Seats</span>
                    </Button>
                    <Button
                        variant="outline"
                        onClick={onDeleteSeats}
                        className="flex items-center justify-center gap-2 border-red-500 bg-white py-2 text-red-600 shadow-sm transition-colors hover:bg-red-50"
                    >
                        <Trash2 size={16} />
                        <span>Delete Seats</span>
                    </Button>
                    <Button
                        variant="outline"
                        onClick={onClearSelection}
                        className="border-gray-300 bg-white py-2 shadow-sm transition-colors hover:bg-gray-50"
                    >
                        Clear Selection
                    </Button>
                </div>
            </div>
        </div>
    );
};
