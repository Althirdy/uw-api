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
import { Check, Clock, LocateFixed, ExternalLink as Open } from 'lucide-react';

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

    return (
        <Card className="flex h-full flex-col rounded-[var(--radius)]">
            <CardHeader>
                <CardTitle className="line-clamp-2 text-base">
                    <div className="flex flex-col gap-2">
                        <div className="flex flex-row items-center gap-2">
<<<<<<< HEAD
                            <Badge variant="default" className="text-sm">
                                {report.report_type.toLocaleUpperCase()}
                            </Badge>
                            <Badge variant="default" className="text-sm">
=======
                            <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${
                                report.report_type === 'CCTV' ? 'bg-sky-100 text-sky-700 dark:bg-sky-900/30 dark:text-sky-400' :
                                report.report_type === 'Citizen Concern' ? 'bg-violet-100 text-violet-700 dark:bg-violet-900/30 dark:text-violet-400' :
                                report.report_type === 'Emergency' ? 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-400' :
                                'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-400'
                            }`}>
                                {report.report_type.toLocaleUpperCase()}
                            </span>
                            <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${
                                report.status === 'Ongoing' ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400' :
                                report.status === 'Pending' ? 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400' :
                                report.status === 'Resolved' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400' :
                                'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-400'
                            }`}>
>>>>>>> 1b8f3af (tempo lang)
                                {report.status.toLocaleUpperCase()}
                            </Badge>
                        </div>

                        <span className="text-md font-semibold">
                            {report.transcript}
                        </span>
                    </div>
                </CardTitle>
            </CardHeader>
            <CardContent className="flex-1 space-y-2">
                <div className="flex flex-col text-xs text-muted-foreground">
                    <span className="flex flex-row items-center gap-2 text-sm">
                        <LocateFixed className="mr-1 inline h-4 w-4" />
                        {report.latitute}, {report.longtitude}
                    </span>
                    <span className="flex flex-row items-center gap-2 text-sm">
                        <Clock className="mr-1 inline h-4 w-4" />
                        {formatRelativeTime(report.created_at)}
                    </span>
                </div>
                <CardDescription className="text-md line-clamp-3">
                    {report.description}
                </CardDescription>
            </CardContent>
            <CardFooter className="flex justify-end space-x-2 pt-2">
                <ViewReportDetails report={report}>
                    <Button
                        variant="outline"
                        size="sm"
                        className="cursor-pointer"
                    >
                        <Open className="mr-1 inline h-4 w-4" />
                        View Details
                    </Button>
                </ViewReportDetails>
                {report.status === 'Pending' && (
                    <Button
                        variant="default"
                        size="sm"
                        className="cursor-pointer"
                        onClick={handleAcknowledge}
                        disabled={report.is_acknowledge}
                    >
                        <Check className="mr-1 inline h-4 w-4" />
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
