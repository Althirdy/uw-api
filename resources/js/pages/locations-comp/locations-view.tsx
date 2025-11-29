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
import { Textarea } from '@/components/ui/textarea';
import { location_T } from '@/types/location-types';
import { Cctv, MoveLeft } from 'lucide-react';

type ViewLocationProps = {
    location: location_T;
    children: React.ReactNode;
};

// Function to get category color based on category name
const getCategoryColor = (categoryName: string) => {
    const colorMap: { [key: string]: string } = {
        School: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
        Hospital: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
        Market: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
        Park: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-200',
        'Government Office':
            'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200',
        Historic:
            'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200',
        Religious:
            'bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200',
        Commercial:
            'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200',
        Residential:
            'bg-cyan-100 text-cyan-800 dark:bg-cyan-900 dark:text-cyan-200',
        Transportation:
            'bg-teal-100 text-teal-800 dark:bg-teal-900 dark:text-teal-200',
    };

    return (
        colorMap[categoryName] ||
        'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-200'
    );
};

function ViewLocation({ location, children }: ViewLocationProps) {
    return (
        <Sheet>
            <SheetTrigger asChild>{children}</SheetTrigger>
            <SheetContent className="max-w-none overflow-y-auto p-4 sm:max-w-lg [&>button]:hidden">
                <SheetHeader className="sticky top-0 z-10 bg-background">
                    <SheetTitle className="flex flex-row items-center gap-2 text-xl">
                        {location.location_name}
                    </SheetTitle>
                    <SheetDescription className="flex flex-row gap-4">
                        <span
                            className={`inline-flex items-center rounded-full px-2 py-1 text-sm font-medium ${getCategoryColor(location.location_category?.name || 'Uncategorized')}`}
                        >
                            {location.location_category
                                ? location.location_category.name
                                : 'Uncategorized'}
                        </span>

                        <span className="flex flex-row items-center gap-2 text-lg">
                            <Cctv className="h-auto w-4.5 text-muted-foreground" />
                            {location.cctv_count || 0}
                        </span>
                    </SheetDescription>
                </SheetHeader>
                <div className="flex w-full flex-col justify-start gap-10 px-4 py-2">
                    <div className="grid auto-rows-min gap-6">
                        <div className="grid gap-3">
                            <Label>Location Map</Label>
                            <div className="h-64 w-full overflow-hidden rounded-[var(--radius)] border">
                                <iframe
                                    width="100%"
                                    height="100%"
                                    frameBorder="0"
                                    style={{ border: 0 }}
                                    src={`https://www.openstreetmap.org/export/embed.html?bbox=${Number(location.longitude) - 0.01},${Number(location.latitude) - 0.01},${Number(location.longitude) + 0.01},${Number(location.latitude) + 0.01}&layer=mapnik&marker=${location.latitude},${location.longitude}`}
                                    loading="lazy"
                                    referrerPolicy="no-referrer-when-downgrade"
                                ></iframe>
                            </div>
                        </div>

                        <div className="flex flex-col">
                            <span className="text-sm text-white">
                                GPS Coordinates{' '}
                            </span>
                            <div className="flex w-full flex-row justify-between gap-3">
                                <div className="w-full space-y-2">
                                    <Label
                                        htmlFor="latitude"
                                        className="text-muted-foreground"
                                    >
                                        Latitude
                                    </Label>
                                    <Input
                                        id="latitude"
                                        value={Number(
                                            location.latitude,
                                        ).toFixed(2)}
                                        readOnly
                                        tabIndex={-1}
                                        className="cursor-not-allowed border-none bg-muted select-none focus:ring-0 focus:ring-offset-0 focus:outline-none focus-visible:ring-0 focus-visible:ring-offset-0 focus-visible:outline-none"
                                    />
                                </div>
                                <div className="w-full space-y-2">
                                    <Label
                                        htmlFor="longitude"
                                        className="text-muted-foreground"
                                    >
                                        Longitude
                                    </Label>

                                    <Input
                                        id="longitude"
                                        value={Number(
                                            location.longitude,
                                        ).toFixed(2)}
                                        readOnly
                                        tabIndex={-1}
                                        className="cursor-not-allowed border-none bg-muted select-none focus:ring-0 focus:ring-offset-0 focus:outline-none focus-visible:ring-0 focus-visible:ring-offset-0 focus-visible:outline-none"
                                    />
                                </div>
                            </div>
                        </div>

                        <div className="grid gap-3">
                            <Label htmlFor="landmark">Near Landmark</Label>
                            <Input
                                id="landmark"
                                value={location.landmark}
                                readOnly
                                tabIndex={-1}
                                className="cursor-not-allowed border-none bg-muted select-none focus:ring-0 focus:ring-offset-0 focus:outline-none focus-visible:ring-0 focus-visible:ring-offset-0 focus-visible:outline-none"
                            />
                        </div>

                        <div className="grid gap-3">
                            <Label htmlFor="barangay">Barangay</Label>
                            <Input
                                id="barangay"
                                value={location.barangay}
                                readOnly
                                tabIndex={-1}
                                className="cursor-not-allowed border-none bg-muted select-none focus:ring-0 focus:ring-offset-0 focus:outline-none focus-visible:ring-0 focus-visible:ring-offset-0 focus-visible:outline-none"
                            />
                        </div>

                        <div className="grid gap-3">
                            <Label htmlFor="description">Description</Label>
                            <Textarea
                                id="description"
                                className="cursor-not-allowed resize-none border-none bg-muted select-none focus:ring-0 focus:ring-offset-0 focus:outline-none focus-visible:ring-0 focus-visible:ring-offset-0 focus-visible:outline-none"
                                value={
                                    location.description ||
                                    'No description provided.'
                                }
                                readOnly
                                tabIndex={-1}
                                rows={4}
                            />
                        </div>
                    </div>
                </div>
                <SheetFooter className="sticky bottom-0 z-10 bg-background">
                    <SheetClose asChild>
                        <Button variant="outline">
                            <MoveLeft className="h-6 w-6" />
                            Return
                        </Button>
                    </SheetClose>
                </SheetFooter>
            </SheetContent>
        </Sheet>
    );
}

export default ViewLocation;
