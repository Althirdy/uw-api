import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardFooter,
    CardHeader,
} from '@/components/ui/card';
import { Cctv, ExternalLink, MapPin, SquarePen, Trash2 } from 'lucide-react';

import { location_T, LocationCategory_T } from '@/types/location-types';
import { paginated_T } from '../../types/cctv-location-types';
import DeleteLocation from './locations-archive';
import EditLocation from './locations-edit';
import ViewLocation from './locations-view';

function LocationCardView({
    location,
    locationCategory = [],
}: {
    location?: paginated_T<location_T>;
    locationCategory?: LocationCategory_T[];
}) {
    return (
        <div className="space-y-6">
            {/* Location Cards */}
            <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                {(location?.data || []).map((loc: location_T) => (
                    <Card
                        key={loc.id}
                        className="rounded-lg border bg-card p-4"
                    >
                        <CardHeader className="flex-row items-center p-0">
                            <MapPin className="h-6 w-6 text-muted-foreground" />
                            <div className="flex flex-1 items-center justify-between">
                                <div>
                                    <h3 className="font-semibold">
                                        {loc.location_name}
                                    </h3>
                                    <p className="text-sm text-muted-foreground">
                                        {loc.landmark}
                                    </p>
                                    <p className="flex items-center gap-2 text-sm text-muted-foreground">
                                        <Cctv size={18} />
                                        <span>{loc.cctv_count} Camera</span>
                                    </p>
                                </div>
                                <span className="rounded text-sm font-medium text-muted-foreground">
                                    {loc.location_category?.name}
                                </span>
                            </div>
                        </CardHeader>
                        <CardContent className="">
                            <span className="text-sm text-muted-foreground">
                                Description
                            </span>
                            <p>{loc.description}</p>
                        </CardContent>
                        <CardFooter className="justify-end p-0">
                            <ViewLocation location={loc}>
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    className="h-8 w-8"
                                >
                                    <ExternalLink className="h-4 w-4" />
                                </Button>
                            </ViewLocation>
                            <EditLocation
                                location={loc}
                                locationCategory={locationCategory}
                            >
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    className="h-8 w-8"
                                >
                                    <SquarePen className="h-4 w-4" />
                                </Button>
                            </EditLocation>
                            <DeleteLocation location={loc}>
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    className="h-8 w-8"
                                >
                                    <Trash2 className="h-4 w-4 text-destructive" />
                                </Button>
                            </DeleteLocation>
                        </CardFooter>
                    </Card>
                ))}
            </div>
        </div>
    );
}

export default LocationCardView;
