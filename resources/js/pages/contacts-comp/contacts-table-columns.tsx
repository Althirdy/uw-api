import { ColumnDef } from '@tanstack/react-table';
import { ArrowUpDown, ExternalLink, SquarePen, Trash2 } from 'lucide-react';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';

import { Contact } from '@/types/contacts-types';
import DeleteContacts from './contacts-delete';
import EditContacts from './contacts-edit';
import ViewContacts from './contacts-view';

const responderTypeColors: Record<string, string> = {
    Fire: 'bg-red-600  text-foreground',
    Emergency: 'bg-yellow-500 text-black',
    Crime: 'bg-zinc-700  text-foreground',
    Traffic: 'bg-orange-500 text-black',
    Barangay: 'bg-blue-500  text-foreground',
    Others: 'bg-gray-600  text-foreground',
};

export const columns = (): ColumnDef<Contact>[] => [
    {
        accessorKey: 'branch_unit_name',
        header: ({ column }) => {
            return (
                <Button
                    variant="ghost"
                    onClick={() =>
                        column.toggleSorting(column.getIsSorted() === 'asc')
                    }
                    className="cursor-pointer transition-colors duration-200 ease-in-out"
                >
                    Branch/Unit Name
                    <ArrowUpDown className="ml-2 h-4 w-4" />
                </Button>
            );
        },
        cell: ({ row }) => {
            const contact = row.original;
            return (
                <div>
                    <div className="font-medium">
                        {contact.branch_unit_name}
                    </div>
                    {contact.contact_person && (
                        <div className="text-xs text-muted-foreground">
                            {contact.contact_person}
                        </div>
                    )}
                </div>
            );
        },
    },
    {
        accessorKey: 'responder_type',
        header: ({ column }) => {
            return (
                <Button
                    variant="ghost"
                    onClick={() =>
                        column.toggleSorting(column.getIsSorted() === 'asc')
                    }
                    className="cursor-pointer transition-colors duration-200 ease-in-out"
                >
                    Responder Type
                    <ArrowUpDown className="ml-2 h-4 w-4" />
                </Button>
            );
        },
        cell: ({ row }) => {
            const responderType = row.getValue('responder_type') as string;
            const colorClass =
                responderTypeColors[responderType] ||
                'bg-blue-100 text-blue-800';

            return (
                <Badge
                    className={`inline-flex items-center rounded-[var(--radius)] px-2.5 py-1 text-xs font-medium ${colorClass}`}
                >
                    {responderType}
                </Badge>
            );
        },
    },
    {
        accessorKey: 'primary_mobile',
        header: 'Primary Number',
        cell: ({ row }) => {
            const contact = row.original;
            return (
                <div>
                    <div>{contact.primary_mobile}</div>
                </div>
            );
        },
    },
    {
        accessorKey: 'location',
        header: 'Location',
        cell: ({ row }) => {
            const contact = row.original;
            return (
                <div>
                    <div className="font-medium">{contact.location}</div>
                </div>
            );
        },
    },
    {
        accessorKey: 'active',
        header: 'Status',
        cell: ({ row }) => {
            const active = row.getValue('active') as boolean;
            return (
                <Badge
                    className={`inline-flex items-center rounded-[var(--radius)] px-2 py-1 text-xs font-medium ${
                        active ? 'bg-green-800' : 'bg-gray-800'
                    }`}
                >
                    {active ? 'Active' : 'Inactive'}
                </Badge>
            );
        },
    },
    {
        id: 'actions',
        header: 'Actions',
        enableHiding: false,
        cell: ({ row }) => {
            const contact = row.original;

            return (
                <div className="flex justify-center gap-2">
                    <Tooltip>
                        <ViewContacts contact={contact}>
                            <TooltipTrigger asChild>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    className="cursor-pointer"
                                    onClick={(e) => e.stopPropagation()}
                                >
                                    <ExternalLink className="h-4 w-4" />
                                </Button>
                            </TooltipTrigger>
                        </ViewContacts>
                        <TooltipContent>
                            <p>View Details</p>
                        </TooltipContent>
                    </Tooltip>
                    <Tooltip>
                        <EditContacts contact={contact}>
                            <TooltipTrigger asChild>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    className="cursor-pointer"
                                    onClick={(e) => e.stopPropagation()}
                                >
                                    <SquarePen className="h-4 w-4" />
                                </Button>
                            </TooltipTrigger>
                        </EditContacts>
                        <TooltipContent>
                            <p>Edit Contact</p>
                        </TooltipContent>
                    </Tooltip>
                    <Tooltip>
                        <DeleteContacts contact={contact}>
                            <TooltipTrigger asChild>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    className="cursor-pointer"
                                    onClick={(e) => e.stopPropagation()}
                                >
                                    <Trash2 className="h-4 w-4 text-[var(--destructive)]" />
                                </Button>
                            </TooltipTrigger>
                        </DeleteContacts>
                        <TooltipContent>
                            <p>Delete Contact</p>
                        </TooltipContent>
                    </Tooltip>
                </div>
            );
        },
    },
];
