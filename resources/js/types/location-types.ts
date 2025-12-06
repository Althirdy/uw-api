export type LocationCategory_T = {
    id: number;
    name: string;
};

export type location_T = {
    id: number;
    location_name: string;
    location_category?: LocationCategory_T;
    landmark: string;
    barangay: string;
    category: string;
    category_name?: string;
    longitude: string;
    latitude: string;
    description?: string;
    cctv_count?: number;
    cameras?: number;
};
