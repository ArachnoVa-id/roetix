import EodashboardLayout from '@/Layouts/EodashboardLayout';
import { Button } from '@/components/ui/button';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import {
    Table,
    TableBody,
    TableCell,
    TableFooter,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';

interface Orders {
    order_id: string;
    order_date: string;
    seat_number: number;
    status: string;
    total_price: string;
    // email: string;
    ticket_type: string;
    created_at: string;
}

import { Link } from '@inertiajs/react';

interface Props {
    orders: Orders[];
    title: string;
    subtitle: string;
    total: number;
}

export default function Index({ orders, total, title, subtitle }: Props) {
    return (
        <EodashboardLayout title={title} subtitle={subtitle}>
            <div className="p-4">
                <h1 className="text-2xl font-bold">{title}</h1>
                <p className="mt-2 text-gray-600">Overview Penjualan Tiket</p>
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>order id</TableHead>
                            <TableHead>order date</TableHead>
                            {/* <TableHead>email</TableHead> */}
                            <TableHead>status</TableHead>
                            <TableHead>total price</TableHead>
                        </TableRow>
                    </TableHeader>

                    <TableBody>
                        {orders.map((order, idx) => (
                            <TableRow key={idx}>
                                <TableCell>
                                    <Link
                                        href={route(
                                            'penjualan.detail',
                                            order.order_id,
                                        )}
                                    >
                                        {order.order_id}
                                    </Link>
                                </TableCell>
                                <TableCell>{order.order_date}</TableCell>
                                <TableCell
                                    className={`hover: font-bold ${
                                        order.status === 'cancelled'
                                            ? 'text-red-700'
                                            : order.status === 'pending'
                                              ? 'text-yellow-400'
                                              : 'text-green-800'
                                    }`}
                                >
                                    {order.status === 'pending' ? (
                                        <Popover>
                                            <PopoverTrigger>
                                                {order.status}
                                            </PopoverTrigger>
                                            <PopoverContent className="flex flex-col gap-2">
                                                <Button className="text-white">
                                                    Verified
                                                </Button>
                                                <Button className="text-white">
                                                    Rejected
                                                </Button>
                                            </PopoverContent>
                                        </Popover>
                                    ) : (
                                        order.status
                                    )}
                                </TableCell>
                                <TableCell>{order.total_price}</TableCell>
                            </TableRow>
                        ))}
                    </TableBody>
                    <TableFooter>
                        <TableRow>
                            <TableCell>Total</TableCell>
                            <TableCell colSpan={6} className="text-right">
                                Rp. {total}
                            </TableCell>
                        </TableRow>
                    </TableFooter>
                </Table>
            </div>
        </EodashboardLayout>
    );
}
