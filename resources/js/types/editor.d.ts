export interface Event {
    id: string;
    name: string;
    venue_id: string;
    team_id: string;
}

export interface Venue {
    id: string;
    name: string;
}

export interface Timeline {
    id: string;
    name: string;
    start_date: string;
    end_date: string;
}

export interface TicketCategory {
    id: string;
    name: string;
    color: string;
}

export interface CategoryPrice {
    ticket_category_id: string;
    timeline_id: string;
    price: number;
}

export interface GridCell {
    type: 'empty' | 'seat' | 'label';
    item?: SeatItem;
    isBlocked?: boolean;
}

export interface GridDimensions {
    top: number;
    bottom: number;
    left: number;
    right: number;
}

export interface EditorProps {
    layout: Layout;
    event: Event;
    venue: Venue;
    ticketTypes: string[];
    categoryColors?: Record<string, string>;
    currentTimeline?: Timeline;
    ticketCategories?: TicketCategory[];
    categoryPrices?: CategoryPrice[];
}

export interface GridEditorProps {
    layout?: Layout;
    venue_id: string;
    errors?: { [key: string]: string };
    flash?: { success?: string };
    isDisabled?: boolean;
}

export interface GridSeatEditorProps {
    initialLayout?: Layout;
    onSave?: (layout: Layout) => void;
    venueId: string;
    isDisabled?: boolean;
}
