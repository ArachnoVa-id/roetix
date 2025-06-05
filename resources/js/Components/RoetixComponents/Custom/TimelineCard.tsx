'use client';
import CustomButton from './CustomButton';

interface TimelineCardProps {
    title: string;
    merits?: string[];
    benefits?: string[];
    isOpened?: boolean;
    onToggle?: () => void;
}

export default function TimelineCard({
    title,
    benefits,
    merits,
    isOpened = false,
    onToggle,
}: TimelineCardProps) {
    const renderList = (items?: string[]) => {
        if (!items?.length) return null;
        return (
            <ul className="list-disc px-[4vw] md:px-[1vw]">
                {items.map((item, idx) => (
                    <li className="text-[4.44vw] md:text-[1.04vw]" key={idx}>
                        {item}
                    </li>
                ))}
            </ul>
        );
    };

    return (
        <div className="flex h-fit w-[81.11vw] flex-col items-center justify-center space-y-[6vw] rounded-lg bg-white p-[3vw] transition-transform duration-300 ease-in-out hover:scale-105 active:scale-95 md:w-[25.47vw] md:space-y-0 md:p-[2vw]">
            <h1 className="text-[8.89vw] font-bold text-[rgba(140,76,227,1)] md:text-[2.71vw]">
                {title}
            </h1>

            <img
                src={'/images/timeline_logo.png'}
                alt="timeline logo"
                width={1000}
                height={1000}
                className="h-[21.9vw] w-[21.9vw] md:h-[4.42vw] md:w-[4.42vw]"
            />

            {!isOpened && (
                <>
                    <CustomButton
                        className="w-full self-center rounded-full bg-[linear-gradient(to_right,rgba(77,42,125,1),rgba(140,76,227,1))] px-4 py-2 transition-transform duration-300 ease-in-out hover:scale-105 active:scale-95"
                        onClick={onToggle}
                    >
                        <p className="text-[4.44vw] md:text-[1.04vw]">
                            Baca Lebih Lanjut
                        </p>
                    </CustomButton>

                    <CustomButton className="w-full rounded-full border-4 border-[rgba(140,76,227,1)] bg-white text-[rgba(140,76,227,1)] transition-transform duration-300 ease-in-out hover:scale-105 active:scale-95">
                        <p className="text-[4.44vw] md:text-[0.83vw]">
                            Beli Sekarang
                        </p>
                    </CustomButton>
                </>
            )}

            {isOpened && (
                <>
                    <div className='py-5'>
                        <p className="text-[8.89vw] font-bold text-[rgba(77,42,125,1)] md:text-[2.71vw]">
                            Time
                        </p>
                        {renderList(benefits)}
                    </div>

                    <div className='hidden'>
                        <p className="text-[8.89vw] font-bold text-[rgba(77,42,125,1)] md:text-[2.71vw]">
                            Merits
                        </p>
                        {renderList(merits)}
                    </div>

                    <CustomButton className="h-[13.33vw] w-[68.61vw] rounded-full border-4 border-[rgba(140,76,227,1)] bg-white text-[rgba(140,76,227,1)] transition-transform duration-300 ease-in-out hover:scale-105 active:scale-95 md:hidden md:h-[2.5vw] md:w-[12.86vw]">
                        <p className="text-[4.44vw] md:text-[0.83vw]">
                            Beli Sekarang
                        </p>
                    </CustomButton>

                    <CustomButton className="mt-[1vw] hidden w-full self-center rounded-full bg-[linear-gradient(to_right,rgba(77,42,125,1),rgba(140,76,227,1))] px-4 py-2 transition-transform duration-300 ease-in-out hover:scale-105 active:scale-95 md:flex">
                        <p className="text-[4.44vw] md:text-[1.04vw]">
                            Beli Sekarang
                        </p>
                    </CustomButton>
                </>
            )}
        </div>
    );
}
