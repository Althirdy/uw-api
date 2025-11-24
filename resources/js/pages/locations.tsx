import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import AppLayout from '@/layouts/app-layout';
import { locations } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { paginated_T } from '@/types/cctv-location-types';
import { location_T, LocationCategory_T } from '@/types/location-types';
import { Head } from '@inertiajs/react';
import { List, Table } from 'lucide-react';
import { useState } from 'react';
import LocationCardView from './locations-comp/locations-card';
import CreateLocation from './locations-comp/locations-create';
import LocationActionTab from './locations-comp/locations-tab';
import LocationsTable from './locations-comp/locations-table';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Location Management',
        href: locations().url,
    },
];

type location_paginated_T = paginated_T<location_T>;

const locationCategory: LocationCategory_T[] = [
    { id: 1, name: 'School' },
    { id: 2, name: 'Hospital' },
    { id: 3, name: 'Market' },
    { id: 4, name: 'Park' },
    { id: 5, name: 'Government Office' },
];

export default function Locations({
    locationCategories = [],
    locations,
}: {
    locationCategories?: LocationCategory_T[];
    locations?: location_paginated_T;
}) {
    const [filteredLocations, setFilteredLocations] = useState<location_T[]>(
        locations?.data || [],
    );

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Locations" />
            <div className="space-y-4 p-4">
                <CreateLocation
                    locationCategory={
                        locationCategories.length > 0
                            ? locationCategories
                            : locationCategory
                    }
                />

                <Tabs defaultValue="table" className="w-full space-y-2">
                    <div className="flex flex-row gap-4">
                        <LocationActionTab
                            locations={locations!}
                            locationCategory={
                                locationCategories.length > 0
                                    ? locationCategories
                                    : locationCategory
                            }
                            setFilteredLocations={setFilteredLocations}
                        />
                        <TabsList className="h-12 w-24">
                            <TabsTrigger
                                value="table"
                                className="cursor-pointer"
                            >
                                <List className="h-8 w-8" />
                            </TabsTrigger>
                            <TabsTrigger
                                value="card"
                                className="cursor-pointer"
                            >
                                <Table className="h-4 w-4" />
                            </TabsTrigger>
                        </TabsList>
                    </div>

                    <TabsContent value="table" className="w-full">
                        <LocationsTable
                            locations={filteredLocations}
                            locationCategory={
                                locationCategories.length > 0
                                    ? locationCategories
                                    : locationCategory
                            }
                        />
                    </TabsContent>
                    <TabsContent value="card" className="w-full">
                        <LocationCardView
                            location={locations}
                            locationCategory={locationCategories}
                        />
                    </TabsContent>
                </Tabs>
            </div>
        </AppLayout>
    );
}
