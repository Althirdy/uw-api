import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
    AlertDialogTrigger,
} from '@/components/ui/alert-dialog';
import { Badge } from '@/components/ui/badge';
import { Label } from '@/components/ui/label';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import { Textarea } from '@/components/ui/textarea';
import { AvailablePunishmentsData, users_T } from '@/types/user-types';
import { router } from '@inertiajs/react';
import { BadgeAlert, History, MoveLeft } from 'lucide-react';
import { useEffect, useState } from 'react';

type SuspensionUsersProps = {
    user: users_T;
    children: React.ReactNode;
};

function SuspensionUser({ user, children }: SuspensionUsersProps) {
    const [open, setOpen] = useState(false);
    const [loading, setLoading] = useState(true);
    const [data, setData] = useState<AvailablePunishmentsData | null>(null);
    const [selectedPunishment, setSelectedPunishment] = useState('');
    const [reason, setReason] = useState('');
    const [processing, setProcessing] = useState(false);

    useEffect(() => {
        if (open) {
            fetchAvailablePunishments();
        }
    }, [open]);

    const fetchAvailablePunishments = async () => {
        try {
            setLoading(true);
            const response = await fetch(
                `/user/${user.id}/available-punishments`,
            );
            const result = await response.json();
            setData(result);
        } catch (error) {
            console.error('Failed to fetch available punishments:', error);
        } finally {
            setLoading(false);
        }
    };

    const handleSuspend = () => {
        console.log('handleSuspend called');
        console.log('selectedPunishment:', selectedPunishment);
        console.log('reason:', reason);
        console.log('user.id:', user.id);

        if (!selectedPunishment) {
            console.log('No punishment selected, returning');
            return;
        }

        setProcessing(true);
        console.log('About to call router.post');
        console.log('URL:', `/user/${user.id}/suspend`);
        console.log('Data:', {
            punishment_type: selectedPunishment,
            reason: reason || null,
        });

        router.post(
            `/user/${user.id}/suspend`,
            {
                punishment_type: selectedPunishment,
                reason: reason || null,
            },
            {
                preserveScroll: true,
                onBefore: (visit) => {
                    console.log('onBefore - Visit:', visit);
                    return true;
                },
                onStart: (visit) => {
                    console.log('onStart - Request started:', visit);
                },
                onProgress: (progress) => {
                    console.log('onProgress:', progress);
                },
                onSuccess: (page) => {
                    console.log(
                        'Suspension successful - Full page response:',
                        page,
                    );
                    console.log('Props:', page.props);
                    setOpen(false);
                    setSelectedPunishment('');
                    setReason('');
                },
                onError: (errors) => {
                    console.error('Suspension failed with errors:', errors);
                    alert(
                        'Failed to apply suspension. Check console for details.',
                    );
                },
                onFinish: () => {
                    console.log('Request finished');
                    setProcessing(false);
                },
            },
        );
    };

    const getUserFullName = (user: users_T) => {
        if (user.official_details) {
            return `${user.official_details.first_name} ${user.official_details.middle_name ? user.official_details.middle_name + ' ' : ''}${user.official_details.last_name}`;
        } else if (user.citizen_details) {
            return `${user.citizen_details.first_name} ${user.citizen_details.middle_name ? user.citizen_details.middle_name + ' ' : ''}${user.citizen_details.last_name}`;
        }
        return user.name;
    };

    const getPunishmentBadgeVariant = (status: string, isActive: boolean) => {
        if (isActive) return 'destructive';
        if (status === 'expired') return 'secondary';
        if (status === 'revoked') return 'outline';
        return 'default';
    };

    const formatFullPunishmentType = (type: string): string => {
        const formats: Record<string, string> = {
            warning_1: 'Warning 1 - 3 days',
            warning_2: 'Warning 2 - 7 days',
            suspension: 'Permanent Suspension',
        };
        return formats[type] || type;
    };

    const formatSummaryPunishmentType = (type: string): string => {
        const formats: Record<string, string> = {
            warning_1: 'Warning 1 ',
            warning_2: 'Warning 2 ',
            suspension: 'Permanent Suspension',
        };
        return formats[type] || type;
    };

    return (
        <AlertDialog open={open} onOpenChange={setOpen}>
            <AlertDialogTrigger asChild>{children}</AlertDialogTrigger>
            <AlertDialogContent className="max-h-[90vh] max-w-none overflow-y-auto sm:max-w-2xl">
                <AlertDialogHeader>
                    <AlertDialogTitle className="flex flex-row items-center gap-2 text-muted-foreground">
                        <BadgeAlert className="h-6 w-6" /> Suspend User
                    </AlertDialogTitle>
                    <AlertDialogDescription className="mt-2 text-foreground">
                        Apply suspension to{' '}
                        <span className="font-bold text-destructive">
                            {getUserFullName(user)}
                        </span>
                    </AlertDialogDescription>
                </AlertDialogHeader>

                {loading ? (
                    <div className="flex justify-center py-8">
                        <div className="h-8 w-8 animate-spin rounded-full border-b-2 border-primary"></div>
                    </div>
                ) : (
                    <>
                        {/* Active Suspension Alert */}
                        {data?.is_suspended && data.active_suspension && (
                            <div className="rounded-lg border border-destructive bg-destructive/10 p-4">
                                <div className="mb-2 flex items-center gap-2">
                                    <Badge className="inline-flex items-center rounded-[var(--radius)] bg-red-800 px-2.5 py-1 text-xs font-medium text-foreground dark:bg-red-900">
                                        Suspended
                                    </Badge>
                                </div>
                                <p className="text-sm text-muted-foreground">
                                    Type:{' '}
                                    {formatFullPunishmentType(
                                        data.active_suspension.type,
                                    )}
                                    {data.active_suspension.expires_at && (
                                        <>
                                            {' '}
                                            • Expires:{' '}
                                            {new Date(
                                                data.active_suspension.expires_at,
                                            ).toLocaleDateString()}
                                        </>
                                    )}
                                </p>
                                {data.active_suspension.reason && (
                                    <p className="mt-1 text-sm text-muted-foreground">
                                        Reason: {data.active_suspension.reason}
                                    </p>
                                )}
                            </div>
                        )}

                        {/* Suspension History */}
                        {data?.suspension_history &&
                            data.suspension_history.length > 0 && (
                                <div>
                                    <h4 className="mb-2 flex items-center gap-2 text-sm font-semibold text-muted-foreground">
                                        <History className="h-4 w-4" />
                                        Suspension History
                                    </h4>
                                    <div className="max-h-60 space-y-2 overflow-y-auto">
                                        {data.suspension_history.map(
                                            (suspension) => (
                                                <div
                                                    key={suspension.id}
                                                    className="rounded-lg border p-3 text-sm"
                                                >
                                                    <div className="mb-1 flex items-center justify-between">
                                                        <Badge
                                                            variant={getPunishmentBadgeVariant(
                                                                suspension.status,
                                                                suspension.is_active,
                                                            )}
                                                        >
                                                            {formatSummaryPunishmentType(
                                                                suspension.punishment_type,
                                                            )}
                                                        </Badge>
                                                    </div>
                                                    <div className="flex w-full flex-row items-center justify-between">
                                                        <p className="text-sm text-muted-foreground">
                                                            Duration:{' '}
                                                            {new Date(
                                                                suspension.suspended_at,
                                                            ).toLocaleDateString()}
                                                            {suspension.expires_at && (
                                                                <>
                                                                    {' '}
                                                                    →{' '}
                                                                    {new Date(
                                                                        suspension.expires_at,
                                                                    ).toLocaleDateString()}
                                                                </>
                                                            )}
                                                        </p>
                                                        <p className="text-sm text-muted-foreground">
                                                            By:{' '}
                                                            {
                                                                suspension.suspended_by
                                                            }
                                                        </p>
                                                    </div>
                                                </div>
                                            ),
                                        )}
                                    </div>
                                </div>
                            )}

                        {/* Available Punishments */}
                        {data?.available_punishments &&
                        data.available_punishments.length > 0 ? (
                            <div className="space-y-4">
                                <div>
                                    <Label className="mb-2 text-sm font-semibold">
                                        Select Punishment Type
                                    </Label>
                                    <RadioGroup
                                        value={selectedPunishment}
                                        onValueChange={setSelectedPunishment}
                                        className="mt-2 space-y-2"
                                    >
                                        {data.available_punishments.map(
                                            (punishment) => (
                                                <div
                                                    key={punishment.type}
                                                    className="flex cursor-pointer items-center space-x-2 rounded-[var(--radius)] border p-3 hover:bg-accent"
                                                >
                                                    <RadioGroupItem
                                                        value={punishment.type}
                                                        id={punishment.type}
                                                    />
                                                    <Label
                                                        htmlFor={
                                                            punishment.type
                                                        }
                                                        className="flex-1 cursor-pointer"
                                                    >
                                                        <div className="font-semibold">
                                                            {punishment.label} -{' '}
                                                            {
                                                                punishment.description
                                                            }
                                                        </div>
                                                    </Label>
                                                </div>
                                            ),
                                        )}
                                    </RadioGroup>
                                </div>

                                <div>
                                    <Label
                                        htmlFor="reason"
                                        className="text-sm font-semibold"
                                    >
                                        Reason (Optional)
                                    </Label>
                                    <Textarea
                                        id="reason"
                                        value={reason}
                                        onChange={(e) =>
                                            setReason(e.target.value)
                                        }
                                        placeholder="Provide a reason for this suspension (e.g., 'Sent fake emergency reports')"
                                        className="mt-2"
                                        rows={3}
                                    />
                                </div>
                            </div>
                        ) : (
                            <div className="py-4 text-center text-muted-foreground">
                                User may have permanent suspension level.
                            </div>
                        )}
                    </>
                )}

                <AlertDialogFooter>
                    <AlertDialogCancel
                        onClick={() => {
                            setSelectedPunishment('');
                            setReason('');
                        }}
                    >
                        <MoveLeft className="h-6 w-6" />
                        Close
                    </AlertDialogCancel>
                    <AlertDialogAction
                        onClick={handleSuspend}
                        disabled={
                            !selectedPunishment ||
                            processing ||
                            loading ||
                            !data?.available_punishments?.length
                        }
                        className="bg-destructive text-destructive-foreground hover:bg-destructive/90"
                    >
                        {processing ? 'Applying...' : 'Apply Suspension'}
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    );
}

export default SuspensionUser;
