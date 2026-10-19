import { ShieldCheck } from 'lucide-react';
import type { ReactNode } from 'react';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';

export default function GuardrailBanner({
    title,
    children,
    action,
}: {
    title: string;
    children: ReactNode;
    action?: ReactNode;
}) {
    return (
        <Alert className="border-amber-200 bg-amber-50 text-amber-900 dark:border-amber-900/60 dark:bg-amber-950/40 dark:text-amber-100 [&>svg]:text-amber-600 dark:[&>svg]:text-amber-400">
            <ShieldCheck />
            <AlertTitle>{title}</AlertTitle>
            <AlertDescription className="text-amber-900/80 dark:text-amber-100/80">
                <div className="flex w-full flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>{children}</div>
                    {action}
                </div>
            </AlertDescription>
        </Alert>
    );
}
