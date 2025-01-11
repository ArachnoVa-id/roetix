export interface Venue {
    venue_id: string;
    name: string;
    location: string;
    capacity: number;
    contact_info: string;
    status: 'active' | 'inactive' | 'under_maintenance';
    created_at: string;
    updated_at: string;
}
