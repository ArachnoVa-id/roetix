import React from 'react';
import { Card } from '../Card';

interface CategorySelectionProps {
    categories: string[];
    selectedCategory: string | null;
    onSelectCategory: (category: string) => void;
    getCategoryColor: (category: string) => string;
}

export const CategorySelection: React.FC<CategorySelectionProps> = ({
    categories,
    selectedCategory,
    onSelectCategory,
    getCategoryColor,
}) => {
    return (
        <Card
            title="Select Ticket Category"
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
                    <path d="M4 20h16a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-7.93a2 2 0 0 1-1.66-.9l-.82-1.2A2 2 0 0 0 7.93 3H4a2 2 0 0 0-2 2v13c0 1.1.9 2 2 2Z"></path>
                </svg>
            }
        >
            <div className="flex flex-wrap gap-3 p-1">
                {categories.map((category) => (
                    <button
                        key={category}
                        className={`flex h-14 w-24 flex-col items-center justify-center rounded-lg border-2 p-1 transition-all hover:bg-gray-50 ${
                            selectedCategory === category
                                ? 'border-blue-500 ring-2 ring-blue-200'
                                : 'border-gray-200'
                        }`}
                        onClick={() => onSelectCategory(category)}
                        style={{
                            borderColor:
                                selectedCategory === category
                                    ? 'rgb(59, 130, 246)'
                                    : '#e5e7eb',
                        }}
                    >
                        <div
                            className="h-6 w-6 rounded-full"
                            style={{
                                backgroundColor: getCategoryColor(category),
                            }}
                        ></div>
                        <span className="mt-1 text-xs font-medium">
                            {category.charAt(0).toUpperCase() +
                                category.slice(1)}
                        </span>
                    </button>
                ))}
            </div>
        </Card>
    );
};
