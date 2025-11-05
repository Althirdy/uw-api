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
import { Archive, ExternalLink as Open, SquarePen, Camera } from 'lucide-react';

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
        <div className="grid auto-rows-min gap-4 grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            {reports.length === 0 && (
                <div className="col-span-full py-8 text-center text-gray-500">
                    No reports found matching your selection.
                </div>
            )}

            {/* Use filtered_roles for displaying cards */}
            {reports.map((report) => {
                // Get first image from media
                const firstImage = report.media && report.media.length > 0 
                    ? report.media[0]
                    : null;

                return (
                <Card
                    key={report.id}
                    className="relative overflow-hidden rounded-[var(--radius)] border border-sidebar-border/70 dark:border-sidebar-border flex flex-col"
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

                    <CardHeader>
                        <CardTitle>
                            {' '}
                            <span>Report ID: #{report.id}</span>
                        </CardTitle>
                        <CardDescription>
                            {' '}
                            <Badge variant="default" className="text-sm">
                                {report.status}
                            </Badge>
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
