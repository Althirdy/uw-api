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
import { Archive, ExternalLink as Open, SquarePen } from 'lucide-react';

import { PublicPost_T } from '@/types/public-post-types';
import ArchivePublicPost from './public-post-archive';
import EditPublicPost from './public-post-edit';
import ViewPublicPostDetails from './public-post-view';

const reportTypeColors: Record<string, string> = {
    CCTV: 'bg-blue-800',
    'Citizen Concern': 'bg-purple-800',
    Emergency: 'bg-red-800',
    Announcement: 'bg-yellow-800',
};

function getStatusInfo(publishedAt: string | null) {
    if (!publishedAt) {
        return { label: 'Draft', className: 'bg-zinc-600' };
    }

    const publishDate = new Date(publishedAt);
    const now = new Date();

    if (publishDate > now) {
        return {
            label: 'Scheduled',
            className: 'bg-yellow-800',
        };
    }

    return { label: 'Published', className: ' text-foreground bg-green-800' };
}

const PublicPostCard = ({ posts }: { posts: PublicPost_T[] }) => {
    return (
        <div className="grid auto-rows-min gap-4 md:grid-cols-4">
            {posts.length === 0 && (
                <Card className="col-span-full rounded-[var(--radius)] border border-sidebar-border/70 dark:border-sidebar-border">
                    <CardContent className="flex items-center justify-center py-12">
                        <p className="text-muted-foreground">
                            No posts found matching your selection.
                        </p>
                    </CardContent>
                </Card>
            )}

            {posts.map((post) => {
                const statusInfo = getStatusInfo(post.published_at);

                return (
                    <Card
                        key={post.id}
                        className="relative overflow-hidden rounded-[var(--radius)] border border-sidebar-border/70 dark:border-sidebar-border"
                    >
                        <CardHeader>
                            <CardTitle> Post ID: #{post.id}</CardTitle>
                            <CardDescription className="flex flex-row gap-2">
                                <Badge
                                    className={`inline-flex items-center rounded-[var(--radius)] px-2.5 py-0.5 text-xs font-medium ${statusInfo.className}`}
                                >
                                    {statusInfo.label}
                                </Badge>
                                <Badge
                                    className={`inline-flex items-center rounded-[var(--radius)] px-2.5 py-0.5 text-xs font-medium ${
                                        reportTypeColors[
                                            post.report?.report_type || ''
                                        ] || 'bg-zinc-600'
                                    }`}
                                >
                                    {post.report?.report_type}
                                </Badge>
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="flex flex-col gap-2">
                                <p className="text-sm font-medium">
                                    Report Content
                                </p>
                                <div className="flex flex-col gap-2 rounded-lg border bg-muted/30 p-3">
                                    <p className="text-sm text-muted-foreground">
                                        {post.report?.transcript || null}
                                    </p>
                                    {post.report?.description && (
                                        <p className="text-sm text-muted-foreground">
                                            {post.report.description}
                                        </p>
                                    )}
                                </div>
                            </div>
                        </CardContent>
                        <CardFooter>
                            <div className="flex w-full justify-end gap-2">
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
                                        <p>Edit User</p>
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

export default PublicPostCard;
