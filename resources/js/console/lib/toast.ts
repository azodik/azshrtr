import { toast as sonnerToast } from 'sonner';

type ToastOptions = {
    description?: string;
};

export const toast = {
    success(title: string, options: ToastOptions = {}): void {
        sonnerToast.success(title, {
            description: options.description,
        });
    },

    error(title: string, options: ToastOptions = {}): void {
        sonnerToast.error(title, {
            description: options.description,
        });
    },

    info(title: string, options: ToastOptions = {}): void {
        sonnerToast.message(title, {
            description: options.description,
        });
    },
};
