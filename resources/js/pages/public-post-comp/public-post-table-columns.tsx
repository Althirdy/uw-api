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
import { PublicPost_T } from '@/types/public-post-types';
import ArchivePublicPost from './public-post-archive';
import EditPublicPost from './public-post-edit';
import ViewPublicPostDetails from './public-post-view';

const reportTypeColors: Record<string, string> = {
    CCTV: 'bg-blue-100 text-blue-800',
    'Citizen Concern': 'bg-purple-100 text-purple-800',
    Emergency: 'bg-red-100 text-red-800',
};

function getStatusBadge(publishedAt: string | null) {
    if (!publishedAt) {
        return (
            <span className="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-800">
                Draft
            </span>
        );
    }

    const publishDate = new Date(publishedAt);
    const now = new Date();

    if (publishDate > now) {
        return (
            <span className="inline-flex items-center rounded-full bg-yellow-100 px-2.5 py-0.5 text-xs font-medium text-yellow-800">
                Scheduled
            </span>
        );
    }

    return (
        <span className="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800">
            Published
        </span>
    );
}

export const columns = (): ColumnDef<PublicPost_T>[] => [
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
                    Post ID
                    <ArrowUpDown className="ml-2 h-4 w-4" />
                </Button>
            );
        },
        cell: ({ row }) => <div>#{row.getValue('id')}</div>,
    },
    {
        accessorKey: 'report.report_type',
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
            const post = row.original;
            const reportType = post.report?.report_type || 'Unknown';
            const colorClass =
                reportTypeColors[reportType] || 'bg-gray-100 text-gray-800';

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
        id: 'report_content',
        header: 'Report Content',
        cell: ({ row }) => {
            const post = row.original;
            return (
                <div className="max-w-xs text-left">
                    <div className="ellipsis flex flex-col truncate">
                        <span className="text-md font-semibold">
                            {post.report?.transcript}
                        </span>
                        <span
                            className="mt-1 block truncate text-xs text-muted-foreground"
                            title={post.report?.description}
                        >
                            {post.report?.description &&
                            post.report.description.length > 100
                                ? post.report.description.substring(0, 100) +
                                  '...'
                                : post.report?.description}
                        </span>
                    </div>
                </div>
            );
        },
    },
    {
        accessorKey: 'report.user.name',
        header: 'Reporter',
        cell: ({ row }) => {
            const post = row.original;
            return <div>{post.report?.user?.name || 'Unknown'}</div>;
        },
    },
    {
        id: 'status',
        header: 'Status',
        cell: ({ row }) => {
            const post = row.original;
            return getStatusBadge(post.published_at);
        },
    },
    {
        accessorKey: 'published_at',
        header: ({ column }) => {
            return (
                <Button
                    variant="ghost"
                    onClick={() =>
                        column.toggleSorting(column.getIsSorted() === 'asc')
                    }
                    className="cursor-pointer transition-colors duration-200 ease-in-out"
                >
                    Published Date
                    <ArrowUpDown className="ml-2 h-4 w-4" />
                </Button>
            );
        },
        cell: ({ row }) => {
            const publishedAt = row.getValue('published_at') as string | null;
            return publishedAt ? (
                <span>{formatDateTime(publishedAt)}</span>
            ) : (
                <span>Not published</span>
            );
        },
    },
    {
        id: 'actions',
        header: 'Actions',
        enableHiding: false,
        cell: ({ row }) => {
            const post = row.original;

            return (
                <div className="flex justify-center gap-2">
                    <Tooltip>
                        <ViewPublicPostDetails post={post}>
                            <TooltipTrigger asChild>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    className="cursor-pointer"
                                >
                                    <Open className="h-4 w-4" />
                                </Button>
                            </TooltipTrigger>
                        </ViewPublicPostDetails>
                        <TooltipContent>
                            <p>View Details</p>
                        </TooltipContent>
                    </Tooltip>
                    <Tooltip>
                        <EditPublicPost post={post}>
                            <TooltipTrigger asChild>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    className="cursor-pointer"
                                >
                                    <SquarePen className="h-4 w-4" />
                                </Button>
                            </TooltipTrigger>
                        </EditPublicPost>
                        <TooltipContent>
                            <p>Edit Post</p>
                        </TooltipContent>
                    </Tooltip>
                    <Tooltip>
                        <ArchivePublicPost post={post}>
                            <TooltipTrigger asChild>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    className="cursor-pointer"
                                >
                                    <Archive className="h-4 w-4 text-[var(--destructive)]" />
                                </Button>
                            </TooltipTrigger>
                        </ArchivePublicPost>
                        <TooltipContent>
                            <p>Archive Post</p>
                        </TooltipContent>
                    </Tooltip>
                </div>
            );
        },
    },
];
