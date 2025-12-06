import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';
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

const getCategoryColor = (categoryName: string) => {
    const colorMap: { [key: string]: string } = {
        School: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
        Hospital: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
        Market: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
        Park: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-200',
        'Government Office':
            'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200',
        Historic:
            'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200',
        Religious:
            'bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200',
        Commercial:
            'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200',
        Residential:
            'bg-cyan-100 text-cyan-800 dark:bg-cyan-900 dark:text-cyan-200',
        Transportation:
            'bg-teal-100 text-teal-800 dark:bg-teal-900 dark:text-teal-200',
    };
    return (
        colorMap[categoryName] ||
        'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-200'
    );
};

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
                    variant="default"
                    className={`inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium ${getCategoryColor(categoryName)}`}
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
                    <span>{location.cctv_count || 0} camera/s</span>
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
                    <Tooltip>
                        <ViewLocation location={location}>
                            <TooltipTrigger asChild>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    className="cursor-pointer"
                                    onClick={(e) => e.stopPropagation()}
                                >
                                    <ExternalLink className="h-4 w-4" />
                                </Button>
                            </TooltipTrigger>
                        </ViewLocation>
                        <TooltipContent>
                            <p>View Details</p>
                        </TooltipContent>
                    </Tooltip>
                    <Tooltip>
                        <EditLocation
                            location={location}
                            locationCategory={locationCategory}
                        >
                            <TooltipTrigger asChild>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    className="cursor-pointer"
                                    onClick={(e) => e.stopPropagation()}
                                >
                                    <SquarePen className="h-4 w-4" />
                                </Button>
                            </TooltipTrigger>
                        </EditLocation>
                        <TooltipContent>
                            <p>Edit Location</p>
                        </TooltipContent>
                    </Tooltip>
                    <Tooltip>
                        <DeleteLocation location={location}>
                            <TooltipTrigger asChild>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    className="cursor-pointer"
                                    onClick={(e) => e.stopPropagation()}
                                >
                                    <Trash2 className="h-4 w-4 text-[var(--destructive)]" />
                                </Button>
                            </TooltipTrigger>
                        </DeleteLocation>
                        <TooltipContent>
                            <p>Delete Location</p>
                        </TooltipContent>
                    </Tooltip>
                </div>
            );
        },
    },
];
