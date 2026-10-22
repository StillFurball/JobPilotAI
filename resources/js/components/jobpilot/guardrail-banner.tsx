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
        <Alert className="border-l-4 border-caution/25 border-l-taxiway bg-taxiway-soft text-foreground [&>svg]:text-caution">
            <ShieldCheck />
            <AlertTitle className="font-bold">{title}</AlertTitle>
            <AlertDescription className="text-foreground/80">
                <div className="flex w-full flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>{children}</div>
                    {action}
                </div>
            </AlertDescription>
        </Alert>
    );
}
