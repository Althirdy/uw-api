import { Badge } from '@/components/ui/badge';
import {
    Pagination,
    PaginationContent,
    PaginationEllipsis,
    PaginationItem,
    PaginationLink,
    PaginationNext,
    PaginationPrevious,
} from '@/components/ui/pagination';
import { Separator } from '@/components/ui/separator';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { useAccidentRealtime } from '@/hooks/use-accident-realtime';
import AppLayout from '@/layouts/app-layout';
import { reports as reportRoutes } from '@/routes';
import { BreadcrumbItem } from '@/types';
import { ReportsProps, reports_T } from '@/types/report-types';
import { Head } from '@inertiajs/react';
import { AlertCircle, LayoutGrid, List, Wifi, WifiOff } from 'lucide-react';
import { useState } from 'react';
import ReportsCard from './reports-comp/reports-card';
import OngoingReport from './reports-comp/reports-ongoing';
import ReportActionTab from './reports-comp/reports-tab';
import ReportsTable from './reports-comp/reports-table';

const Reports = ({ reports, reportTypes }: ReportsProps) => {
    const [filteredReports, setFilteredReports] = useState<reports_T[]>(
        reports.data,
    );

    // Real-time connection
    const { isConnected } = useAccidentRealtime();

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Incident Monitoring',
            href: reportRoutes().url,
        },
    ];

    const ongoingReports = reports.data.filter(
        (report) => !report.is_acknowledge,
    );

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Incident Monitoring" />
            <div className="mx-auto max-w-[1600px] space-y-8 p-6">
                {/* Header Section */}
                <div className="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight text-foreground">
                            Incident Monitoring
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            Real-time overview of detected accidents and citizen
                            reports.
                        </p>
                    </div>
                    <div className="flex items-center gap-2">
                        <Badge
                            variant={isConnected ? 'outline' : 'destructive'}
                            className={`gap-1.5 px-3 py-1 ${isConnected ? 'border-green-500 text-green-600 bg-green-50 dark:bg-green-950/20' : ''}`}
                        >
                            {isConnected ? (
                                <>
                                    <span className="relative flex h-2 w-2">
                                        <span className="absolute inline-flex h-full w-full animate-ping rounded-full bg-green-400 opacity-75"></span>
                                        <span className="relative inline-flex h-2 w-2 rounded-full bg-green-500"></span>
                                    </span>
                                    <span className="font-medium">
                                        System Online
                                    </span>
                                </>
                            ) : (
                                <>
                                    <WifiOff className="h-3.5 w-3.5" />
                                    <span>Reconnecting...</span>
                                </>
                            )}
                        </Badge>
                    </div>
                </div>

                {/* Ongoing Incidents Section */}
                {ongoingReports.length > 0 && (
                    <div className="space-y-4 rounded-xl border border-red-100 bg-red-50/50 p-6 dark:border-red-900/20 dark:bg-red-950/10">
                        <div className="flex items-center gap-2 text-red-600 dark:text-red-400">
                            <AlertCircle className="h-5 w-5 animate-pulse" />
                            <h2 className="font-bold uppercase tracking-wide text-sm">
                                Critical Attention Needed ({ongoingReports.length})
                            </h2>
                        </div>
                        <div className="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                            {ongoingReports.map((report) => (
                                <OngoingReport
                                    key={report.id}
                                    report={report}
                                />
                            ))}
                        </div>
                    </div>
                )}

                <Separator className="my-6" />

                {/* All Reports Section */}
                <div className="space-y-6">
                    <Tabs defaultValue="card" className="w-full">
                        <div className="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                            <h2 className="text-lg font-semibold text-foreground">
                                Incident History
                            </h2>
                            <div className="flex items-center gap-2">
                                <ReportActionTab
                                    reports={reports}
                                    reportTypes={reportTypes}
                                    setFilteredReports={setFilteredReports}
                                />
                                <TabsList className="grid h-10 w-24 grid-cols-2">
                                    <TabsTrigger value="card">
                                        <LayoutGrid className="h-4 w-4" />
                                    </TabsTrigger>
                                    <TabsTrigger value="table">
                                        <List className="h-4 w-4" />
                                    </TabsTrigger>
                                </TabsList>
                            </div>
                        </div>

                        <TabsContent value="card" className="mt-0 space-y-8">
                            <ReportsCard
                                reports={filteredReports}
                                reportTypes={reportTypes}
                            />

                            {/* Pagination */}
                            {reports.links && (
                                <div className="flex justify-center pt-4">
                                    <Pagination>
                                        <PaginationContent>
                                            <PaginationItem>
                                                <PaginationPrevious
                                                    href={
                                                        reports.prev_page_url ||
                                                        '#'
                                                    }
                                                    className={
                                                        !reports.prev_page_url
                                                            ? 'pointer-events-none opacity-50'
                                                            : ''
                                                    }
                                                />
                                            </PaginationItem>
                                            {reports.links.map(
                                                (
                                                    link: {
                                                        url: string | null;
                                                        active: boolean;
                                                        label: string;
                                                    },
                                                    index: number,
                                                ) => {
                                                    if (
                                                        link.url !== null &&
                                                        index !== 0 &&
                                                        index !==
                                                            reports.links
                                                                .length -
                                                                1
                                                    ) {
                                                        return (
                                                            <PaginationItem
                                                                key={index}
                                                            >
                                                                <PaginationLink
                                                                    isActive={
                                                                        link.active
                                                                    }
                                                                    href={
                                                                        link.url ||
                                                                        '#'
                                                                    }
                                                                >
                                                                    {link.label}
                                                                </PaginationLink>
                                                            </PaginationItem>
                                                        );
                                                    }
                                                    return null;
                                                },
                                            )}
                                            <PaginationItem>
                                                <PaginationEllipsis />
                                            </PaginationItem>
                                            <PaginationItem>
                                                <PaginationNext
                                                    href={
                                                        reports.next_page_url ||
                                                        '#'
                                                    }
                                                    className={
                                                        !reports.next_page_url
                                                            ? 'pointer-events-none opacity-50'
                                                            : ''
                                                    }
                                                />
                                            </PaginationItem>
                                        </PaginationContent>
                                    </Pagination>
                                </div>
                            )}
                        </TabsContent>
                        <TabsContent value="table" className="mt-0">
                            <ReportsTable
                                reports={filteredReports}
                                reportTypes={reportTypes}
                            />
                        </TabsContent>
                    </Tabs>
                </div>
            </div>
        </AppLayout>
    );
};

export default Reports;
