import { Button } from '@/components/ui/button';

function RowComponent({ idtf, content }: { idtf: string; content: string }) {
    return (
        <div className="flex w-full">
            <p className="w-[30%]">{idtf}</p>
            <p className="w-[70%]">{content}</p>
        </div>
    );
}

export default function Ticket({
    ticketType,
    ticketCode,
    ticketData,
    ticketURL,
}: {
    ticketType: string;
    ticketCode: string;
    ticketURL: string;
    ticketData: {
        date: string;
        type: string;
        seat: number;
        price: number;
    };
}) {
    return (
        <div className="relative flex h-[32vw] w-[80vw] flex-row items-center justify-between rounded-lg border shadow-lg md:h-[16vw] md:w-[40vw]">
            {/* Ticket Background */}
            <img
                src="/images/ticket-vector.png"
                alt="Ticket Background"
                className="absolute left-0 top-0 z-[-1] h-full w-full object-cover"
            />

            {/* Barcode */}
            <div className="flex h-full w-[40%] items-center justify-center">
                <img
                    src={`https://api.qrserver.com/v1/create-qr-code/?size=100x100&data=${ticketURL}`}
                    alt="QR Code"
                />
            </div>

            {/* Ticket Info */}
            <div className="flex h-full w-[60%] flex-col justify-around py-[1.2vw]">
                <div className="flex items-center justify-between pr-[2vw]">
                    <p>{ticketType}</p>
                    <Button className="w-[20%]">Unduh</Button>
                </div>
                <div>
                    <RowComponent idtf="ID" content={ticketCode} />
                    <RowComponent idtf="Tanggal" content={ticketData.date} />
                    <RowComponent idtf="Tipe" content={ticketData.type} />
                    <RowComponent
                        idtf="Kursi"
                        content={String(ticketData.seat)}
                    />
                </div>
                <RowComponent
                    idtf="Subtotal"
                    content={String(ticketData.price)}
                />
            </div>
        </div>
    );
}
