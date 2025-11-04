import React from 'react'
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
import { Button } from '@/components/ui/button'
import { ExternalLink, MapPin } from 'lucide-react'
import { Label } from '@/components/ui/label'
import { Input } from '@/components/ui/input'
import { Textarea } from '@/components/ui/textarea'
import { Badge } from '@/components/ui/badge'
import { location_T } from '../locations'

interface ViewLocationProps {
    location: location_T;
}

// Function to get category color based on category name
const getCategoryColor = (categoryName: string) => {
    const colorMap: { [key: string]: string } = {
        'School': 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
        'Hospital': 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
        'Market': 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
        'Park': 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-200',
        'Government Office': 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200',
        'Historic': 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200',
        'Religious': 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200',
        'Commercial': 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200',
        'Residential': 'bg-cyan-100 text-cyan-800 dark:bg-cyan-900 dark:text-cyan-200',
        'Transportation': 'bg-teal-100 text-teal-800 dark:bg-teal-900 dark:text-teal-200',
    };
    
    return colorMap[categoryName] || 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-200';
};

function ViewLocation({ location }: ViewLocationProps) {
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
                        <MapPin className="h-5 w-5" />
                        Location Details
                    </SheetTitle>
                    <SheetDescription>
                        View detailed information about this location.
                    </SheetDescription>
                </SheetHeader>
                <div className="flex-1 overflow-y-auto px-4 py-6 pb-20">
                    <div className="grid auto-rows-min gap-6">
                    <div className="grid gap-3">
                        <Label htmlFor="location-name">Location Name</Label>
                        <Input
                            id="location-name"
                            value={location.location_name}
                            readOnly
                            className="bg-muted"
                        />
                    </div>

                    <div className="grid gap-3">
                        <Label htmlFor="landmark">Near Landmark</Label>
                        <Input
                            id="landmark"
                            value={location.landmark}
                            readOnly
                            className="bg-muted"
                        />
                    </div>

                    <div className="grid gap-3">
                        <Label htmlFor="barangay">Barangay</Label>
                        <Input
                            id="barangay"
                            value={location.barangay}
                            readOnly
                            className="bg-muted"
                        />
                    </div>

                    <div className="grid gap-3">
                        <Label htmlFor="category">Category</Label>
                        <div className="p-2">
                            <span className={`inline-flex items-center px-2 py-1 rounded-full text-sm font-medium ${getCategoryColor(location.location_category?.name || 'Uncategorized')}`}>
                                {location.location_category ? location.location_category.name : 'Uncategorized'}
                            </span>
                        </div>
                    </div>

                    <div className="grid grid-cols-2 gap-3">
                        <div className="space-y-2">
                            <Label htmlFor="latitude">Latitude</Label>
                            <Input
                                id="latitude"
                                value={Number(location.latitude).toFixed(2)}
                                readOnly
                                className="bg-muted"
                            />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="longitude">Longitude</Label>
                            <Input
                                id="longitude"
                                value={Number(location.longitude).toFixed(2)}
                                readOnly
                                className="bg-muted"
                            />
                        </div>
                    </div>

                    <div className="grid gap-3">
                        <Label htmlFor="cameras">Available Cameras</Label>
                        <div className="p-2">
                            <span className="text-2xl font-bold text-primary">
                                {location.cctv_count || 0}
                            </span>
                            <span className="text-sm text-muted-foreground ml-2">cameras</span>
                        </div>
                    </div>

                    <div className="grid gap-3">
                        <Label htmlFor="description">Description</Label>
                        <Textarea
                            id='description'
                            className='resize-none bg-muted'
                            value={location.description || 'No description provided.'}
                            readOnly
                            rows={4}
                        />
                    </div>
                </div>
                </div>
                <SheetFooter className="sticky bottom-0 z-10 bg-background pt-4 px-4">
                    <SheetClose asChild>
                        <Button variant="outline">Close</Button>
                    </SheetClose>
                </SheetFooter>
            </SheetContent>
        </Sheet>
    )
}

export default ViewLocation