import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import AppLayout from '@/layouts/app-layout';
import { contacts } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Contact, ContactsPageProps } from '@/types/contacts-types';
import { Head } from '@inertiajs/react';
import { List, Table } from 'lucide-react';
import { useState } from 'react';
import AddContacts from './contacts-comp/contacts-create';
import ContactCard from './contacts-comp/contacts-card';
import ContactActionTab from './contacts-comp/contacts-tab';
import ContactTable from './contacts-comp/contacts-table';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Contacts',
        href: contacts().url,
    },
];

export default function Contacts({
    auth,
    contacts,
    filters,
}: ContactsPageProps) {
    const [filtered_contacts, setFilteredContacts] = useState<Contact[]>(
        contacts?.data || [],
    );

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Contacts" />

            <div className="space-y-4 p-4">
                <AddContacts />

                <Tabs defaultValue="table" className="w-full space-y-2">
                    <div className="flex flex-row gap-4">
                        <ContactActionTab
                            contacts={contacts}
                            setFilteredContacts={setFilteredContacts}
                        />
                        <TabsList className="h-12 w-24">
                            <TabsTrigger
                                value="table"
                                className="cursor-pointer"
                            >
                                <List className="h-8 w-8" />
                            </TabsTrigger>
                            <TabsTrigger
                                value="card"
                                className="cursor-pointer"
                            >
                                <Table className="h-4 w-4" />
                            </TabsTrigger>
                        </TabsList>
                    </div>

                    <TabsContent value="table" className="w-full">
                        <ContactTable contacts={filtered_contacts} />
                    </TabsContent>
                    <TabsContent value="card" className="w-full">
                        <ContactCard contacts={filtered_contacts} />
                    </TabsContent>
                </Tabs>
            </div>
        </AppLayout>
    );
}
