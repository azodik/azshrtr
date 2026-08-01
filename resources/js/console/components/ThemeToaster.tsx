import { Toaster } from 'sonner';
import { useTheme } from '@/theme/ThemeProvider';

export function ThemeToaster() {
    const { resolved } = useTheme();

    return (
        <Toaster
            position="top-right"
            closeButton
            theme={resolved}
            gap={10}
            visibleToasts={4}
            toastOptions={{
                classNames: {
                    toast: 'border bg-paper-elevated text-ink shadow-none',
                    title: 'text-ink font-medium',
                    description: 'text-ink-soft',
                    success:
                        'border-success/40 bg-success/10 [&_[data-title]]:text-success [&_[data-description]]:text-ink-soft',
                    error: 'border-danger/40 bg-danger/10 [&_[data-title]]:text-danger [&_[data-description]]:text-ink-soft',
                    info: 'border-mist bg-paper-elevated',
                    closeButton: 'border-mist bg-paper text-ink',
                },
            }}
        />
    );
}
