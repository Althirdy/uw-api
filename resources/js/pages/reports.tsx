import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import AppLayout from '@/layouts/app-layout';
import { reports as reportRoutes } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';
import { List, Table, Wifi, WifiOff } from 'lucide-react';
import { useState } from 'react';
import { useAccidentRealtime } from '@/hooks/use-accident-realtime';
import { Badge } from '@/components/ui/badge';
import {
    Pagination,
    PaginationContent,
    PaginationEllipsis,
    PaginationItem,
    PaginationLink,
    PaginationNext,
    PaginationPrevious,
} from "@/components/ui/pagination";

import { reports_T, ReportsProps } from '@/types/report-types';
import ReportsCard from './reports-comp/reports-card';
import OngoingReport from './reports-comp/reports-ongoing';
import ReportActionTab from './reports-comp/reports-tab';
import ReportsTable from './reports-comp/reports-table';

const Reports = ({ reports, reportTypes, statusOptions }: ReportsProps) => {
    const [filteredReports, setFilteredReports] = useState<reports_T[]>(
        reports.data,
    );
    console.log(filteredReports)

    // Real-time connection
    const { isConnected } = useAccidentRealtime();

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Reports Page',
            href: reportRoutes().url,
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Reports Page" />
            <div className="space-y-4 p-4">
                {/* Real-time Connection Status */}
                <div className="flex items-center justify-end">
                    <Badge 
                        variant={isConnected ? 'default' : 'secondary'} 
                        className="gap-1"
                    >
                        {isConnected ? (
                            <>
                                <Wifi className="h-3 w-3" />
                                Live Updates Active
                            </>
                        ) : (
                            <>
                                <WifiOff className="h-3 w-3" />
                                Connecting...
                            </>
                        )}
                    </Badge>
                </div>

                <div className="flex flex-col gap-4">
                    <div className="flex flex-col gap-1">
                        <h1 className="text-md font-semibold">
                            Ongoing Reports
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            Active ongoing incidents requiring immediate
                            attention.
                        </p>
                    </div>
                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                        {reports.data
                            .filter((report) => !report.is_acknowledge)
                            .map((report) => (
                                <OngoingReport
                                    key={report.id}
                                    report={report}
                                />
                            ))}
                    </div>
                </div>

                <Tabs defaultValue="card" className="w-full space-y-2">
                    <div className="flex flex-row gap-4">
                        <ReportActionTab
                            reports={reports}
                            reportTypes={reportTypes}
                            statusOptions={statusOptions}
                            setFilteredReports={setFilteredReports}
                        />
                        <TabsList className="h-12 w-24">
                            <TabsTrigger
                                value="card"
                                className="cursor-pointer"
                            >
                                <Table className="h-4 w-4" />
                            </TabsTrigger>
                            <TabsTrigger
                                value="table"
                                className="cursor-pointer"
                            >
                                <List className="h-8 w-8" />
                            </TabsTrigger>
                        </TabsList>
                    </div>

                    <TabsContent value="card" className="w-full space-y-6">
                        <ReportsCard
                            reports={filteredReports}
                            reportTypes={reportTypes}
                        />
                        
                        {/* Pagination */}
                        {reports.links && (
                            <Pagination className='flex justify-end'>
                                <PaginationContent>
                                    <PaginationItem>
                                        <PaginationPrevious href={reports.prev_page_url || '#'} />
                                    </PaginationItem>
                                    {
                                        reports.links.map((link: { url: string | null; active: boolean; label: string }, index: number) => {
                                            if (link.url !== null && index !== 0 && index !== reports.links.length - 1) {
                                                return (
                                                    <PaginationItem key={index}>
                                                        <PaginationLink isActive={link.active} href={link.url || '#'}>{link.label}</PaginationLink>
                                                    </PaginationItem>
                                                )
                                            }
                                        })
                                    }
                                    <PaginationItem>
                                        <PaginationEllipsis />
                                    </PaginationItem>
                                    <PaginationItem>
                                        <PaginationNext href={reports.next_page_url || '#'} />
                                    </PaginationItem>
                                </PaginationContent>
                            </Pagination>
                        )}
                    </TabsContent>
                    <TabsContent value="table" className="w-full">
                        <ReportsTable
                            reports={filteredReports}
                            reportTypes={reportTypes}
                        />
                    </TabsContent>
                </Tabs>
            </div>
        </AppLayout>
    );
};

export default Reports;
