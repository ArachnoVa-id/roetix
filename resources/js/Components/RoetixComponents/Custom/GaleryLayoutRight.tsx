interface GaleryLayoutLeftProps {
    largeImage: string;
    smallImages: [string, string, string];
    titleLine1: string;
    titleLine2: string;
}

export default function GaleryLayoutRight({
    largeImage,
    smallImages,
    titleLine1,
    titleLine2,
}: GaleryLayoutLeftProps) {
    return (
        <div className="grid grid-cols-8 grid-rows-4 gap-[1vw] px-[5vw]">
            <div className="col-span-2 row-span-4">
                <img
                    src={largeImage}
                    alt="large image"
                    width={1000}
                    height={1000}
                    className="h-full w-full hover:scale-105 active:scale-95 transition-transform duration-300 ease-in-out"
                />
            </div>
            <div className="col-span-6 col-start-3 row-span-2 flex flex-col items-start justify-center text-[3.33vw] font-bold hover:scale-95 active:scale-85 transition-transform duration-300 ease-in-out">
                <p className="text-[rgba(77,42,125,1)]">{titleLine1}</p>
                <p className="text-[rgba(140,76,227,1)]">{titleLine2}</p>
            </div>
            <div className="col-span-2 col-start-3 row-span-2 row-start-3">
                <img
                    src={smallImages[2]}
                    alt="large image"
                    width={1000}
                    height={1000}
                    className="h-full w-full hover:scale-105 active:scale-95 transition-transform duration-300 ease-in-out"
                />
            </div>
            <div className="col-span-2 col-start-5 row-span-2 row-start-3">
                <img
                    src={smallImages[1]}
                    alt="large image"
                    width={1000}
                    height={1000}
                    className="h-full w-full hover:scale-105 active:scale-95 transition-transform duration-300 ease-in-out"
                />
            </div>
            <div className="col-span-2 col-start-7 row-span-2 row-start-3">
                <img
                    src={smallImages[0]}
                    alt="large image"
                    width={1000}
                    height={1000}
                    className="h-full w-full hover:scale-105 active:scale-95 transition-transform duration-300 ease-in-out"
                />
            </div>
        </div>
    );
}
