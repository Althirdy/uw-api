import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { formatRelativeTime } from '@/lib/utils';
import { router } from '@inertiajs/react';
import { Check, Clock, LocateFixed, ExternalLink as Open, Camera } from 'lucide-react';

import { reports_T } from '@/types/report-types';
import ViewReportDetails from './reports-view';

const OngoingReport = ({ report }: { report: reports_T }) => {
    const handleAcknowledge = () => {
        router.patch(
            `/report/${report.id}/acknowledge`,
            {},
            {
                preserveScroll: false,
                onSuccess: () => {
                    router.reload();
                },
            },
        );
    };

    const handleResolve = () => {
        router.patch(
            `/report/${report.id}/resolve`,
            {},
            {
                preserveScroll: true,
                onSuccess: () => {
                    router.reload({ only: ['reports'] });
                },
            },
        );
    };

    // Get first image from media
    const firstImage = report.media && report.media.length > 0 
        ? report.media[0]
        : null;

    return (
        <Card className="flex h-full flex-col rounded-[var(--radius)] overflow-hidden">
            {/* Accident Image - Full width at top */}
            {firstImage ? (
                <div className="relative h-48 w-full overflow-hidden bg-muted">
                    <img 
                        src={firstImage} 
                        alt="Accident detection" 
                        className="h-full w-full object-cover"
                    />
                    <div className="absolute top-2 right-2">
                        <Badge variant="destructive" className="text-xs">
                            <Camera className="mr-1 h-3 w-3" />
                            YOLO Detection
                        </Badge>
                    </div>
                </div>
            ) : (
                <div className="relative h-48 w-full bg-muted flex items-center justify-center">
                    <Camera className="h-12 w-12 text-muted-foreground/20" />
                </div>
            )}

            <CardHeader className="pb-3">
                <CardTitle className="line-clamp-2 text-base">
                    <div className="flex flex-col gap-2">
                        <div className="flex flex-row items-center gap-2 flex-wrap">
                            <Badge variant="default" className="text-xs">
                                {report.report_type.toLocaleUpperCase()}
                            </Badge>
                            <Badge 
                                variant={report.status === 'Pending' ? 'destructive' : 'default'} 
                                className="text-xs"
                            >
                                {report.status.toLocaleUpperCase()}
                            </Badge>
                        </div>

                        <span className="text-base font-semibold">
                            {report.transcript}
                        </span>
                    </div>
                </CardTitle>
            </CardHeader>
            <CardContent className="flex-1 space-y-2 pb-3">
                <CardDescription className="text-sm line-clamp-2">
                    {report.description}
                </CardDescription>
                <div className="flex flex-col gap-1 text-xs text-muted-foreground">
                    <span className="flex flex-row items-center gap-1">
                        <LocateFixed className="h-3 w-3" />
                        {report.latitute}, {report.longtitude}
                    </span>
                    <span className="flex flex-row items-center gap-1">
                        <Clock className="h-3 w-3" />
                        {formatRelativeTime(report.created_at)}
                    </span>
                </div>
            </CardContent>
            <CardFooter className="flex justify-between gap-2 pt-2">
                <ViewReportDetails report={report}>
                    <Button
                        variant="outline"
                        size="sm"
                        className="flex-1 cursor-pointer"
                    >
                        <Open className="mr-1 h-4 w-4" />
                        View Details
                    </Button>
                </ViewReportDetails>
                {report.status === 'Pending' && (
                    <Button
                        variant="default"
                        size="sm"
                        className="flex-1 cursor-pointer"
                        onClick={handleAcknowledge}
                        disabled={report.is_acknowledge}
                    >
                        <Check className="mr-1 h-4 w-4" />
                        {report.is_acknowledge ? 'Acknowledged' : 'Acknowledge'}
                    </Button>
                )}
                {report.status === 'Ongoing' && (
                    <Button
                        variant="default"
                        size="sm"
                        className="cursor-pointer bg-green-600 hover:bg-green-700"
                        onClick={handleResolve}
                    >
                        <Check className="mr-1 inline h-4 w-4" />
                        Resolve
                    </Button>
                )}
            </CardFooter>
        </Card>
    );
};

export default OngoingReport;
