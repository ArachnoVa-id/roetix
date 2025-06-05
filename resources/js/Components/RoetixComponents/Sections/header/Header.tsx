import CustomButton from '@/Components/RoetixComponents/Custom/CustomButton';

const Header = () => {
    return (
        <div className="relative">
            {/* background */}
            <div>
                <img
                    src="/images/header_mobile.png"
                    alt="Header Mobile"
                    width={1000}
                    height={1000}
                    className="h-[159.17vw] w-[100vw] object-cover md:hidden"
                />
                <img
                    src="/images/header_desktop.png"
                    alt="Header Desktop"
                    width={1000}
                    height={1000}
                    className="hidden h-[39.01vw] w-[100vw] object-cover md:block"
                />
            </div>

            {/* content */}
            <div className="absolute inset-0 flex w-full flex-col items-center justify-center md:items-start md:pl-[9.38vw]">
                <h1 className="text-center text-[8.89vw] font-bold text-[rgba(211,190,255,1)] md:text-[3.33vw]">
                    Every Great Event, There’s Roetix
                </h1>
                <p className="mt-[8vw] max-w-md text-start text-[4.24vw] text-white md:mt-[3vw] md:text-[1.25vw]">
                    Roetix adalah platform ticketing yang menghadirkan kemudahan
                    dalam mengelola dan mengakses acara. Dengan sistem yang
                    ringkas dan transparan, Roetix memfasilitasi seluruh proses,
                    mulai dari penjualan tiket, pengelolaan peserta, hingga
                    pelaporan agar setiap pihak dapat fokus pada keberhasilan
                    acara.
                </p>
                {/* button  */}
                <div className="mt-[8vw] flex flex-col space-y-[3vw] md:mt-[2vw] md:flex-row md:space-x-[1vw] md:space-y-0">
                    <CustomButton
                        className="h-[13.33vw] w-[68.61vw] rounded-full bg-[linear-gradient(to_right,rgba(77,42,125,1),rgba(140,76,227,1))] transition-transform duration-300 ease-in-out hover:scale-105 active:scale-95 md:h-[2.5vw] md:w-[12.86vw]"
                        href="#timeline"
                    >
                        <p className="text-[4.44vw] md:text-[0.83vw]">
                            Pesan Tiket Sekarang
                        </p>
                    </CustomButton>
                    <CustomButton
                        className="border-[rgba(140,76,227,1) h-[13.33vw] w-[68.61vw] rounded-full border-4 bg-white text-[rgba(140,76,227,1)] transition-transform duration-300 ease-in-out hover:scale-105 active:scale-95 md:h-[2.5vw] md:w-[12.86vw]"
                        href="#timeline"
                    >
                        <p className="text-[4.44vw] md:text-[0.83vw]">
                            Telusuri Lebih Lanjut
                        </p>
                    </CustomButton>
                </div>
            </div>
        </div>
    );
};

export default Header;
