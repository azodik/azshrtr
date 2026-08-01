import { useEffect, useMemo } from 'react';
import { useParams } from 'react-router';
import { useAuth } from '@/auth/AuthContext';
import { readLastOrgId, writeLastOrgId } from '@/lib/activeOrg';

export function useActiveOrg() {
    const { user } = useAuth();
    const { orgId } = useParams();

    const organization = useMemo(() => {
        if (!user) {
            return null;
        }
        if (orgId) {
            return user.organizations.find((o) => o.id === orgId) ?? user.organizations[0] ?? null;
        }
        const last = readLastOrgId();
        if (last) {
            const remembered = user.organizations.find((o) => o.id === last);
            if (remembered) {
                return remembered;
            }
        }
        return user.organizations[0] ?? null;
    }, [user, orgId]);

    useEffect(() => {
        if (organization) {
            writeLastOrgId(organization.id);
        }
    }, [organization]);

    return organization;
}
