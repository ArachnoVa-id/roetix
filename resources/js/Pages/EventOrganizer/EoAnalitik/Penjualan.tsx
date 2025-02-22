import EodashboardLayout from '@/Layouts/EodashboardLayout';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { ShowTicket } from './TicketDetailDialog';

interface Order {
    email: string;
    order_date: string;
    first_name: string;
    last_name: string;
    total_price: string;
}

interface Ticket {
    ticket_id: string;
    ticket_type: string;
    seat_number: number;
    price: number;
    status: string;
    name: string;
    location: string;
}

interface Props {
    orderId: string;
    orders: Order[];
    tickets: Ticket[];
}

export default function Penjualan({ orderId, orders, tickets }: Props) {
    return (
        <EodashboardLayout title="Order / Penjualan" subtitle={orderId}>
            <div className="md:pb-[3vw]">
                <p className="font-bold">
                    {orders[0].first_name} {orders[0].last_name}
                </p>
                <div>{orders[0].email}</div>
            </div>
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>ticket id</TableHead>
                        <TableHead>venue name</TableHead>
                        <TableHead>seat</TableHead>
                        <TableHead>pice</TableHead>
                        <TableHead>ticket type</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    {tickets.map((item, idx) => (
                        <TableRow key={idx}>
                            <TableCell>
                                <ShowTicket
                                    price={item.price}
                                    seat_number={item.seat_number}
                                    ticket_id={item.ticket_id}
                                    ticketType={item.ticket_type}
                                    date={orders[0].order_date}
                                />
                            </TableCell>
                            <TableCell>{item.name}</TableCell>
                            <TableCell>{item.seat_number}</TableCell>
                            <TableCell>{item.price}</TableCell>
                            <TableCell>{item.ticket_type}</TableCell>
                        </TableRow>
                    ))}
                </TableBody>
            </Table>
        </EodashboardLayout>
    );
}
