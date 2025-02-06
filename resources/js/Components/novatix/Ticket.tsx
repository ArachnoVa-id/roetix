import { Button } from "@/components/ui/button"

function RowComponent({ idtf, content }: { idtf: string; content: string }) {
  return (
    <div className="flex w-full">
      <p className="w-[30%]">{idtf}</p>
      <p className="w-[70%]">{content}</p>
    </div>
  )
}

export default function Ticket({
  ticketType,
  ticketCode,
  ticketData,
  ticketURL
}: {
  ticketType: string;
  ticketCode: string;
  ticketURL: string;
  ticketData: {
    date: string;
    type: string;
    seat: string;
    price: string;
  };
}) {
  return (
    <div className="relative w-[80vw] h-[32vw] md:w-[40vw] md:h-[16vw] flex flex-row items-center justify-between border rounded-lg shadow-lg">
      {/* Ticket Background */}
      <img
        src="/images/ticket-vector.png"
        alt="Ticket Background"
        className="absolute top-0 left-0 w-full h-full object-cover z-[-1]"
      />

      {/* Barcode */}
      <div className="w-[40%] h-full flex justify-center items-center">
        <img
          src={`https://api.qrserver.com/v1/create-qr-code/?size=100x100&data=${ticketURL}`}
          alt="QR Code"
        />
      </div>

      {/* Ticket Info */}
      <div className="w-[60%] h-full flex flex-col justify-around py-[1.2vw]">
        <div className="flex justify-between pr-[2vw] items-center">
          <p>{ticketType}</p>
          <Button>Unduh</Button>
        </div>
        <div>
        <RowComponent idtf="ID" content={ticketCode} />
        <RowComponent idtf="Tanggal" content={ticketData.date} />
        <RowComponent idtf="Tipe" content={ticketData.type} />
        <RowComponent idtf="Kursi" content={ticketData.seat} />
        </div>
        <RowComponent idtf="Subtotal" content={ticketData.price} />
      </div>
    </div>
  );
}
