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
import { Contact, PaginatedContacts } from '@/types/contacts-types';
import { Check, ChevronsUpDown } from 'lucide-react';
import { useEffect, useState } from 'react';

const ContactActionTab = ({
    contacts,
    setFilteredContacts,
}: {
    contacts: PaginatedContacts;
    setFilteredContacts: (contacts: Contact[]) => void;
}) => {
    const [open, setOpen] = useState(false);
    const [statusOpen, setStatusOpen] = useState(false);
    const [value, setValue] = useState<string | null>(null);
    const [statusValue, setStatusValue] = useState<string | null>(null);
    const [searchQuery, setSearchQuery] = useState<string>('');
    const [searchable_units, setSearchableUnits] = useState<string[]>([]);

    // Extract unique branch/unit names from contacts data
    useEffect(() => {
        const units = contacts.data
            .map((contact: Contact) => contact.branch_unit_name)
            .filter((unit): unit is string => Boolean(unit))
            .filter(
                (value: string, index: number, self: string[]) =>
                    self.indexOf(value) === index,
            )
            .sort();
        setSearchableUnits(units);
    }, [contacts.data]);

    // Filter displayed contacts based on selected unit, status and search query
    useEffect(() => {
        let filteredResults = contacts.data;

        // Filter by branch/unit name if selected
        if (value) {
            filteredResults = filteredResults.filter(
                (contact: Contact) => contact.branch_unit_name === value,
            );
        }

        // Filter by status if selected
        if (statusValue) {
            const isActive = statusValue === 'Active';
            filteredResults = filteredResults.filter(
                (contact: Contact) => contact.active === isActive,
            );
        }

        // Filter by search query (branch name, location, or contact person)
        if (searchQuery.trim()) {
            filteredResults = filteredResults.filter((contact: Contact) => {
                const branch = contact.branch_unit_name.toLowerCase();
                const location = contact.location.toLowerCase();
                const person = contact.contact_person?.toLowerCase() || '';
                const query = searchQuery.toLowerCase();

                return (
                    branch.includes(query) ||
                    location.includes(query) ||
                    person.includes(query)
                );
            });
        }

        setFilteredContacts(filteredResults);
    }, [value, statusValue, searchQuery, contacts.data, setFilteredContacts]);

    return (
        <div className="flex max-w-4xl flex-wrap gap-4">
            <Input
                placeholder="Search contacts by branch, location, or person"
                value={searchQuery}
                onChange={(e) => setSearchQuery(e.target.value)}
                className="h-12 min-w-[300px] flex-1"
            />
            <Popover open={open} onOpenChange={setOpen}>
                <PopoverTrigger asChild className="h-12">
                    <Button
                        variant="outline"
                        role="combobox"
                        aria-expanded={open}
                        className="w-[200px] cursor-pointer justify-between"
                    >
                        {value || 'Select branch/unit...'}
                        <ChevronsUpDown className="opacity-50" />
                    </Button>
                </PopoverTrigger>
                <PopoverContent className="w-[200px] p-0">
                    <Command>
                        <CommandInput
                            placeholder="Search branch/unit..."
                            className="h-9"
                        />
                        <CommandList>
                            <CommandEmpty>No branch/unit found.</CommandEmpty>
                            <CommandGroup>
                                {/* Add "All Branch/Units" option */}
                                <CommandItem
                                    key="all"
                                    value=""
                                    onSelect={() => {
                                        setValue(null);
                                        setOpen(false);
                                    }}
                                >
                                    All Branch/Units
                                    <Check
                                        className={cn(
                                            'ml-auto',
                                            value === null
                                                ? 'opacity-100'
                                                : 'opacity-0',
                                        )}
                                    />
                                </CommandItem>
                                {/* Use searchable_units for dropdown options */}
                                {searchable_units.map((unitName) => (
                                    <CommandItem
                                        key={unitName}
                                        value={unitName}
                                        onSelect={(currentValue) => {
                                            setValue(
                                                currentValue === value
                                                    ? null
                                                    : currentValue,
                                            );
                                            setOpen(false);
                                        }}
                                    >
                                        {unitName}
                                        <Check
                                            className={cn(
                                                'ml-auto',
                                                value === unitName
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
            <Popover open={statusOpen} onOpenChange={setStatusOpen}>
                <PopoverTrigger asChild className="h-12">
                    <Button
                        variant="outline"
                        role="combobox"
                        aria-expanded={statusOpen}
                        className="w-[150px] cursor-pointer justify-between"
                    >
                        {statusValue || 'Select status...'}
                        <ChevronsUpDown className="opacity-50" />
                    </Button>
                </PopoverTrigger>
                <PopoverContent className="w-[150px] p-0">
                    <Command>
                        <CommandList>
                            <CommandEmpty>No status found.</CommandEmpty>
                            <CommandGroup>
                                {/* Add "All Status" option */}
                                <CommandItem
                                    key="all-status"
                                    value=""
                                    onSelect={() => {
                                        setStatusValue(null);
                                        setStatusOpen(false);
                                    }}
                                >
                                    All Status
                                    <Check
                                        className={cn(
                                            'ml-auto',
                                            statusValue === null
                                                ? 'opacity-100'
                                                : 'opacity-0',
                                        )}
                                    />
                                </CommandItem>
                                <CommandItem
                                    key="active"
                                    value="Active"
                                    onSelect={(currentValue) => {
                                        setStatusValue(
                                            currentValue === statusValue
                                                ? null
                                                : currentValue,
                                        );
                                        setStatusOpen(false);
                                    }}
                                >
                                    Active
                                    <Check
                                        className={cn(
                                            'ml-auto',
                                            statusValue === 'Active'
                                                ? 'opacity-100'
                                                : 'opacity-0',
                                        )}
                                    />
                                </CommandItem>
                                <CommandItem
                                    key="inactive"
                                    value="Inactive"
                                    onSelect={(currentValue) => {
                                        setStatusValue(
                                            currentValue === statusValue
                                                ? null
                                                : currentValue,
                                        );
                                        setStatusOpen(false);
                                    }}
                                >
                                    Inactive
                                    <Check
                                        className={cn(
                                            'ml-auto',
                                            statusValue === 'Inactive'
                                                ? 'opacity-100'
                                                : 'opacity-0',
                                        )}
                                    />
                                </CommandItem>
                            </CommandGroup>
                        </CommandList>
                    </Command>
                </PopoverContent>
            </Popover>
        </div>
    );
};

export default ContactActionTab;
