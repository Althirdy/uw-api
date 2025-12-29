import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';
import { location_T } from '@/types/location-types';
import { roles_T } from '@/types/role-types';
import { users_T } from '@/types/user-types';
import { useForm } from '@inertiajs/react';
import { MoveLeft, Save } from 'lucide-react';
import { FormEvent } from 'react';

type EditUserProps = {
    user: users_T;
    roles: roles_T[];
    locations: location_T[];
    children: React.ReactNode;
};

type EditUserForm = {
    first_name: string;
    middle_name: string;
    last_name: string;
    suffix: string;
    email: string;
    phone_number: string;
    role_id: string;
    status: string;
    // For citizens
    date_of_birth: string;
    address: string;
    barangay: string;
    city: string;
    province: string;
    postal_code: string;
    is_verified: boolean;
    // For officials
    office_address: string;
    assigned_brgy: string;
    latitude: string;
    longitude: string;
};

function EditUser({ user, roles, locations, children }: EditUserProps) {
    // Function to generate initials from user's name
    const getInitials = (user: users_T) => {
        let firstName = '';
        let lastName = '';

        if (user.official_details) {
            firstName = user.official_details.first_name;
            lastName = user.official_details.last_name;
        } else if (user.citizen_details) {
            firstName = user.citizen_details.first_name;
            lastName = user.citizen_details.last_name;
        } else {
            const nameParts = user.name.split(' ');
            firstName = nameParts[0] || '';
            lastName = nameParts[nameParts.length - 1] || '';
        }

        const firstInitial = firstName?.charAt(0)?.toUpperCase() || '';
        const lastInitial = lastName?.charAt(0)?.toUpperCase() || '';
        return firstInitial + lastInitial;
    };

    const getUserFullName = (user: users_T) => {
        if (user.official_details) {
            return `${user.official_details.first_name} ${user.official_details.middle_name ? user.official_details.middle_name + ' ' : ''}${user.official_details.last_name}`;
        } else if (user.citizen_details) {
            return `${user.citizen_details.first_name} ${user.citizen_details.middle_name ? user.citizen_details.middle_name + ' ' : ''}${user.citizen_details.last_name}`;
        }
        return user.name;
    };

    const { data, setData, put, processing, errors, reset } =
        useForm<EditUserForm>({
            first_name:
                user.official_details?.first_name ||
                user.citizen_details?.first_name ||
                '',
            middle_name:
                user.official_details?.middle_name ||
                user.citizen_details?.middle_name ||
                '',
            last_name:
                user.official_details?.last_name ||
                user.citizen_details?.last_name ||
                '',
            suffix:
                user.official_details?.suffix ||
                user.citizen_details?.suffix ||
                '',
            email: user.email || '',
            phone_number:
                user.official_details?.contact_number ||
                user.citizen_details?.phone_number ||
                '',
            role_id: user.role?.id?.toString() || '',
            status: user.status || 'Active',
            // Citizen fields
            date_of_birth: user.citizen_details?.date_of_birth || '',
            address: user.citizen_details?.address || '',
            barangay:
                user.citizen_details?.barangay ||
                user.official_details?.assigned_brgy ||
                '',
            city: user.citizen_details?.city || '',
            province: user.citizen_details?.province || '',
            postal_code: user.citizen_details?.postal_code || '',
            is_verified: user.citizen_details?.is_verified || false,
            // Official fields
            office_address: user.official_details?.office_address || '',
            assigned_brgy: user.official_details?.assigned_brgy || '',
            latitude: user.official_details?.latitude || '',
            longitude: user.official_details?.longitude || '',
        });

    const handleSubmit = (e: FormEvent) => {
        e.preventDefault();

        put(`/user/${user.id}`, {
            onSuccess: (page) => {
                reset();
                const closeButton = document.querySelector(
                    '[data-dialog-close]',
                ) as HTMLButtonElement;
                if (closeButton) closeButton.click();
            },
            onError: (errors) => {},
            preserveScroll: true,
        });
    };

    return (
        <Dialog>
            <DialogTrigger asChild>{children}</DialogTrigger>
            <DialogContent
                className="flex max-h-[90vh] max-w-none flex-col overflow-hidden sm:max-w-2xl"
                showCloseButton={false}
            >
                <form
                    onSubmit={handleSubmit}
                    className="flex h-full flex-col overflow-hidden"
                >
                    <DialogHeader className="mb-6 flex-shrink-0">
                        <div className="flex flex-row items-center gap-4">
                            <Avatar className="h-14 w-14">
                                <AvatarFallback className="bg-primary text-2xl font-semibold text-primary-foreground">
                                    {getInitials(user)}
                                </AvatarFallback>
                            </Avatar>
                            <div className="text-left">
                                <h3 className="text-xl font-semibold">
                                    {getUserFullName(user)}
                                </h3>
                                <p className="text-sm text-muted-foreground">
                                    {user.email}
                                </p>
                            </div>
                        </div>
                    </DialogHeader>

                    <div className="flex-1 space-y-6 overflow-y-auto">
                        {/* Contact Information & Role */}
                        <div className="flex w-full flex-col gap-6">
                            <div className="grid flex-1 auto-rows-min gap-4">
                                <div className="grid">
                                    <p className="text-sm font-medium text-muted-foreground">
                                        Personal Information
                                    </p>
                                </div>
                                {/* First Name and Middle name */}
                                <div className="grid w-full grid-cols-5 gap-4">
                                    <div className="col-span-3 grid gap-2">
                                        <Label htmlFor="first-name">
                                            First Name
                                        </Label>
                                        <div className="relative">
                                            <Input
                                                id="first-name"
                                                value={data.first_name}
                                                onChange={(e) =>
                                                    setData(
                                                        'first_name',
                                                        e.target.value,
                                                    )
                                                }
                                                placeholder="Enter first name"
                                                className={
                                                    errors.first_name
                                                        ? 'border-[var(--destructive)] focus:ring-[var(--ring)]'
                                                        : ''
                                                }
                                            />
                                            {errors.first_name && (
                                                <span className="absolute -bottom-5 left-0 text-xs text-[var(--destructive)]">
                                                    {errors.first_name}
                                                </span>
                                            )}
                                        </div>
                                    </div>

                                    <div className="col-span-2 grid gap-2">
                                        <Label htmlFor="middle-name">
                                            Middle Name
                                        </Label>
                                        <Input
                                            id="middle-name"
                                            value={data.middle_name}
                                            onChange={(e) =>
                                                setData(
                                                    'middle_name',
                                                    e.target.value,
                                                )
                                            }
                                            placeholder="Enter middle name (optional)"
                                        />
                                    </div>
                                </div>
                                {/* Last Name and Suffix */}
                                <div className="grid w-full grid-cols-4 gap-4">
                                    <div className="col-span-3 grid gap-2">
                                        <Label htmlFor="last-name">
                                            Last Name
                                        </Label>
                                        <div className="relative">
                                            <Input
                                                id="last-name"
                                                value={data.last_name}
                                                onChange={(e) =>
                                                    setData(
                                                        'last_name',
                                                        e.target.value,
                                                    )
                                                }
                                                placeholder="Enter last name"
                                                className={
                                                    errors.last_name
                                                        ? 'border-red-500 focus:ring-red-500'
                                                        : ''
                                                }
                                            />
                                            {errors.last_name && (
                                                <span className="absolute -bottom-5 left-0 text-xs text-red-500">
                                                    {errors.last_name}
                                                </span>
                                            )}
                                        </div>
                                    </div>
                                    <div className="col-span-1 grid gap-2">
                                        <Label htmlFor="suffix">
                                            Suffix (Optional)
                                        </Label>
                                        <Input
                                            id="suffix"
                                            value={data.suffix}
                                            onChange={(e) =>
                                                setData(
                                                    'suffix',
                                                    e.target.value,
                                                )
                                            }
                                            placeholder="Jr., Sr., III, etc."
                                        />
                                    </div>
                                </div>
                            </div>
                            <div className="flex flex-col gap-4">
                                <div className="grid">
                                    <p className="text-sm font-medium text-[var(--color-text-muted)]">
                                        Contact Information
                                    </p>
                                </div>
                                <div className="grid w-full grid-cols-2 gap-4">
                                    <div className="grid gap-2">
                                        <Label htmlFor="email">Email</Label>
                                        <div className="relative">
                                            <Input
                                                id="email"
                                                type="email"
                                                value={data.email}
                                                onChange={(e) =>
                                                    setData(
                                                        'email',
                                                        e.target.value,
                                                    )
                                                }
                                                placeholder="Enter email address"
                                                className={
                                                    errors.email
                                                        ? 'border-red-500 focus:ring-red-500'
                                                        : ''
                                                }
                                            />
                                            {errors.email && (
                                                <span className="absolute -bottom-5 left-0 text-xs text-red-500">
                                                    {errors.email}
                                                </span>
                                            )}
                                        </div>
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="contact">
                                            Contact Number
                                        </Label>
                                        <div className="relative">
                                            <Input
                                                id="contact"
                                                type="tel"
                                                value={data.phone_number}
                                                onChange={(e) =>
                                                    setData(
                                                        'phone_number',
                                                        e.target.value,
                                                    )
                                                }
                                                placeholder="Enter contact number"
                                                className={
                                                    errors.phone_number
                                                        ? 'border-red-500 focus:ring-red-500'
                                                        : ''
                                                }
                                            />
                                            {errors.phone_number && (
                                                <span className="absolute -bottom-5 left-0 text-xs text-red-500">
                                                    {errors.phone_number}
                                                </span>
                                            )}
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div className="flex flex-col gap-4">
                                <div className="grid">
                                    <p className="text-sm font-medium text-[var(--color-text-muted)]">
                                        Role & Location
                                    </p>
                                </div>
                                <div className="grid w-full grid-cols-2 gap-4">
                                    <div className="grid flex-1 gap-2">
                                        <Label htmlFor="role">Role</Label>
                                        <div className="relative">
                                            <Select
                                                value={data.role_id}
                                                onValueChange={(value) =>
                                                    setData('role_id', value)
                                                }
                                            >
                                                <SelectTrigger
                                                    className={
                                                        errors.role_id
                                                            ? 'border-red-500 focus:ring-red-500'
                                                            : ''
                                                    }
                                                >
                                                    <SelectValue placeholder="Select a role" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    {roles.map((role) => (
                                                        <SelectItem
                                                            key={role.id}
                                                            value={role.id.toString()}
                                                        >
                                                            {role.name}
                                                        </SelectItem>
                                                    ))}
                                                </SelectContent>
                                            </Select>
                                            {errors.role_id && (
                                                <span className="absolute -bottom-5 left-0 text-xs text-red-500">
                                                    {errors.role_id}
                                                </span>
                                            )}
                                        </div>
                                    </div>
                                    <div className="grid flex-1 gap-2">
                                        <Label htmlFor="location">
                                            Assigned Location
                                        </Label>
                                        <div className="relative">
                                            <Select
                                                value={data.barangay}
                                                onValueChange={(value) => {
                                                    setData('barangay', value);
                                                    setData(
                                                        'assigned_brgy',
                                                        value,
                                                    );
                                                }}
                                            >
                                                <SelectTrigger
                                                    className={
                                                        errors.barangay
                                                            ? 'border-red-500 focus:ring-red-500'
                                                            : ''
                                                    }
                                                >
                                                    <SelectValue placeholder="Select location" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    {locations.map(
                                                        (location) => (
                                                            <SelectItem
                                                                key={
                                                                    location.id
                                                                }
                                                                value={
                                                                    location.location_name
                                                                }
                                                            >
                                                                {
                                                                    location.location_name
                                                                }
                                                            </SelectItem>
                                                        ),
                                                    )}
                                                </SelectContent>
                                            </Select>
                                            {errors.barangay && (
                                                <span className="absolute -bottom-5 left-0 text-xs text-red-500">
                                                    {errors.barangay}
                                                </span>
                                            )}
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div className="flex flex-col gap-2">
                                <div className="grid">
                                    <p className="text-sm font-medium text-[var(--gray)]">
                                        Status
                                    </p>
                                </div>
                                <div className="flex w-auto flex-row gap-2">
                                    <div className="grid flex-2 gap-2">
                                        <div className="relative">
                                            <Select
                                                value={data.status}
                                                onValueChange={(value) =>
                                                    setData('status', value)
                                                }
                                            >
                                                <SelectTrigger
                                                    className={
                                                        errors.status
                                                            ? 'border-red-500 focus:ring-red-500'
                                                            : ''
                                                    }
                                                >
                                                    <SelectValue placeholder="Select status" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem value="Active">
                                                        Active
                                                    </SelectItem>
                                                    <SelectItem value="Inactive">
                                                        Inactive
                                                    </SelectItem>
                                                    <SelectItem value="Archived">
                                                        Archived
                                                    </SelectItem>
                                                </SelectContent>
                                            </Select>
                                            {errors.status && (
                                                <span className="absolute -bottom-5 left-0 text-xs text-red-500">
                                                    {errors.status}
                                                </span>
                                            )}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <DialogFooter className="flex-shrink-0 bg-background pt-5">
                        <div className="flex w-full gap-4">
                            <DialogClose asChild>
                                <Button
                                    type="button"
                                    variant="outline"
                                    data-dialog-close
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

export default EditUser;
