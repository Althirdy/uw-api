import React from 'react';
import { Button } from '@/components/ui/button';
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
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import { ExternalLink, MapPin, Phone, User, Building, Clock } from 'lucide-react';

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
    created_at?: string;
    updated_at?: string;
};

interface ViewContactsProps {
    contact: Contact;
}

export default function ViewContacts({ contact }: ViewContactsProps) {
    return (
        <Sheet>
            <SheetTrigger asChild>
                <div className='p-2 rounded-full hover:bg-secondary/20 cursor-pointer' >
                    <ExternalLink size={20} />
                </div>
            </SheetTrigger>
            <SheetContent className="flex flex-col">
                <SheetHeader className="sticky top-0 z-10 bg-background pb-4">
                    <SheetTitle className="flex items-center gap-2">
                        <Building className="h-5 w-5" />
                        Contact Details
                    </SheetTitle>
                    <SheetDescription>
                        View detailed information about this contact.
                    </SheetDescription>
                </SheetHeader>
                <div className="flex-1 overflow-y-auto px-4 py-6 pb-20">
                    <div className="grid auto-rows-min gap-6">
                    <div className="grid gap-3">
                        <Label htmlFor="branch-name">Branch/Unit Name</Label>
                        <Input
                            id="branch-name"
                            value={contact.branch_unit_name}
                            readOnly
                            className="bg-muted"
                        />
                    </div>

                    {contact.contact_person && (
                        <div className="grid gap-3">
                            <Label htmlFor="contact-person">Contact Person</Label>
                            <Input
                                id="contact-person"
                                value={contact.contact_person}
                                readOnly
                                className="bg-muted"
                            />
                        </div>
                    )}

                    <div className="grid gap-3">
                        <Label htmlFor="responder-type">Responder Type</Label>
                        <div className="p-2">
                            <Badge variant="secondary" className="text-sm">
                                {contact.responder_type}
                            </Badge>
                        </div>
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
                        <Label htmlFor="primary-mobile">Primary Contact</Label>
                        <Input
                            id="primary-mobile"
                            value={contact.primary_mobile}
                            readOnly
                            className="bg-muted"
                        />
                    </div>

                    {contact.backup_mobile && (
                        <div className="grid gap-3">
                            <Label htmlFor="backup-mobile">Backup Contact</Label>
                            <Input
                                id="backup-mobile"
                                value={contact.backup_mobile}
                                readOnly
                                className="bg-muted"
                            />
                        </div>
                    )}

                    {(contact.latitude && contact.longitude) && (
                        <div className="grid grid-cols-2 gap-3">
                            <div className="space-y-2">
                                <Label htmlFor="latitude">Latitude</Label>
                                <Input
                                    id="latitude"
                                    value={Number(contact.latitude).toFixed(6)}
                                    readOnly
                                    className="bg-muted"
                                />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="longitude">Longitude</Label>
                                <Input
                                    id="longitude"
                                    value={Number(contact.longitude).toFixed(6)}
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
                                variant={contact.active ? "default" : "secondary"} 
                                className="text-sm"
                            >
                                {contact.active ? '● Active' : '○ Inactive'}
                            </Badge>
                        </div>
                    </div>

                    {contact.created_at && (
                        <div className="grid gap-3">
                            <Label htmlFor="created">Created Date</Label>
                            <Input
                                id="created"
                                value={new Date(contact.created_at).toLocaleDateString('en-US', {
                                    year: 'numeric',
                                    month: 'long',
                                    day: 'numeric'
                                })}
                                readOnly
                                className="bg-muted"
                            />
                        </div>
                    )}
                </div>
                </div>
                <SheetFooter className="sticky bottom-0 z-10 bg-background pt-4 px-4">
                    <SheetClose asChild>
                        <Button variant="outline">Close</Button>
                    </SheetClose>
                </SheetFooter>
            </SheetContent>
        </Sheet>
    );
}