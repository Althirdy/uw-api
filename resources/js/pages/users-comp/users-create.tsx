import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Sheet,
    SheetClose,
    SheetContent,
    SheetDescription,
    SheetFooter,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';
import { useForm } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { FormEvent, useState } from 'react';
import { roles_T } from '@/types/role-types';

type CreateUserForm = {
    first_name: string;
    middle_name: string;
    last_name: string;
    email: string;
    phone_number: string;
    assigned_brgy: string;
    role_id: string;
    password: string;
    password_confirmation: string;
    suffix?: string;
    office_address?: string;
    latitude?: string;
    longitude?: string;
};

function CreateUsers({ roles }: { roles: roles_T[] }) {
    const { data, setData, post, processing, errors, reset } =
        useForm<CreateUserForm>({
            first_name: '',
            middle_name: '',
            last_name: '',
            email: '',
            phone_number: '',
            assigned_brgy: 'Barangay 176-E',
            role_id: '',
            password: '',
            password_confirmation: '',
            suffix: '',
            office_address: '',
            latitude: '',
            longitude: '',
        });

    const [clientErrors, setClientErrors] = useState<Partial<CreateUserForm>>(
        {},
    );

    // Validation functions
    const validateName = (
        value: string,
        fieldName: string,
        required = true,
    ) => {
        if (required && !value.trim()) {
            return `${fieldName} is required`;
        }
        if (value && !/^[a-zA-Z\s'-]*$/.test(value)) {
            return `${fieldName} can only contain letters and spaces`;
        }
        return '';
    };

    const validateEmail = (value: string) => {
        if (!value.trim()) {
            return 'Email is required';
        }
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(value)) {
            return 'Please enter a valid email address';
        }
        return '';
    };

    const validatePhoneNumber = (value: string) => {
        if (value && !/^[0-9]{10,15}$/.test(value)) {
            return 'Phone number must be 10-15 digits only';
        }
        return '';
    };

    const validatePassword = (value: string, roleId?: string) => {
        const isPurokLeader = (roleId || data.role_id) === '2';
        const fieldName = isPurokLeader ? 'PIN' : 'Password';
        
        if (!value) {
            return `${fieldName} is required`;
        }
        
        // Different validation for PIN (Purok Leader) vs Password
        if (isPurokLeader) {
            // PIN should only contain numbers
            if (!/^\d+$/.test(value)) {
                return 'PIN must contain only numbers';
            }
            if (value.length < 4) {
                return 'PIN must be at least 4 digits';
            }
        } else {
            // Regular password validation
            if (value.length < 8) {
                return `${fieldName} must be at least 8 characters`;
            }
            if (!/[a-zA-Z]/.test(value)) {
                return `${fieldName} must contain at least one letter`;
            }
            if (!/[0-9]/.test(value)) {
                return `${fieldName} must contain at least one number`;
            }
            if (!/[!@#$%^&*(),.?":{}|<>]/.test(value)) {
                return `${fieldName} must contain at least one symbol`;
            }
        }
        return '';
    };

    const validatePasswordConfirmation = (value: string, password: string, roleId?: string) => {
        const isPurokLeader = (roleId || data.role_id) === '2';
        const fieldName = isPurokLeader ? 'PIN' : 'Password';
        
        if (!value) {
            return `${fieldName} confirmation is required`;
        }
        if (value !== password) {
            return `${fieldName}s do not match`;
        }
        return '';
    };

    // Handle input changes with validation
    const handleInputChange = (field: keyof CreateUserForm, value: string) => {
        setData(field, value);

        // Clear previous client error for this field
        setClientErrors((prev) => ({ ...prev, [field]: undefined }));

        // Validate on change
        let error = '';
        switch (field) {
            case 'first_name':
                error = validateName(value, 'First name');
                break;
            case 'middle_name':
                error = validateName(value, 'Middle name', false);
                break;
            case 'last_name':
                error = validateName(value, 'Last name');
                break;
            case 'email':
                error = validateEmail(value);
                break;
            case 'phone_number':
                error = validatePhoneNumber(value);
                break;
            case 'password':
                error = validatePassword(value);
                // Also revalidate password confirmation if it exists
                if (data.password_confirmation) {
                    const confirmError = validatePasswordConfirmation(
                        data.password_confirmation,
                        value,
                    );
                    setClientErrors((prev) => ({
                        ...prev,
                        password_confirmation: confirmError || undefined,
                    }));
                }
                break;
            case 'password_confirmation':
                error = validatePasswordConfirmation(value, data.password);
                break;
        }

        if (error) {
            setClientErrors((prev) => ({ ...prev, [field]: error }));
        }
    };

    const handleSubmit = (e: FormEvent) => {
        e.preventDefault();

        // Client-side validation before submission
        const validationErrors: Partial<CreateUserForm> = {};

        validationErrors.first_name =
            validateName(data.first_name, 'First name') || undefined;
        validationErrors.middle_name =
            validateName(data.middle_name, 'Middle name', false) || undefined;
        validationErrors.last_name =
            validateName(data.last_name, 'Last name') || undefined;
        validationErrors.email = validateEmail(data.email) || undefined;
        validationErrors.phone_number =
            validatePhoneNumber(data.phone_number) || undefined;
        validationErrors.password =
            validatePassword(data.password) || undefined;
        validationErrors.password_confirmation =
            validatePasswordConfirmation(
                data.password_confirmation,
                data.password,
            ) || undefined;

        // Check if role_id and assigned_brgy are selected
        if (!data.role_id) {
            validationErrors.role_id = 'Please select a role';
        }
        if (!data.assigned_brgy) {
            validationErrors.assigned_brgy = 'Please select a barangay';
        }

        // Remove undefined values
        Object.keys(validationErrors).forEach((key) => {
            if (validationErrors[key as keyof CreateUserForm] === undefined) {
                delete validationErrors[key as keyof CreateUserForm];
            }
        });

        console.log('Validation errors:', validationErrors);
        console.log('Form data:', data);

        if (Object.keys(validationErrors).length > 0) {
            setClientErrors(validationErrors);
            console.log('Form has validation errors, not submitting');
            return;
        }

        // Clear client errors and submit
        setClientErrors({});
        console.log('Submitting form...');
        console.log('Form data being sent:', data);
        post('/user', {
            onSuccess: () => {
                console.log('User created successfully');
                reset();
                setClientErrors({});
                // Force page reload to show the new user
                window.location.href = '/users';
            },
            onError: (errors) => {
                console.log('Server validation errors:', errors);
                console.error('Full error object:', JSON.stringify(errors, null, 2));
            },
        });
    };

    return (
        <Sheet>
            <SheetTrigger asChild>
                <Button className="cursor-pointer px-4 py-2">
                    <Plus /> Add User
                </Button>
            </SheetTrigger>
            <SheetContent className="flex flex-col h-full">
                <form onSubmit={handleSubmit} className="flex flex-col h-full">
                    <SheetHeader className="flex-shrink-0 pb-4 border-b">
                        <SheetTitle>Add New User</SheetTitle>
                        <SheetDescription>
                            Create a new user account with their personal
                            information and role assignment.
                        </SheetDescription>
                    </SheetHeader>
                    <div className="flex-1 overflow-y-auto py-6 px-4 space-y-6">
                        <div className="grid flex-1 auto-rows-min gap-2">
                            <div className="grid gap-3">
                                <Label htmlFor="first-name">First Name</Label>
                                <div>
                                    <Input
                                        id="first-name"
                                        value={data.first_name}
                                        onChange={(e) =>
                                            handleInputChange(
                                                'first_name',
                                                e.target.value,
                                            )
                                        }
                                        placeholder=""
                                        className={
                                            errors.first_name ||
                                            clientErrors.first_name
                                                ? 'border-[var(--destructive)] focus:ring-[var(--ring)]'
                                                : ''
                                        }
                                    />
                                    {(errors.first_name ||
                                        clientErrors.first_name) && (
                                        <span className="mt-1 block text-xs text-[var(--destructive)]">
                                            {errors.first_name ||
                                                clientErrors.first_name}
                                        </span>
                                    )}
                                </div>
                            </div>
                            <div className="grid gap-3">
                                <Label htmlFor="middle-name">Middle Name</Label>
                                <div>
                                    <Input
                                        id="middle-name"
                                        value={data.middle_name}
                                        onChange={(e) =>
                                            handleInputChange(
                                                'middle_name',
                                                e.target.value,
                                            )
                                        }
                                        placeholder=""
                                        className={
                                            clientErrors.middle_name
                                                ? 'border-red-500 focus:ring-red-500'
                                                : ''
                                        }
                                    />
                                    {clientErrors.middle_name && (
                                        <span className="mt-1 block text-xs text-red-500">
                                            {clientErrors.middle_name}
                                        </span>
                                    )}
                                </div>
                            </div>
                            <div className="grid gap-3">
                                <Label htmlFor="last-name">Last Name</Label>
                                <div>
                                    <Input
                                        id="last-name"
                                        value={data.last_name}
                                        onChange={(e) =>
                                            handleInputChange(
                                                'last_name',
                                                e.target.value,
                                            )
                                        }
                                        placeholder=""
                                        className={
                                            errors.last_name ||
                                            clientErrors.last_name
                                                ? 'border-red-500 focus:ring-red-500'
                                                : ''
                                        }
                                    />
                                    {(errors.last_name ||
                                        clientErrors.last_name) && (
                                        <span className="mt-1 block text-xs text-red-500">
                                            {errors.last_name ||
                                                clientErrors.last_name}
                                        </span>
                                    )}
                                </div>
                            </div>
                        </div>

                        <div className="grid flex-1 auto-rows-min gap-2">
                            <div className="grid gap-3">
                                <Label htmlFor="email">Email</Label>
                                <div>
                                    <Input
                                        id="email"
                                        type="email"
                                        value={data.email}
                                        onChange={(e) =>
                                            handleInputChange(
                                                'email',
                                                e.target.value,
                                            )
                                        }
                                        placeholder=""
                                        className={
                                            errors.email || clientErrors.email
                                                ? 'border-red-500 focus:ring-red-500'
                                                : ''
                                        }
                                    />
                                    {(errors.email || clientErrors.email) && (
                                        <span className="mt-1 block text-xs text-red-500">
                                            {errors.email || clientErrors.email}
                                        </span>
                                    )}
                                </div>
                            </div>
                            <div className="grid gap-3">
                                <Label htmlFor="phone-number">
                                    Phone Number
                                </Label>
                                <div>
                                    <Input
                                        id="phone-number"
                                        value={data.phone_number}
                                        onChange={(e) =>
                                            handleInputChange(
                                                'phone_number',
                                                e.target.value,
                                            )
                                        }
                                        placeholder=""
                                        className={
                                            errors.phone_number ||
                                            clientErrors.phone_number
                                                ? 'border-red-500 focus:ring-red-500'
                                                : ''
                                        }
                                    />
                                    {(errors.phone_number ||
                                        clientErrors.phone_number) && (
                                        <span className="mt-1 block text-xs text-red-500">
                                            {errors.phone_number ||
                                                clientErrors.phone_number}
                                        </span>
                                    )}
                                </div>
                            </div>
                        </div>

                        <div className="grid flex-1 auto-rows-min gap-2">
                            <div className="flex w-full flex-row gap-4">
                                <div className="grid flex-1 gap-3">
                                    <Label htmlFor="role">Role</Label>
                                    <div>
                                        <Select
                                            value={data.role_id}
                                            onValueChange={(value) => {
                                                setData('role_id', value);
                                                setClientErrors((prev) => ({
                                                    ...prev,
                                                    role_id: undefined,
                                                }));
                                                // Revalidate password when role changes
                                                if (data.password) {
                                                    const passwordError = validatePassword(data.password);
                                                    setClientErrors((prev) => ({
                                                        ...prev,
                                                        password: passwordError || undefined,
                                                    }));
                                                }
                                                if (data.password_confirmation) {
                                                    const confirmError = validatePasswordConfirmation(
                                                        data.password_confirmation,
                                                        data.password
                                                    );
                                                    setClientErrors((prev) => ({
                                                        ...prev,
                                                        password_confirmation: confirmError || undefined,
                                                    }));
                                                }
                                            }}
                                        >
                                            <SelectTrigger
                                                className={
                                                    errors.role_id ||
                                                    clientErrors.role_id
                                                        ? 'border-red-500 focus:ring-red-500'
                                                        : ''
                                                }
                                            >
                                                <SelectValue placeholder="" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {roles
                                                    .filter((role) => role.name !== 'Citizen')
                                                    .map((role) => (
                                                        <SelectItem
                                                            key={role.id}
                                                            value={role.id.toString()}
                                                        >
                                                            {role.name}
                                                        </SelectItem>
                                                    ))}
                                            </SelectContent>
                                        </Select>
                                        {(errors.role_id ||
                                            clientErrors.role_id) && (
                                            <span className="mt-1 block text-xs text-red-500">
                                                {errors.role_id ||
                                                    clientErrors.role_id}
                                            </span>
                                        )}
                                    </div>
                                </div>
                                <div className="grid flex-1 gap-3">
                                    <Label htmlFor="barangay">Barangay</Label>
                                    <div>
                                        <Select
                                            value={data.assigned_brgy}
                                            onValueChange={(value) => {
                                                setData('assigned_brgy', value);
                                                setClientErrors((prev) => ({
                                                    ...prev,
                                                    assigned_brgy: undefined,
                                                }));
                                            }}
                                        >
                                            <SelectTrigger
                                                className={
                                                    errors.assigned_brgy ||
                                                    clientErrors.assigned_brgy
                                                        ? 'border-red-500 focus:ring-red-500'
                                                        : ''
                                                }
                                            >
                                                <SelectValue placeholder="" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="Barangay 176-A">
                                                    Barangay 176-A
                                                </SelectItem>
                                                <SelectItem value="Barangay 176-B">
                                                    Barangay 176-B
                                                </SelectItem>
                                                <SelectItem value="Barangay 176-C">
                                                    Barangay 176-C
                                                </SelectItem>
                                                <SelectItem value="Barangay 176-D">
                                                    Barangay 176-D
                                                </SelectItem>
                                                <SelectItem value="Barangay 176-E">
                                                    Barangay 176-E
                                                </SelectItem>
                                                <SelectItem value="Barangay 176-F">
                                                    Barangay 176-F
                                                </SelectItem>
                                            </SelectContent>
                                        </Select>
                                        {(errors.assigned_brgy ||
                                            clientErrors.assigned_brgy) && (
                                            <span className="mt-1 block text-xs text-red-500">
                                                {errors.assigned_brgy ||
                                                    clientErrors.assigned_brgy}
                                            </span>
                                        )}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div className="grid flex-1 auto-rows-min gap-2">
                            <div className="grid gap-3">
                                <Label htmlFor="password">
                                    {data.role_id === '2' ? 'PIN' : 'Password'}
                                </Label>
                                <div>
                                    <Input
                                        id="password"
                                        type="password"
                                        value={data.password}
                                        onChange={(e) =>
                                            handleInputChange(
                                                'password',
                                                e.target.value,
                                            )
                                        }
                                        placeholder=""
                                        className={
                                            errors.password ||
                                            clientErrors.password
                                                ? 'border-red-500 focus:ring-red-500'
                                                : ''
                                        }
                                    />
                                    {(errors.password ||
                                        clientErrors.password) && (
                                        <span className="mt-1 block text-xs text-red-500">
                                            {errors.password ||
                                                clientErrors.password}
                                        </span>
                                    )}
                                </div>
                            </div>
                            <div className="grid gap-3">
                                <Label htmlFor="password-confirmation">
                                    {data.role_id === '2' ? 'Confirm PIN' : 'Confirm Password'}
                                </Label>
                                <div>
                                    <Input
                                        id="password-confirmation"
                                        type="password"
                                        value={data.password_confirmation}
                                        onChange={(e) =>
                                            handleInputChange(
                                                'password_confirmation',
                                                e.target.value,
                                            )
                                        }
                                        placeholder=""
                                        className={
                                            errors.password_confirmation ||
                                            clientErrors.password_confirmation
                                                ? 'border-red-500 focus:ring-red-500'
                                                : ''
                                        }
                                    />
                                    {(errors.password_confirmation ||
                                        clientErrors.password_confirmation) && (
                                        <span className="mt-1 block text-xs text-red-500">
                                            {errors.password_confirmation ||
                                                clientErrors.password_confirmation}
                                        </span>
                                    )}
                                </div>
                            </div>
                        </div>
                    </div>
                    <SheetFooter className="flex-shrink-0 px-4 py-4 border-t bg-background">
                        <div className="flex w-full gap-2">
                            <Button
                                type="submit"
                                disabled={processing}
                                className="flex-1"
                            >
                                {processing ? 'Creating...' : 'Create User'}
                            </Button>
                            <SheetClose asChild>
                                <Button
                                    type="button"
                                    variant="outline"
                                    data-sheet-close
                                    className="flex-1"
                                >
                                    Cancel
                                </Button>
                            </SheetClose>
                        </div>
                    </SheetFooter>
                </form>
            </SheetContent>
        </Sheet>
    );
}

export default CreateUsers;
