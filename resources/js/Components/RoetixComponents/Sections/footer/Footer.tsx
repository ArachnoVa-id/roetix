import { Instagram, Phone, Youtube } from 'lucide-react';

const Footer = () => {
    return (
        <div className="flex h-full w-full flex-col items-center justify-center space-y-[5vw] bg-[linear-gradient(to_right,rgba(77,42,125,1),rgba(151,71,255,1))] md:space-y-[1.2vw]">
            <img
                src={'/images/footer_logo.png'}
                width={1000}
                height={1000}
                alt="Footer Logo"
                className="h-[16.15vw] w-[40.61vw] md:h-[6.67vw] md:w-[17.6vw]"
            />

            <p className="text-[4.44vw] font-bold text-white md:text-[1.67vw]">
                Roetix @ 2025 All Rights Reserved
            </p>

            <div className="flex flex-col items-center justify-center space-y-[2vw] text-[4.44vw] text-white md:flex-row md:space-x-[1vw] md:space-y-0 md:text-[1.25vw]">
                <a
                    href="https://wa.me/6285123883960"
                    target="_blank"
                    rel="noreferrer"
                    className="flex items-center space-x-2 hover:underline"
                >
                    <Phone color="rgba(252, 216, 4, 1)" />
                    <span>+62 851-2388-3960</span>
                </a>
            </div>

            <p className="text-[3.89vw] font-bold text-white md:text-[1.67vw]">
                Contact Us Through
            </p>
            <div className="flex items-center space-x-[5vw]">
                {/* <div className="active:scale-85 cursor-pointer rounded-full bg-white p-[2vw] transition-transform duration-300 ease-in-out hover:scale-95 md:p-[1vw] hidden">
                    <Facebook
                        color="rgba(77, 42, 125, 1)"
                        className="h-[6vw] w-[6vw] md:h-[1.5vw] md:w-[1.5vw]"
                    />
                </div> */}
                <a
                    className="active:scale-85 cursor-pointer rounded-full bg-white p-[2vw] transition-transform duration-300 ease-in-out hover:scale-95 md:p-[1vw]"
                    href="https://www.instagram.com/rumahorkestrajogja/"
                    target="_blank"
                    rel="noreferrer"
                >
                    <Instagram
                        color="rgba(77, 42, 125, 1)"
                        className="h-[6vw] w-[6vw] md:h-[1.5vw] md:w-[1.5vw]"
                    />
                </a>
                {/* <div className="active:scale-85 cursor-pointer rounded-full bg-white p-[2vw] transition-transform duration-300 ease-in-out hover:scale-95 md:p-[1vw]">
                    <Twitter
                        color="rgba(77, 42, 125, 1)"
                        className="h-[6vw] w-[6vw] md:h-[1.5vw] md:w-[1.5vw]"
                    />
                </div> */}
                <a
                    className="active:scale-85 cursor-pointer rounded-full bg-white p-[2vw] transition-transform duration-300 ease-in-out hover:scale-95 md:p-[1vw]"
                    href="https://www.youtube.com/@rumahorkestrajogja6902"
                    target="_blank"
                    rel="noreferrer"
                >
                    <Youtube
                        color="rgba(77, 42, 125, 1)"
                        className="h-[6vw] w-[6vw] md:h-[1.5vw] md:w-[1.5vw]"
                    />
                </a>
            </div>
        </div>
    );
};

export default Footer;
