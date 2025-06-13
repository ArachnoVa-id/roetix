import CustomButton from '@/Components/RoetixComponents/Custom/CustomButton';
import { Phone } from 'lucide-react';

const ContactUs = () => {
    return (
        <div className="relative bg-white">
            {/* content */}
            <div className="flex w-full flex-col items-center justify-center space-y-[12vw] p-[2vw] pt-[10vw] md:space-y-[1vw] md:pt-[5vw]">
                <h1 className="text-center text-[8.89vw] font-bold text-[rgba(77,42,125,1)] md:text-[3.33vw]">
                    Tertarik Untuk Bekerja Bersama Kami?
                </h1>
                <p className="text-[4.44vw] text-[rgba(77,42,125,1)] md:text-[2.08vw]">
                    Klik di Bawah untuk Detail Kontak Kami!
                </p>
                <CustomButton className="flex h-[12.15vw] w-[73.49vw] items-center justify-center gap-[2vw] rounded-full bg-[linear-gradient(to_right,rgba(77,42,125,1),rgba(140,76,227,1))] transition-transform duration-300 ease-in-out hover:scale-105 active:scale-95 md:h-[3.62vw] md:w-[21.8vw]">
                    <a
                        className="text-[4.44vw] text-white md:text-[1.1vw]"
                        href="https://wa.me/6282265486116"
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
