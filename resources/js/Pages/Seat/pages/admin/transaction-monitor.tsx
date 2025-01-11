import React from 'react';
import { ActiveTransactions } from '../../components/Transaction/ActiveTransactions';
import { TransactionHistory } from '../../components/Transaction/TransactionHistory';

const TransactionMonitor: React.FC = () => {
    return (
        <div className="container mx-auto px-4 py-8">
            <h1 className="mb-6 text-2xl font-bold">Transaction Monitor</h1>

            <div className="grid gap-6 md:grid-cols-2">
                <ActiveTransactions
                    venueId="your-venue-id"
                    initialTransactions={[]}
                />
                <TransactionHistory userId="your-user-id" />
            </div>
        </div>
    );
};

export default TransactionMonitor;
