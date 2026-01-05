import { Button } from '@/components/ui/button';
import { Calendar } from '@/components/ui/calendar';
import { Checkbox } from '@/components/ui/checkbox';
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
import { Label } from '@/components/ui/label';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { Spinner } from '@/components/ui/spinner';
import { toast } from '@/components/use-toast';
import { router, useForm } from '@inertiajs/react';
import { format } from 'date-fns';
import {
    Calendar as CalendarIcon,
    ChevronDownIcon,
    FileText,
    Globe,
    MoveLeft,
    Plus,
} from 'lucide-react';
import { FormEvent, useState } from 'react';

type CreatePublicPostForm = {
    description: string;
    published_at: string;
};

function CreatePublicPost() {
    const [dialogOpen, setDialogOpen] = useState(false);
    const { data, setData, post, processing, errors, reset } =
        useForm<CreatePublicPostForm>({
            description: '',
            published_at: '',
        });

    const [scheduleMode, setScheduleMode] = useState(false);
    const [publishNow, setPublishNow] = useState(false);
    const [calendarOpen, setCalendarOpen] = useState(false);
    const [selectedDate, setSelectedDate] = useState<Date | undefined>(
        undefined,
    );

    const handleSubmit = (e: FormEvent) => {
        e.preventDefault();

        // Determine the final published_at value
        let finalPublishedAt = data.published_at;

        if (publishNow) {
            finalPublishedAt = new Date().toISOString();
        } else if (!scheduleMode) {
            finalPublishedAt = '';
        }

        // Use router.post to send data directly
        router.post(
            '/public-post',
            {
                description: data.description,
                published_at: finalPublishedAt,
            },
            {
                onSuccess: () => {
                    toast({
                        title: 'Success!',
                        description: 'Public post created successfully.',
                        variant: 'default',
                    });
                    reset();
                    setScheduleMode(false);
                    setPublishNow(false);
                    setSelectedDate(undefined);
                    setDialogOpen(false);
                },
                onError: () => {
                    toast({
                        title: 'Error',
                        description:
                            'Failed to create public post. Please check your inputs.',
                        variant: 'destructive',
                    });
                },
                preserveScroll: true,
            },
        );
    };

    return (
        <Dialog open={dialogOpen} onOpenChange={setDialogOpen}>
            <DialogTrigger asChild>
                <Button>
                    <Plus className="h-4 w-4" /> Create Post
                </Button>
            </DialogTrigger>
            <DialogContent
                className="flex max-h-[90vh] max-w-none flex-col overflow-hidden p-0 sm:max-w-2xl"
                showCloseButton={false}
            >
                <form
                    onSubmit={handleSubmit}
                    className="flex h-full flex-col overflow-hidden"
                >
                    <DialogHeader className="flex-shrink-0 px-6 pt-6 pb-4">
                        <DialogTitle>Create New Public Post</DialogTitle>
                        <DialogDescription>
                            Create a new public post to share with the
                            community.
                        </DialogDescription>
                    </DialogHeader>

                    <div className="flex w-full flex-1 flex-col justify-start gap-6 overflow-y-auto px-6 py-4">
                        {/* Post Content */}
                        <div className="flex w-full flex-col gap-4">
                            <div className="grid gap-3">
                                <p className="text-sm font-medium text-[var(--gray)]">
                                    Post Content
                                </p>
                            </div>

                            {/* Description */}
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
                                        placeholder="Enter detailed description of the post"
                                        rows={8}
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

                        {/* Publication Settings */}
                        <div className="flex w-full flex-col gap-4">
                            <div className="grid gap-3">
                                <p className="text-sm font-medium text-[var(--gray)]">
                                    Publication Settings
                                </p>
                            </div>

                            {/* Publish Now Option */}
                            <div className="flex items-center space-x-2">
                                <Checkbox
                                    id="publish-now"
                                    checked={publishNow}
                                    onCheckedChange={(checked: boolean) => {
                                        setPublishNow(checked);
                                        if (checked) {
                                            setScheduleMode(false);
                                        }
                                    }}
                                />
                                <Label
                                    htmlFor="publish-now"
                                    className="flex cursor-pointer items-center gap-2"
                                >
                                    <Globe className="h-4 w-4" />
                                    Publish immediately
                                </Label>
                            </div>

                            {/* Schedule Option */}
                            <div className="flex items-center space-x-2">
                                <Checkbox
                                    id="schedule-mode"
                                    checked={scheduleMode}
                                    onCheckedChange={(checked: boolean) => {
                                        setScheduleMode(checked);
                                        if (checked) {
                                            setPublishNow(false);
                                        }
                                    }}
                                />
                                <Label
                                    htmlFor="schedule-mode"
                                    className="flex cursor-pointer items-center gap-2"
                                >
                                    <CalendarIcon className="h-4 w-4" />
                                    Schedule for later
                                </Label>
                            </div>

                            {/* Schedule Date Picker */}
                            {scheduleMode && (
                                <div className="grid gap-3">
                                    <Label htmlFor="published_at">
                                        Schedule Date
                                    </Label>
                                    <div className="relative">
                                        <Popover
                                            open={calendarOpen}
                                            onOpenChange={setCalendarOpen}
                                        >
                                            <PopoverTrigger asChild>
                                                <Button
                                                    variant="outline"
                                                    id="published_at"
                                                    className="w-full justify-between font-normal"
                                                >
                                                    {selectedDate
                                                        ? format(
                                                              selectedDate,
                                                              'PPP',
                                                          )
                                                        : 'Select date'}
                                                    <ChevronDownIcon />
                                                </Button>
                                            </PopoverTrigger>
                                            <PopoverContent
                                                className="w-auto overflow-hidden p-0"
                                                align="start"
                                            >
                                                <Calendar
                                                    mode="single"
                                                    selected={selectedDate}
                                                    captionLayout="dropdown"
                                                    onSelect={(date) => {
                                                        setSelectedDate(date);
                                                        setData(
                                                            'published_at',
                                                            date
                                                                ? date.toISOString()
                                                                : '',
                                                        );
                                                        setCalendarOpen(false);
                                                    }}
                                                    disabled={(date) =>
                                                        date < new Date()
                                                    }
                                                />
                                            </PopoverContent>
                                        </Popover>
                                        {errors.published_at && (
                                            <span className="absolute -bottom-5 left-0 text-xs text-red-500">
                                                {errors.published_at}
                                            </span>
                                        )}
                                    </div>
                                </div>
                            )}
                        </div>
                    </div>

                    <DialogFooter className="flex-shrink-0 px-6 py-4">
                        <div className="flex w-full gap-2">
                            <DialogClose asChild>
                                <Button
                                    type="button"
                                    variant="outline"
                                    className="flex-1"
                                >
                                    <MoveLeft className="inline h-4 w-4" />
                                    Close
                                </Button>
                            </DialogClose>
                            <Button
                                type="submit"
                                disabled={processing}
                                className="flex-2"
                            >
                                {processing ? (
                                    <Spinner className="inline h-4 w-4" />
                                ) : (
                                    <FileText className="inline h-4 w-4" />
                                )}
                                {processing ? 'Creating...' : 'Create Post'}
                            </Button>
                        </div>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

export default CreatePublicPost;
