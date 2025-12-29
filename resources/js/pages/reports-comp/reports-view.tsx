import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { PlaceholderPattern } from '@/components/ui/placeholder-pattern';
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
        <Dialog>
            <DialogTrigger asChild>{children}</DialogTrigger>
            <DialogContent
                className="flex max-h-[90vh] max-w-none flex-col overflow-hidden p-0 sm:max-w-2xl"
                showCloseButton={false}
            >
                <DialogHeader className="flex-shrink-0 px-6 pt-6 pb-4">
                    <DialogTitle>Incident Details</DialogTitle>
                    <DialogDescription className="flex flex-col gap-2">
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
                    </DialogDescription>
                </DialogHeader>
                <div className="flex w-full flex-1 flex-col justify-start gap-4 overflow-y-auto px-6 py-4">
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
                                text: `${Number(report.latitude).toFixed(2)}, ${Number(report.longtitude).toFixed(2)}`,
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
                <DialogFooter className="flex-shrink-0 px-6 py-4">
                    <div className="flex w-full gap-2">
                        <DialogClose asChild>
                            <Button
                                variant="outline"
                                className={
                                    !report.is_acknowledge ? 'flex-1' : 'w-full'
                                }
                            >
                                {!report.is_acknowledge && (
                                    <MoveLeft className="inline h-4 w-4" />
                                )}
                                Close
                            </Button>
                        </DialogClose>
                        {!report.is_acknowledge && (
                            <Button
                                variant="default"
                                onClick={handleAcknowledge}
                                className="flex-2"
                            >
                                <Check className="inline h-4 w-4" />
                                Acknowledge
                            </Button>
                        )}
                    </div>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
export default ViewReportDetails;
