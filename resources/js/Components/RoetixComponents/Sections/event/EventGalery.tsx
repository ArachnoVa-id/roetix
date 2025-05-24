import GaleryLayoutMobile from '@/Components/RoetixComponents/Custom/GaleryLayoutMobile';
import {
    default as GaleryLayoutLeft,
    default as GaleryLayoutRight,
} from '@/Components/RoetixComponents/Custom/GaleryLayoutRight';

const EventGalery = () => {
    return (
        <div>
            {/* desktop grid */}
            <div className="hidden md:flex">
                <div className="space-y-[1vw] py-[2vw]">
                    <GaleryLayoutLeft
                        largeImage="/images/galery_large.png"
                        smallImages={[
                            '/images/galery_sm3.png',
                            '/images/galery_sm2.png',
                            '/images/galery_sm1.png',
                        ]}
                        titleLine1="Roetix"
                        titleLine2="Event A"
                    />
                    <GaleryLayoutRight
                        largeImage="/images/galery_large.png"
                        smallImages={[
                            '/images/galery_sm3.png',
                            '/images/galery_sm2.png',
                            '/images/galery_sm1.png',
                        ]}
                        titleLine1="Roetix"
                        titleLine2="Event B"
                    />
                    <GaleryLayoutLeft
                        largeImage="/images/galery_large.png"
                        smallImages={[
                            '/images/galery_sm3.png',
                            '/images/galery_sm2.png',
                            '/images/galery_sm1.png',
                        ]}
                        titleLine1="Roetix"
                        titleLine2="Event C"
                    />
                    <GaleryLayoutRight
                        largeImage="/images/galery_large.png"
                        smallImages={[
                            '/images/galery_sm3.png',
                            '/images/galery_sm2.png',
                            '/images/galery_sm1.png',
                        ]}
                        titleLine1="Roetix"
                        titleLine2="Event D"
                    />
                </div>
            </div>
            {/* mobile grid */}
            <div className="flex md:hidden">
                <div>
                    <GaleryLayoutMobile
                        largeImage="/images/galery_large.png"
                        smallImages={[
                            '/images/galery_sm1.png',
                            '/images/galery_sm1.png',
                        ]}
                        titleLine1="Roetix"
                        titleLine2="Event A"
                        isLeft={false}
                    />
                    <GaleryLayoutMobile
                        largeImage="/images/galery_large.png"
                        smallImages={[
                            '/images/galery_sm1.png',
                            '/images/galery_sm1.png',
                        ]}
                        titleLine1="Roetix"
                        titleLine2="Event B"
                        isLeft={true}
                    />
                    <GaleryLayoutMobile
                        largeImage="/images/galery_large.png"
                        smallImages={[
                            '/images/galery_sm1.png',
                            '/images/galery_sm1.png',
                        ]}
                        titleLine1="Roetix"
                        titleLine2="Event C"
                        isLeft={false}
                    />
                    <GaleryLayoutMobile
                        largeImage="/images/galery_large.png"
                        smallImages={[
                            '/images/galery_sm1.png',
                            '/images/galery_sm1.png',
                        ]}
                        titleLine1="Roetix"
                        titleLine2="Event D"
                        isLeft={true}
                    />
                </div>
            </div>
        </div>
    );
};

export default EventGalery;
