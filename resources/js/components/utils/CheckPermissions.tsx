import { usePage } from '@inertiajs/react';

interface PageProps {
    permissions?: string[];
    [key: string]: unknown;
}

interface CheckPermissionsProps {
    permissions?: string[];
    notPermissions?: string[];
    anotherValidation?: boolean;
    children?: React.ReactNode;
    elseChildren?: React.ReactNode;
}

export default function CheckPermissions({
    permissions = [],
    notPermissions = [],
    anotherValidation = true,
    children = null,
    elseChildren = null,
}: CheckPermissionsProps) {
    const { permissions: userPermissions } = usePage<PageProps>().props;

    const hasRequiredPermission = (): boolean => {
        if (!Array.isArray(permissions) || permissions.length === 0) return true;
        return permissions.some((permission) => userPermissions?.includes(permission));
    };

    const hasNoForbiddenPermission = (): boolean => {
        if (!Array.isArray(notPermissions) || notPermissions.length === 0) return true;
        return !notPermissions.some((permission) => userPermissions?.includes(permission));
    };

    const isAuthorized = hasRequiredPermission() && hasNoForbiddenPermission() && anotherValidation;

    return isAuthorized ? children : elseChildren;
}
