import React, { ReactNode, RefObject } from 'react';

interface SidebarProps {
    icon: ReactNode;
    title: string;
    content: ReactNode;
    footer: ReactNode;
    contentRef: RefObject<HTMLDivElement>;
}

interface EditorLayoutProps {
    sidebar: SidebarProps;
    content: ReactNode;
    isMobile?: boolean;
    droppedDown: boolean;
    handleToggle: () => void;
}

export const EditorLayout: React.FC<EditorLayoutProps> = ({
    sidebar,
    content,
    droppedDown,
    handleToggle,
}) => {
    return (
        <div className="flex h-screen max-md:flex-col">
            {/* Panel Kontrol - Posisi absolut dengan lebar tetap di atas */}
            <div
                className={`flex h-fit w-80 flex-col border-r border-gray-200 bg-white shadow-lg max-md:order-2 max-md:w-full md:h-full`}
            >
                {/* Header */}
                <div className="flex w-full justify-between border-b border-gray-200 bg-blue-600 p-4 text-white">
                    <div className="flex w-fit gap-2">
                        <button
                            className="h-full w-fit rounded bg-blue-500 px-1 font-bold text-white hover:bg-blue-700"
                            onClick={() => window.history.back()}
                        >
                            {/* back icon */}
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                width="20"
                                height="20"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                strokeWidth="2"
                                strokeLinecap="round"
                                strokeLinejoin="round"
                            >
                                <polyline points="15 18 9 12 15 6"></polyline>
                            </svg>
                        </button>
                        <h2 className="flex items-center gap-2 text-xl font-bold">
                            {sidebar.icon}
                            {sidebar.title}
                        </h2>
                    </div>
                    <button
                        className={`h-full w-fit rotate-90 rounded-full bg-blue-500 px-1 font-bold text-white duration-500 hover:bg-blue-700 md:hidden ${
                            droppedDown ? 'rotate-90' : '-rotate-90'
                        }`}
                        onClick={handleToggle}
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            width="20"
                            height="20"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            strokeWidth="2"
                            strokeLinecap="round"
                            strokeLinejoin="round"
                        >
                            <polyline points="15 18 9 12 15 6"></polyline>
                        </svg>
                    </button>
                </div>

                {/* Content Scrollable */}
                <div
                    className={`flex-l overflow-y-auto duration-500 md:h-full md:p-5 ${
                        droppedDown ? 'max-md:h-0' : 'h-[35vh] p-5'
                    }`}
                    ref={sidebar.contentRef}
                >
                    {sidebar.content}
                </div>

                {/* Save Button */}
                <div
                    className={`overflow-hidden border-gray-200 bg-gray-50 duration-500 md:border-t md:p-4 ${
                        droppedDown ? 'max-md:h-0' : 'border-t p-4'
                    }`}
                >
                    {sidebar.footer}
                </div>
            </div>

            {/* Main content area */}
            <div className="h-full flex-1 overflow-hidden">{content}</div>
        </div>
    );
};
