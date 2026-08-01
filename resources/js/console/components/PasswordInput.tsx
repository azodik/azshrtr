import { Eye, EyeOff } from 'lucide-react';
import { type InputHTMLAttributes, useId, useState } from 'react';
import { Input } from '@/components/ui/input';
import { useI18n } from '@/i18n/useI18n';
import { cn } from '@/lib/cn';

type PasswordInputProps = Omit<InputHTMLAttributes<HTMLInputElement>, 'type'> & {
    label: string;
};

export function PasswordInput({ label, className, id, ...props }: PasswordInputProps) {
    const { t } = useI18n();
    const generatedId = useId();
    const inputId = id ?? generatedId;
    const [visible, setVisible] = useState(false);

    return (
        <label className="block space-y-1.5 text-sm font-medium text-ink" htmlFor={inputId}>
            {label}
            <div className="relative">
                <Input
                    {...props}
                    id={inputId}
                    type={visible ? 'text' : 'password'}
                    className={cn('pr-10', className)}
                />
                <button
                    type="button"
                    onClick={() => setVisible((current) => !current)}
                    className="absolute inset-y-0 right-0 flex items-center px-3 text-ink-soft transition-colors hover:text-ink"
                    aria-label={visible ? t('common.password.hide') : t('common.password.show')}
                    aria-pressed={visible}
                    tabIndex={-1}
                >
                    {visible ? (
                        <EyeOff className="size-4" aria-hidden="true" />
                    ) : (
                        <Eye className="size-4" aria-hidden="true" />
                    )}
                </button>
            </div>
        </label>
    );
}
