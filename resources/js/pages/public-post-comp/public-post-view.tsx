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
import { PublicPost_T } from '@/types/public-post-types';
import { router } from '@inertiajs/react';
import {
    Calendar,
    Eye,
    Globe,
    LocateFixed,
    Mail,
    MoveLeft,
    TriangleAlert,
    User,
} from 'lucide-react';

type ViewPublicPostDetailsProps = {
    post: PublicPost_T;
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

function getStatusBadge(publishedAt: string | null) {
    if (!publishedAt) {
        return (
            <span className="inline-flex items-center rounded-full bg-zinc-800 px-2.5 py-0.5 text-xs font-medium">
                Draft
            </span>
        );
    }

    const publishDate = new Date(publishedAt);
    const now = new Date();

    if (publishDate > now) {
        return (
            <span className="inline-flex items-center rounded-full bg-yellow-800 px-2.5 py-0.5 text-xs font-medium">
                Scheduled
            </span>
        );
    }

    return (
        <span className="inline-flex items-center rounded-full bg-green-800 px-2.5 py-0.5 text-xs font-medium text-foreground">
            Published
        </span>
    );
}

const reportTypeColors: Record<string, string> = {
    CCTV: 'bg-blue-800',
    'Citizen Concern': 'bg-purple-800',
    Emergency: 'bg-red-800',
    Announcement: 'bg-yellow-800',
};

function ViewPublicPostDetails({ post, children }: ViewPublicPostDetailsProps) {
    const handlePublish = () => {
        router.patch(
            `/public-post/${post.id}/publish`,
            {},
            {
                preserveScroll: true,
            },
        );
    };

    const handleUnpublish = () => {
        router.patch(
            `/public-post/${post.id}/unpublish`,
            {},
            {
                preserveScroll: true,
            },
        );
    };

    return (
        <Dialog>
            <DialogTrigger asChild>{children}</DialogTrigger>
            <DialogContent
                className="flex max-h-[90vh] max-w-none flex-col overflow-hidden p-0 sm:max-w-2xl"
                showCloseButton={false}
            >
                <DialogHeader className="flex-shrink-0 px-6 pt-6">
                    <DialogTitle>Public Post Details</DialogTitle>
                    <DialogDescription className="flex flex-col gap-1">
                        <span>Post ID: #{post.id}</span>
                        <div className="w-fit">
                            {getStatusBadge(post.published_at)}
                        </div>
                    </DialogDescription>
                </DialogHeader>
                <div className="flex w-full flex-1 flex-col justify-start gap-4 overflow-y-auto px-6 py-4">
                    {/* Report Preview */}
                    <div className="flex flex-col gap-2">
                        <p className="text-md font-medium">Report Snapshot</p>
                        <div className="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                            <PlaceholderPattern className="absolute inset-0 size-full stroke-neutral-900/20 dark:stroke-neutral-100/20" />
                        </div>
                    </div>

                    {/* Report Content */}
                    <div className="flex flex-col gap-2">
                        <p className="text-md font-medium">Report Content</p>
                        <div className="rounded-lg border bg-muted/30 p-3">
                            <span
                                className={`mb-2 inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ${
                                    reportTypeColors[
                                        post.report?.report_type || ''
                                    ] || 'bg-gray-100 text-gray-800'
                                }`}
                            >
                                {post.report?.report_type}
                            </span>
                            <p className="mb-2 text-sm text-muted-foreground">
                                {post.report?.transcript ||
                                    'No transcript available'}
                            </p>
                            {post.report?.description && (
                                <p className="text-sm text-muted-foreground">
                                    {post.report.description}
                                </p>
                            )}
                        </div>
                    </div>

                    {/* Publication Details */}
                    <div className="flex flex-col gap-2">
                        <p className="text-md font-medium">
                            Publication Details
                        </p>
                        {renderDetailItems([
                            {
                                icon: User,
                                text: `Published by: ${post.publishedBy?.name || 'Unknown'}`,
                            },
                            {
                                icon: Calendar,
                                text: post.published_at
                                    ? `Published: ${formatDateTime(post.published_at)}`
                                    : 'Not published yet',
                            },
                            {
                                icon: Globe,
                                text: `Created: ${formatDateTime(post.created_at)}`,
                            },
                        ])}
                    </div>

                    {/* Original Report Details */}
                    <div className="flex flex-col gap-2">
                        <p className="text-md font-medium">
                            Original Report Information
                        </p>
                        {renderDetailItems([
                            {
                                icon: User,
                                text: `Reporter: ${post.report?.user?.name || 'Unknown'}`,
                            },
                            {
                                icon: Mail,
                                text: `Email: ${post.report?.user?.email || 'No email provided'}`,
                            },
                            {
                                icon: LocateFixed,
                                text: `Location: ${post.report?.latitude || 'N/A'}, ${post.report?.longtitude || 'N/A'}`,
                            },
                            {
                                icon: TriangleAlert,
                                text: `Report Type: ${post.report?.report_type || 'Unknown'}`,
                            },
                        ])}
                    </div>
                </div>
                <DialogFooter className="flex-shrink-0 px-6 pb-4">
                    <div className="flex w-full gap-2">
                        <DialogClose asChild>
                            <Button
                                variant="outline"
                                size="sm"
                                className="flex-1 cursor-pointer py-4"
                            >
                                <MoveLeft className="inline h-4 w-4" />
                                Close
                            </Button>
                        </DialogClose>
                        {!post.published_at ? (
                            <Button
                                variant="default"
                                size="sm"
                                onClick={handlePublish}
                                className="flex-2 cursor-pointer py-4"
                            >
                                <Globe className="inline h-4 w-4" />
                                Publish Now
                            </Button>
                        ) : (
                            <Button
                                variant="outline"
                                size="sm"
                                onClick={handleUnpublish}
                                className="flex-2 cursor-pointer py-4"
                            >
                                <Eye className="inline h-4 w-4" />
                                Unpublish
                            </Button>
                        )}
                    </div>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}

export default ViewPublicPostDetails;
