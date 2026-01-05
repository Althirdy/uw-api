import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { PublicPost_T } from '@/types/public-post-types';
import { router, useForm } from '@inertiajs/react';
import { Calendar, Globe, MoveLeft, Save, User } from 'lucide-react';
import { FormEvent } from 'react';

type EditPublicPostProps = {
    post: PublicPost_T;
    children: React.ReactNode;
};

type EditPublicPostForm = {
    transcript: string;
    description: string;
};

const reportTypeColors: Record<string, string> = {
    CCTV: 'bg-blue-800',
    'Citizen Concern': 'bg-purple-800',
    Emergency: 'bg-red-800',
    Announcement: 'bg-yellow-800',
};

function getStatusBadge(publishedAt: string | null) {
    if (!publishedAt) {
        return (
            <span className="inline-flex items-center rounded-[var(--radius)] bg-zinc-800 px-2.5 py-0.5 text-sm font-medium">
                Draft
            </span>
        );
    }

    const publishDate = new Date(publishedAt);
    const now = new Date();

    if (publishDate > now) {
        return (
            <span className="inline-flex items-center rounded-[var(--radius)] bg-yellow-800 px-2.5 py-0.5 text-sm font-medium">
                Scheduled
            </span>
        );
    }

    return (
        <span className="inline-flex items-center rounded-[var(--radius)] bg-green-800 px-2.5 py-0.5 text-sm font-medium text-foreground">
            Published
        </span>
    );
}

function EditPublicPost({ post, children }: EditPublicPostProps) {
    const { data, setData, processing, errors } = useForm<EditPublicPostForm>({
        transcript: post.report?.transcript || '',
        description: post.report?.description || '',
    });

    const handleSubmit = (e: FormEvent) => {
        e.preventDefault();

        router.put(
            `/public-post/${post.id}`,
            {
                transcript: data.transcript,
                description: data.description,
            },
            {
                onSuccess: () => {
                    const closeButton = document.querySelector(
                        '[data-dialog-close]',
                    ) as HTMLButtonElement;
                    if (closeButton) closeButton.click();
                },
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
                <form
                    onSubmit={handleSubmit}
                    className="flex h-full flex-col overflow-hidden"
                >
                    <DialogHeader className="flex-shrink-0 px-6 pt-6 pb-4">
                        <div className="flex flex-row items-center gap-4">
                            <div className="text-left">
                                <h3 className="text-xl font-semibold">
                                    Edit Public Post #{post.id}
                                </h3>

                                <div className="mt-1 flex flex-row items-center gap-2">
                                    {getStatusBadge(post.published_at)}
                                    <span
                                        className={`inline-flex items-center rounded-[var(--radius)] px-2.5 py-0.5 text-sm font-medium ${
                                            reportTypeColors[
                                                post.report?.report_type || ''
                                            ] || 'bg-gray-100 text-gray-800'
                                        }`}
                                    >
                                        {post.report?.report_type}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </DialogHeader>

                    <div className="flex w-full flex-1 flex-col justify-start gap-6 overflow-y-auto px-6 py-4">
                        {/* Report Content - Editable */}
                        <div className="flex w-full flex-col gap-4">
                            {/* Editable Transcript */}
                            {post.report?.transcript && (
                                <div className="grid gap-3">
                                    <Label htmlFor="transcript">
                                        Transcript
                                    </Label>
                                    <div className="relative">
                                        <textarea
                                            id="transcript"
                                            value={data.transcript}
                                            onChange={(e) =>
                                                setData(
                                                    'transcript',
                                                    e.target.value,
                                                )
                                            }
                                            placeholder="Enter report transcript or summary"
                                            rows={4}
                                            className={`w-full resize-none rounded-md border px-3 py-2 text-sm focus:border-transparent focus:ring-2 focus:ring-ring focus:outline-none ${
                                                errors.transcript
                                                    ? 'border-red-500 focus:ring-red-500'
                                                    : 'border-input'
                                            }`}
                                        />
                                        {errors.transcript && (
                                            <span className="absolute -bottom-5 left-0 text-xs text-red-500">
                                                {errors.transcript}
                                            </span>
                                        )}
                                    </div>
                                </div>
                            )}

                            {/* Editable Description */}
                            <div className="grid gap-3">
                                <Label htmlFor="description">Description</Label>
                                <div className="relative">
                                    <textarea
                                        id="description"
                                        value={data.description}
                                        onChange={(e) =>
                                            setData(
                                                'description',
                                                e.target.value,
                                            )
                                        }
                                        placeholder="Enter detailed description of the incident"
                                        rows={6}
                                        className={`w-full resize-none rounded-md border px-3 py-2 text-sm focus:border-transparent focus:ring-2 focus:ring-ring focus:outline-none ${
                                            errors.description
                                                ? 'border-red-500 focus:ring-red-500'
                                                : 'border-input'
                                        }`}
                                    />
                                    {errors.description && (
                                        <span className="absolute -bottom-5 left-0 text-xs text-red-500">
                                            {errors.description}
                                        </span>
                                    )}
                                </div>
                            </div>
                        </div>

                        {/* Post Metadata (Read-only) */}
                        <div className="flex flex-col gap-2">
                            <div className="grid">
                                <p className="text-sm font-medium text-[var(--gray)]">
                                    Post Information
                                </p>
                            </div>
                            <div className="flex flex-col gap-2 text-sm text-muted-foreground">
                                <div className="flex flex-row items-center gap-2">
                                    <User className="h-4 w-4" />
                                    <span>
                                        Published by:{' '}
                                        {post.publishedBy?.name || 'Unknown'}
                                    </span>
                                </div>
                                <div className="flex flex-row items-center gap-2">
                                    <User className="h-4 w-4" />
                                    <span>
                                        Reported by:{' '}
                                        {post.report?.user?.name || 'Unknown'}
                                    </span>
                                </div>

                                <div className="flex flex-row items-center gap-2">
                                    <Calendar className="h-4 w-4" />
                                    <span>
                                        Created:{' '}
                                        {new Date(
                                            post.created_at,
                                        ).toLocaleDateString()}
                                    </span>
                                </div>
                                {post.published_at && (
                                    <div className="flex flex-row items-center gap-2">
                                        <Globe className="h-4 w-4" />
                                        <span>
                                            Current publish date:{' '}
                                            {new Date(
                                                post.published_at,
                                            ).toLocaleDateString()}{' '}
                                            at{' '}
                                            {new Date(
                                                post.published_at,
                                            ).toLocaleTimeString()}
                                        </span>
                                    </div>
                                )}
                            </div>
                        </div>
                    </div>

                    <DialogFooter className="flex-shrink-0 px-6 py-4">
                        <div className="flex w-full gap-2">
                            <DialogClose asChild>
                                <Button
                                    type="button"
                                    variant="outline"
                                    data-dialog-close
                                    className="flex-1 cursor-pointer"
                                >
                                    <MoveLeft className="inline h-4 w-4" />
                                    Close
                                </Button>
                            </DialogClose>
                            <Button
                                type="submit"
                                disabled={processing}
                                className="flex-2 cursor-pointer"
                            >
                                {processing ? (
                                    <Spinner className="inline h-4 w-4" />
                                ) : (
                                    <Save className="inline h-4 w-4" />
                                )}
                                {processing ? 'Saving...' : 'Save Changes'}
                            </Button>
                        </div>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

export default EditPublicPost;
