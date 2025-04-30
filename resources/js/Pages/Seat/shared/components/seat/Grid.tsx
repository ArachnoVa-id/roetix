import React, { MouseEvent, ReactNode, RefObject } from 'react';
import { Stage } from '../Layout';

interface SelectionBox {
    left: number;
    top: number;
    width: number;
    height: number;
}

interface GridProps {
    children: ReactNode;
    onMouseMove?: (event: MouseEvent<HTMLDivElement>) => void;
    onMouseUp?: () => void;
    onMouseLeave?: () => void;
    selectionBox?: SelectionBox | null;
    isDragging?: boolean;
    className?: string;
    gridRef?: RefObject<HTMLDivElement>;
}

export const Grid: React.FC<GridProps> = ({
    children,
    onMouseMove,
    onMouseUp,
    onMouseLeave,
    selectionBox = null,
    isDragging = false,
    className = '',
    gridRef,
}) => {
    const defaultGridRef = React.useRef<HTMLDivElement>(null);
    const usedGridRef = gridRef || defaultGridRef;

    return (
        <div className="flex h-full items-center justify-center">
            <div className="h-full w-full p-4">
                <div
                    className={`relative h-full w-full rounded-3xl border-2 border-dashed border-gray-300 bg-white p-4 ${className}`}
                    onMouseUp={onMouseUp}
                    onMouseLeave={onMouseLeave}
                >
                    <div
                        className="h-full overflow-auto"
                        ref={usedGridRef}
                        onMouseMove={onMouseMove}
                    >
                        <div className="min-w-fit p-4">
                            {/* Grid content */}
                            <div className="flex h-full items-center justify-center">
                                <div className="grid grid-flow-row gap-1">
                                    {/* Visual selection box overlay */}
                                    {isDragging && selectionBox && (
                                        <div
                                            className="pointer-events-none absolute z-10 border-2 border-blue-500 bg-blue-100 bg-opacity-20"
                                            style={{
                                                left: selectionBox.left + 'px',
                                                top: selectionBox.top + 'px',
                                                width:
                                                    selectionBox.width + 'px',
                                                height:
                                                    selectionBox.height + 'px',
                                            }}
                                        />
                                    )}

                                    {children}
                                </div>
                            </div>

                            {/* Stage */}
                            <Stage />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
};
