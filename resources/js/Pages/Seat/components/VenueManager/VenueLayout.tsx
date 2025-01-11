import React, { useState } from 'react';
import { SeatEditor } from './SeatEditor';

interface VenueLayoutProps {
    venueId: string;
    eventId: string;
}

export const VenueLayout: React.FC<VenueLayoutProps> = ({
    venueId,
    eventId,
}) => {
    const [showGrid, setShowGrid] = useState(true);
    const [gridSize, setGridSize] = useState({ rows: 10, cols: 20 });

    const handleGridSizeChange = (rows: number, cols: number) => {
        setGridSize({ rows, cols });
    };

    return (
        <div className="p-6">
            <div className="mb-6 flex items-center justify-between">
                <h2 className="text-2xl font-bold">Venue Layout Editor</h2>
                <div className="flex gap-4">
                    <button
                        className={`rounded-lg px-4 py-2 ${
                            showGrid ? 'bg-blue-500 text-white' : 'bg-gray-200'
                        }`}
                        onClick={() => setShowGrid(!showGrid)}
                    >
                        {showGrid ? 'Hide Grid' : 'Show Grid'}
                    </button>
                    <div className="flex items-center gap-2">
                        <label className="text-sm">Rows:</label>
                        <input
                            type="number"
                            value={gridSize.rows}
                            onChange={(e) =>
                                handleGridSizeChange(
                                    parseInt(e.target.value),
                                    gridSize.cols,
                                )
                            }
                            className="w-16 rounded border px-2 py-1"
                        />
                        <label className="text-sm">Columns:</label>
                        <input
                            type="number"
                            value={gridSize.cols}
                            onChange={(e) =>
                                handleGridSizeChange(
                                    gridSize.rows,
                                    parseInt(e.target.value),
                                )
                            }
                            className="w-16 rounded border px-2 py-1"
                        />
                    </div>
                </div>
            </div>

            <div className={`relative ${showGrid ? 'bg-gray-100' : ''}`}>
                {showGrid && (
                    <div
                        className="absolute inset-0 grid"
                        style={{
                            gridTemplateRows: `repeat(${gridSize.rows}, 1fr)`,
                            gridTemplateColumns: `repeat(${gridSize.cols}, 1fr)`,
                            pointerEvents: 'none',
                        }}
                    >
                        {Array.from({
                            length: gridSize.rows * gridSize.cols,
                        }).map((_, i) => (
                            <div key={i} className="border border-gray-200" />
                        ))}
                    </div>
                )}
                <SeatEditor venueId={venueId} eventId={eventId} />
            </div>
        </div>
    );
};
