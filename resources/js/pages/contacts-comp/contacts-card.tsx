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

const ContactCard = ({ contacts }: { contacts: Contact[] }) => {
    return (
        <div className="grid auto-rows-min gap-4 md:grid-cols-4">
            {contacts.length === 0 && (
                <div className="py-8 text-center text-gray-500">
                    No contacts found matching your selection.
                </div>
            )}

            {contacts.map((contact) => (
                <Card
                    key={contact.id}
                    className="relative overflow-hidden rounded-[var(--radius)] border border-sidebar-border/70 dark:border-sidebar-border"
                >
                    <CardHeader>
                        <CardTitle>{contact.branch_unit_name}</CardTitle>
                        <CardDescription>
                            {contact.contact_person || contact.primary_mobile}
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div className="flex flex-col gap-3">
                            <div>
                                <p className="text-xs font-medium text-muted-foreground">
                                    Responder Type
                                </p>
                                <span
                                    className={`inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ${
                                        contact.responder_type === 'Fire'
                                            ? 'bg-red-100 text-red-800'
                                            : contact.responder_type ===
                                                'Emergency'
                                              ? 'bg-orange-100 text-orange-800'
                                              : contact.responder_type ===
                                                  'Crime'
                                                ? 'bg-purple-100 text-purple-800'
                                                : contact.responder_type ===
                                                    'Traffic'
                                                  ? 'bg-yellow-100 text-yellow-800'
                                                  : contact.responder_type ===
                                                      'Barangay'
                                                    ? 'bg-green-100 text-green-800'
                                                    : contact.responder_type ===
                                                        'Others'
                                                      ? 'bg-gray-100 text-gray-800'
                                                      : 'bg-blue-100 text-blue-800'
                                    }`}
                                >
                                    {contact.responder_type}
                                </span>
                            </div>
                            <div>
                                <p className="text-xs font-medium text-muted-foreground">
                                    Location
                                </p>
                                <p className="text-sm">{contact.location}</p>
                            </div>
                            <div>
                                <p className="text-xs font-medium text-muted-foreground">
                                    Contact Numbers
                                </p>
                                <p className="text-sm">
                                    {contact.primary_mobile}
                                </p>
                                {contact.backup_mobile && (
                                    <p className="text-xs text-muted-foreground">
                                        Backup: {contact.backup_mobile}
                                    </p>
                                )}
                            </div>
                            <div>
                                <span
                                    className={`inline-flex items-center rounded-full px-2 py-1 text-xs font-medium ${
                                        contact.active
                                            ? 'bg-green-100 text-green-800'
                                            : 'bg-gray-100 text-gray-800'
                                    }`}
                                >
                                    ● {contact.active ? 'Active' : 'Inactive'}
                                </span>
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
