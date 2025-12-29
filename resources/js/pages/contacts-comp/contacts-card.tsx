import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { Contact } from '@/types/contacts-types';
import { ExternalLink as Open, SquarePen, Trash2 } from 'lucide-react';

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

const ContactCard = ({ contacts }: { contacts: Contact[] }) => {
    return (
        <div className="grid auto-rows-min gap-4 md:grid-cols-4">
            {contacts.length === 0 && (
                <Card className="col-span-full rounded-[var(--radius)] border border-sidebar-border/70 dark:border-sidebar-border">
                    <CardContent className="flex items-center justify-center py-12">
                        <p className="text-muted-foreground">
                            No contacts found matching your selection.
                        </p>
                    </CardContent>
                </Card>
            )}

            {contacts.map((contact) => (
                <Card
                    key={contact.id}
                    className="relative overflow-hidden rounded-[var(--radius)] border border-sidebar-border/70 dark:border-sidebar-border"
                >
                    <CardHeader>
                        <CardTitle>
                            <div className="flex w-full flex-row justify-between">
                                <span> Branch: {contact.branch_unit_name}</span>
                                <span
                                    className={`inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium ${
                                        responderTypeColors[
                                            contact.responder_type
                                        ] || 'bg-blue-100 text-blue-800'
                                    }`}
                                >
                                    {contact.responder_type}
                                </span>
                            </div>
                        </CardTitle>
                        <CardDescription>
                            <div className="flex w-full flex-row items-center justify-between">
                                <span className="text-md">
                                    Name: {contact.contact_person}
                                </span>
                                <span
                                    className={`inline-flex items-center rounded-full px-2 py-1 text-xs font-medium text-foreground ${
                                        contact.active
                                            ? 'bg-green-800'
                                            : 'bg-gray-800'
                                    }`}
                                >
                                    {contact.active ? 'Active' : 'Inactive'}
                                </span>
                            </div>
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div className="flex flex-col gap-3 text-sm">
                            <div>
                                <p className="font-medium text-muted-foreground">
                                    Location
                                </p>
                                <p className="">{contact.location}</p>
                            </div>
                            <div>
                                <p className="font-medium text-muted-foreground">
                                    Contact Numbers
                                </p>
                                <p className="">{contact.primary_mobile}</p>
                                {contact.backup_mobile && (
                                    <p className="font-medium text-muted-foreground">
                                        Backup: {contact.backup_mobile}
                                    </p>
                                )}
                            </div>
                        </div>
                    </CardContent>
                    <CardFooter>
                        <div className="flex w-full justify-end gap-2">
                            <Tooltip>
                                <ViewContacts contact={contact}>
                                    <TooltipTrigger asChild>
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            className="cursor-pointer"
                                        >
                                            <Open className="h-4 w-4" />
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
                    </CardFooter>
                </Card>
            ))}
        </div>
    );
};

export default ContactCard;
