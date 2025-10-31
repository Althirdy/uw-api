import { Head } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Search, ChevronDown } from 'lucide-react';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import {
    Command,
    CommandEmpty,
    CommandGroup,
    CommandInput,
    CommandItem,
    CommandList,
} from '@/components/ui/command';
import { cn } from '@/lib/utils';
import { Check, ChevronsUpDown } from 'lucide-react';
import AddContacts from './contacts-comp/add-contacts';
import ViewContacts from './contacts-comp/view-contacts';
import EditContacts from './contacts-comp/edit-contacts';
import DeleteContacts from './contacts-comp/delete-contacts';
import { Auth, type BreadcrumbItem } from '@/types';
import { contacts } from '@/routes';
import { useState, useEffect } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Contacts',
        href: contacts().url,
    },
];

// Define PageProps interface
interface PageProps {
    auth: Auth;
}

// Type definitions based on backend structure
type Contact = {
    id: number;
    branch_unit_name: string;
    contact_person?: string;
    responder_type: string;
    location: string;
    primary_mobile: string;
    backup_mobile?: string;
    latitude?: number;
    longitude?: number;
    active: boolean;
    created_at: string;
    updated_at: string;
};

type ContactsPageProps = PageProps & {
    contacts: {
        data: Contact[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
    filters: {
        search?: string;
        responder_type?: string;
        active?: string;
    };
};

export default function Contacts({ auth, contacts, filters }: ContactsPageProps) {
    const [searchTerm, setSearchTerm] = useState(filters?.search || '');
    const [selectedType, setSelectedType] = useState(filters?.responder_type || '');
    const [selectedStatus, setSelectedStatus] = useState(filters?.active !== undefined ? (filters.active === '1' ? 'Active' : 'Inactive') : '');
    const [isTypeDropdownOpen, setIsTypeDropdownOpen] = useState(false);
    const [isStatusDropdownOpen, setIsStatusDropdownOpen] = useState(false);
    const [filteredContacts, setFilteredContacts] = useState<Contact[]>(contacts?.data || []);

    const responderTypes = ['BEST', 'BCCM', 'BCPC', 'BDRRM', 'BHERT', 'BHW', 'BPSO', 'BTMO', 'VAWC'];
    const statusOptions = ['Active', 'Inactive'];
    
    // Get unique branch/unit names from contacts
    const branchUnitNames = Array.from(new Set(contacts?.data?.map(c => c.branch_unit_name) || [])).sort();

    // Filter contacts based on search term, type, and status - client-side filtering as fallback
    useEffect(() => {
        let filteredResults = contacts?.data || [];

        const matchesSearch = (contact: Contact) => {
            if (searchTerm === '') return true;
            return (
                contact.branch_unit_name.toLowerCase().includes(searchTerm.toLowerCase()) ||
                contact.location.toLowerCase().includes(searchTerm.toLowerCase()) ||
                (contact.contact_person && contact.contact_person.toLowerCase().includes(searchTerm.toLowerCase()))
            );
        };
        
        const matchesType = (contact: Contact) => {
            return selectedType === '' || contact.branch_unit_name === selectedType;
        };

        const matchesStatus = (contact: Contact) => {
            return selectedStatus === '' || 
                (selectedStatus === 'Active' && contact.active) ||
                (selectedStatus === 'Inactive' && !contact.active);
        };
        
        filteredResults = filteredResults.filter((contact) => 
            matchesSearch(contact) && matchesType(contact) && matchesStatus(contact)
        );

        setFilteredContacts(filteredResults);
    }, [searchTerm, selectedType, selectedStatus, contacts?.data]);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Contacts" />
            
            <div className="space-y-4 p-4">
                {/* Header with Add Contact Button */}
                <div className="flex items-center justify-start">
                    <AddContacts />
                </div>

                {/* Search and Filters - Horizontal Layout */}
                <div className="flex max-w-4xl flex-wrap gap-4">
                    {/* Search Bar */}
                    <Input
                        placeholder="Search contact"
                        value={searchTerm}
                        onChange={(e) => setSearchTerm(e.target.value)}
                        className="h-12 min-w-[300px] flex-1"
                    />
                    
                    {/* Type Filter - Popover Dropdown */}
                    <Popover open={isTypeDropdownOpen} onOpenChange={setIsTypeDropdownOpen}>
                        <PopoverTrigger asChild className="h-12">
                            <Button
                                variant="outline"
                                role="combobox"
                                aria-expanded={isTypeDropdownOpen}
                                className="w-[180px] cursor-pointer justify-between"
                            >
                                {selectedType || 'Select barangay...'}
                                <ChevronsUpDown className="opacity-50" />
                            </Button>
                        </PopoverTrigger>
                        <PopoverContent className="w-[180px] p-0">
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
                                            key="all-types"
                                            value=""
                                            onSelect={() => {
                                                setSelectedType('');
                                                setIsTypeDropdownOpen(false);
                                            }}
                                        >
                                            All Branch/Units
                                            <Check
                                                className={cn(
                                                    'ml-auto',
                                                    selectedType === ''
                                                        ? 'opacity-100'
                                                        : 'opacity-0',
                                                )}
                                            />
                                        </CommandItem>
                                        {branchUnitNames.map((type) => (
                                            <CommandItem
                                                key={type}
                                                value={type}
                                                onSelect={(currentValue) => {
                                                    setSelectedType(
                                                        currentValue === selectedType
                                                            ? ''
                                                            : currentValue
                                                    );
                                                    setIsTypeDropdownOpen(false);
                                                }}
                                            >
                                                {type}
                                                <Check
                                                    className={cn(
                                                        'ml-auto',
                                                        selectedType === type
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

                    {/* Status Filter - Popover Dropdown */}
                    <Popover open={isStatusDropdownOpen} onOpenChange={setIsStatusDropdownOpen}>
                        <PopoverTrigger asChild className="h-12">
                            <Button
                                variant="outline"
                                role="combobox"
                                aria-expanded={isStatusDropdownOpen}
                                className="w-[180px] cursor-pointer justify-between"
                            >
                                {selectedStatus || 'Select role...'}
                                <ChevronsUpDown className="opacity-50" />
                            </Button>
                        </PopoverTrigger>
                        <PopoverContent className="w-[180px] p-0">
                            <Command>
                                <CommandInput
                                    placeholder="Search status..."
                                    className="h-9"
                                />
                                <CommandList>
                                    <CommandEmpty>No status found.</CommandEmpty>
                                    <CommandGroup>
                                        {/* Add "All Status" option */}
                                        <CommandItem
                                            key="all-status"
                                            value=""
                                            onSelect={() => {
                                                setSelectedStatus('');
                                                setIsStatusDropdownOpen(false);
                                            }}
                                        >
                                            All Status
                                            <Check
                                                className={cn(
                                                    'ml-auto',
                                                    selectedStatus === ''
                                                        ? 'opacity-100'
                                                        : 'opacity-0',
                                                )}
                                            />
                                        </CommandItem>
                                        {statusOptions.map((status) => (
                                            <CommandItem
                                                key={status}
                                                value={status}
                                                onSelect={(currentValue) => {
                                                    setSelectedStatus(
                                                        currentValue === selectedStatus
                                                            ? ''
                                                            : currentValue
                                                    );
                                                    setIsStatusDropdownOpen(false);
                                                }}
                                            >
                                                {status}
                                                <Check
                                                    className={cn(
                                                        'ml-auto',
                                                        selectedStatus === status
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

                {/* Contacts Table */}
                <div className="border rounded-lg overflow-hidden bg-card">
                    {/* Table Header */}
                    <div className="grid grid-cols-11 gap-4 p-4 bg-muted/50 border-b font-medium text-sm">
                        <div className="col-span-2">Branch/Unit Name</div>
                        <div className="col-span-2">Responder Type</div>
                        <div className="col-span-2">Primary Number</div>
                        <div className="col-span-2">Location</div>
                        <div className="col-span-1">Status</div>
                        <div className="col-span-2 text-center">Actions</div>
                    </div>

                    {/* Table Body - Dynamic Data */}
                    <div className="divide-y">
                        {filteredContacts.length === 0 ? (
                            <div className="p-8 text-center text-muted-foreground">
                                No contacts found matching your criteria.
                            </div>
                        ) : (
                            filteredContacts.map((contact: Contact) => (
                                <div key={contact.id} className="grid grid-cols-11 gap-4 p-4 hover:bg-muted/20 transition-colors">
                                    <div className="col-span-2 font-medium">
                                        {contact.branch_unit_name}
                                        {contact.contact_person && (
                                            <div className="text-sm text-muted-foreground">
                                                {contact.contact_person}
                                            </div>
                                        )}
                                    </div>
                                    <div className="col-span-2">
                                        <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${
                                            contact.responder_type === 'BEST' ? 'bg-blue-100 text-blue-800' :
                                            contact.responder_type === 'BCCM' ? 'bg-green-100 text-green-800' :
                                            contact.responder_type === 'BCPC' ? 'bg-purple-100 text-purple-800' :
                                            contact.responder_type === 'BDRRM' ? 'bg-red-100 text-red-800' :
                                            contact.responder_type === 'BHERT' ? 'bg-orange-100 text-orange-800' :
                                            contact.responder_type === 'BHW' ? 'bg-pink-100 text-pink-800' :
                                            contact.responder_type === 'BPSO' ? 'bg-indigo-100 text-indigo-800' :
                                            contact.responder_type === 'BTMO' ? 'bg-cyan-100 text-cyan-800' :
                                            contact.responder_type === 'VAWC' ? 'bg-yellow-100 text-yellow-800' :
                                            'bg-gray-100 text-gray-800'
                                        }`}>
                                            {contact.responder_type}
                                        </span>
                                    </div>
                                    <div className="col-span-2">
                                        {contact.primary_mobile}
                                        {contact.backup_mobile && (
                                            <div className="text-sm text-muted-foreground">
                                                Backup: {contact.backup_mobile}
                                            </div>
                                        )}
                                    </div>
                                    <div className="col-span-2">
                                        <div className="font-medium">{contact.location}</div>
                                        {contact.latitude && contact.longitude && (
                                            <div className="text-sm text-muted-foreground">
                                                {contact.latitude}, {contact.longitude}
                                            </div>
                                        )}
                                    </div>
                                    <div className="col-span-1">
                                        <span className={`inline-flex items-center px-2 py-1 rounded-full text-xs font-medium ${
                                            contact.active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'
                                        }`}>
                                            ● {contact.active ? 'Active' : 'Inactive'}
                                        </span>
                                    </div>
                                    <div className="col-span-2 flex items-center justify-center gap-0">
                                        <ViewContacts contact={contact} />
                                        <EditContacts contact={contact} />
                                        <DeleteContacts contact={contact} />
                                    </div>
                                </div>
                            ))
                        )}
                    </div>
                </div>

                {/* Footer with results count */}
                <div className="text-sm text-muted-foreground">
                    Showing {filteredContacts.length} of {contacts?.total || 0} Contacts
                </div>
            </div>
        </AppLayout>
    );
}