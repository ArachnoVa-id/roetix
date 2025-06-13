import { Head } from '@inertiajs/react';
import About from '../../Components/RoetixComponents/Sections/about/About';
import ContactUs from '../../Components/RoetixComponents/Sections/contactus/ContactUs';
import Event from '../../Components/RoetixComponents/Sections/event/Event';
import EventGalery from '../../Components/RoetixComponents/Sections/event/EventGalery';
import TimeLine from '../../Components/RoetixComponents/Sections/event/TimeLine';
import Footer from '../../Components/RoetixComponents/Sections/footer/Footer';
import Header from '../../Components/RoetixComponents/Sections/header/Header';
import Navigation from '../../Components/RoetixComponents/Sections/navbar/Navigation';

export default function RoetixLanding() {
    return (
        <div className="font-unageo">
            <Head>
                <title>Roetix</title>
                <link
                    rel="icon"
                    type="image/png"
                    href="/images/about_logo.png"
                />
            </Head>
            <div className="aspect-[360/35] px-[5.56vw] py-[2.43vw] md:aspect-[1920/100] md:px-[8.42vw] md:py-[0.98vw]">
                <Navigation />
            </div>
            <div className="aspect-[360/573] md:aspect-[1920/749]">
                <Header />
            </div>
            <div className="aspect-[360/820] md:aspect-[1920/727]">
                <About />
            </div>
            <div className="hidden aspect-[360/800] md:aspect-[1920/800]">
                <Event />
            </div>
            <div
                className="aspect-[360/1716] md:aspect-[1920/1225]"
                id="timeline"
            >
                <TimeLine />
            </div>
            <div className="hidden aspect-[360/1623] md:aspect-[1920/727]">
                <EventGalery />
            </div>
            <div className="aspect-[360/412] md:aspect-[1920/675]">
                <ContactUs />
            </div>
            <div className="aspect-[360/377] md:aspect-[1920/520]">
                <Footer />
            </div>
        </div>
    );
}
