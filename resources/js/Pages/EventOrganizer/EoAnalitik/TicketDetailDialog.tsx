import Ticket from '@/Components/novatix/Ticket';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogTrigger } from '@/components/ui/dialog';

export function ShowTicket({
    ticket_id,
    ticketType,
    seat_number,
    price,
    date,
}: {
    ticket_id: string;
    ticketType: string;
    seat_number: number;
    price: number;
    date: string;
}) {
    return (
        <Dialog>
            <DialogTrigger asChild>
                <Button variant="ghost">{ticket_id}</Button>
            </DialogTrigger>
            <DialogContent className="flex justify-center py-[5vw] md:max-w-[80vw]">
                <Ticket
                    ticketURL={`https://example.com/ticket/${ticket_id}`}
                    ticketCode={ticket_id}
                    ticketType={ticketType}
                    ticketData={{
                        date: date,
                        type: ticketType,
                        seat: seat_number,
                        price: price,
                    }}
                />
            </DialogContent>
        </Dialog>
    );
}
