import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { cctv_T, uwDevice_T } from '@/types/cctv-location-types';
import { Camera, Cpu, ExternalLink, MapPin, Zap } from 'lucide-react';
import React, { useState } from 'react';

interface ViewUWDeviceProps {
    device: uwDevice_T;
    children?: React.ReactNode;
}

function ViewUWDevice({
    device,
    children,
}: ViewUWDeviceProps): React.JSX.Element {
    const [dialogOpen, setDialogOpen] = useState(false);

    // Get status badge variant - matching CCTV pattern
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

    return (
        <Dialog open={dialogOpen} onOpenChange={setDialogOpen}>
            <DialogTrigger asChild>
                {children || (
                    <div className="cursor-pointer rounded-full p-2 hover:bg-secondary/20">
                        <ExternalLink size={20} />
                    </div>
                )}
            </DialogTrigger>
            <DialogContent
                className="flex max-h-[90vh] max-w-none flex-col overflow-hidden p-0 sm:max-w-2xl"
                showCloseButton={false}
            >
                {/* Fixed Header */}
                <DialogHeader className="flex-shrink-0 px-6 pt-6 pb-4">
                    <DialogTitle className="flex items-center gap-2">
                        <Cpu className="h-5 w-5 text-green-600" />
                        Device Details
                    </DialogTitle>
                    <DialogDescription>
                        View details and configuration of {device.device_name}
                    </DialogDescription>
                </DialogHeader>

                {/* Scrollable Content */}
                <div className="flex-1 overflow-y-auto px-6 py-4">
                    <div className="space-y-6">
                        {/* Device Name Section */}
                        <div className="space-y-2">
                            <Label className="text-sm font-medium text-muted-foreground">
                                Device Name
                            </Label>
                            <div className="rounded-lg bg-muted/50 p-3">
                                <p className="font-medium">
                                    {device.device_name}
                                </p>
                            </div>
                        </div>

                        {/* Status Section */}
                        <div className="space-y-2">
                            <Label className="text-sm font-medium text-muted-foreground">
                                Status
                            </Label>
                            <div className="flex items-center gap-2">
                                <Badge
                                    className={`capitalize ${getStatusStyles(device.status)}`}
                                >
                                    {device.status}
                                </Badge>
                            </div>
                        </div>
                        {/* Location Assignment Section */}
                        <div className="space-y-3">
                            <Label className="text-sm font-medium text-muted-foreground">
                                Location Assignment
                            </Label>

                            {/* Predefined Location */}
                            {device.location && !device.custom_address && (
                                <div className="rounded-lg bg-muted/50 p-4">
                                    <div className="flex items-start gap-3">
                                        <MapPin className="mt-1 h-4 w-4 text-muted-foreground" />
                                        <div className="space-y-1">
                                            <div className="flex items-center gap-2">
                                                <p className="font-medium">
                                                    {
                                                        device.location
                                                            .location_name
                                                    }
                                                </p>
                                                <Badge
                                                    variant="outline"
                                                    className="text-xs"
                                                >
                                                    Predefined
                                                </Badge>
                                            </div>
                                            {device.location?.category_name && (
                                                <Badge
                                                    variant="outline"
                                                    className="text-xs"
                                                >
                                                    {
                                                        device.location
                                                            .category_name
                                                    }
                                                </Badge>
                                            )}
                                            <p className="text-sm text-muted-foreground">
                                                {device.location?.landmark}
                                            </p>
                                            <p className="text-xs text-muted-foreground">
                                                {device.location?.barangay}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            )}

                            {/* Custom Location */}
                            {device.custom_address && (
                                <div className="rounded-lg bg-muted/50 p-4">
                                    <div className="flex items-start gap-3">
                                        <MapPin className="mt-1 h-4 w-4 text-muted-foreground" />
                                        <div className="space-y-1">
                                            <div className="flex items-center gap-2">
                                                <p className="font-medium">
                                                    Custom Location
                                                </p>
                                                <Badge
                                                    variant="outline"
                                                    className="border-blue-200 bg-blue-50 text-xs text-blue-700"
                                                >
                                                    Custom
                                                </Badge>
                                            </div>
                                            <p className="text-sm text-muted-foreground">
                                                {device.custom_address}
                                            </p>
                                            <div className="mt-2 grid grid-cols-2 gap-4">
                                                <div>
                                                    <p className="text-xs font-medium text-muted-foreground">
                                                        Latitude
                                                    </p>
                                                    <p className="text-xs">
                                                        {Number(
                                                            device.custom_latitude,
                                                        ).toFixed(2)}
                                                    </p>
                                                </div>
                                                <div>
                                                    <p className="text-xs font-medium text-muted-foreground">
                                                        Longitude
                                                    </p>
                                                    <p className="text-xs">
                                                        {Number(
                                                            device.custom_longitude,
                                                        ).toFixed(2)}
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            )}

                            {/* No Location */}
                            {!device.location && !device.custom_address && (
                                <div className="rounded-lg border-2 border-dashed border-muted p-4 text-center">
                                    <MapPin className="mx-auto mb-2 h-8 w-8 text-muted-foreground" />
                                    <p className="text-sm text-muted-foreground">
                                        No location assigned
                                    </p>
                                </div>
                            )}
                        </div>

                        {/* Linked CCTV Cameras Section */}
                        <div className="space-y-3">
                            <Label className="text-sm font-medium text-muted-foreground">
                                Linked CCTV Cameras
                            </Label>
                            {device.cctv_cameras &&
                            device.cctv_cameras.length > 0 ? (
                                <div className="space-y-3">
                                    {device.cctv_cameras.map(
                                        (camera: cctv_T, index: number) => (
                                            <div
                                                key={index}
                                                className="rounded-lg border bg-muted/20 p-3"
                                            >
                                                <div className="flex items-center gap-3">
                                                    <Camera className="h-4 w-4 text-blue-500" />
                                                    <div className="flex-1">
                                                        <div className="text-sm font-medium">
                                                            {camera.device_name ||
                                                                `Camera ${index + 1}`}
                                                        </div>
                                                        <div className="text-xs text-muted-foreground">
                                                            {camera.location
                                                                ?.location_name ||
                                                                device.location
                                                                    ?.location_name}
                                                        </div>
                                                    </div>
                                                    <Badge
                                                        className={`capitalize ${getStatusStyles(device.status)}`}
                                                    >
                                                        {camera.status}
                                                    </Badge>
                                                </div>
                                            </div>
                                        ),
                                    )}
                                </div>
                            ) : (
                                <div className="rounded-lg border-2 border-dashed border-muted p-4 text-center">
                                    <Camera className="mx-auto mb-2 h-8 w-8 text-muted-foreground" />
                                    <p className="text-sm text-muted-foreground">
                                        No cameras linked
                                    </p>
                                    <p className="text-xs text-muted-foreground">
                                        This device operates independently
                                    </p>
                                </div>
                            )}
                        </div>

                        {/* Device Information Summary */}
                        <div className="space-y-3">
                            <Label className="text-sm font-medium text-muted-foreground">
                                Device Summary
                            </Label>
                            <div className="grid grid-cols-2 gap-4">
                                <div className="rounded-lg bg-muted/50 p-3">
                                    <div className="mb-1 flex items-center gap-2">
                                        <Zap className="h-3 w-3 text-green-500" />
                                        <span className="text-xs font-medium">
                                            Status
                                        </span>
                                    </div>
                                    <p className="text-sm font-medium capitalize">
                                        {device.status}
                                    </p>
                                </div>
                                <div className="rounded-lg bg-muted/50 p-3">
                                    <div className="mb-1 flex items-center gap-2">
                                        <Camera className="h-3 w-3 text-blue-500" />
                                        <span className="text-xs font-medium">
                                            Cameras
                                        </span>
                                    </div>
                                    <p className="text-sm font-medium">
                                        {device.cctv_cameras?.length || 0}{' '}
                                        linked
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Fixed Footer */}
                <DialogFooter className="flex-shrink-0 px-6 py-4">
                    <div className="flex w-full gap-2">
                        <DialogClose asChild>
                            <Button variant="outline" className="flex-1">
                                Close
                            </Button>
                        </DialogClose>
                    </div>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}

export default ViewUWDevice;
