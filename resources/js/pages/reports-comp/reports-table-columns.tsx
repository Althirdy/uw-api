import { ColumnDef } from '@tanstack/react-table';
import {
    Archive,
    ArrowUpDown,
    ExternalLink as Open,
    SquarePen,
} from 'lucide-react';

import { Button } from '@/components/ui/button';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';

import { formatDateTime } from '@/lib/utils';
import { reports_T } from '@/types/report-types';
import ArchiveReport from './reports-archive';
import EditReport from './reports-edit';
import ViewReportDetails from './reports-view';

const reportTypeColors: Record<string, string> = {
    CCTV: 'bg-sky-100 text-sky-700 dark:bg-sky-900/30 dark:text-sky-400',
    'Citizen Concern':
        'bg-violet-100 text-violet-700 dark:bg-violet-900/30 dark:text-violet-400',
    Emergency:
        'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-400',
};

const statusColors: Record<string, string> = {
    Ongoing:
        'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
    Pending:
        'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400',
    Resolved:
        'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
};

export const columns = (reportTypes: string[]): ColumnDef<reports_T>[] => [
    {
        accessorKey: 'id',
        header: ({ column }) => {
            return (
                <Button
                    variant="ghost"
                    onClick={() =>
                        column.toggleSorting(column.getIsSorted() === 'asc')
                    }
                    className="cursor-pointer transition-colors duration-200 ease-in-out"
                >
                    Report ID
                    <ArrowUpDown className="ml-2 h-4 w-4" />
                </Button>
            );
        },
        cell: ({ row }) => <div>#{row.getValue('id')}</div>,
    },
    {
        accessorKey: 'report_type',
        header: ({ column }) => {
            return (
                <Button
                    variant="ghost"
                    onClick={() =>
                        column.toggleSorting(column.getIsSorted() === 'asc')
                    }
                    className="cursor-pointer transition-colors duration-200 ease-in-out"
                >
                    Report Type
                    <ArrowUpDown className="ml-2 h-4 w-4" />
                </Button>
            );
        },
        cell: ({ row }) => {
            const reportType = row.getValue('report_type') as string;
            const colorClass =
                reportTypeColors[reportType] ||
                'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-400';

            return (
                <span
                    className={`inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ${colorClass}`}
                >
                    {reportType}
                </span>
            );
        },
    },
    {
        accessorKey: 'transcript',
        header: 'Report',
        cell: ({ row }) => <div>{row.getValue('transcript')}</div>,
    },
    {
        id: 'location',
        header: 'Location',
        cell: ({ row }) => {
            const report = row.original;
            return (
                <div>
                    {Number(report.latitude).toFixed(2)},{' '}
                    {Number(report.longtitude).toFixed(2)}
                </div>
            );
        },
    },
    {
        accessorKey: 'created_at',
        header: ({ column }) => {
            return (
                <Button
                    variant="ghost"
                    onClick={() =>
                        column.toggleSorting(column.getIsSorted() === 'asc')
                    }
                    className="cursor-pointer transition-colors duration-200 ease-in-out"
                >
                    Date and Time
                    <ArrowUpDown className="ml-2 h-4 w-4" />
                </Button>
            );
        },
        cell: ({ row }) => {
            return <div>{formatDateTime(row.getValue('created_at'))}</div>;
        },
    },
    {
        accessorKey: 'status',
        header: 'Status',
        cell: ({ row }) => {
            const status = row.getValue('status') as string;
            const colorClass =
                statusColors[status] ||
                'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-400';

            return (
                <span
                    className={`inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ${colorClass}`}
                >
                    {status}
                </span>
            );
        },
    },
    {
        id: 'actions',
        header: 'Actions',
        enableHiding: false,
        cell: ({ row, table }) => {
            const report = row.original;
            const reportTypes = (table.options.meta as any)?.reportTypes || [];

            return (
                <div className="flex justify-center gap-2">
                    <Tooltip>
                        <ViewReportDetails report={report}>
                            <TooltipTrigger asChild>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    className="cursor-pointer"
                                    onClick={(e) => e.stopPropagation()}
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
                        <EditReport report={report} reportTypes={reportTypes}>
                            <TooltipTrigger asChild>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    className="cursor-pointer"
                                    onClick={(e) => e.stopPropagation()}
                                >
                                    <SquarePen className="h-4 w-4" />
                                </Button>
                            </TooltipTrigger>
                        </EditReport>
                        <TooltipContent>
                            <p>Edit Report</p>
                        </TooltipContent>
                    </Tooltip>
                    <Tooltip>
                        <ArchiveReport report={report}>
                            <TooltipTrigger asChild>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    className="cursor-pointer"
                                    onClick={(e) => e.stopPropagation()}
                                >
                                    <Archive className="h-4 w-4 text-[var(--destructive)]" />
                                </Button>
                            </TooltipTrigger>
                        </ArchiveReport>
                        <TooltipContent>
                            <p>Archive Report</p>
                        </TooltipContent>
                    </Tooltip>
                </div>
            );
        },
    },
];
