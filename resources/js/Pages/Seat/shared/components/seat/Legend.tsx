import React from 'react';
import { Card } from '../Card';

interface StatusLegend {
    label: string;
    color: string;
}

interface LegendProps {
    ticketTypes: string[];
    categoryPrices?: Record<string, number>;
    statusLegends?: StatusLegend[];
    getColorForCategory: (category: string) => string;
}

export const Legend: React.FC<LegendProps> = ({
    ticketTypes,
    categoryPrices = {},
    statusLegends = [],
    getColorForCategory,
}) => {
    return (
        <Card
            title="Legend"
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
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="8" x2="12" y2="12"></line>
                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                </svg>
            }
        >
            <div className="grid grid-cols-1 gap-2">
                {/* Category legend */}
                <div className="overflow-hidden rounded-lg border border-gray-100 bg-gray-50">
                    <div className="border-b border-gray-100 px-3 py-2 text-center text-sm font-medium text-gray-600">
                        Category
                    </div>
                    <div className="flex flex-wrap items-center justify-center gap-6 p-4">
                        {[...ticketTypes]
                            .sort(
                                (a, b) =>
                                    (categoryPrices[b] || 0) -
                                    (categoryPrices[a] || 0),
                            )
                            .map((type) => (
                                <div
                                    key={type}
                                    className="flex flex-col items-center"
                                >
                                    <div
                                        className="h-8 w-8 rounded-full shadow-sm"
                                        style={{
                                            backgroundColor:
                                                getColorForCategory(type),
                                        }}
                                    ></div>
                                    <span className="mt-1 text-sm font-medium">
                                        {type.charAt(0).toUpperCase() +
                                            type.slice(1)}
                                    </span>
                                    <span className="text-xs text-gray-500">
                                        Rp{' '}
                                        {(
                                            categoryPrices[type] || 0
                                        ).toLocaleString()}
                                    </span>
                                </div>
                            ))}
                    </div>
                </div>

                {/* Status legend */}
                <div className="overflow-hidden rounded-lg border border-gray-100 bg-gray-50">
                    <div className="border-b border-gray-100 px-3 py-2 text-center text-sm font-medium text-gray-600">
                        Seat Status
                    </div>
                    <div className="flex flex-wrap items-center justify-center gap-6 p-4">
                        <div className="flex flex-col items-center">
                            <div className="h-8 w-8 rounded-full bg-green-500 shadow-sm"></div>
                            <span className="mt-1 text-sm font-medium">
                                Available
                            </span>
                        </div>
                        {statusLegends.map((legend, i) => (
                            <div key={i} className="flex flex-col items-center">
                                <div
                                    className={`h-8 w-8 ${legend.color} rounded-full shadow-sm`}
                                ></div>
                                <span className="mt-1 text-sm font-medium">
                                    {legend.label}
                                </span>
                            </div>
                        ))}
                    </div>
                </div>
            </div>
        </Card>
    );
};
