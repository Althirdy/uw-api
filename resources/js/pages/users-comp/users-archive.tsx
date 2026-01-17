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
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { users_T } from '@/types/user-types';
import { useForm } from '@inertiajs/react';
import { Archive } from 'lucide-react';
import { useState } from 'react';

type ArchiveUserProps = {
    user: users_T;
    children: React.ReactNode;
};

function ArchiveUser({ user, children }: ArchiveUserProps) {
    const [confirmText, setConfirmText] = useState('');
    const { patch, processing } = useForm();

    const handleArchive = () => {
        if (confirmText !== getUserFullName(user)) {
            return;
        }

        patch(`/user/${user.id}/archive`, {
            preserveScroll: true,
            onSuccess: () => {
                setConfirmText('');
            },
        });
    };

    const getUserFullName = (user: users_T) => {
        if (user.official_details) {
            return `${user.official_details.first_name} ${user.official_details.middle_name ? user.official_details.middle_name + ' ' : ''}${user.official_details.last_name}`;
        } else if (user.citizen_details) {
            return `${user.citizen_details.first_name} ${user.citizen_details.middle_name ? user.citizen_details.middle_name + ' ' : ''}${user.citizen_details.last_name}`;
        }
        return user.name;
    };

    return (
        <AlertDialog>
            <AlertDialogTrigger asChild>{children}</AlertDialogTrigger>
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle className="flex flex-row items-center gap-2 text-muted-foreground">
                        <Archive className="h-6 w-6" /> Archive User
                    </AlertDialogTitle>
                    <AlertDialogDescription className="text-whi mt-2">
                        Are you sure you want to archive{' '}
                        <span className="font-bold text-destructive">
                            {getUserFullName(user)}?
                        </span>{' '}
                        This action cannot be undone.
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <div className="flex flex-col gap-2">
                    <Label
                        htmlFor="archive-user "
                        className="text-sm text-muted-foreground"
                    >
                        To confirm archiving, type{' '}
                        <span className="font-semibold">
                            "{getUserFullName(user)}"
                        </span>{' '}
                        below:
                    </Label>
                    <div className="relative">
                        <Input
                            id="archive-user"
                            value={confirmText}
                            onChange={(e) => setConfirmText(e.target.value)}
                            placeholder="Enter full name to confirm"
                            className={
                                confirmText &&
                                confirmText !== getUserFullName(user)
                                    ? 'border-red-500'
                                    : ''
                            }
                        />
                        {confirmText &&
                            confirmText !== getUserFullName(user) && (
                                <span className="absolute -bottom-5 left-0 text-xs text-red-500">
                                    Please type the exact full name to confirm
                                </span>
                            )}
                    </div>
                </div>
                <AlertDialogFooter>
                    <AlertDialogCancel
                        className="cursor-pointer"
                        onClick={() => setConfirmText('')}
                    >
                        Cancel
                    </AlertDialogCancel>
                    <AlertDialogAction
                        onClick={handleArchive}
                        disabled={
                            confirmText !== getUserFullName(user) || processing
                        }
                        className="cursor-pointer bg-destructive text-destructive-foreground hover:bg-destructive/90"
                    >
                        {processing ? 'Archiving...' : 'Archive User'}
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    );
}

export default ArchiveUser;
