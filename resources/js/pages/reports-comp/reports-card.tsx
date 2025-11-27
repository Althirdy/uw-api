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
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { Archive, Camera, ExternalLink as Open, SquarePen } from 'lucide-react';

import { reports_T } from '@/types/report-types';
import ArchiveReport from './reports-archive';
import EditReport from './reports-edit';
import ViewReportDetails from './reports-view';

type ReportsCardProps = {
    reports: reports_T[];
    reportTypes: string[];
};

const ReportsCard = ({ reports, reportTypes }: ReportsCardProps) => {
    return (
        <div className="grid auto-rows-min grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            {reports.length === 0 && (
                <Card className="col-span-full rounded-[var(--radius)] border border-sidebar-border/70 dark:border-sidebar-border">
                    <CardContent className="flex items-center justify-center py-12">
                        <p className="text-muted-foreground">
                            No reports found matching your selection.
                        </p>
                    </CardContent>
                </Card>
            )}

            {/* Use filtered_roles for displaying cards */}
            {reports.map((report) => {
                // Get first image from media
                const firstImage =
                    report.media && report.media.length > 0
                        ? report.media[0]
                        : null;

                return (
                    <Card
                        key={report.id}
                        className="relative flex flex-col overflow-hidden rounded-[var(--radius)] border border-sidebar-border/70 dark:border-sidebar-border"
                    >
                        {/* Accident Image - Full width at top */}
                        {firstImage ? (
                            <div className="relative h-48 w-full overflow-hidden bg-muted">
                                <img
                                    src={firstImage}
                                    alt="Accident detection"
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
                            </div>
                        ) : (
                            <div className="relative flex h-48 w-full items-center justify-center bg-muted">
                                <Camera className="h-12 w-12 text-muted-foreground/20" />
                            </div>
                        )}

                        <CardHeader>
                            <CardTitle>
                                {' '}
                                <span>Report ID: #{report.id}</span>
                            </CardTitle>
                            <CardDescription>
                                <div className="flex gap-2">
                                    <span
                                        className={`inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ${
                                            report.report_type === 'CCTV'
                                                ? 'bg-sky-100 text-sky-700 dark:bg-sky-900/30 dark:text-sky-400'
                                                : report.report_type ===
                                                    'Citizen Concern'
                                                  ? 'bg-violet-100 text-violet-700 dark:bg-violet-900/30 dark:text-violet-400'
                                                  : report.report_type ===
                                                      'Emergency'
                                                    ? 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-400'
                                                    : 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-400'
                                        }`}
                                    >
                                        {report.report_type}
                                    </span>
                                    <span
                                        className={`inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ${
                                            report.status === 'Ongoing'
                                                ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400'
                                                : report.status === 'Pending'
                                                  ? 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400'
                                                  : report.status === 'Resolved'
                                                    ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400'
                                                    : 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-400'
                                        }`}
                                    >
                                        {report.status}
                                    </span>
                                </div>
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="flex-1">
                            <div className="flex flex-col">
                                <p className="text-md">Incident Description</p>
                                <p className="text-md text-muted-foreground">
                                    {report.description}
                                </p>
                            </div>
                        </CardContent>
                        <CardFooter>
                            <div className="flex w-full justify-end gap-2">
                                <Tooltip>
                                    <ViewReportDetails report={report}>
                                        <TooltipTrigger asChild>
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                className="cursor-pointer"
                                            >
                                                <Open className="h-4 w-4" />
                                            </Button>
                                        </TooltipTrigger>
                                    </ViewReportDetails>
                                    <TooltipContent>
                                        <p>View Details</p>
                                    </TooltipContent>
                                </Tooltip>
                                <Tooltip>
                                    <EditReport
                                        report={report}
                                        reportTypes={reportTypes}
                                    >
                                        <TooltipTrigger asChild>
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                className="cursor-pointer"
                                            >
                                                <SquarePen className="h-4 w-4" />
                                            </Button>
                                        </TooltipTrigger>
                                    </EditReport>
                                    <TooltipContent>
                                        <p>Edit User</p>
                                    </TooltipContent>
                                </Tooltip>

                                <Tooltip>
                                    <ArchiveReport report={report}>
                                        <TooltipTrigger asChild>
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                className="cursor-pointer"
                                            >
                                                <Archive className="h-4 w-4 text-[var(--destructive)]" />
                                            </Button>
                                        </TooltipTrigger>
                                    </ArchiveReport>
                                    <TooltipContent>
                                        <p>Archive User</p>
                                    </TooltipContent>
                                </Tooltip>
                            </div>
                        </CardFooter>
                    </Card>
                );
            })}
        </div>
    );
};

export default ReportsCard;
