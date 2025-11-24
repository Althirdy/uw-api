import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Sheet,
    SheetClose,
    SheetContent,
    SheetDescription,
    SheetFooter,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';
import { Contact } from '@/types/contacts-types';
import React from 'react';

interface ViewContactsProps {
    contact: Contact;
    children?: React.ReactNode;
}

const responderTypeColors: Record<string, string> = {
    Fire: 'bg-red-600 text-white',
    Emergency: 'bg-yellow-500 text-black',
    Crime: 'bg-zinc-800 text-white',
    Traffic: 'bg-orange-500 text-black',
    Barangay: 'bg-blue-500 text-white',
    Others: 'bg-gray-600 text-white',
};

export default function ViewContacts({ contact, children }: ViewContactsProps) {
    return (
        <Sheet>
            <SheetTrigger asChild>{children}</SheetTrigger>
            <SheetContent className="max-w-none overflow-y-auto p-2 sm:max-w-lg [&>button]:hidden">
                <SheetHeader className="sticky top-0 z-10 bg-background pb-4">
                    <SheetTitle className="flex items-center gap-2">
                        Contact Details
                    </SheetTitle>
                    <SheetDescription>
                        View detailed information about this contact.
                    </SheetDescription>
                </SheetHeader>
                <div className="flex-1 overflow-y-auto px-4 py-6 pb-20">
                    <div className="grid auto-rows-min gap-6">
                        <div className="grid gap-3">
                            <Label htmlFor="branch-name">
                                Branch/Unit Name
                            </Label>
                            <Input
                                id="branch-name"
                                value={contact.branch_unit_name}
                                readOnly
                                className="bg-muted"
                            />
                        </div>

                        {contact.contact_person && (
                            <div className="grid gap-3">
                                <Label htmlFor="contact-person">
                                    Contact Person
                                </Label>
                                <Input
                                    id="contact-person"
                                    value={contact.contact_person}
                                    readOnly
                                    className="bg-muted"
                                />
                            </div>
                        )}

                        <div className="grid gap-3">
                            <Label htmlFor="responder-type">
                                Responder Type
                            </Label>

                            <Badge
                                className={`inline-flex items-center rounded-full px-2.5 py-1 text-sm font-medium ${
                                    responderTypeColors[
                                        contact.responder_type
                                    ] || 'bg-blue-100 text-blue-800'
                                }`}
                            >
                                {contact.responder_type}
                            </Badge>
                        </div>

                        <div className="grid gap-3">
                            <Label htmlFor="location">Location</Label>
                            <Input
                                id="location"
                                value={contact.location}
                                readOnly
                                className="bg-muted"
                            />
                        </div>

                        <div className="grid gap-3">
                            <Label htmlFor="primary-mobile">
                                Primary Contact
                            </Label>
                            <Input
                                id="primary-mobile"
                                value={contact.primary_mobile}
                                readOnly
                                className="bg-muted"
                            />
                        </div>

                        {contact.backup_mobile && (
                            <div className="grid gap-3">
                                <Label htmlFor="backup-mobile">
                                    Backup Contact
                                </Label>
                                <Input
                                    id="backup-mobile"
                                    value={contact.backup_mobile}
                                    readOnly
                                    className="bg-muted"
                                />
                            </div>
                        )}

                        {contact.latitude && contact.longitude && (
                            <div className="grid grid-cols-2 gap-3">
                                <div className="space-y-2">
                                    <Label htmlFor="latitude">Latitude</Label>
                                    <Input
                                        id="latitude"
                                        value={Number(contact.latitude).toFixed(
                                            6,
                                        )}
                                        readOnly
                                        className="bg-muted"
                                    />
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="longitude">Longitude</Label>
                                    <Input
                                        id="longitude"
                                        value={Number(
                                            contact.longitude,
                                        ).toFixed(6)}
                                        readOnly
                                        className="bg-muted"
                                    />
                                </div>
                            </div>
                        )}

                        <div className="grid gap-3">
                            <Label htmlFor="status">Status</Label>
                            <div className="p-2">
                                <Badge
                                    className={`inline-flex items-center rounded-full px-2 py-1 text-sm font-medium ${
                                        contact.active
                                            ? 'bg-green-800'
                                            : 'bg-gray-800'
                                    }`}
                                >
                                    {contact.active ? 'Active' : 'Inactive'}
                                </Badge>
                            </div>
                        </div>

                        {contact.created_at && (
                            <div className="grid gap-3">
                                <Label htmlFor="created">Created Date</Label>
                                <Input
                                    id="created"
                                    value={new Date(
                                        contact.created_at,
                                    ).toLocaleDateString('en-US', {
                                        year: 'numeric',
                                        month: 'long',
                                        day: 'numeric',
                                    })}
                                    readOnly
                                    className="bg-muted"
                                />
                            </div>
                        )}
                    </div>
                </div>
                <SheetFooter className="sticky bottom-0 z-10 bg-background px-4 pt-4">
                    <SheetClose asChild>
                        <Button variant="outline">Close</Button>
                    </SheetClose>
                </SheetFooter>
            </SheetContent>
        </Sheet>
    );
}
