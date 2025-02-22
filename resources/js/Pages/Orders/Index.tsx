import { Button } from '@/components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Link } from '@inertiajs/react';
import React from 'react';

interface Order {
    order_id: string;
    user_id: string;
    ticket_id: string;
    order_date: string;
    total_price: number;
    status: string;
    user: {
        name: string;
    };
    ticket: {
        event_name: string;
    };
}

interface Props {
    orders: Order[];
}

const Index: React.FC<Props> = ({ orders }) => {
    return (
        <div className="container mx-auto p-6">
            <h1 className="mb-4 text-2xl font-bold">Daftar Order</h1>
            <div className="mb-4">
                <Link href="/orders/create">
                    <Button className="bg-blue-500 hover:bg-blue-600">
                        Tambah Order
                    </Button>
                </Link>
            </div>

            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>Order ID</TableHead>
                        <TableHead>User</TableHead>
                        <TableHead>Event</TableHead>
                        <TableHead>Order Date</TableHead>
                        <TableHead>Total Price</TableHead>
                        <TableHead>Status</TableHead>
                        <TableHead>Aksi</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    {orders.map((order) => (
                        <TableRow key={order.order_id}>
                            <TableCell>{order.order_id}</TableCell>
                            <TableCell>{order.user.name}</TableCell>
                            <TableCell>{order.ticket.event_name}</TableCell>
                            <TableCell>{order.order_date}</TableCell>
                            <TableCell>{order.total_price}</TableCell>
                            <TableCell>{order.status}</TableCell>
                            <TableCell>
                                <Link href={`/orders/${order.order_id}/edit`}>
                                    <Button variant="outline" className="mr-2">
                                        Edit
                                    </Button>
                                </Link>
                                <Link
                                    method="delete"
                                    href={`/orders/${order.order_id}`}
                                >
                                    <Button variant="destructive">Hapus</Button>
                                </Link>
                            </TableCell>
                        </TableRow>
                    ))}
                </TableBody>
            </Table>
        </div>
    );
};

export default Index;
