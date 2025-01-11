import axios from 'axios';
import { format } from 'date-fns';
import React, { useEffect, useState } from 'react';
import { SeatTransaction } from '../../types/transaction';

interface TransactionHistoryProps {
    userId: string;
}

export const TransactionHistory: React.FC<TransactionHistoryProps> = ({
    userId,
}) => {
    const [transactions, setTransactions] = useState<SeatTransaction[]>([]);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        const fetchTransactions = async () => {
            try {
                const response = await axios.get(
                    `/api/users/${userId}/transactions`,
                );
                setTransactions(response.data);
            } catch (error) {
                console.error('Error fetching transactions:', error);
            } finally {
                setLoading(false);
            }
        };

        fetchTransactions();
    }, [userId]);

    const getStatusBadgeColor = (status: string) => {
        switch (status) {
            case 'completed':
                return 'bg-green-100 text-green-800';
            case 'pending':
                return 'bg-yellow-100 text-yellow-800';
            case 'cancelled':
                return 'bg-red-100 text-red-800';
            case 'expired':
                return 'bg-gray-100 text-gray-800';
            default:
                return 'bg-gray-100 text-gray-800';
        }
    };

    if (loading) {
        return <div className="flex justify-center py-8">Loading...</div>;
    }

    return (
        <div className="overflow-hidden rounded-lg bg-white shadow">
            <div className="p-6">
                <h2 className="mb-4 text-xl font-semibold">
                    Transaction History
                </h2>

                <div className="overflow-x-auto">
                    <table className="min-w-full divide-y divide-gray-200">
                        <thead className="bg-gray-50">
                            <tr>
                                <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                    Transaction ID
                                </th>
                                <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                    Seat
                                </th>
                                <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                    Status
                                </th>
                                <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                    Reservation Time
                                </th>
                                <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                    Expiry Time
                                </th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-200 bg-white">
                            {transactions.map((transaction) => (
                                <tr key={transaction.transaction_id}>
                                    <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                                        {transaction.transaction_id.slice(0, 8)}
                                        ...
                                    </td>
                                    <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-900">
                                        {transaction.seat_id}
                                    </td>
                                    <td className="whitespace-nowrap px-6 py-4">
                                        <span
                                            className={`inline-flex rounded-full px-2 text-xs font-semibold leading-5 ${getStatusBadgeColor(transaction.status)}`}
                                        >
                                            {transaction.status}
                                        </span>
                                    </td>
                                    <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                                        {format(
                                            new Date(
                                                transaction.reservation_time,
                                            ),
                                            'MMM d, yyyy HH:mm:ss',
                                        )}
                                    </td>
                                    <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                                        {format(
                                            new Date(transaction.expiry_time),
                                            'MMM d, yyyy HH:mm:ss',
                                        )}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    );
};
