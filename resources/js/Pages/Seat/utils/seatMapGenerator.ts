interface SeatGeneratorConfig {
    rows: string[];
    seatsPerRow: number;
    categoryMapping: {
        [key: string]: {
            startRow: string;
            endRow: string;
            sections: {
                start: number;
                end: number;
                category: string;
            }[];
        };
    };
    venue_id: string;
}

interface GeneratedSeat {
    seat_id: string;
    venue_id: string;
    seat_number: string;
    position: string;
    status: 'available' | 'booked' | 'reserved' | 'in_transaction';
}

export const generateSeatMap = (
    config: SeatGeneratorConfig,
): GeneratedSeat[] => {
    const seats: GeneratedSeat[] = [];
    const { rows, seatsPerRow, categoryMapping, venue_id } = config;

    rows.forEach((row) => {
        for (let seatNum = 1; seatNum <= seatsPerRow; seatNum++) {
            // Find the category mapping for this row
            const mapping = Object.values(categoryMapping).find(
                (m) => row >= m.startRow && row <= m.endRow,
            );

            if (mapping) {
                // Find the section this seat belongs to
                const section = mapping.sections.find(
                    (s) => seatNum >= s.start && seatNum <= s.end,
                );

                if (section) {
                    const seatNumber = `${row}${String(seatNum).padStart(2, '0')}`;
                    const position = `row-${row}-seat-${seatNum}`;

                    seats.push({
                        seat_id: crypto.randomUUID(), // Use UUID v4 for unique ID
                        venue_id,
                        seat_number: seatNumber,
                        position,
                        status: 'available',
                    });
                }
            }
        }
    });

    return seats;
};

// Example configuration for Auditorium layout
const exampleConfig: SeatGeneratorConfig = {
    venue_id: 'venue-uuid-here',
    rows: ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J'],
    seatsPerRow: 30,
    categoryMapping: {
        vip: {
            startRow: 'A',
            endRow: 'C',
            sections: [{ start: 5, end: 25, category: 'VIP' }],
        },
        premium: {
            startRow: 'D',
            endRow: 'F',
            sections: [{ start: 3, end: 27, category: 'Premium' }],
        },
        regular: {
            startRow: 'G',
            endRow: 'J',
            sections: [{ start: 1, end: 30, category: 'Regular' }],
        },
    },
};
