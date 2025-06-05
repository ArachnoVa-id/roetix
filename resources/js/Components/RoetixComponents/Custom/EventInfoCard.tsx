import { Banknote, Clock } from 'lucide-react';
import { twMerge } from 'tailwind-merge';
import CustomButton from './CustomButton';

interface CustomCardProps {
    title: string;
    start: string;
    end: string;
    price: string;
    className?: string;
    onClick?: () => void;
    href?: string;
    imgSrc: string;
    disabled?: boolean;
}

export default function EventInfoCard({
    className,
    onClick,
    href,
    title,
    start,
    end,
    price,
    imgSrc,
    disabled = false,
}: CustomCardProps) {
    const baseClass =
        'flex flex-col rounded-lg w-[74.44vw] h-[161.72vw] md:w-[65vw] md:h-[20.16vw] hover:scale-105 active:scale-80 transition-transform duration-300 ease-in-out';

    const disabledClass = disabled
        ? 'opacity-50 cursor-not-allowed pointer-events-none'
        : 'hover:shadow-xl';

    const mergedClass = twMerge(baseClass, disabledClass, className);

    const content = (
        <div className={mergedClass} onClick={disabled ? undefined : onClick}>
            <img
                src={imgSrc}
                alt="event image"
                width={1000}
                height={1000}
                className="h-[69.72vw] w-[74.72vw] bg-black md:h-[20.16vw] md:w-[18.91vw]"
            />
            <div className="flex h-full w-full flex-col items-center space-y-[4vw] rounded-bl-[2vw] rounded-br-[2vw] border-2 border-[rgba(77,42,125,1)] pt-[5vw] md:items-start md:justify-center md:space-y-[0.9vw] md:rounded-bl-none md:rounded-tr-[1vw] md:p-[1vw] md:pt-0">
                <h2 className="text-center text-[6.67vw] font-bold text-[rgba(77,42,125,1)] md:text-start md:text-[2.08vw]">
                    {title}
                </h2>
                <div className="flex items-center space-x-[2vw]">
                    <Clock
                        color="purple"
                        className="h-[9.56vw] w-[9.56vw] md:h-[1.51vw] md:w-[1.51vw]"
                    />
                    <div className="flex flex-col md:flex-row md:items-center md:justify-center">
                        <p className="text-[5.56vw] md:text-[1.04vw]">
                            {start}
                        </p>
                        <p className="hidden md:flex">{' - '}</p>
                        <p className="text-[5.56vw] md:text-[1.04vw]">{end}</p>
                    </div>
                </div>
                <div className="flex items-center space-x-[2vw]">
                    <Banknote
                        color="purple"
                        className="h-[9.56vw] w-[9.56vw] md:h-[1.51vw] md:w-[1.51vw]"
                    />
                    <p className="text-[5.56vw] md:text-[1.04vw]">
                        {' '}
                        IDR{price}
                    </p>
                </div>
                <CustomButton
                    className="h-[11.11vw] w-[49.44vw] rounded-full bg-[linear-gradient(to_right,rgba(77,42,125,1),rgba(140,76,227,1))] transition-transform duration-300 ease-in-out hover:scale-105 active:scale-95 md:h-[2.48vw] md:w-[12.57vw]"
                    disabled={disabled}
                    // onClick={onClick}
                    href='#timeline'

                >
                    <p className="text-[3.89vw] md:text-[0.94vw]">
                        Telusuri Lebih Lanjut
                    </p>
                </CustomButton>
            </div>
        </div>
    );

    return href && !disabled ? <a href={href}>{content}</a> : content;
}
