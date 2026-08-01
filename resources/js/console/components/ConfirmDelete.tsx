import { useEffect, useState } from 'react';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useI18n } from '@/i18n/useI18n';

type ConfirmDeleteProps = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    title?: string;
    description?: string;
    confirmLabel?: string;
    /** Word the user must type; defaults to "delete". */
    confirmWord?: string;
    count?: number;
    onConfirm: () => Promise<void> | void;
};

export function ConfirmDelete({
    open,
    onOpenChange,
    title,
    description,
    confirmLabel,
    confirmWord = 'delete',
    count,
    onConfirm,
}: ConfirmDeleteProps) {
    const { t } = useI18n();
    const [pending, setPending] = useState(false);
    const [typed, setTyped] = useState('');

    useEffect(() => {
        if (!open) {
            setTyped('');
            setPending(false);
        }
    }, [open]);

    const canConfirm = typed.trim().toLowerCase() === confirmWord.toLowerCase();

    return (
        <AlertDialog open={open} onOpenChange={onOpenChange}>
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>
                        {title ??
                            (count && count > 1
                                ? t('common.delete_confirm_title_many', { count })
                                : t('common.delete_confirm_title'))}
                    </AlertDialogTitle>
                    <AlertDialogDescription>
                        {description ?? t('common.delete_confirm_description')}
                    </AlertDialogDescription>
                </AlertDialogHeader>

                <div className="space-y-1.5">
                    <Label htmlFor="confirm-delete-input">
                        {t('common.delete_confirm_type', { word: confirmWord })}
                    </Label>
                    <Input
                        id="confirm-delete-input"
                        autoFocus
                        autoComplete="off"
                        value={typed}
                        onChange={(e) => setTyped(e.target.value)}
                        placeholder={confirmWord}
                    />
                </div>

                <AlertDialogFooter>
                    <AlertDialogCancel disabled={pending}>{t('common.cancel')}</AlertDialogCancel>
                    <AlertDialogAction
                        className="bg-danger text-paper-elevated hover:opacity-90"
                        disabled={pending || !canConfirm}
                        onClick={(event) => {
                            event.preventDefault();
                            if (!canConfirm) return;
                            setPending(true);
                            void Promise.resolve(onConfirm())
                                .then(() => onOpenChange(false))
                                .catch(() => {
                                    // Keep dialog open; callers surface errors (e.g. toasts).
                                })
                                .finally(() => setPending(false));
                        }}
                    >
                        {pending ? t('common.deleting') : (confirmLabel ?? t('common.delete'))}
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    );
}
