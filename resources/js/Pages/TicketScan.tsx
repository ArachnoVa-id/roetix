import React from 'react';

interface TicketScanProps {
    event: {
        id: string;
        name: string;
    };
}

const TicketScan: React.FC<TicketScanProps> = () => {
    return (
        <div className="p-6 text-center">
            <h1 className="mb-4 text-2xl font-bold">Scan Ticket</h1>
        </div>
    );
};

export default TicketScan;
