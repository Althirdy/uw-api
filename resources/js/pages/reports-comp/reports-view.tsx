import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { PlaceholderPattern } from '@/components/ui/placeholder-pattern';
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
import { formatDateTime } from '@/lib/utils';
import { reports_T } from '@/types/report-types';
import { router } from '@inertiajs/react';
import {
    Camera,
    Check,
    Clock,
    LocateFixed,
    Mail,
    MoveLeft,
    Phone,
    TriangleAlert,
    User,
} from 'lucide-react';

type ViewReportDetailsProps = {
    report: reports_T;
    children: React.ReactNode;
};

type DetailItem = {
    icon: React.ComponentType<{ className?: string }>;
    text: string;
};

function renderDetailItems(items: DetailItem[]) {
    return items.map(({ icon: Icon, text }, index) => (
        <div
            key={index}
            className="flex flex-row items-center gap-2 text-muted-foreground"
        >
            <Icon className="h-5 w-5" />
            <span>{text}</span>
        </div>
    ));
}

function ViewReportDetails({ report, children }: ViewReportDetailsProps) {
    const handleAcknowledge = () => {
        router.patch(
            `/report/${report.id}/acknowledge`,
            {},
            {
                preserveScroll: true,
            },
        );
    };

    // Get first image from media
    const firstImage =
        report.media && report.media.length > 0 ? report.media[0] : null;

    return (
        <Sheet>
            <SheetTrigger asChild>{children}</SheetTrigger>
            <SheetContent className="max-w-none overflow-y-auto p-2 sm:max-w-lg [&>button]:hidden">
                <SheetHeader className="sticky top-0 z-10 bg-background">
                    <SheetTitle>Incident Details</SheetTitle>
                    <SheetDescription className="flex flex-col gap-2">
                        <span className="text-base font-semibold">
                            Report ID: #{report.id}
                        </span>
                        <div className="flex flex-wrap gap-2">
                            <Badge variant="default" className="text-xs">
                                {report.report_type}
                            </Badge>
                            <Badge
                                variant={
                                    report.status === 'Pending'
                                        ? 'destructive'
                                        : 'default'
                                }
                                className="text-xs"
                            >
                                {report.status.toUpperCase()}
                            </Badge>
                        </div>
                    </SheetDescription>
                </SheetHeader>
                <div className="flex w-full flex-col justify-start gap-4 px-4 py-2">
                    {/* Accident Image */}
                    <div className="flex flex-col gap-2">
                        <p className="text-md font-semibold">
                            Incident Snapshot
                        </p>
                        <div className="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                            {firstImage ? (
                                <>
                                    <img
                                        src={firstImage}
                                        alt="Accident snapshot"
                                        className="h-full w-full object-cover"
                                    />
                                    <div className="absolute top-2 right-2">
                                        <Badge
                                            variant="destructive"
                                            className="text-xs"
                                        >
                                            <Camera className="mr-1 h-3 w-3" />
                                            YOLO Detection
                                        </Badge>
                                    </div>
                                </>
                            ) : (
                                <div className="relative flex h-full w-full items-center justify-center bg-muted">
                                    <PlaceholderPattern className="absolute inset-0 size-full stroke-neutral-900/20 dark:stroke-neutral-100/20" />
                                    <Camera className="relative z-10 h-16 w-16 text-muted-foreground/20" />
                                </div>
                            )}
                        </div>
                    </div>

                    {/* Incident Description */}
                    <div className="flex flex-col gap-1">
                        <p className="text-md font-semibold">
                            Incident Description
                        </p>
                        <p className="text-sm text-muted-foreground">
                            {report.description}
                        </p>
                    </div>

                    {/* Details */}
                    <div className="flex flex-col gap-2">
                        <p className="text-md font-semibold">Details</p>
                        {renderDetailItems([
                            {
                                icon: LocateFixed,
                                text: `${Number(report.latitute).toFixed(2)}, ${Number(report.longtitude).toFixed(2)}`,
                            },
                            {
                                icon: TriangleAlert,
                                text: report.report_type,
                            },
                            {
                                icon: Clock,
                                text: `Reported: ${formatDateTime(report.created_at)}`,
                            },
                        ])}
                    </div>

                    {/* Report Information - Show "Unknown" for YOLO detections */}
                    <div className="flex flex-col gap-2">
                        <p className="text-md font-semibold">
                            Report Information
                        </p>
                        {renderDetailItems([
                            {
                                icon: User,
                                text: report.user?.name || 'Unknown',
                            },
                            {
                                icon: Mail,
                                text: report.user?.email || 'No email provided',
                            },
                            {
                                icon: Phone,
                                text:
                                    report.user?.official_details
                                        ?.contact_number || 'undefined',
                            },
                        ])}
                    </div>
                </div>
                <SheetFooter className="sticky bottom-0 z-10 flex-shrink-0 border-t bg-background px-4 py-4">
                    <div className="flex w-full gap-2">
                        {!report.is_acknowledge && (
                            <Button
                                variant="default"
                                onClick={handleAcknowledge}
                                className="flex-1"
                            >
                                <Check className="mr-2 inline h-4 w-4" />
                                Acknowledge
                            </Button>
                        )}
                        <SheetClose asChild>
                            <Button
                                variant="outline"
                                className={
                                    !report.is_acknowledge ? 'flex-1' : 'w-full'
                                }
                            >
                                {!report.is_acknowledge && (
                                    <MoveLeft className="mr-2 inline h-4 w-4" />
                                )}
                                Return
                            </Button>
                        </SheetClose>
                    </div>
                </SheetFooter>
            </SheetContent>
        </Sheet>
    );
}
export default ViewReportDetails;
