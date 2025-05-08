import { Button } from '@/Components/ui/button';
import { Grid, Plus, Trash2, X } from 'lucide-react';
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
        <div className="mb-6 overflow-hidden rounded-xl border border-blue-400 bg-gradient-to-r from-blue-100 to-indigo-100 shadow-md">
            <div className="border-b border-blue-300 bg-blue-200/80 p-3">
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-2 font-medium text-blue-800">
                        <Grid size={18} className="text-blue-700" />
                        <span className="text-lg">
                            {blockedAreasCount > 1
                                ? `${blockedAreasCount} Areas Selected`
                                : 'Area Selected'}
                        </span>
                    </div>
                    <Button
                        variant="ghost"
                        size="sm"
                        onClick={onClearSelection}
                        className="h-8 w-8 rounded-full p-0 text-blue-700 hover:bg-blue-300/50 hover:text-blue-900"
                    >
                        <X size={16} />
                    </Button>
                </div>
            </div>
            <div className="p-4">
                <div className="grid grid-cols-1 gap-3">
                    <Button
                        variant="outline"
                        onClick={onAddSeats}
                        className="flex items-center justify-center gap-2 border-2 border-green-500 bg-white py-2.5 text-green-600 shadow-sm transition-colors hover:bg-green-50 hover:text-green-700 active:bg-green-100"
                    >
                        <Plus size={18} />
                        <span className="font-medium">Add Seats to Area</span>
                    </Button>
                    <Button
                        variant="outline"
                        onClick={onDeleteSeats}
                        className="flex items-center justify-center gap-2 border-2 border-red-500 bg-white py-2.5 text-red-600 shadow-sm transition-colors hover:bg-red-50 hover:text-red-700 active:bg-red-100"
                    >
                        <Trash2 size={18} />
                        <span className="font-medium">
                            Delete Seats from Area
                        </span>
                    </Button>
                </div>
            </div>
            <div className="bg-blue-50 px-4 py-3 text-sm text-blue-700">
                <p>Tip: Hold and drag to select multiple cells at once</p>
            </div>
        </div>
    );
};
