import { Button } from '@/Components/ui/button';
import React from 'react';

interface DimensionControlProps {
    label: string;
    value: number;
    onIncrease: () => void;
    onDecrease: () => void;
    // minValue?: number;
}

export const DimensionControl: React.FC<DimensionControlProps> = ({
    label,
    value,
    onIncrease,
    onDecrease,
    //minValue = 0,
}) => {
    return (
        <div className="rounded-lg bg-blue-50 p-3">
            <label className="mb-2 block text-sm font-medium text-gray-700">
                {label}
            </label>
            <div className="flex items-center gap-2">
                <Button
                    variant="outline"
                    onClick={onDecrease}
                    className="h-8 w-8 rounded-md border-gray-300 bg-white p-0 shadow-sm transition-colors hover:bg-gray-50"
                >
                    -
                </Button>
                <span className="flex w-12 items-center justify-center rounded-md bg-white py-1 text-center font-medium shadow-sm">
                    {value}
                </span>
                <Button
                    variant="outline"
                    onClick={onIncrease}
                    className="h-8 w-8 rounded-md border-gray-300 bg-white p-0 shadow-sm transition-colors hover:bg-gray-50"
                >
                    +
                </Button>
            </div>
        </div>
    );
};
