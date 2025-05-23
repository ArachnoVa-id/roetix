'use client';

import useEmblaCarousel from 'embla-carousel-react';
import { ChevronLeft, ChevronRight } from 'lucide-react';
import { useCallback, useEffect, useState } from 'react';

import EventInfoCard from '@/Components/RoetixComponents/Custom/EventInfoCard';

const data = [
    {
        title: "Event Name with Two Lines but I Think it's Way Too Long",
        start: '11 January 2025',
        end: '16 January 2025',
        price: '50.000,00',
        imgSrc: '/images/event_info_dummy.png',
        className: 'flex flex-col md:flex-row md:p-0',
    },
    {
        title: "Event Name with Two Lines but I Think it's Way Too Long",
        start: '11 January 2025',
        end: '16 January 2025',
        price: '50.000,00',
        imgSrc: '/images/event_info_dummy.png',
        className: 'flex flex-col md:flex-row md:p-0',
    },
    {
        title: "Event Name with Two Lines but I Think it's Way Too Long",
        start: '11 January 2025',
        end: '16 January 2025',
        price: '50.000,00',
        imgSrc: '/images/event_info_dummy.png',
        className: 'flex flex-col md:flex-row md:p-0',
    },
    {
        title: "Event Name with Two Lines but I Think it's Way Too Long",
        start: '11 January 2025',
        end: '16 January 2025',
        price: '50.000,00',
        imgSrc: '/images/event_info_dummy.png',
        className: 'flex flex-col md:flex-row md:p-0',
    },
    {
        title: "Event Name with Two Lines but I Think it's Way Too Long",
        start: '11 January 2025',
        end: '16 January 2025',
        price: '50.000,00',
        imgSrc: '/images/event_info_dummy.png',
        className: 'flex flex-col md:flex-row md:p-0',
    },
    {
        title: "Event Name with Two Lines but I Think it's Way Too Long",
        start: '11 January 2025',
        end: '16 January 2025',
        price: '50.000,00',
        imgSrc: '/images/event_info_dummy.png',
        className: 'flex flex-col md:flex-row md:p-0',
    },
    {
        title: "Event Name with Two Lines but I Think it's Way Too Long",
        start: '11 January 2025',
        end: '16 January 2025',
        price: '50.000,00',
        imgSrc: '/images/event_info_dummy.png',
        className: 'flex flex-col md:flex-row md:p-0',
    },
];

const Event = () => {
    const [emblaRef, emblaApi] = useEmblaCarousel({ loop: false });
    const [selectedIndex, setSelectedIndex] = useState(0);
    const [scrollSnaps, setScrollSnaps] = useState<number[]>([]);

    const onSelect = useCallback(() => {
        if (!emblaApi) return;
        setSelectedIndex(emblaApi.selectedScrollSnap());
    }, [emblaApi]);

    const scrollTo = useCallback(
        (index: number) => emblaApi && emblaApi.scrollTo(index),
        [emblaApi],
    );

    useEffect(() => {
        if (!emblaApi) return;
        setScrollSnaps(emblaApi.scrollSnapList());
        emblaApi.on('select', onSelect);
        onSelect();
    }, [emblaApi, onSelect]);

    return (
        <div className="space-y-[3vw]">
            <h1 className="text-center text-[8.89vw] font-bold md:text-[3.33vw]">
                <span className="text-[rgba(77,42,125,1)]">Event</span>{' '}
                <span className="text-[rgba(140,76,227,1)]">Information</span>
            </h1>
            <div className="flex flex-col items-center">
                {/* courasel */}
                <div className="flex w-[75%] items-center justify-around">
                    <button
                        onClick={() => emblaApi?.scrollPrev()}
                        className="hidden h-[4vw] w-[5vw] items-center rounded-full bg-[rgba(77,42,125,1)] md:flex"
                    >
                        <ChevronLeft className="h-6 w-6 font-bold text-white" />
                    </button>
                    {/* card courasel */}
                    <div className="embla" ref={emblaRef}>
                        <div className="embla__container">
                            {data.map((d, i) => (
                                <div
                                    className="embla__slide flex w-full justify-center py-[1vw]"
                                    key={i}
                                >
                                    <EventInfoCard
                                        title={d.title}
                                        start={d.start}
                                        end={d.end}
                                        price={d.price}
                                        imgSrc={d.imgSrc}
                                        className="flex flex-col md:flex-row md:p-0"
                                    />
                                </div>
                            ))}
                        </div>
                    </div>
                    {/* next button */}
                    <button
                        onClick={() => emblaApi?.scrollNext()}
                        className="hidden h-[4vw] w-[5vw] items-center rounded-full bg-[rgba(77,42,125,1)] md:flex"
                    >
                        <ChevronRight className="h-6 w-6 font-bold text-white" />
                    </button>
                </div>
                {/* pagination */}
                <div>
                    <div className="flex w-full justify-center">
                        <div className="mt-4 flex items-center space-x-2">
                            {scrollSnaps.map((_, index) => (
                                <button
                                    key={index}
                                    onClick={() => scrollTo(index)}
                                    className={`h-3 w-3 rounded-full ${
                                        index === selectedIndex
                                            ? 'h-5 w-5 bg-purple-700'
                                            : 'bg-gray-300'
                                    }`}
                                />
                            ))}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
};

export default Event;
