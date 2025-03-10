import Ticket from '@/Components/novatix/Ticket';

export default function Test() {
    return (
        <div className="flex h-screen items-center justify-center">
            <Ticket
                ticketURL="https://youtu.be/dQw4w9WgXcQ"
                ticketCode="1234"
                ticketType="Diamond"
                ticketData={{
                    date: '6 April 2024, 14:40',
                    type: 'VIP+',
                    seat: 'C3',
                    price: 'Rp150.000',
                }}
            />
        </div>
    );
}
