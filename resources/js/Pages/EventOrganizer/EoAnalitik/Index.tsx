import React from "react";
import EodashboardLayout from "@/Layouts/EodashboardLayout";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow, TableFooter } from "@/components/ui/table";
import { Popover, PopoverContent, PopoverTrigger } from "@/components/ui/popover"
import { Button } from "@/components/ui/button"
interface Orders {
    order_id: string;
    order_date: string;
    // seat_number: number;
    status: string;
    total_price: string;
    email: string;
    // ticket_type: string;
    created_at: string;
}

interface Props {
    tickets: Orders[];
    title: string;
    subtitle: string;
    total: number;
}

export default function Index({ tickets, total, title, subtitle }: Props) {
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
                            <TableHead>email</TableHead>
                            <TableHead>status</TableHead>
                            <TableHead>total price</TableHead>
                        </TableRow>
                    </TableHeader>

                    <TableBody>
                        {tickets.map((ticket, idx) => (
                            <TableRow key={idx}>
                                <TableCell>{ticket.order_id}</TableCell>
                                <TableCell>{ticket.order_date}</TableCell>
                                <TableCell>{ticket.email}</TableCell>
                                <TableCell
                                    className={`font-bold hover:
                                        ${ticket.status === 'cancelled' ? 'text-red-700'
                                            : ticket.status === 'pending' ? 'text-yellow-400'
                                                : 'text-green-800'
                                        }`}
                                >
                                    {ticket.status === 'pending' ?
                                        <Popover>
                                            <PopoverTrigger>{ticket.status}</PopoverTrigger>
                                            <PopoverContent className="flex flex-col gap-2">
                                                <Button className="text-white">Verified</Button>
                                                <Button className="text-white">Rejected</Button>
                                            </PopoverContent>
                                        </Popover>
                                        : ticket.status}
                                </TableCell>
                                <TableCell>{ticket.total_price}</TableCell>
                            </TableRow>
                        ))}
                    </TableBody>
                    <TableFooter>
                        <TableRow>
                            <TableCell>Total</TableCell>
                            <TableCell colSpan={6} className="text-right">Rp. {total}</TableCell>
                        </TableRow>
                    </TableFooter>
                </Table>
            </div>
        </EodashboardLayout>
    );
}
