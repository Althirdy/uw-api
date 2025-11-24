import { ColumnDef } from '@tanstack/react-table';
import {
    ArrowUpDown,
    Cctv,
    ExternalLink,
    SquarePen,
    Trash2,
} from 'lucide-react';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';

import { location_T, LocationCategory_T } from '@/types/location-types';
import DeleteLocation from './locations-archive';
import EditLocation from './locations-edit';
import ViewLocation from './locations-view';

export const columns = (
    locationCategory: LocationCategory_T[],
): ColumnDef<location_T>[] => [
    {
        accessorKey: 'id',
        header: ({ column }) => {
            return (
                <Button
                    variant="ghost"
                    onClick={() =>
                        column.toggleSorting(column.getIsSorted() === 'asc')
                    }
                    className="cursor-pointer transition-colors duration-200 ease-in-out"
                >
                    Location ID
                    <ArrowUpDown className="ml-2 h-4 w-4" />
                </Button>
            );
        },
        cell: ({ row }) => <div>#{row.getValue('id')}</div>,
    },
    {
        accessorKey: 'location_name',
        header: ({ column }) => {
            return (
                <Button
                    variant="ghost"
                    onClick={() =>
                        column.toggleSorting(column.getIsSorted() === 'asc')
                    }
                    className="cursor-pointer transition-colors duration-200 ease-in-out"
                >
                    Location Name
                    <ArrowUpDown className="ml-2 h-4 w-4" />
                </Button>
            );
        },
        cell: ({ row }) => {
            const location = row.original;
            return (
                <div>
                    <div className="font-medium">{location.location_name}</div>
                    {location.landmark && (
                        <div className="text-xs text-muted-foreground">
                            {location.landmark}
                        </div>
                    )}
                </div>
            );
        },
    },
    {
        accessorKey: 'location_category.name',
        header: 'Category',
        cell: ({ row }) => {
            const location = row.original;
            const categoryName =
                location.location_category?.name ||
                location.category_name ||
                'N/A';

            return (
                <Badge
                    variant="secondary"
                    className="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium"
                >
                    {categoryName}
                </Badge>
            );
        },
    },
    {
        accessorKey: 'barangay',
        header: 'Barangay',
        cell: ({ row }) => <div>{row.getValue('barangay')}</div>,
    },
    {
        id: 'cctv_count',
        header: 'CCTV Cameras',
        cell: ({ row }) => {
            const location = row.original;
            return (
                <div className="flex items-center justify-center gap-2">
                    <Cctv className="h-4 w-4 text-muted-foreground" />
                    <span>{location.cctv_count || 0}</span>
                </div>
            );
        },
    },
    {
        id: 'coordinates',
        header: 'Coordinates',
        cell: ({ row }) => {
            const location = row.original;
            return (
                <div className="text-xs">
                    {Number(location.latitude).toFixed(4)},{' '}
                    {Number(location.longitude).toFixed(4)}
                </div>
            );
        },
    },
    {
        id: 'actions',
        header: 'Actions',
        enableHiding: false,
        cell: ({ row }) => {
            const location = row.original;

            return (
                <div className="flex justify-center gap-2">
                    <ViewLocation location={location}>
                        <Button
                            variant="outline"
                            size="sm"
                            className="cursor-pointer"
                            onClick={(e) => e.stopPropagation()}
                        >
                            <ExternalLink className="h-4 w-4" />
                        </Button>
                    </ViewLocation>
                    <EditLocation
                        location={location}
                        locationCategory={locationCategory}
                    >
                        <Button
                            variant="outline"
                            size="sm"
                            className="cursor-pointer"
                            onClick={(e) => e.stopPropagation()}
                        >
                            <SquarePen className="h-4 w-4" />
                        </Button>
                    </EditLocation>
                    <DeleteLocation location={location}>
                        <Button
                            variant="outline"
                            size="sm"
                            className="cursor-pointer"
                            onClick={(e) => e.stopPropagation()}
                        >
                            <Trash2 className="h-4 w-4 text-[var(--destructive)]" />
                        </Button>
                    </DeleteLocation>
                </div>
            );
        },
    },
];
