import React, { useEffect, useRef, useState } from 'react';

interface TicketScanProps {
    event: {
        id: string;
        name: string;
    };
}

const TicketScan: React.FC<TicketScanProps> = ({ event }) => {
    return (
        <div className="p-6 text-center">
            <h1 className="text-2xl font-bold mb-4">Scan Ticket</h1>
        </div>
    );
};

export default TicketScan;