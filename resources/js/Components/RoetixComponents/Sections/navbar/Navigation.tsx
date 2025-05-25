import CustomButton from '@/Components/RoetixComponents/Custom/CustomButton';

const Navigation = () => {
    return (
        <div className="flex items-center justify-between">
            <img
                src={'/images/nav_logo.png'}
                width={1000}
                height={1000}
                alt="navigation logo"
                className="h-[6.94vw] w-[17.13vw] md:h-[4.01vw] md:w-[9.89vw]"
            />
            <CustomButton className="h-[4.86vw] w-[18.7vw] rounded-full bg-gradient-to-r from-[#4D2A7D] to-[#8C4CE3] px-[3vw] py-[1vw] shadow-lg transition-transform duration-300 ease-in-out hover:scale-105 hover:from-[#6A3FA4] hover:to-[#A96DFF] hover:shadow-xl focus:outline-none focus:ring-2 focus:ring-purple-400 active:scale-95 md:h-[2.5vw] md:w-[9.9vw] md:p-0">
                <p className="text-[1.57vw] font-medium text-white md:text-[0.83vw]">
                    Hubungi Kami
                </p>
            </CustomButton>
        </div>
    );
};

export default Navigation;
