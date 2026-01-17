import { Button } from '@/components/ui/button';
import {
    Command,
    CommandEmpty,
    CommandGroup,
    CommandInput,
    CommandItem,
    CommandList,
} from '@/components/ui/command';
import { Input } from '@/components/ui/input';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { cn } from '@/lib/utils';
import { Check, ChevronsUpDown } from 'lucide-react';
import { useEffect, useState } from 'react';

import { location_T } from '@/types/location-types';

const LocationActionTab = ({
    locations,
    setFilteredLocations,
}: {
    locations: location_T[];
    setFilteredLocations: (locations: location_T[]) => void;
}) => {
    const [categoryOpen, setCategoryOpen] = useState(false);
    const [barangayOpen, setBarangayOpen] = useState(false);
    const [categoryValue, setCategoryValue] = useState<string | null>(null);
    const [barangayValue, setBarangayValue] = useState<string | null>(null);
    const [searchQuery, setSearchQuery] = useState<string>('');
    const [searchable_barangays, setSearchableBarangays] = useState<string[]>(
        [],
    );

    // Extract unique barangays from locations data
    useEffect(() => {
        const barangays = locations
            .map((location: location_T) => location.barangay)
            .filter((barangay): barangay is string => Boolean(barangay))
            .filter(
                (value: string, index: number, self: string[]) =>
                    self.indexOf(value) === index,
            );
        setSearchableBarangays(barangays);
    }, [locations]);

    // Filter displayed locations based on selected category, barangay and search query
    useEffect(() => {
        let filteredResults = locations;

        // Filter by barangay if selected
        if (barangayValue) {
            filteredResults = filteredResults.filter(
                (location: location_T) => location.barangay === barangayValue,
            );
        }

        // Filter by search query (location name or landmark)
        if (searchQuery.trim()) {
            filteredResults = filteredResults.filter((location: location_T) => {
                const locationName = location.location_name.toLowerCase();
                const landmark = (location.landmark || '').toLowerCase();
                const query = searchQuery.toLowerCase();

                return locationName.includes(query) || landmark.includes(query);
            });
        }

        setFilteredLocations(filteredResults);
    }, [
        categoryValue,
        barangayValue,
        searchQuery,
        locations,
        setFilteredLocations,
    ]);

    return (
        <div className="flex flex-wrap gap-4">
            <Input
                placeholder="Search by location name or landmark"
                value={searchQuery}
                onChange={(e) => setSearchQuery(e.target.value)}
                className="h-12 min-w-[300px] flex-1"
            />
            <Popover open={barangayOpen} onOpenChange={setBarangayOpen}>
                <PopoverTrigger asChild className="h-12">
                    <Button
                        variant="outline"
                        role="combobox"
                        aria-expanded={barangayOpen}
                        className="w-[180px] cursor-pointer justify-between"
                    >
                        {barangayValue || 'Select barangay...'}
                        <ChevronsUpDown className="opacity-50" />
                    </Button>
                </PopoverTrigger>
                <PopoverContent className="w-[180px] p-0">
                    <Command>
                        <CommandInput
                            placeholder="Search barangay..."
                            className="h-9"
                        />
                        <CommandList>
                            <CommandEmpty>No Barangay found.</CommandEmpty>
                            <CommandGroup>
                                {/* Add "All Barangays" option */}
                                <CommandItem
                                    key="all-barangay"
                                    value=""
                                    onSelect={() => {
                                        setBarangayValue(null);
                                        setBarangayOpen(false);
                                    }}
                                >
                                    All Barangays
                                    <Check
                                        className={cn(
                                            'ml-auto',
                                            barangayValue === null
                                                ? 'opacity-100'
                                                : 'opacity-0',
                                        )}
                                    />
                                </CommandItem>
                                {/* Use searchable_barangays for dropdown options */}
                                {searchable_barangays.map((barangayName) => (
                                    <CommandItem
                                        key={barangayName}
                                        value={barangayName}
                                        onSelect={(currentValue) => {
                                            setBarangayValue(
                                                currentValue === barangayValue
                                                    ? null
                                                    : currentValue,
                                            );
                                            setBarangayOpen(false);
                                        }}
                                    >
                                        {barangayName}
                                        <Check
                                            className={cn(
                                                'ml-auto',
                                                barangayValue === barangayName
                                                    ? 'opacity-100'
                                                    : 'opacity-0',
                                            )}
                                        />
                                    </CommandItem>
                                ))}
                            </CommandGroup>
                        </CommandList>
                    </Command>
                </PopoverContent>
            </Popover>
        </div>
    );
};

export default LocationActionTab;
