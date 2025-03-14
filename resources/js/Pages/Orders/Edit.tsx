import React, { useEffect, useState } from 'react';
// import { Inertia } from "@inertiajs/react";
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Select } from '@/Components/ui/select';

interface Order {
    order_id: string;
    user_id: string;
    ticket_id: string;
    order_date: string;
    total_price: string;
    status: string;
}

interface Props {
    order: Order;
}

const Edit: React.FC<Props> = ({ order }) => {
    const [formData, setFormData] = useState(order);

    useEffect(() => {
        setFormData(order);
    }, [order]);

    const handleChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        setFormData({
            ...formData,
            [e.target.name]: e.target.value,
        });
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        // Inertia.put(`/orders/${order.order_id}`, formData);
    };

    return (
        <div className="container mx-auto p-6">
            <h1 className="mb-4 text-2xl font-bold">Edit Order</h1>

            <form onSubmit={handleSubmit} className="space-y-4">
                <div>
                    <Label htmlFor="user_id">User ID</Label>
                    <Input
                        id="user_id"
                        name="user_id"
                        value={formData.user_id}
                        onChange={handleChange}
                        required
                    />
                </div>

                <div>
                    <Label htmlFor="ticket_id">Ticket ID</Label>
                    <Input
                        id="ticket_id"
                        name="ticket_id"
                        value={formData.ticket_id}
                        onChange={handleChange}
                        required
                    />
                </div>

                <div>
                    <Label htmlFor="order_date">Order Date</Label>
                    <Input
                        id="order_date"
                        name="order_date"
                        type="date"
                        value={formData.order_date}
                        onChange={handleChange}
                        required
                    />
                </div>

                <div>
                    <Label htmlFor="total_price">Total Price</Label>
                    <Input
                        id="total_price"
                        name="total_price"
                        type="number"
                        value={formData.total_price}
                        onChange={handleChange}
                        required
                    />
                </div>

                <div>
                    <Label htmlFor="status">Status</Label>
                    <Select
                        // id="status"
                        name="status"
                        value={formData.status}
                        // onChange={handleChange}
                        required
                    >
                        <option value="pending">Pending</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </Select>
                </div>

                <Button type="submit">Simpan</Button>
            </form>
        </div>
    );
};

export default Edit;
