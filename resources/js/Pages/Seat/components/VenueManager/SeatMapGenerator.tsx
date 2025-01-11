import axios from 'axios';
import React, { useState } from 'react';
import { generateSeatMap } from '../../utils/seatMapGenerator';

interface SeatMapGeneratorProps {
    venueId: string;
    onGenerate: () => void;
}

export const SeatMapGenerator: React.FC<SeatMapGeneratorProps> = ({
    venueId,
    onGenerate,
}) => {
    const [config, setConfig] = useState<SeatGeneratorConfig>({
        venue_id: venueId,
        rows: ['A', 'B', 'C'],
        seatsPerRow: 20,
        categoryMapping: {
            default: {
                startRow: 'A',
                endRow: 'C',
                sections: [{ start: 1, end: 20, category: 'Regular' }],
            },
        },
    });

    const [generating, setGenerating] = useState(false);

    const handleGenerate = async () => {
        try {
            setGenerating(true);
            const generatedSeats = generateSeatMap(config);

            // Send generated seats to backend
            await axios.post(`/api/venues/${venueId}/seats/bulk-create`, {
                seats: generatedSeats,
            });

            onGenerate();
        } catch (error) {
            console.error('Error generating seat map:', error);
        } finally {
            setGenerating(false);
        }
    };

    const addRow = () => {
        const lastRow = config.rows[config.rows.length - 1];
        const nextRow = String.fromCharCode(lastRow.charCodeAt(0) + 1);
        setConfig((prev) => ({
            ...prev,
            rows: [...prev.rows, nextRow],
        }));
    };

    const removeRow = () => {
        setConfig((prev) => ({
            ...prev,
            rows: prev.rows.slice(0, -1),
        }));
    };

    return (
        <div className="rounded-lg bg-white p-6 shadow-lg">
            <h3 className="mb-4 text-xl font-semibold">Generate Seat Map</h3>

            <div className="space-y-4">
                <div>
                    <label className="block text-sm font-medium">Rows</label>
                    <div className="mt-1 flex items-center gap-2">
                        <div className="flex gap-1">
                            {config.rows.map((row) => (
                                <span
                                    key={row}
                                    className="rounded bg-gray-100 px-2 py-1"
                                >
                                    {row}
                                </span>
                            ))}
                        </div>
                        <button
                            onClick={addRow}
                            className="rounded bg-blue-500 px-2 py-1 text-sm text-white"
                        >
                            +
                        </button>
                        <button
                            onClick={removeRow}
                            className="rounded bg-red-500 px-2 py-1 text-sm text-white"
                        >
                            -
                        </button>
                    </div>
                </div>

                <div>
                    <label className="block text-sm font-medium">
                        Seats per Row
                    </label>
                    <input
                        type="number"
                        value={config.seatsPerRow}
                        onChange={(e) =>
                            setConfig((prev) => ({
                                ...prev,
                                seatsPerRow: parseInt(e.target.value),
                            }))
                        }
                        className="mt-1 w-24 rounded border px-3 py-2"
                    />
                </div>

                <button
                    onClick={handleGenerate}
                    disabled={generating}
                    className={`rounded-lg px-4 py-2 text-white ${generating ? 'bg-gray-400' : 'bg-blue-500 hover:bg-blue-600'} `}
                >
                    {generating ? 'Generating...' : 'Generate Seat Map'}
                </button>
            </div>
        </div>
    );
};
