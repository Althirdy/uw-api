import { MapModal } from '@/components/map-modal';
import { Button } from '@/components/ui/button';
import {
    Command,
    CommandEmpty,
    CommandGroup,
    CommandInput,
    CommandItem,
    CommandList,
} from '@/components/ui/command';
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
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import { toast } from '@/components/use-toast';
import { cn } from '@/lib/utils';
import { LocationCategory_T, location_T } from '@/types/location-types';
import { useForm } from '@inertiajs/react';
import { Check, ChevronsUpDown, MoveLeft, Save } from 'lucide-react';
import React, { useState } from 'react';

const barangay = [
    { id: 1, name: 'Brgy 176 - A' },
    { id: 2, name: 'Brgy 176 - B' },
    { id: 3, name: 'Brgy 176 - C' },
    { id: 4, name: 'Brgy 176 - D' },
    { id: 5, name: 'Brgy 176 - E' },
    { id: 6, name: 'Brgy 176 - F' },
];

type Barangay = {
    id: number;
    name: string;
};

type SelectionState = {
    value: Barangay | LocationCategory_T | null;
    searchQuery: string;
    open: boolean;
};

type EditLocationProps = {
    location: location_T;
    locationCategory?: LocationCategory_T[];
    children: React.ReactNode;
};

function EditLocation({
    location,
    locationCategory = [],
    children,
}: EditLocationProps) {
    const [isOpen, setIsOpen] = useState(false);

    // Find initial barangay and category from the location data
    const initialBarangay =
        barangay.find((b) => b.name === location.barangay) || null;

    // Inertia form handling
    const { data, setData, put, processing, errors, reset } = useForm({
        location_name: location.location_name,
        landmark: location.landmark,
        barangay: location.barangay,
        latitude: location.latitude,
        longitude: location.longitude,
        description: location.description || '',
    });

    // Combined states for both selectors
    const [barangayState, setBarangayState] = useState<SelectionState>({
        value: initialBarangay,
        searchQuery: '',
        open: false,
    });

    const [coordinates, setCoordinates] = useState({
        latitude: location.latitude,
        longitude: location.longitude,
    });

    // Filtered lists based on search
    const filteredBarangay = barangayState.searchQuery.trim()
        ? barangay.filter((b) =>
              b.name
                  .toLowerCase()
                  .includes(barangayState.searchQuery.toLowerCase()),
          )
        : barangay;

    // Handlers for barangay selection
    const handleBarangaySelect = (selected: Barangay | null) => {
        setBarangayState({
            value: selected,
            searchQuery: '',
            open: false,
        });
        setData('barangay', selected ? selected.name : '');
    };

    const handleLocationSelect = (location: { lat: number; lng: number }) => {
        const coords = {
            latitude: location.lat.toString(),
            longitude: location.lng.toString(),
        };
        setCoordinates(coords);
        setData('latitude', coords.latitude);
        setData('longitude', coords.longitude);
    };

    const onSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        put(`/locations/${location.id}`, {
            onSuccess: () => {
                setIsOpen(false);
                toast({
                    title: 'Success!',
                    description: 'Location updated successfully.',
                    variant: 'default',
                });
                reset();
            },
            onError: () => {
                // Keep the dialog open to show errors
                toast({
                    title: 'Error',
                    description:
                        'Failed to update location. Please check your inputs and try again.',
                    variant: 'destructive',
                });
                setIsOpen(true);
            },
            preserveScroll: true,
        });
    };

    const handleCancel = () => {
        // Reset form data to original values
        reset();
        setData({
            location_name: location.location_name,
            landmark: location.landmark,
            barangay: location.barangay,
            latitude: location.latitude,
            longitude: location.longitude,
            description: location.description || '',
        });

        // Reset selectors to original values
        setBarangayState({
            value: initialBarangay,
            searchQuery: '',
            open: false,
        });

        setCoordinates({
            latitude: location.latitude,
            longitude: location.longitude,
        });
    };

    return (
        <Dialog open={isOpen} onOpenChange={setIsOpen}>
            <DialogTrigger asChild>{children}</DialogTrigger>
            <DialogContent
                className="flex max-h-[90vh] max-w-none flex-col overflow-hidden p-0 sm:max-w-2xl"
                showCloseButton={false}
            >
                <form
                    onSubmit={onSubmit}
                    className="flex h-full flex-col overflow-hidden"
                >
                    <DialogHeader className="flex-shrink-0 px-6 pt-6 pb-4">
                        <DialogTitle className="flex items-center gap-2">
                            Edit Location
                        </DialogTitle>
                        <DialogDescription>
                            Update the location details and coordinates.
                        </DialogDescription>
                    </DialogHeader>
                    <div className="flex-1 overflow-y-auto px-6 py-4">
                        <div className="grid auto-rows-min gap-6">
                            <div className="grid gap-3">
                                <div>
                                    <Label htmlFor="edit-location-name">
                                        Location Name
                                    </Label>
                                    <div className="relative">
                                        <Input
                                            id="edit-location-name"
                                            placeholder="Ph-1 Palengke"
                                            value={data.location_name}
                                            onChange={(e) =>
                                                setData(
                                                    'location_name',
                                                    e.target.value,
                                                )
                                            }
                                            className={
                                                errors.location_name
                                                    ? 'border-red-500 focus:ring-red-500'
                                                    : ''
                                            }
                                        />
                                        {errors.location_name && (
                                            <span className="absolute -bottom-5 left-0 text-xs text-red-500">
                                                {errors.location_name}
                                            </span>
                                        )}
                                    </div>
                                </div>
                                <div>
                                    <Label htmlFor="edit-location-landmark">
                                        Near Landmark
                                    </Label>
                                    <div className="relative">
                                        <Input
                                            id="edit-location-landmark"
                                            placeholder="Enter nearby landmark"
                                            value={data.landmark}
                                            onChange={(e) =>
                                                setData(
                                                    'landmark',
                                                    e.target.value,
                                                )
                                            }
                                            className={
                                                errors.landmark
                                                    ? 'border-red-500 focus:ring-red-500'
                                                    : ''
                                            }
                                        />
                                        {errors.landmark && (
                                            <span className="absolute -bottom-5 left-0 text-xs text-red-500">
                                                {errors.landmark}
                                            </span>
                                        )}
                                    </div>
                                </div>

                                {/* Barangay Selector */}
                                <div className="relative">
                                    <Popover
                                        open={barangayState.open}
                                        onOpenChange={(open: boolean) =>
                                            setBarangayState((prev) => ({
                                                ...prev,
                                                open,
                                            }))
                                        }
                                    >
                                        <PopoverTrigger asChild>
                                            <div>
                                                <Label className="mb-2">
                                                    Barangay
                                                </Label>
                                                <Button
                                                    type="button"
                                                    variant="outline"
                                                    role="combobox"
                                                    aria-expanded={
                                                        barangayState.open
                                                    }
                                                    className="w-full justify-between"
                                                >
                                                    {barangayState.value
                                                        ? (
                                                              barangayState.value as Barangay
                                                          ).name
                                                        : 'Select Barangay'}
                                                    <ChevronsUpDown className="ml-2 h-4 w-4 shrink-0 opacity-50" />
                                                </Button>
                                            </div>
                                        </PopoverTrigger>
                                        <PopoverContent className="p-0">
                                            <Command>
                                                <CommandInput
                                                    value={
                                                        barangayState.searchQuery
                                                    }
                                                    onValueChange={(search) =>
                                                        setBarangayState(
                                                            (prev) => ({
                                                                ...prev,
                                                                searchQuery:
                                                                    search,
                                                            }),
                                                        )
                                                    }
                                                    placeholder="Search barangay..."
                                                    className="h-9"
                                                />
                                                <CommandList>
                                                    <CommandEmpty>
                                                        No barangay found.
                                                    </CommandEmpty>
                                                    <CommandGroup>
                                                        {filteredBarangay.map(
                                                            (brgy) => (
                                                                <CommandItem
                                                                    key={
                                                                        brgy.id
                                                                    }
                                                                    value={
                                                                        brgy.name
                                                                    }
                                                                    onSelect={() =>
                                                                        handleBarangaySelect(
                                                                            (
                                                                                barangayState.value as Barangay
                                                                            )
                                                                                ?.id ===
                                                                                brgy.id
                                                                                ? null
                                                                                : brgy,
                                                                        )
                                                                    }
                                                                >
                                                                    {brgy.name}
                                                                    <Check
                                                                        className={cn(
                                                                            'ml-auto',
                                                                            (
                                                                                barangayState.value as Barangay
                                                                            )
                                                                                ?.id ===
                                                                                brgy.id
                                                                                ? 'opacity-100'
                                                                                : 'opacity-0',
                                                                        )}
                                                                    />
                                                                </CommandItem>
                                                            ),
                                                        )}
                                                    </CommandGroup>
                                                </CommandList>
                                            </Command>
                                        </PopoverContent>
                                    </Popover>
                                    {errors.barangay && (
                                        <p className="mt-1 text-sm text-red-500">
                                            {errors.barangay}
                                        </p>
                                    )}
                                </div>

                                {/* GPS Coordinates */}
                                <div className="space-y-2">
                                    <Label>GPS Coordinates</Label>
                                    <MapModal
                                        onLocationSelect={handleLocationSelect}
                                        coordinates={coordinates}
                                    />

                                    {/* Map Preview */}
                                    {coordinates.latitude &&
                                        coordinates.longitude && (
                                            <div className="mt-3">
                                                <Label className="mb-2 block">
                                                    Location Preview
                                                </Label>
                                                <div className="h-48 w-full overflow-hidden rounded-[var(--radius)] border">
                                                    <iframe
                                                        width="100%"
                                                        height="100%"
                                                        frameBorder="0"
                                                        style={{ border: 0 }}
                                                        src={`https://www.openstreetmap.org/export/embed.html?bbox=${Number(coordinates.longitude) - 0.01},${Number(coordinates.latitude) - 0.01},${Number(coordinates.longitude) + 0.01},${Number(coordinates.latitude) + 0.01}&layer=mapnik&marker=${coordinates.latitude},${coordinates.longitude}`}
                                                        loading="lazy"
                                                        referrerPolicy="no-referrer-when-downgrade"
                                                    ></iframe>
                                                </div>
                                            </div>
                                        )}

                                    <div className="grid grid-cols-2 gap-2">
                                        <div className="relative">
                                            <Label htmlFor="edit-latitude">
                                                Latitude
                                            </Label>
                                            <Input
                                                id="edit-latitude"
                                                value={coordinates.latitude}
                                                disabled
                                                className="bg-muted"
                                            />
                                        </div>
                                        <div className="relative">
                                            <Label htmlFor="edit-longitude">
                                                Longitude
                                            </Label>
                                            <Input
                                                id="edit-longitude"
                                                value={coordinates.longitude}
                                                disabled
                                                className="bg-muted"
                                            />
                                        </div>
                                    </div>
                                    {errors.latitude && (
                                        <p className="mt-1 text-sm text-red-500">
                                            {errors.latitude}
                                        </p>
                                    )}
                                    {errors.longitude && (
                                        <p className="mt-1 text-sm text-red-500">
                                            {errors.longitude}
                                        </p>
                                    )}
                                </div>

                                {/* Description */}
                                <div>
                                    <Label htmlFor="edit-description">
                                        Description
                                    </Label>
                                    <div className="relative">
                                        <Textarea
                                            id="edit-description"
                                            className={
                                                'resize-none ' +
                                                (errors.description
                                                    ? 'border-red-500 focus:ring-red-500'
                                                    : '')
                                            }
                                            placeholder="e.g High traffic during rush hour"
                                            value={data.description}
                                            onChange={(e) =>
                                                setData(
                                                    'description',
                                                    e.target.value,
                                                )
                                            }
                                            rows={3}
                                        />
                                        {errors.description && (
                                            <span className="absolute -bottom-5 left-0 text-xs text-red-500">
                                                {errors.description}
                                            </span>
                                        )}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <DialogFooter className="flex-shrink-0 px-6 py-4">
                        <div className="flex w-full gap-2">
                            <DialogClose asChild>
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={handleCancel}
                                    disabled={processing}
                                    className="flex-1"
                                >
                                    <MoveLeft className="inline h-4 w-4" />
                                    Close
                                </Button>
                            </DialogClose>
                            <Button
                                type="submit"
                                disabled={processing}
                                className="flex-2"
                            >
                                {processing ? (
                                    <Spinner className="inline h-4 w-4" />
                                ) : (
                                    <Save className="inline h-4 w-4" />
                                )}
                                {processing ? 'Saving...' : ' Save Changes'}
                            </Button>
                        </div>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

export default EditLocation;
