import React, { useEffect, useState } from 'react';
import { useWebSocket } from '../../hooks/useWebSocket';
import { Seat } from '../../types/seat';
import { SeatTransaction } from '../../types/transaction';

interface TransactionMonitorProps {
    venueId: string;
}

export const TransactionMonitor: React.FC<TransactionMonitorProps> = ({
    venueId,
}) => {
    const [activeTransactions, setActiveTransactions] = useState<
        SeatTransaction[]
    >([]);
    const [stats, setStats] = useState({
        total: 0,
        completed: 0,
        cancelled: 0,
        expired: 0,
        pending: 0,
    });

    useWebSocket(`transactions.${venueId}`, (data) => {
        handleTransactionUpdate(data);
    });

    useEffect(() => {
        fetchInitialData();
    }, [venueId]);

    const fetchInitialData = async () => {
        try {
            const [transactionsResponse, statsResponse] = await Promise.all([
                axios.get(`/api/venues/${venueId}/transactions`),
                axios.get(`/api/venues/${venueId}/transaction-statistics`),
            ]);

            setActiveTransactions(transactionsResponse.data);
            setStats(statsResponse.data);
        } catch (error) {
            console.error('Error fetching transaction data:', error);
        }
    };

    const handleTransactionUpdate = (data: {
        transaction: SeatTransaction;
        seat: Seat;
    }) => {
        const { transaction } = data;

        // Update active transactions
        setActiveTransactions((prev) => {
            if (transaction.status !== 'pending') {
                return prev.filter(
                    (t) => t.transaction_id !== transaction.transaction_id,
                );
            }

            const index = prev.findIndex(
                (t) => t.transaction_id === transaction.transaction_id,
            );
            if (index === -1) {
                return [...prev, transaction];
            }

            const updated = [...prev];
            updated[index] = transaction;
            return updated;
        });

        // Update statistics
        setStats((prev) => {
            const newStats = { ...prev };

            if (transaction.status === 'pending') {
                newStats.pending++;
            } else {
                newStats.pending--;
                newStats[transaction.status as keyof typeof newStats]++;
            }

            return newStats;
        });
    };

    return (
        <div className="rounded-lg bg-white p-6 shadow-lg">
            <h2 className="mb-6 text-2xl font-bold">Transaction Monitor</h2>

            {/* Statistics Dashboard */}
            <div className="mb-8 grid grid-cols-1 gap-4 md:grid-cols-5">
                <StatCard
                    title="Total"
                    value={stats.total}
                    className="bg-gray-100"
                />
                <StatCard
                    title="Pending"
                    value={stats.pending}
                    className="bg-yellow-100"
                />
                <StatCard
                    title="Completed"
                    value={stats.completed}
                    className="bg-green-100"
                />
                <StatCard
                    title="Cancelled"
                    value={stats.cancelled}
                    className="bg-red-100"
                />
                <StatCard
                    title="Expired"
                    value={stats.expired}
                    className="bg-gray-200"
                />
            </div>

            {/* Active Transactions List */}
            <div className="space-y-4">
                <h3 className="text-lg font-semibold">Active Transactions</h3>
                {activeTransactions.length === 0 ? (
                    <p className="py-4 text-center text-gray-500">
                        No active transactions
                    </p>
                ) : (
                    activeTransactions.map((transaction) => (
                        <TransactionCard
                            key={transaction.transaction_id}
                            transaction={transaction}
                            onCancel={async () => {
                                try {
                                    await axios.post(
                                        `/api/seat-transactions/${transaction.seat_id}/release`,
                                        {
                                            user_id: transaction.user_id,
                                        },
                                    );
                                } catch (error) {
                                    console.error(
                                        'Error cancelling transaction:',
                                        error,
                                    );
                                }
                            }}
                        />
                    ))
                )}
            </div>
        </div>
    );
};

// StatCard Component
interface StatCardProps {
    title: string;
    value: number;
    className?: string;
}

const StatCard: React.FC<StatCardProps> = ({
    title,
    value,
    className = '',
}) => (
    <div className={`${className} rounded-lg p-4`}>
        <h4 className="text-sm font-medium text-gray-600">{title}</h4>
        <p className="mt-1 text-2xl font-bold">{value}</p>
    </div>
);

// TransactionCard Component
interface TransactionCardProps {
    transaction: SeatTransaction;
    onCancel: () => void;
}

const TransactionCard: React.FC<TransactionCardProps> = ({
    transaction,
    onCancel,
}) => {
    const timeLeft = useMemo(() => {
        const expiry = new Date(transaction.expiry_time).getTime();
        const now = new Date().getTime();
        return Math.max(0, Math.floor((expiry - now) / 1000));
    }, [transaction.expiry_time]);

    const [countdown, setCountdown] = useState(timeLeft);

    useEffect(() => {
        if (countdown <= 0) return;

        const timer = setInterval(() => {
            setCountdown((prev) => Math.max(0, prev - 1));
        }, 1000);

        return () => clearInterval(timer);
    }, [countdown]);

    return (
        <div className="flex items-center justify-between rounded-lg border p-4">
            <div>
                <p className="font-medium">Seat {transaction.seat_id}</p>
                <p className="text-sm text-gray-600">
                    User: {transaction.user_id}
                </p>
                <p className="text-sm text-gray-600">
                    Time left: {Math.floor(countdown / 60)}:
                    {(countdown % 60).toString().padStart(2, '0')}
                </p>
            </div>
            <button
                onClick={onCancel}
                className="rounded bg-red-500 px-4 py-2 text-white transition-colors hover:bg-red-600"
            >
                Cancel
            </button>
        </div>
    );
};

// Example usage in admin dashboard
const AdminDashboard: React.FC = () => {
    return (
        <div className="container mx-auto px-4 py-8">
            <TransactionMonitor venueId="your-venue-id" />
        </div>
    );
};

export default AdminDashboard;
