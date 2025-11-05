import React, { useState } from 'react'
import {
    Sheet,
    SheetClose,
    SheetContent,
    SheetDescription,
    SheetFooter,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from "@/components/ui/sheet"
import { useForm } from '@inertiajs/react'
import { Check, ChevronsUpDown, Plus } from 'lucide-react'
import { Label } from '@/components/ui/label'
import { Input } from '@/components/ui/input'
import { Textarea } from '@/components/ui/textarea'
import { Button } from '@/components/ui/button'
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from "@/components/ui/popover"
import { Command, CommandEmpty, CommandGroup, CommandInput, CommandItem, CommandList } from '@/components/ui/command';
import { cn } from '@/lib/utils'
import { LocationCategory_T } from '../locations'
import { MapModal } from "@/components/map-modal"
import { toast } from "@/components/use-toast"

const barangay = [
    { id: 1, name: 'Brgy 176 - A' },
    { id: 2, name: 'Brgy 176 - B' },
    { id: 3, name: 'Brgy 176 - C' },
    { id: 4, name: 'Brgy 176 - D' },
    { id: 5, name: 'Brgy 176 - E' },
    { id: 6, name: 'Brgy 176 - F' },
]

const packages = [
    { id: 1, name: 'Pkg. 1A Bicolandia' },
    { id: 2, name: 'Pkg. 1B Powerline' },
    { id: 3, name: 'Pkg. 1C Sampalukan' },
    { id: 4, name: 'Pkg. 2 Botlog' },
    { id: 5, name: 'Pkg. 2 GK Staging' },
    { id: 6, name: 'Pkg. 3 Kaunlaran' },
    { id: 7, name: 'Pkg. 3 Maharlika' },
    { id: 8, name: 'Pkg. 3 Maharlika 2' },
    { id: 9, name: 'Pkg. 3 Damayan' },
    { id: 10, name: 'Pkg. 4A Atlantika' },
    { id: 11, name: 'Pkg. 4B Aklan Wire' },
    { id: 12, name: 'Pkg. 5 San Roque' },
    { id: 13, name: 'Pkg. 5 Brgy. Annex (BFP)' },
    { id: 14, name: 'Pkg. 5 Crasher' },
    { id: 15, name: 'Pkg. 5 Gatnai' },
    { id: 16, name: 'Pkg. 6 Bayanihan' },
    { id: 17, name: 'Pkg. 7A Lakan' },
    { id: 18, name: 'Pkg. 7B PhilRad' },
    { id: 19, name: 'Pkg. 7B  Khulits Court' },
    { id: 20, name: 'Pkg. 7B Dating Daan' },
    { id: 21, name: 'Pkg. 7C GS Senior High' },
    { id: 22, name: 'Pkg. 8A North Cal' },
    { id: 23, name: 'Pkg. 8B Makati' },
    { id: 24, name: 'Pkg. 9 Plaza Maria Upper' },
    { id: 25, name: 'Pkg. 9 Plaza Maria Lower' },
]

type Barangay = {
    id: number;
    name: string;
}

type Package = {
    id: number;
    name: string;
}

type SelectionState = {
    value: Barangay | LocationCategory_T | Package | null;
    open: boolean;
}

function CreateLocation({ locationCategory = [] }: { locationCategory?: LocationCategory_T[] }) {
    // Sheet control state
    const [sheetOpen, setSheetOpen] = useState(false);

    // Track if form has been submitted to control error display

    // Inertia form handling
    const { data, setData, post, processing, errors, reset } = useForm({
        location_name: '',
        landmark: '',
        barangay: '',
        location_category: '',
        latitude: '',
        longitude: '',
        description: '',
    });


    // Combined states for both selectors
    const [packageState, setPackageState] = useState<SelectionState>({
        value: null,
        open: false
    });

    const [barangayState, setBarangayState] = useState<SelectionState>({
        value: null,
        open: false
    });

    const [categoryState, setCategoryState] = useState<SelectionState>({
        value: null,
        open: false
    });

    const [coordinates, setCoordinates] = useState({
        latitude: '',
        longitude: ''
    });

    // Handlers for package selection
    const handlePackageSelect = (selected: Package | null) => {
        setPackageState({
            value: selected,
            open: false
        });
        setData('location_name', selected ? selected.name : '');
    };

    // Handlers for barangay selection
    const handleBarangaySelect = (selected: Barangay | null) => {
        setBarangayState({
            value: selected,
            open: false
        });
        setData('barangay', selected ? selected.name : '');
    };

    // Handlers for category selection
    const handleCategorySelect = (selected: LocationCategory_T | null) => {
        setCategoryState({
            value: selected,
            open: false
        });
        setData('location_category', selected ? selected.id.toString() : '');
    };

    const handleLocationSelect = (location: { lat: number; lng: number }) => {
        const coords = {
            latitude: location.lat.toString(),
            longitude: location.lng.toString()
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
                    title: "Success!",
                    description: "Location created successfully.",
                    variant: "default",
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
                console.error('Full error object:', JSON.stringify(errors, null, 2));
                
                // Show error toast
                toast({
                    title: "Error",
                    description: "Failed to create location. Please check your inputs and try again.",
                    variant: "destructive",
                });
            }
        });
    }

    return (
        <Sheet open={sheetOpen} onOpenChange={setSheetOpen}>
            <SheetTrigger asChild>
                <Button>
                    <Plus className="mr-2 h-4 w-4" /> Add Location
                </Button>
            </SheetTrigger>
            <SheetContent className="flex flex-col h-full">
                <form onSubmit={onSubmit} className="flex flex-col h-full">
                    {/* Fixed Header */}
                    <SheetHeader className="flex-shrink-0 pb-4 border-b">
                        <SheetTitle>Create New Location</SheetTitle>
                        <SheetDescription>
                            Add a new location with its details and coordinates.
                        </SheetDescription>
                    </SheetHeader>

                    {/* Scrollable Form Content */}
                    <div className="flex-1 overflow-y-auto py-6 px-4 space-y-6">
                        <div className="space-y-4">
                            {/* Location Name (Package) Selector */}
                            <Popover
                                open={packageState.open}
                                onOpenChange={(open: boolean) => setPackageState(prev => ({ ...prev, open }))}
                            >
                                <PopoverTrigger asChild>
                                    <div>
                                        <Label className='mb-2'>Location Name</Label>
                                        <Button
                                            type='button'
                                            variant="outline"
                                            role="combobox"
                                            aria-expanded={packageState.open}
                                            className="w-full justify-between"
                                        >
                                            {packageState.value ? (packageState.value as Package).name : "Select Package"}
                                            <ChevronsUpDown className="ml-2 h-4 w-4 shrink-0 opacity-50" />
                                        </Button>
                                    </div>
                                </PopoverTrigger>
                                <PopoverContent className="p-0">
                                    <Command>
                                        <CommandInput placeholder="Search package..." />
                                        <CommandList>
                                            <CommandEmpty>No package found.</CommandEmpty>
                                            <CommandGroup>
                                                {packages.map((pkg) => (
                                                    <CommandItem
                                                        key={pkg.id}
                                                        value={pkg.name}
                                                        onSelect={() => handlePackageSelect(
                                                            (packageState.value as Package)?.id === pkg.id ? null : pkg
                                                        )}
                                                    >
                                                        {pkg.name}
                                                        <Check
                                                            className={cn(
                                                                "ml-auto",
                                                                (packageState.value as Package)?.id === pkg.id
                                                                    ? "opacity-100"
                                                                    : "opacity-0"
                                                            )}
                                                        />
                                                    </CommandItem>
                                                ))}
                                            </CommandGroup>
                                        </CommandList>
                                    </Command>
                                </PopoverContent>
                            </Popover>
                            {errors.location_name && <p className="text-red-500 text-sm mt-1">{errors.location_name}</p>}
                            
                            <div>
                                <Label htmlFor="location-landmark">Near Landmark</Label>
                                <Input
                                    id="location-landmark"
                                    placeholder="e.g sto nino parish"
                                    value={data.landmark}
                                    onChange={(e) => setData('landmark', e.target.value)}
                                />
                                {errors.landmark && <p className="text-red-500 text-sm mt-1">{errors.landmark}</p>}
                            </div>

                            {/* Barangay Selector */}
                            <Popover
                                open={barangayState.open}
                                onOpenChange={(open: boolean) => setBarangayState(prev => ({ ...prev, open }))}
                            >
                                <PopoverTrigger asChild>
                                    <div>
                                        <Label className='mb-2'>Barangay</Label>
                                        <Button
                                            type='button'
                                            variant="outline"
                                            role="combobox"
                                            aria-expanded={barangayState.open}
                                            className="w-full justify-between"
                                        >
                                            {barangayState.value ? (barangayState.value as Barangay).name : "Select Barangay"}
                                            <ChevronsUpDown className="ml-2 h-4 w-4 shrink-0 opacity-50" />
                                        </Button>
                                    </div>
                                </PopoverTrigger>
                                <PopoverContent className="p-0">
                                    <Command>
                                        <CommandList>
                                            <CommandEmpty>No barangay found.</CommandEmpty>
                                            <CommandGroup>
                                                {barangay.map((brgy) => (
                                                    <CommandItem
                                                        key={brgy.id}
                                                        value={brgy.name}
                                                        onSelect={() => handleBarangaySelect(
                                                            (barangayState.value as Barangay)?.id === brgy.id ? null : brgy
                                                        )}
                                                    >
                                                        {brgy.name}
                                                        <Check
                                                            className={cn(
                                                                "ml-auto",
                                                                (barangayState.value as Barangay)?.id === brgy.id
                                                                    ? "opacity-100"
                                                                    : "opacity-0"
                                                            )}
                                                        />
                                                    </CommandItem>
                                                ))}
                                            </CommandGroup>
                                        </CommandList>
                                    </Command>
                                </PopoverContent>
                            </Popover>
                            {errors.barangay && <p className="text-red-500 text-sm mt-1">{errors.barangay}</p>}

                            {/* Zone Category */}
                            <Popover
                                open={categoryState.open}
                                onOpenChange={(open: boolean) => setCategoryState(prev => ({ ...prev, open }))}
                            >
                                <PopoverTrigger asChild>
                                    <div>
                                        <Label className='mb-2'>Zone Category</Label>
                                        <Button
                                            type='button'
                                            variant="outline"
                                            role="combobox"
                                            aria-expanded={categoryState.open}
                                            className="w-full justify-between"
                                        >
                                            {categoryState.value ? categoryState.value.name : "Select Category"}
                                            <ChevronsUpDown className="opacity-50" />
                                        </Button>
                                    </div>
                                </PopoverTrigger>
                                <PopoverContent className=" p-0">
                                    <Command>
                                        <CommandList>
                                            <CommandEmpty>No category found.</CommandEmpty>
                                            <CommandGroup>
                                                {(locationCategory || []).map((category) => (
                                                    <CommandItem
                                                        key={category.id}
                                                        value={category.name}
                                                        onSelect={() => handleCategorySelect(categoryState.value?.id === category.id ? null : category)}
                                                    >
                                                        {category.name}
                                                        <Check
                                                            className={cn(
                                                                "ml-auto",
                                                                categoryState.value?.id === category.id ? "opacity-100" : "opacity-0"
                                                            )}
                                                        />
                                                    </CommandItem>
                                                ))}
                                            </CommandGroup>
                                        </CommandList>
                                    </Command>
                                </PopoverContent>
                            </Popover>
                            {errors.location_category && <p className="text-red-500 text-sm mt-1">{errors.location_category}</p>}

                            {/* GPS Coordinates */}
                            <div className="space-y-2">
                                <Label>GPS Coordinates</Label>
                                <MapModal
                                    onLocationSelect={handleLocationSelect}
                                    coordinates={coordinates}
                                />
                                <div className="grid grid-cols-2 gap-2">
                                    <div>
                                        <Label htmlFor="latitude">Latitude</Label>
                                        <Input
                                            id="latitude"
                                            value={coordinates.latitude.slice(0, 8) + '...'}
                                            disabled
                                        />
                                    </div>
                                    <div>
                                        <Label htmlFor="longitude">Longitude</Label>
                                        <Input
                                            id="longitude"
                                            value={coordinates.longitude.slice(0, 8) + '...'}
                                            disabled
                                        />
                                    </div>
                                </div>
                                {errors.latitude && <p className="text-red-500 text-sm mt-1">{errors.latitude}</p>}
                                {errors.longitude && <p className="text-red-500 text-sm mt-1">{errors.longitude}</p>}
                            </div>

                            {/* Description */}
                            <div>
                                <Label htmlFor="description">Description</Label>
                                <Textarea
                                    id="description"
                                    className='resize-none'
                                    placeholder="e.g High traffic during rush hour"
                                    value={data.description}
                                    onChange={(e) => setData('description', e.target.value)}
                                />
                                {errors.description && <p className="text-red-500 text-sm mt-1">{errors.description}</p>}
                            </div>
                        </div>
                    </div>

                    {/* Fixed Footer */}
                    <SheetFooter className="flex-shrink-0 pt-4 border-t bg-background">
                        <div className="flex gap-2 w-full">
                            <Button
                                type="submit"
                                disabled={processing}
                                className="flex-1"
                            >
                                {processing ? 'Creating...' : 'Add Location'}
                            </Button>
                            <SheetClose asChild>
                                <Button variant='outline' type="button" className="flex-1">Cancel</Button>
                            </SheetClose>
                        </div>
                    </SheetFooter>
                </form>
            </SheetContent>
        </Sheet>
    )
}

export default CreateLocation