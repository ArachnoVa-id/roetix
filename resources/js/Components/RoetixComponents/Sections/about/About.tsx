const About = () => {
    return (
        <div className="">
            <div className="flex items-center justify-center pt-[22.22vw] md:px-[10.47vw] md:pt-[2vw]">
                <img
                    src={'/images/about_logo.png'}
                    alt="Aboaut Logo"
                    width={1000}
                    height={1000}
                    className="h-[35.54vw] w-[27.19vw] md:h-[36.022vw] md:w-[27.552vw]"
                />
                <div className="flex flex-col items-start justify-center">
                    <h1 className="text-[5.8vw] font-bold text-[rgba(77,42,125,1)] md:text-[3.2vw]">
                        Saga From Our Childhood
                    </h1>
                    <h2 className="text-[4.8vw] font-bold text-[rgba(140,76,227,1)] md:text-[2.3vw]">
                        Whispers Before the Romance Dawn
                    </h2>
                    {/* 1 nd paragraph */}
                    <p className="mt-[0.8vw] hidden text-justify text-[1.04vw] font-bold md:flex">
                        A special performance by Rumah Orkestra Jogja
                    </p>
                    {/* 2 nd paragraph */}
                    <p className="mt-[0.8vw] hidden text-justify text-[1.14vw] md:flex">
                        Childhood is a timeless voyage, one that never truly
                        ends. In this nostalgic concert, Rumah Orkestra Jogja
                        proudly presents SAGA, a musical journey through the
                        stories that shaped our youth. From iconic anime
                        blockbusters to beloved pop culture moments, we'll
                        relive the magic that once lit up our imaginations.
                    </p>
                    {/* 3 nd paragraph */}
                    <p className="mt-[0.8vw] hidden text-justify text-[1.14vw] md:flex">
                        “Whispers Before the Romance Dawn” will feature a
                        beautiful repertoire from One Piece, inviting us to sail
                        across the seas of memory, adventure, and dreams. This
                        isn't just a concert, it's a celebration of the moments
                        we once cherished, and the spirit that still lives on
                        within us.
                    </p>
                    {/* 4 nd paragraph */}
                    <p className="mt-[0.5vw] hidden text-justify text-[1.04vw] font-bold italic md:flex">
                        Don't be sad that it's over, be glad that it happened.
                    </p>
                    {/* 5 nd paragraph */}
                    <p className="mt-[0.2vw] hidden text-justify text-[1.04vw] font-bold italic md:flex">
                        Come, let's set sail together,one more time.
                    </p>
                </div>
            </div>

            <div className="px-[6vw] text-justify md:hidden">
                {/* 1st paragraph */}
                <p className="mt-[5vw] text-[4vw] font-bold leading-[6vw]">
                    A special performance by Rumah Orkestra Jogja
                </p>

                {/* 2nd paragraph */}
                <p className="mt-[4vw] text-[3.6vw] leading-[6.2vw]">
                    Childhood is a timeless voyage, one that never truly ends.
                    In this nostalgic concert, Rumah Orkestra Jogja proudly
                    presents SAGA, a musical journey through the stories that
                    shaped our youth. From iconic anime blockbusters to beloved
                    pop culture moments, we'll relive the magic that once lit up
                    our imaginations.
                </p>

                {/* 3rd paragraph */}
                <p className="mt-[4vw] text-[3.6vw] leading-[6.2vw]">
                    “Whispers Before the Romance Dawn” will feature a beautiful
                    repertoire from One Piece, inviting us to sail across the
                    seas of memory, adventure, and dreams. This isn't just a
                    concert, it's a celebration of the moments we once
                    cherished, and the spirit that still lives on within us.
                </p>

                {/* 4th paragraph */}
                <p className="mt-[4vw] text-[3.6vw] italic leading-[6.2vw]">
                    Don't be sad that it's over, be glad that it happened.
                </p>

                {/* 5th paragraph */}
                <p className="mt-[4vw] text-[3.6vw] font-semibold leading-[6.2vw]">
                    Come, let's set sail together, one more time.
                </p>
            </div>
        </div>
    );
};

export default About;
