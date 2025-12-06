import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import {
    Pagination,
    PaginationContent,
    PaginationEllipsis,
    PaginationItem,
    PaginationLink,
    PaginationNext,
    PaginationPrevious,
} from '@/components/ui/pagination';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';

import {
    Activity,
    Archive,
    Camera,
    MapPin,
    Settings,
    SquarePen,
    Wifi,
} from 'lucide-react';
import { useState } from 'react';
import {
    cctv_T,
    location_T,
    paginated_T,
} from '../../types/cctv-location-types';
import EditCCTVDevice from './EditCCTV';
import ArchiveCCTV from './archiveCCTV';

interface CCTVDisplayProps {
    onEdit?: (device: any) => void;
    onDelete?: (device: any) => void;
    onViewStream?: (device: any) => void;
    devices: paginated_T<cctv_T>;
    locations: location_T[];
}

function CCTVDisplay({
    onEdit,
    onDelete,
    onViewStream,
    devices,
    locations = [],
}: CCTVDisplayProps) {
    const [selectedDevices, setSelectedDevices] = useState<number[]>([]);

    // Handle individual device selection
    const handleDeviceSelect = (deviceId: number, checked: boolean) => {
        if (checked) {
            setSelectedDevices((prev) => [...prev, deviceId]);
        } else {
            setSelectedDevices((prev) => prev.filter((id) => id !== deviceId));
        }
    };

    // Get status badge variant and colors
    const getStatusStyles = (status: string) => {
        switch (status.toLocaleUpperCase()) {
            case 'ACTIVE':
                return 'bg-green-700 rounded-full  dark:bg-green-800 dark:';
            case 'MAINTENANCE':
                return 'bg-orange-100 rounded-full dark:bg-orange-700 ';
            case 'INACTIVE':
                return 'bg-gray-100 rounded-full  dark:bg-zinc-600 ';
            default:
                return 'bg-gray-100 rounded-full  dark:bg-zinc-600 ';
        }
    };

    // Get status icon
    const getStatusIcon = (status: string) => {
        switch (status) {
            case 'active':
                return <Activity className="h-3 w-3" />;
            case 'inactive':
                return <Wifi className="h-3 w-3" />;
            case 'maintenance':
                return <Settings className="h-3 w-3" />;
            default:
                return null;
        }
    };

    return (
        <div className="space-y-4">
            {/* CCTV Cards Grid */}
            <div className="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                {devices?.data.map((device) => (
                    <Card
                        key={device.id}
                        className="group relative overflow-hidden rounded-[var(--radius)] transition-all duration-200 hover:shadow-md"
                    >
                        <CardHeader className="flex flex-row items-start justify-between pb-3">
                            <div className="flex flex-row items-center gap-4">
                                <div className="h-fit w-fit rounded-lg bg-zinc-500 p-2">
                                    <Camera className="h-5 w-auto" />
                                </div>
                                <div className="flex flex-col">
                                    <h3 className="truncate text-base font-semibold">
                                        {device.device_name}
                                    </h3>
                                    <div className="flex items-center gap-1 text-sm text-muted-foreground">
                                        <MapPin className="h-5 w-auto" />
                                        <span className="truncate">
                                            {device.location.barangay}
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
                                        {getStatusIcon(device.status)}
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
                                        Resolution
                                    </p>
                                    <p className="font-medium">
                                        {device.resolution}
                                    </p>
                                </div>
                                <div>
                                    <p className="text-muted-foreground">FPS</p>
                                    <p className="font-medium">{device.fps}</p>
                                </div>
                                <div>
                                    <p className="text-muted-foreground">
                                        Brand
                                    </p>
                                    <p className="font-medium">
                                        {device.brand}
                                    </p>
                                </div>
                                <div>
                                    <p className="text-muted-foreground">
                                        Category
                                    </p>
                                    <p className="font-medium">
                                        {device.location.category_name}
                                    </p>
                                </div>
                            </div>

                            {/* Location Details */}
                            <div>
                                <p className="mb-1 text-muted-foreground">
                                    Location
                                </p>
                                <p className="font-medium">
                                    {device.location.location_name}
                                </p>
                                <p className="text-muted-foreground">
                                    {device.location.landmark}
                                </p>
                            </div>

                            {/* Action Buttons */}
                            <div className="flex items-center justify-end gap-2 pt-2">
                                <Tooltip>
                                    <EditCCTVDevice
                                        location={locations}
                                        cctv={device}
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
                                    </EditCCTVDevice>
                                    <TooltipContent>
                                        <p>Edit CCTV</p>
                                    </TooltipContent>
                                </Tooltip>
                                <Tooltip>
                                    <ArchiveCCTV cctv={device}>
                                        <TooltipTrigger asChild>
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                className="cursor-pointer"
                                            >
                                                <Archive className="h-4 w-4 text-[var(--destructive)]" />
                                            </Button>
                                        </TooltipTrigger>
                                    </ArchiveCCTV>
                                    <TooltipContent>
                                        <p>Archive CCTV</p>
                                    </TooltipContent>
                                </Tooltip>
                            </div>
                        </CardContent>
                    </Card>
                ))}
            </div>

            {/* Pagination Controls */}
            {devices && devices.links && (
                <Pagination className="flex justify-end">
                    <PaginationContent>
                        <PaginationItem>
                            <PaginationPrevious
                                href={devices.prev_page_url || '#'}
                            />
                        </PaginationItem>
                        {devices.links.map((link, index) => {
                            if (link.url !== null) {
                                return (
                                    <PaginationItem key={index}>
                                        <PaginationLink
                                            isActive={link.active}
                                            href={link.url || '#'}
                                        >
                                            {link.label}
                                        </PaginationLink>
                                    </PaginationItem>
                                );
                            }
                        })}
                        <PaginationItem>
                            <PaginationEllipsis />
                        </PaginationItem>
                        <PaginationItem>
                            <PaginationNext
                                href={devices.next_page_url || '#'}
                            />
                        </PaginationItem>
                    </PaginationContent>
                </Pagination>
            )}
        </div>
    );
}

export default CCTVDisplay;
