import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import AppLayout from '@/layouts/app-layout';
import { locations } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { location_T } from '@/types/location-types';
import { Head } from '@inertiajs/react';
import { List, Table } from 'lucide-react';
import { useState } from 'react';
import LocationCardView from './locations-comp/locations-card';
import CreateLocation from './locations-comp/locations-create';
import LocationActionTab from './locations-comp/locations-tab';
import LocationsTable from './locations-comp/locations-table';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Locations',
        href: locations().url,
    },
];

export default function Locations({
    locations = [],
    packages = [],
}: {
    locations?: location_T[];
    packages?: { id: number; name: string }[];
}) {
    const [filteredLocations, setFilteredLocations] = useState<location_T[]>(
        locations || [],
    );

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Locations" />
            <div className="space-y-4 p-4">
                <CreateLocation packages={packages} />

                <Tabs defaultValue="card" className="w-full space-y-2">
                    <div className="flex flex-row gap-4">
                        <LocationActionTab
                            locations={locations!}
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
                        <LocationsTable locations={filteredLocations} />
                    </TabsContent>
                    <TabsContent value="card" className="w-full">
                        <LocationCardView locations={filteredLocations} />
                    </TabsContent>
                </Tabs>
            </div>
        </AppLayout>
    );
}
