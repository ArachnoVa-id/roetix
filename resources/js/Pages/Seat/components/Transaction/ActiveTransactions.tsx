import React from 'react';
import { useWebSocket } from '../../hooks/useWebSocket';
import { SeatTransaction } from '../../types/transaction';

interface ActiveTransactionsProps {
    venueId: string;
    initialTransactions: SeatTransaction[];
}

export const ActiveTransactions: React.FC<ActiveTransactionsProps> = ({
    venueId,
    initialTransactions,
}) => {
    const [transactions, setTransactions] =
        useState<SeatTransaction[]>(initialTransactions);

    useWebSocket(`transactions.${venueId}`, (data) => {
        const { transaction } = data;

        setTransactions((prev) => {
            const index = prev.findIndex(
                (t) => t.transaction_id === transaction.transaction_id,
            );
            if (index === -1) {
                return [...prev, transaction];
            }
            const newTransactions = [...prev];
            newTransactions[index] = transaction;
            return newTransactions.filter((t) => t.status === 'pending');
        });
    });

    return (
        <div className="rounded-lg bg-white p-6 shadow-lg">
            <h2 className="mb-4 text-lg font-semibold">Active Transactions</h2>

            <div className="space-y-4">
                {transactions.map((transaction) => (
                    <div
                        key={transaction.transaction_id}
                        className="flex items-center justify-between rounded-lg bg-blue-50 p-4"
                    >
                        <div>
                            <p className="font-medium">
                                Seat: {transaction.seat_id}
                            </p>
                            <p className="text-sm text-gray-600">
                                Expires:{' '}
                                {formatDistanceToNow(
                                    new Date(transaction.expiry_time),
                                )}
                            </p>
                        </div>
                        <TransactionTimer
                            expiryTime={transaction.expiry_time}
                            onExpiry={() => {
                                setTransactions((prev) =>
                                    prev.filter(
                                        (t) =>
                                            t.transaction_id !==
                                            transaction.transaction_id,
                                    ),
                                );
                            }}
                        />
                    </div>
                ))}

                {transactions.length === 0 && (
                    <p className="py-4 text-center text-gray-500">
                        No active transactions
                    </p>
                )}
            </div>
        </div>
    );
};
