import { MapModal } from '@/components/map-modal';
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
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { ScrollArea } from '@/components/ui/scroll-area';
import {
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { toast } from '@/components/use-toast';
import { LocationCategory_T } from '@/types/location-types';
import { useForm } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import React, { useState } from 'react';

const barangay = [{ id: 5, name: 'Brgy 176 - E' }];

type Barangay = {
    id: number;
    name: string;
};

type Package = {
    id: number;
    name: string;
};

type SelectionState = {
    value: Barangay | LocationCategory_T | Package | null;
    open: boolean;
};

function CreateLocation({
    locationCategory = [],
    packages = [],
}: {
    locationCategory?: LocationCategory_T[];
    packages?: Package[];
}) {
    // Dialog control state
    const [dialogOpen, setDialogOpen] = useState(false);

    // Track if form has been submitted to control error display

    // Inertia form handling
    const { data, setData, post, processing, errors, reset } = useForm({
        location_name: '',
        landmark: '',
        barangay: 'Brgy 176 - E',
        location_category: '',
        latitude: '',
        longitude: '',
        description: '',
    });

    // Combined states for both selectors
    const [packageState, setPackageState] = useState<SelectionState>({
        value: null,
        open: false,
    });

    const [barangayState, setBarangayState] = useState<SelectionState>({
        value: barangay[0],
        open: false,
    });

    const [categoryState, setCategoryState] = useState<SelectionState>({
        value: null,
        open: false,
    });

    const [coordinates, setCoordinates] = useState({
        latitude: '',
        longitude: '',
    });

    // Handlers for package selection
    const handlePackageSelect = (selected: Package | null) => {
        setPackageState({
            value: selected,
            open: false,
        });
        setData('location_name', selected ? selected.name : '');
    };

    // Handlers for barangay selection
    const handleBarangaySelect = (selected: Barangay | null) => {
        setBarangayState({
            value: selected,
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

        console.log('Submitting location form...');
        console.log('Form data:', data);

        post('/locations', {
            onSuccess: () => {
                console.log('Location created successfully');
                // Show success toast
                toast({
                    title: 'Success!',
                    description: 'Location created successfully.',
                    variant: 'default',
                });

                // Reset form
                reset();
                setPackageState({ value: null, open: false });
                setBarangayState({ value: null, open: false });
                setCategoryState({ value: null, open: false });
                setCoordinates({ latitude: '', longitude: '' });

                // Force page reload to show the new location
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            },
            onError: (errors) => {
                console.log('Server validation errors:', errors);
                console.error(
                    'Full error object:',
                    JSON.stringify(errors, null, 2),
                );

                // Show error toast
                toast({
                    title: 'Error',
                    description:
                        'Failed to create location. Please check your inputs and try again.',
                    variant: 'destructive',
                });
            },
        });
    };

    return (
        <Dialog open={dialogOpen} onOpenChange={setDialogOpen}>
            <DialogTrigger asChild>
                <Button>
                    <Plus className="h-4 w-4" /> Add Location
                </Button>
            </DialogTrigger>
            <DialogContent
                className="flex max-h-[90vh] max-w-none flex-col overflow-hidden p-0 sm:max-w-2xl"
                showCloseButton={false}
            >
                <form
                    onSubmit={onSubmit}
                    className="flex h-full flex-col overflow-hidden"
                >
                    <DialogHeader className="flex-shrink-0 px-6 pt-6 pb-4">
                        <DialogTitle>Create New Location</DialogTitle>
                        <DialogDescription>
                            Add a new location with its details and coordinates.
                        </DialogDescription>
                    </DialogHeader>

                    {/* Scrollable Form Content */}
                    <div className="flex-1 space-y-6 overflow-y-auto px-6 py-4">
                        <div className="space-y-4">
                            {/* Location Name (Package) Selector */}
                            <div>
                                <Label htmlFor="location-name">
                                    Location Name
                                </Label>
                                <Select
                                    value={
                                        packageState.value
                                            ? (
                                                  packageState.value as Package
                                              ).id.toString()
                                            : ''
                                    }
                                    onValueChange={(value) => {
                                        const selected = packages.find(
                                            (pkg) =>
                                                pkg.id.toString() === value,
                                        );
                                        handlePackageSelect(selected || null);
                                    }}
                                >
                                    <SelectTrigger className="w-full">
                                        <SelectValue placeholder="Select Package" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <ScrollArea className="h-[300px]">
                                            <SelectGroup>
                                                {packages.map((pkg) => (
                                                    <SelectItem
                                                        key={pkg.id}
                                                        value={pkg.id.toString()}
                                                    >
                                                        {pkg.name}
                                                    </SelectItem>
                                                ))}
                                            </SelectGroup>
                                        </ScrollArea>
                                    </SelectContent>
                                </Select>
                            </div>
                            {errors.location_name && (
                                <p className="mt-1 text-sm text-red-500">
                                    {errors.location_name}
                                </p>
                            )}

                            <div>
                                <Label htmlFor="location-landmark">
                                    Near Landmark{' '}
                                    <span className="text-muted-foreground">
                                        (Optional)
                                    </span>
                                </Label>
                                <Input
                                    id="location-landmark"
                                    placeholder="e.g sto nino parish"
                                    value={data.landmark}
                                    onChange={(e) =>
                                        setData('landmark', e.target.value)
                                    }
                                />
                                {errors.landmark && (
                                    <p className="mt-1 text-sm text-red-500">
                                        {errors.landmark}
                                    </p>
                                )}
                            </div>

                            {/* Barangay Selector */}
                            <div>
                                <Label htmlFor="barangay">Barangay</Label>
                                <Select
                                    value={
                                        barangayState.value
                                            ? (
                                                  barangayState.value as Barangay
                                              ).id.toString()
                                            : ''
                                    }
                                    onValueChange={(value) => {
                                        const selected = barangay.find(
                                            (brgy) =>
                                                brgy.id.toString() === value,
                                        );
                                        handleBarangaySelect(selected || null);
                                    }}
                                >
                                    <SelectTrigger className="w-full">
                                        <SelectValue placeholder="Select Barangay" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectGroup>
                                            {barangay.map((brgy) => (
                                                <SelectItem
                                                    key={brgy.id}
                                                    value={brgy.id.toString()}
                                                >
                                                    {brgy.name}
                                                </SelectItem>
                                            ))}
                                        </SelectGroup>
                                    </SelectContent>
                                </Select>
                            </div>
                            {errors.barangay && (
                                <p className="mt-1 text-sm text-red-500">
                                    {errors.barangay}
                                </p>
                            )}

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
                                            <div className="h-64 w-full overflow-hidden rounded-[var(--radius)] border">
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
                                    <div>
                                        <Label htmlFor="latitude">
                                            Latitude
                                        </Label>
                                        <Input
                                            id="latitude"
                                            value={coordinates.latitude}
                                            disabled
                                            className="bg-muted"
                                        />
                                    </div>
                                    <div>
                                        <Label htmlFor="longitude">
                                            Longitude
                                        </Label>
                                        <Input
                                            id="longitude"
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
                                <Label htmlFor="description">
                                    Description{' '}
                                    <span className="text-muted-foreground">
                                        (Optional)
                                    </span>
                                </Label>
                                <Textarea
                                    id="description"
                                    className="resize-none"
                                    placeholder="e.g High traffic during rush hour"
                                    value={data.description}
                                    onChange={(e) =>
                                        setData('description', e.target.value)
                                    }
                                />
                                {errors.description && (
                                    <p className="mt-1 text-sm text-red-500">
                                        {errors.description}
                                    </p>
                                )}
                            </div>
                        </div>
                    </div>

                    <DialogFooter className="flex-shrink-0 px-6 py-4">
                        <div className="flex w-full gap-2">
                            <DialogClose asChild>
                                <Button
                                    variant="outline"
                                    type="button"
                                    className="flex-1"
                                >
                                    Cancel
                                </Button>
                            </DialogClose>
                            <Button
                                type="submit"
                                disabled={processing}
                                className="flex-2"
                            >
                                {processing ? 'Creating...' : 'Add Location'}
                            </Button>
                        </div>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

export default CreateLocation;
