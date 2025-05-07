import React from 'react';
import { GridDimensions } from '../../utils/gridHelpers';
import { Card } from '../Card';
import { DimensionControl } from '../DimensionControl';

interface DimensionsPanelProps {
    dimensions: GridDimensions;
    setDimensions: React.Dispatch<React.SetStateAction<GridDimensions>>;
}

export const DimensionsPanel: React.FC<DimensionsPanelProps> = ({
    dimensions,
    setDimensions,
}) => {
    return (
        <Card
            title="Dimensi Layout"
            icon={
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
                    <rect
                        width="18"
                        height="18"
                        x="3"
                        y="3"
                        rx="2"
                        ry="2"
                    ></rect>
                    <line x1="3" x2="21" y1="15" y2="15"></line>
                    <line x1="3" x2="21" y1="9" y2="9"></line>
                    <line x1="9" x2="9" y1="21" y2="3"></line>
                    <line x1="15" x2="15" y1="21" y2="3"></line>
                </svg>
            }
        >
            <div className="max-md:grid max-md:grid-cols-2 max-md:gap-2 md:space-y-5">
                <DimensionControl
                    label="Bottom Rows"
                    value={dimensions.top}
                    onIncrease={() =>
                        setDimensions((d) => ({ ...d, top: d.top + 1 }))
                    }
                    onDecrease={() =>
                        setDimensions((d) => ({
                            ...d,
                            top: Math.max(0, d.top - 1),
                        }))
                    }
                />
                <DimensionControl
                    label="Top Rows"
                    value={dimensions.bottom}
                    onIncrease={() =>
                        setDimensions((d) => ({ ...d, bottom: d.bottom + 1 }))
                    }
                    onDecrease={() =>
                        setDimensions((d) => ({
                            ...d,
                            bottom: Math.max(1, d.bottom - 1),
                        }))
                    }
                />
                <DimensionControl
                    label="Left Columns"
                    value={dimensions.left}
                    onIncrease={() =>
                        setDimensions((d) => ({ ...d, left: d.left + 1 }))
                    }
                    onDecrease={() =>
                        setDimensions((d) => ({
                            ...d,
                            left: Math.max(0, d.left - 1),
                        }))
                    }
                />
                <DimensionControl
                    label="Right Columns"
                    value={dimensions.right}
                    onIncrease={() =>
                        setDimensions((d) => ({ ...d, right: d.right + 1 }))
                    }
                    onDecrease={() =>
                        setDimensions((d) => ({
                            ...d,
                            right: Math.max(1, d.right - 1),
                        }))
                    }
                />
            </div>
        </Card>
    );
};
