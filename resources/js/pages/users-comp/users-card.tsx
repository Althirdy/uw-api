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

import { location_T } from '@/types/location-types';
import { roles_T } from '@/types/role-types';
import { users_T } from '@/types/user-types';
import ArchiveUser from './users-archive';
import EditUser from './users-edit';
import ViewUser from './users-view';

const UserCard = ({
    users,
    roles,
    locations,
}: {
    users: users_T[];
    roles: roles_T[];
    locations: location_T[];
}) => {
    return (
        <div className="grid auto-rows-min gap-4 md:grid-cols-4">
            {users.length === 0 && (
                <Card className="col-span-full rounded-[var(--radius)] border border-sidebar-border/70 dark:border-sidebar-border">
                    <CardContent className="flex items-center justify-center py-12">
                        <p className="text-muted-foreground">
                            No users found matching your selection.
                        </p>
                    </CardContent>
                </Card>
            )}

            {/* Use filtered_roles for displaying cards */}
            {users.map((user) => (
                <Card
                    key={user.id}
                    className="relative overflow-hidden rounded-[var(--radius)] border border-sidebar-border/70 dark:border-sidebar-border"
                >
                    <CardHeader>
                        <CardTitle>{user.name}</CardTitle>
                        <CardDescription>{user.email}</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div className="flex flex-col gap-2 text-sm">
                            <p className="font-medium">Role & Location</p>
                            <div className="flex items-center gap-2">
                                <Badge
                                    className={`inline-flex items-center rounded-[var(--radius)] px-2.5 py-1 text-xs font-medium text-white ${
                                        user.role?.name === 'Operator'
                                            ? 'bg-green-800'
                                            : user.role?.name === 'Citizen'
                                              ? 'bg-orange-500'
                                              : user.role?.name ===
                                                  'Purok Leader'
                                                ? 'bg-blue-500'
                                                : user.role?.name === 'Admin'
                                                  ? 'bg-red-500'
                                                  : 'bg-gray-500'
                                    }`}
                                >
                                    {user.role ? user.role.name : 'N/A'}
                                </Badge>
                                <span className="text-muted-foreground">
                                    at{' '}
                                    {user.citizen_details?.barangay ||
                                        user.official_details?.assigned_brgy ||
                                        'N/A'}
                                </span>
                            </div>
                        </div>
                    </CardContent>
                    <CardFooter>
                        <div className="flex w-full justify-end gap-2">
                            <Tooltip>
                                <ViewUser user={user}>
                                    <TooltipTrigger asChild>
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            className="cursor-pointer"
                                        >
                                            <Open className="h-4 w-4" />
                                        </Button>
                                    </TooltipTrigger>
                                </ViewUser>
                                <TooltipContent>
                                    <p>View Details</p>
                                </TooltipContent>
                            </Tooltip>
                            <Tooltip>
                                <EditUser
                                    user={user}
                                    roles={roles}
                                    locations={locations}
                                >
                                    <TooltipTrigger asChild>
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            className="cursor-pointer"
                                        >
                                            <SquarePen className="h-4 w-4" />
                                        </Button>
                                    </TooltipTrigger>
                                </EditUser>
                                <TooltipContent>
                                    <p>Edit User</p>
                                </TooltipContent>
                            </Tooltip>

                            <Tooltip>
                                <ArchiveUser user={user}>
                                    <TooltipTrigger asChild>
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            className="cursor-pointer"
                                        >
                                            <Archive className="h-4 w-4 text-[var(--destructive)]" />
                                        </Button>
                                    </TooltipTrigger>
                                </ArchiveUser>
                                <TooltipContent>
                                    <p>Archive User</p>
                                </TooltipContent>
                            </Tooltip>
                        </div>
                    </CardFooter>
                </Card>
            ))}
        </div>
    );
};

export default UserCard;
