import type { ReactNode } from 'react';
import { LanguageSwitcher } from '@/components/LanguageSwitcher';
import { ThemeSwitcher } from '@/components/ThemeSwitcher';

type AuthShellProps = {
    children: ReactNode;
};

export function AuthShell({ children }: AuthShellProps) {
    return (
        <div className="az-atmosphere relative min-h-screen">
            <div className="absolute top-4 right-4 z-20 flex items-center gap-2 sm:top-6 sm:right-6">
                <ThemeSwitcher />
                <LanguageSwitcher />
            </div>
            <div className="az-shell-motion relative z-10">{children}</div>
        </div>
    );
}
