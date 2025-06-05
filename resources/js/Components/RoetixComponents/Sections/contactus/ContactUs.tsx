import CustomButton from '@/Components/RoetixComponents/Custom/CustomButton';

import { Phone } from 'lucide-react';

const ContactUs = () => {
    return (
        <div className="relative">
            {/* background */}
            <div>
                <img
                    src="/images/contact_mobile.png"
                    alt="Contact Mobile"
                    width={1000}
                    height={1000}
                    className="h-[159.17vw] w-[100vw] object-cover md:hidden"
                />
                <img
                    src="/images/contact_desktop.png"
                    alt="Contact Desktop"
                    width={1000}
                    height={1000}
                    className="hidden h-[39.01vw] w-[100vw] object-cover md:block"
                />
            </div>
            {/* content */}
            <div className="absolute inset-0 flex w-full flex-col items-center justify-center space-y-[12vw] p-[2vw] md:space-y-[1vw]">
                <h1 className="text-center text-[8.89vw] font-bold text-[rgba(211,190,255,1)] md:text-[3.33vw]">
                    Tertarik Untuk Bekerja Bersama Kami?
                </h1>
                <p className="text-[4.44vw] text-white md:text-[2.08vw]">
                    Klik di Bawah untuk Detail Kontak Kami!
                </p>
                <CustomButton className="h-[12.15vw] w-[73.49vw] rounded-full bg-[linear-gradient(to_right,rgba(77,42,125,1),rgba(140,76,227,1))] transition-transform duration-300 ease-in-out hover:scale-105 active:scale-95 md:h-[3.62vw] md:w-[21.8vw]">
                    <a
                        className="text-[4.44vw] md:text-[1.1vw]"
                        href="http://wa.me/6282265486116"
                        target="_blank"
                        rel="noreferrer"
                    >
                        Kontak Kami Sekarang!
                    </a>
                    <Phone className="text-[5.44vw] text-white md:text-[1.83vw]" />
                </CustomButton>
            </div>
        </div>
    );
};

export default ContactUs;
