'use client';

import TimelineCard from '@/Components/RoetixComponents/Custom/TimelineCard';
import { useEffect, useState } from 'react';

const TimeLine = () => {
    const [activeIndex, setActiveIndex] = useState<number | null>(0);
    const [isMobile, setIsMobile] = useState(false);

    useEffect(() => {
        const checkMobile = () => {
            setIsMobile(window.innerWidth < 768);
        };

        checkMobile();
        window.addEventListener('resize', checkMobile);
        return () => window.removeEventListener('resize', checkMobile);
    }, []);

    const handleToggle = (index: number) => {
        if (!isMobile) return;
        setActiveIndex((prev) => (prev === index ? null : index));
    };

    const timelineData = [
        { title: 'Early Bird' },
        { title: 'Middle Bird' },
        { title: 'Top Bird' },
    ];

    const sampleContent = {
        benefits: [
            'Lorem ipsum dolor sit amet, consectetur adipiscing elit.',
            'Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.',
            'Ut enim ad minim veniam, quis nostrud exercitation.',
            'Duis aute irure dolor in reprehenderit in voluptate velit.',
        ],
        merits: [
            'Lorem ipsum dolor sit amet, consectetur adipiscing elit.',
            'Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.',
            'Ut enim ad minim veniam, quis nostrud exercitation.',
            'Duis aute irure dolor in reprehenderit in voluptate velit.',
        ],
    };

    return (
        <div className="relative">
            <div>
                <img
                    src="/images/timeline_mobile.png"
                    alt="Header Mobile"
                    width={1000}
                    height={1000}
                    className="h-[444.89vw] w-[100vw] object-cover md:hidden"
                />
                <img
                    src="/images/timeline_desktop.png"
                    alt="Header Desktop"
                    width={1000}
                    height={1000}
                    className="hidden h-[63.8vw] w-[100vw] object-cover md:block"
                />
            </div>

            <div className="absolute inset-0 flex w-full flex-col items-center justify-center space-y-[2vw]">
                <h1 className="text-center text-[8.89vw] font-bold text-[rgba(211,190,255,1)] md:text-[3.33vw]">
                    Timeline Event
                </h1>

                <div className="space-y-5 md:flex md:space-x-[2vw] md:space-y-0">
                    {timelineData.map((item, idx) => (
                        <TimelineCard
                            key={idx}
                            title={item.title}
                            benefits={sampleContent.benefits}
                            merits={sampleContent.merits}
                            isOpened={!isMobile || activeIndex === idx}
                            onToggle={() => handleToggle(idx)}
                        />
                    ))}
                </div>
            </div>
        </div>
    );
};

export default TimeLine;
