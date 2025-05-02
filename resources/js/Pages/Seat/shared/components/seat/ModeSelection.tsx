import { MousePointer, Square, Trash2 } from 'lucide-react';
import React, { ReactNode } from 'react';
import { Card } from '../Card';

type ModeType = string;

interface ModeInfo {
    icon: ReactNode;
    label: string;
    description: string;
    activeClass: string;
}

interface ModeSelectionProps {
    mode: string;
    onModeChange: (mode: ModeType) => void;
    modes: ModeType[];
}

export const ModeSelection: React.FC<ModeSelectionProps> = ({
    mode,
    onModeChange,
    modes,
}) => {
    const getModeInfo = (modeType: ModeType): ModeInfo => {
        switch (modeType) {
            case 'add':
                return {
                    icon: <MousePointer size={16} />,
                    label: 'Add Seats',
                    description: 'Click on empty cells to add new seats',
                    activeClass:
                        'border-blue-500 bg-blue-50 text-blue-700 hover:bg-blue-100',
                };
            case 'delete':
                return {
                    icon: <Trash2 size={16} />,
                    label: 'Delete Seats',
                    description:
                        'Click on seats to remove them from the layout',
                    activeClass:
                        'border-red-500 bg-red-50 text-red-700 hover:bg-red-100',
                };
            case 'block':
            case 'DRAG':
                return {
                    icon: <Square size={16} />,
                    label: 'Block Area',
                    description:
                        'Click and drag to select and block/unblock multiple cells at once',
                    activeClass:
                        'border-purple-500 bg-purple-50 text-purple-700 hover:bg-purple-100',
                };
            case 'SINGLE':
                return {
                    icon: <MousePointer size={16} />,
                    label: 'Single',
                    description: 'Click on individual seats to select them',
                    activeClass:
                        'border-blue-500 bg-blue-50 text-blue-700 hover:bg-blue-100',
                };
            case 'MULTIPLE':
                return {
                    icon: (
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
                            <rect x="3" y="3" width="7" height="7"></rect>
                            <rect x="14" y="3" width="7" height="7"></rect>
                            <rect x="14" y="14" width="7" height="7"></rect>
                            <rect x="3" y="14" width="7" height="7"></rect>
                        </svg>
                    ),
                    label: 'Multiple',
                    description: 'Click on multiple seats to select them',
                    activeClass:
                        'border-green-500 bg-green-50 text-green-700 hover:bg-green-100',
                };
            case 'CATEGORY':
                return {
                    icon: (
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
                    ),
                    label: 'Category',
                    description: 'Select all seats of the same category',
                    activeClass:
                        'border-indigo-500 bg-indigo-50 text-indigo-700 hover:bg-indigo-100',
                };
            default:
                return {
                    icon: <MousePointer size={16} />,
                    label: modeType,
                    description: '',
                    activeClass: '',
                };
        }
    };

    return (
        <Card
            title="Mode Editor"
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
                    <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path>
                </svg>
            }
        >
            <div className="mb-3 grid grid-cols-1 gap-2">
                {modes.map((modeType) => {
                    const modeInfo = getModeInfo(modeType);
                    const isActive = mode === modeType;

                    return (
                        <button
                            key={modeType}
                            onClick={() => onModeChange(modeType)}
                            className={`flex items-center justify-start gap-2 rounded border py-2 pl-3 pr-2 text-left transition-all ${
                                isActive
                                    ? modeInfo.activeClass
                                    : 'border-gray-200 bg-white hover:bg-gray-50'
                            }`}
                        >
                            <div
                                className={`flex h-7 w-7 items-center justify-center rounded-full ${
                                    isActive ? 'bg-blue-600' : 'bg-gray-100'
                                }`}
                            >
                                {React.cloneElement(
                                    modeInfo.icon as React.ReactElement,
                                    {
                                        size: 14,
                                        className: isActive
                                            ? 'text-white'
                                            : 'text-gray-500',
                                    },
                                )}
                            </div>
                            <span>{modeInfo.label}</span>
                        </button>
                    );
                })}
            </div>

            {/* Mode description */}
            <div className="rounded-lg border border-gray-200 bg-gray-50 p-3 text-sm text-gray-700">
                {mode && (
                    <div className="flex items-start gap-2">
                        {React.cloneElement(
                            getModeInfo(mode).icon as React.ReactElement,
                            {
                                size: 14,
                                className: 'mt-0.5 shrink-0 text-blue-500',
                            },
                        )}
                        <div>
                            <span className="font-medium">
                                {getModeInfo(mode).description}
                            </span>
                        </div>
                    </div>
                )}
            </div>
        </Card>
    );
};
