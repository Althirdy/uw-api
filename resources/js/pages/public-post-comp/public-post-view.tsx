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

function getStatusInfo(publishedAt: string | null) {
    if (!publishedAt) {
        return { label: 'Draft', variant: 'outline' as const };
    }

    const publishDate = new Date(publishedAt);
    const now = new Date();

    if (publishDate > now) {
        return { label: 'Scheduled', variant: 'secondary' as const };
    }

    return { label: 'Published', variant: 'default' as const };
}

function ViewPublicPostDetails({ post, children }: ViewPublicPostDetailsProps) {
    const statusInfo = getStatusInfo(post.published_at);

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
                <DialogHeader className="flex-shrink-0 px-6 pt-6 pb-4">
                    <DialogTitle>Public Post Details</DialogTitle>
                    <DialogDescription className="flex flex-col gap-1">
                        <span>Post ID: #{post.id}</span>
                        <Badge
                            variant={statusInfo.variant}
                            className="w-fit text-sm"
                        >
                            {statusInfo.label}
                        </Badge>
                    </DialogDescription>
                </DialogHeader>
                <div className="flex w-full flex-1 flex-col justify-start gap-4 overflow-y-auto px-6 py-4">
                    {/* Post Preview Image */}
                    <div className="flex flex-col gap-2">
                        <p className="text-md font-medium">Post Image</p>
                        <div className="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                            {post.image_path ? (
                                <img
                                    src={post.image_path}
                                    alt={post.title}
                                    className="h-full w-full object-cover"
                                />
                            ) : (
                                <PlaceholderPattern className="absolute inset-0 size-full stroke-neutral-900/20 dark:stroke-neutral-100/20" />
                            )}
                        </div>
                    </div>

                    {/* Post Content */}
                    <div className="flex flex-col gap-2">
                        <p className="text-md font-medium">Post Content</p>
                        <div className="rounded-lg border bg-muted/30 p-3">
                            <div className="mb-2 flex items-center justify-between">
                                <p className="text-sm font-bold uppercase tracking-wide">
                                    {post.title}
                                </p>
                                <Badge variant="outline" className="uppercase">
                                    {post.category}
                                </Badge>
                            </div>
                            <p className="text-sm text-muted-foreground whitespace-pre-wrap">
                                {post.content}
                            </p>
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

                    {/* Original Source Details (if linked) */}
                    {post.postable && (
                        <div className="flex flex-col gap-2">
                            <p className="text-md font-medium">
                                Original Source Information
                            </p>
                            <div className="rounded-lg border border-dashed p-3 text-sm text-muted-foreground">
                                <p>Linked to: {post.postable_type?.split('\\').pop()}</p>
                                <p>Source ID: #{post.postable_id}</p>
                            </div>
                        </div>
                    )}
                </div>
                <DialogFooter className="flex-shrink-0 px-6 pb-4">
                    <div className="flex w-full gap-2">
                        <DialogClose asChild>
                            <Button
                                variant="outline"
                                size="sm"
                                className="flex-1 cursor-pointer py-4"
                            >
                                <MoveLeft className="mr-2 inline h-4 w-4" />
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
                                <Globe className="mr-2 inline h-4 w-4" />
                                Publish Now
                            </Button>
                        ) : (
                            <Button
                                variant="outline"
                                size="sm"
                                onClick={handleUnpublish}
                                className="flex-2 cursor-pointer py-4"
                            >
                                <Eye className="mr-2 inline h-4 w-4" />
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
