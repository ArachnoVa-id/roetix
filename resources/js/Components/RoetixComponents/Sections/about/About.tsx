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
                <div className="flex flex-col items-start justify-center text-[8.89vw] md:text-[3.33vw]">
                    <h1 className="font-bold text-[rgba(77,42,125,1)]">
                        Mengenai
                    </h1>
                    <h2 className="font-bold text-[rgba(140,76,227,1)]">
                        Roetix
                    </h2>
                    <p className="mt-[1vw] hidden text-justify text-[1.04vw] md:flex">
                        Lorem ipsum dolor sit amet, consectetur adipiscing elit,
                        sed do eiusmod tempor incididunt ut labore et dolore
                        magna aliqua. Ut enim ad minim veniam, quis nostrud
                        exercitation ullamco laboris nisi ut aliquip ex ea
                        commodo consequat. Duis aute irure dolor in
                        reprehenderit in voluptate velit esse cillum dolore eu
                        fugiat nulla pariatur. Excepteur sint occaecat cupidatat
                        non proident, sunt in culpa qui officia deserunt mollit
                        anim id est laborum.
                    </p>
                    {/* 2 nd paragraph */}
                    <p className="mt-[1vw] hidden text-justify text-[1.04vw] md:flex">
                        Lorem ipsum dolor sit amet, consectetur adipiscing elit,
                        sed do eiusmod tempor incididunt ut labore et dolore
                        magna aliqua. Ut enim ad minim veniam, quis nostrud
                        exercitation ullamco laboris nisi ut aliquip ex ea
                        commodo consequat. Duis aute irure dolor in
                        reprehenderit in voluptate velit esse cillum dolore eu
                        fugiat nulla pariatur. Excepteur sint occaecat cupidatat
                        non proident, sunt in culpa qui officia deserunt mollit
                        anim id est laborum.
                    </p>
                </div>
            </div>

            <div className="px-[8vw] text-justify text-[4.44vw] md:hidden md:text-[1.04vw]">
                {/* 1 st paragraph */}
                <p className="mt-[5vw]">
                    Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed
                    do eiusmod tempor incididunt ut labore et dolore magna
                    aliqua. Ut enim ad minim veniam, quis nostrud exercitation
                    ullamco laboris nisi ut aliquip ex ea commodo consequat.
                    Duis aute irure dolor in reprehenderit in voluptate velit
                    esse cillum dolore eu fugiat nulla pariatur. Excepteur sint
                    occaecat cupidatat non proident, sunt in culpa qui officia
                    deserunt mollit anim id est laborum.
                </p>
                {/* 2 nd paragraph */}
                <p className="mt-[5vw]">
                    Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed
                    do eiusmod tempor incididunt ut labore et dolore magna
                    aliqua. Ut enim ad minim veniam, quis nostrud exercitation
                    ullamco laboris nisi ut aliquip ex ea commodo consequat.
                    Duis aute irure dolor in reprehenderit in voluptate velit
                    esse cillum dolore eu fugiat nulla pariatur. Excepteur sint
                    occaecat cupidatat non proident, sunt in culpa qui officia
                    deserunt mollit anim id est laborum.
                </p>
            </div>
        </div>
    );
};

export default About;
