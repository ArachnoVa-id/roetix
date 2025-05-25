interface GaleryLayoutMobileProps {
    largeImage: string;
    smallImages: [string, string];
    titleLine1: string;
    titleLine2: string;
    isLeft?: boolean;
}

export default function GaleryLayoutMobile({
    largeImage,
    smallImages,
    titleLine1,
    titleLine2,
    isLeft = true,
}: GaleryLayoutMobileProps) {
    return (
        <div className="grid grid-cols-4 grid-rows-8 gap-4 px-[5vw]">
            {/* Judul */}
            <div
                className={`col-span-4 row-span-2 flex flex-col text-[6.89vw] font-bold ${isLeft ? 'items-end' : ''} active:scale-85 transition-transform duration-300 ease-in-out hover:scale-95`}
            >
                <p className="text-[rgba(77,42,125,1)]">{titleLine1}</p>
                <p className="text-[rgba(140,76,227,1)]">{titleLine2}</p>
            </div>

            {isLeft ? (
                <>
                    {/* Large Image Left */}
                    <div className="col-span-2 col-start-1 row-span-6 row-start-3">
                        <img
                            src={largeImage}
                            alt="large image"
                            width={1000}
                            height={1000}
                            className="h-full w-full transition-transform duration-300 ease-in-out hover:scale-105 active:scale-95"
                        />
                    </div>

                    {/* Small Top Right */}
                    <div className="col-span-2 col-start-3 row-span-3 row-start-3">
                        <img
                            src={smallImages[0]}
                            alt="small image 1"
                            width={1000}
                            height={1000}
                            className="h-full w-full object-cover transition-transform duration-300 ease-in-out hover:scale-105 active:scale-95"
                        />
                    </div>

                    {/* Small Bottom Right */}
                    <div className="col-span-2 col-start-3 row-span-3 row-start-6">
                        <img
                            src={smallImages[1]}
                            alt="small image 2"
                            width={1000}
                            height={1000}
                            className="h-full w-full object-cover transition-transform duration-300 ease-in-out hover:scale-105 active:scale-95"
                        />
                    </div>
                </>
            ) : (
                <>
                    {/* Large Image Right */}
                    <div className="col-span-2 col-start-3 row-span-6 row-start-3">
                        <img
                            src={largeImage}
                            alt="large image"
                            width={1000}
                            height={1000}
                            className="h-full w-full transition-transform duration-300 ease-in-out hover:scale-105 active:scale-95"
                        />
                    </div>

                    {/* Small Top Left */}
                    <div className="col-span-2 col-start-1 row-span-3 row-start-3">
                        <img
                            src={smallImages[0]}
                            alt="small image 1"
                            width={1000}
                            height={1000}
                            className="h-full w-full object-cover transition-transform duration-300 ease-in-out hover:scale-105 active:scale-95"
                        />
                    </div>

                    {/* Small Bottom Left */}
                    <div className="col-span-2 col-start-1 row-span-3 row-start-6">
                        <img
                            src={smallImages[1]}
                            alt="small image 2"
                            width={1000}
                            height={1000}
                            className="h-full w-full object-cover transition-transform duration-300 ease-in-out hover:scale-105 active:scale-95"
                        />
                    </div>
                </>
            )}
        </div>
    );
}
