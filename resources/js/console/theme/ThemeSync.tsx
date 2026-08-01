import { useEffect, useRef } from 'react';
import { useAuth } from '@/auth/AuthContext';
import { isThemePreference } from '@/lib/theme';
import { useTheme } from '@/theme/ThemeProvider';

/**
 * When a logged-in user has a saved theme_preference, prefer that over local storage.
 */
export function ThemeSync() {
    const { user, loading } = useAuth();
    const { preference, setPreference } = useTheme();
    const appliedUserId = useRef<number | null>(null);

    useEffect(() => {
        if (loading) {
            return;
        }

        if (!user) {
            appliedUserId.current = null;
            return;
        }

        if (appliedUserId.current === user.id) {
            return;
        }

        appliedUserId.current = user.id;

        if (isThemePreference(user.theme_preference) && user.theme_preference !== preference) {
            setPreference(user.theme_preference);
        }
    }, [loading, preference, setPreference, user]);

    return null;
}
