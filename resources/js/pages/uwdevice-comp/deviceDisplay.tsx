import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { Archive, Cpu, ExternalLink, MapPin, SquarePen } from 'lucide-react';
import React from 'react';
import {
    cctv_T,
    location_T,
    paginated_T,
    uwDevice_T,
} from '../../types/cctv-location-types';
import ArchiveUWDevice from './archiveDevice';
import EditUWDevice from './editDevice';
import ViewUWDevice from './viewDevice';

interface UWDeviceDisplayProps {
    onEdit?: (device: uwDevice_T) => void;
    onDelete?: (device: uwDevice_T) => void;
    onViewReports?: (device: uwDevice_T) => void;
    devices: paginated_T<uwDevice_T>;
    locations?: location_T[];
    cctvDevices?: cctv_T[];
}

function UWDeviceDisplay({
    onEdit,
    onDelete,
    onViewReports,
    devices,
    locations = [],
    cctvDevices = [],
}: UWDeviceDisplayProps): React.JSX.Element {
    if (!devices || !devices.data) {
        return (
            <div className="p-4 text-center text-muted-foreground">
                No devices found
            </div>
        );
    }

    // Get status badge variant and colors - matching CCTV pattern
    const getStatusStyles = (status: string) => {
        switch (status.toLocaleUpperCase()) {
            case 'ACTIVE':
                return 'bg-green-700 rounded-full dark:bg-green-800';
            case 'MAINTENANCE':
                return 'bg-orange-100 rounded-full dark:bg-orange-700';
            case 'INACTIVE':
                return 'bg-gray-100 rounded-full dark:bg-zinc-600';
            default:
                return 'bg-gray-100 rounded-full dark:bg-zinc-600';
        }
    };

    return (
        <div className="space-y-4">
            {/* UW Device Cards Grid */}
            <div className="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                {devices.data.map((device) => (
                    <Card
                        key={device.id}
                        className="group relative overflow-hidden rounded-[var(--radius)] transition-all duration-200 hover:shadow-md"
                    >
                        <CardHeader className="flex flex-row items-start justify-between pb-3">
                            <div className="flex flex-row items-center gap-4">
                                <div className="h-fit w-fit rounded-lg bg-zinc-500 p-2">
                                    <Cpu className="h-5 w-auto" />
                                </div>
                                <div className="flex flex-col">
                                    <h3 className="truncate text-base font-semibold">
                                        {device.device_name}
                                    </h3>
                                    <div className="flex items-center gap-1 text-sm text-muted-foreground">
                                        <MapPin className="h-5 w-auto" />
                                        <span className="truncate">
                                            {device.location?.barangay ||
                                                (device.custom_address
                                                    ? 'Custom Location'
                                                    : 'No location')}
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div className="items-center">
                                {/* Status Badge */}
                                <div>
                                    <Badge
                                        className={`gap-1 text-sm capitalize ${getStatusStyles(device.status)}`}
                                    >
                                        {device.status}
                                    </Badge>
                                </div>
                            </div>
                        </CardHeader>

                        <CardContent className="space-y-4">
                            {/* Technical Details */}
                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <p className="text-muted-foreground">
                                        Category
                                    </p>
                                    <p className="font-medium">
                                        {device.location?.category_name ||
                                            (device.custom_address
                                                ? 'Custom'
                                                : 'N/A')}
                                    </p>
                                </div>
                            </div>
                            <div>
                                <p className="mb-1 text-muted-foreground">
                                    Location
                                </p>
                                {device.location ? (
                                    <>
                                        <p className="font-medium">
                                            {device.location.location_name}
                                        </p>
                                        <p className="text-muted-foreground">
                                            {device.location.landmark}
                                        </p>
                                    </>
                                ) : device.custom_address ? (
                                    <>
                                        <div className="mb-1 flex items-center gap-2">
                                            <p className="font-medium">
                                                {device.custom_address}
                                            </p>
                                            <Badge
                                                variant="outline"
                                                className="border-blue-200 bg-blue-50 text-xs text-blue-700 dark:border-blue-800 dark:bg-blue-950 dark:text-blue-300"
                                            >
                                                Custom
                                            </Badge>
                                        </div>
                                        {device.custom_latitude &&
                                            device.custom_longitude && (
                                                <p className="text-muted-foreground">
                                                    {Number(
                                                        device.custom_latitude,
                                                    ).toFixed(4)}
                                                    ,{' '}
                                                    {Number(
                                                        device.custom_longitude,
                                                    ).toFixed(4)}
                                                </p>
                                            )}
                                    </>
                                ) : (
                                    <p className="text-muted-foreground">
                                        No location assigned
                                    </p>
                                )}
                            </div>

                            {/* Action Buttons */}
                            <div className="flex items-center justify-end gap-2">
                                <Tooltip>
                                    <ViewUWDevice device={device}>
                                        <TooltipTrigger asChild>
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                className="cursor-pointer"
                                            >
                                                <ExternalLink className="h-4 w-4" />
                                            </Button>
                                        </TooltipTrigger>
                                    </ViewUWDevice>
                                    <TooltipContent>
                                        <p>View Device</p>
                                    </TooltipContent>
                                </Tooltip>
                                <Tooltip>
                                    <EditUWDevice
                                        location={locations}
                                        device={device}
                                        cctvDevices={cctvDevices}
                                    >
                                        <TooltipTrigger asChild>
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                className="cursor-pointer"
                                            >
                                                <SquarePen className="h-4 w-4" />
                                            </Button>
                                        </TooltipTrigger>
                                    </EditUWDevice>
                                    <TooltipContent>
                                        <p>Edit Device</p>
                                    </TooltipContent>
                                </Tooltip>
                                <Tooltip>
                                    <ArchiveUWDevice device={device}>
                                        <TooltipTrigger asChild>
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                className="cursor-pointer"
                                            >
                                                <Archive className="h-4 w-4 text-[var(--destructive)]" />
                                            </Button>
                                        </TooltipTrigger>
                                    </ArchiveUWDevice>
                                    <TooltipContent>
                                        <p>Archive Device</p>
                                    </TooltipContent>
                                </Tooltip>
                            </div>
                        </CardContent>
                    </Card>
                ))}
            </div>
        </div>
    );
}

export default UWDeviceDisplay;
