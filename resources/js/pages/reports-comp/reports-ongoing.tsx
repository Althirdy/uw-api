import { ImagePreview } from '@/components/image-preview';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardFooter,
    CardHeader,
} from '@/components/ui/card';
import { formatRelativeTime } from '@/lib/utils';
import { router } from '@inertiajs/react';
import {
    Camera,
    Check,
    Clock,
    ExternalLink as Open,
    LocateFixed,
} from 'lucide-react';

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

    // Get first image from media
    const firstImage =
        report.media && report.media.length > 0 ? report.media[0] : null;

    // Determine severity color for badge
    const getSeverityColor = (severity: string) => {
        switch (severity?.toLowerCase()) {
            case 'high':
                return 'bg-red-500 hover:bg-red-600';
            case 'medium':
                return 'bg-orange-500 hover:bg-orange-600';
            case 'low':
                return 'bg-yellow-500 hover:bg-yellow-600';
            default:
                return 'bg-primary';
        }
    };

    // Determine severity color for border
    const getSeverityBorderColor = (severity: string) => {
        switch (severity?.toLowerCase()) {
            case 'high':
                return 'border-l-red-500';
            case 'medium':
                return 'border-l-orange-500';
            case 'low':
                return 'border-l-yellow-500';
            default:
                return 'border-l-slate-500';
        }
    };

    return (
        <Card
            className={`flex h-full flex-col overflow-hidden rounded-[var(--radius)] border-l-4 shadow-md transition-shadow hover:shadow-lg ${getSeverityBorderColor((report as any).severity || 'high')}`}
        >
            {/* Accident Image - Reduced height */}
            {firstImage ? (
                <ImagePreview src={firstImage} alt="Accident detection">
                    <div className="group relative h-40 w-full overflow-hidden bg-muted">
                        <img
                            src={firstImage}
                            alt="Accident detection"
                            className="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"
                        />
                        <div className="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent opacity-80" />

                        <div className="absolute top-2 right-2">
                            <Badge
                                variant="destructive"
                                className="animate-pulse px-1.5 py-0 text-[10px] font-bold shadow-sm"
                            >
                                <Camera className="mr-1 h-2.5 w-2.5" />
                                AI DETECTED
                            </Badge>
                        </div>

                        <div className="absolute bottom-2 left-2 right-2 text-white">
                            <div className="flex items-center gap-1.5">
                                <Badge
                                    className={`${getSeverityColor((report as any).severity || 'medium')} h-5 border-0 px-1.5 text-[10px] text-white`}
                                >
                                    {(
                                        (report as any).severity || 'MEDIUM'
                                    ).toUpperCase()}
                                </Badge>
                                <span className="flex items-center rounded-full bg-black/40 px-1.5 py-0.5 text-[9px] font-medium backdrop-blur-sm">
                                    <Clock className="mr-1 h-2.5 w-2.5" />
                                    {formatRelativeTime(report.created_at)}
                                </span>
                            </div>
                        </div>
                    </div>
                </ImagePreview>
            ) : (
                <div className="relative flex h-40 w-full items-center justify-center bg-muted">
                    <Camera className="h-10 w-10 text-muted-foreground/20" />
                </div>
            )}

            <CardHeader className="p-3 pb-1">
                <div className="flex items-start justify-between gap-2">
                    <div className="space-y-1">
                        <h3 className="line-clamp-1 text-base font-extrabold leading-tight tracking-tight text-foreground">
                            {report.transcript || 'Incident Detected'}
                        </h3>
                        <div className="flex items-center gap-1.5 text-xs font-medium text-muted-foreground">
                            <LocateFixed className="h-3.5 w-3.5 text-primary" />
                            <span className="truncate">
                                {report.location_name || `${report.latitute}, ${report.longtitude}`}
                            </span>
                        </div>
                    </div>
                </div>
            </CardHeader>

            <CardContent className="flex-1 p-3 pt-1 pb-1">
                <p className="line-clamp-2 text-xs font-medium leading-relaxed text-muted-foreground/90">
                    {report.description}
                </p>
            </CardContent>

            <CardFooter className="grid grid-cols-2 gap-2 p-3 pb-3 pt-1">
                <Button
                    onClick={handleAcknowledge}
                    size="sm"
                    className="h-8 w-full text-xs font-bold"
                >
                    <Check className="mr-1.5 h-3.5 w-3.5" />
                    Acknowledge
                </Button>

                <ViewReportDetails report={report}>
                    <Button variant="outline" size="sm" className="h-8 w-full text-xs">
                        <Open className="mr-1.5 h-3.5 w-3.5" />
                        Details
                    </Button>
                </ViewReportDetails>
            </CardFooter>
        </Card>
    );
};

export default OngoingReport;
