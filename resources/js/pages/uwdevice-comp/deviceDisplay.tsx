import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import { Activity, Cpu, MapPin, Settings, Wifi } from 'lucide-react';
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

    // Get status badge variant - matching CCTV pattern
    const getStatusVariant = (status: string) => {
        switch (status) {
            case 'active':
                return 'default';
            case 'inactive':
                return 'secondary';
            case 'maintenance':
                return 'destructive';
            default:
                return 'outline';
        }
    };

    // Get status icon - matching CCTV pattern
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
        <div className="space-y-6">
            {/* UW Device Cards Grid */}
            <div className="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                {devices.data.map((device) => (
                    <Card
                        key={device.id}
                        className="transition-shadow hover:shadow-md"
                    >
                        <CardHeader className="pb-3">
                            <div className="flex items-start justify-between">
                                <div className="flex items-start gap-3">
                                    <div className="rounded-lg bg-green-100 p-2">
                                        <Cpu className="h-5 w-5 text-green-600" />
                                    </div>
                                    <div className="min-w-0 flex-1">
                                        <h3 className="truncate text-base font-semibold">
                                            {device.device_name}
                                        </h3>
                                        <div className="mt-1 flex items-center gap-1 text-sm text-muted-foreground">
                                            <MapPin className="h-3 w-3" />
                                            <span className="truncate">
                                                {device.location?.barangay ||
                                                    (device.custom_address
                                                        ? 'Custom Location'
                                                        : 'No location')}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <Badge
                                    variant={getStatusVariant(device.status)}
                                    className="gap-1 capitalize"
                                >
                                    {getStatusIcon(device.status)}
                                    {device.status}
                                </Badge>
                            </div>
                        </CardHeader>

                        <CardContent className="space-y-4">
                            {/* Basic Details */}
                            <div className="grid grid-cols-2 gap-4 text-sm">
                                <div>
                                    <p className="text-muted-foreground">
                                        Status
                                    </p>
                                    <p className="font-medium capitalize">
                                        {device.status}
                                    </p>
                                </div>
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

                            {/* Location Details */}
                            <div className="border-t pt-2">
                                <p className="mb-1 text-xs text-muted-foreground">
                                    Location
                                </p>
                                {device.location ? (
                                    <>
                                        <p className="text-sm font-medium">
                                            {device.location.location_name}
                                        </p>
                                        <p className="text-xs text-muted-foreground">
                                            {device.location.landmark}
                                        </p>
                                    </>
                                ) : device.custom_address ? (
                                    <>
                                        <div className="mb-1 flex items-center gap-1">
                                            <p className="text-sm font-medium">
                                                {device.custom_address}
                                            </p>
                                            <Badge
                                                variant="outline"
                                                className="border-blue-200 bg-blue-50 text-xs text-blue-700"
                                            >
                                                Custom
                                            </Badge>
                                        </div>
                                        {device.custom_latitude &&
                                            device.custom_longitude && (
                                                <p className="text-xs text-muted-foreground">
                                                    {Number(
                                                        device.custom_latitude,
                                                    ).toFixed(2)}
                                                    ,{' '}
                                                    {Number(
                                                        device.custom_longitude,
                                                    ).toFixed(2)}
                                                </p>
                                            )}
                                    </>
                                ) : (
                                    <p className="text-sm text-muted-foreground">
                                        No location assigned
                                    </p>
                                )}
                            </div>

                            {/* Action Buttons */}
                            <div className="flex items-center justify-end gap-1 pt-2">
                                <ViewUWDevice device={device} />
                                <EditUWDevice
                                    location={locations}
                                    device={device}
                                    cctvDevices={cctvDevices}
                                />
                                <ArchiveUWDevice device={device} />
                            </div>
                        </CardContent>
                    </Card>
                ))}
            </div>

            {/* Simple Pagination */}
            {devices.links && devices.links.length > 3 && (
                <div className="flex justify-center gap-2">
                    {devices.prev_page_url && (
                        <Button variant="outline" size="sm">
                            Previous
                        </Button>
                    )}
                    <span className="self-center text-sm text-muted-foreground">
                        {devices.data.length} devices shown
                    </span>
                    {devices.next_page_url && (
                        <Button variant="outline" size="sm">
                            Next
                        </Button>
                    )}
                </div>
            )}
        </div>
    );
}

export default UWDeviceDisplay;
