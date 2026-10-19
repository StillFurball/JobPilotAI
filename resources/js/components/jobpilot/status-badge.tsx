import {
    AlertTriangle,
    CheckCircle2,
    Clock,
    Loader2,
    Send,
    XCircle,
} from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { cn } from '@/lib/utils';

const tones = {
    green: 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900 dark:bg-emerald-950 dark:text-emerald-300',
    amber: 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-900 dark:bg-amber-950 dark:text-amber-300',
    red: 'border-rose-200 bg-rose-50 text-rose-700 dark:border-rose-900 dark:bg-rose-950 dark:text-rose-300',
    blue: 'border-blue-200 bg-blue-50 text-blue-700 dark:border-blue-900 dark:bg-blue-950 dark:text-blue-300',
} as const;

const statuses: Record<
    string,
    {
        label: string;
        icon: LucideIcon;
        tone: keyof typeof tones;
        spin?: boolean;
    }
> = {
    draft: { label: 'Awaiting approval', icon: Clock, tone: 'amber' },
    awaiting_approval: {
        label: 'Awaiting approval',
        icon: Clock,
        tone: 'amber',
    },
    approved: { label: 'Approved', icon: CheckCircle2, tone: 'green' },
    rejected: { label: 'Rejected', icon: XCircle, tone: 'red' },
    sent: { label: 'Sent', icon: Send, tone: 'blue' },
    sending: { label: 'Sending…', icon: Loader2, tone: 'amber', spin: true },
    failed: { label: 'Failed', icon: AlertTriangle, tone: 'red' },
    uncertain: { label: 'Uncertain', icon: AlertTriangle, tone: 'red' },
    pending: { label: 'Queued', icon: Clock, tone: 'amber' },
    running: { label: 'Running', icon: Loader2, tone: 'blue', spin: true },
    completed: { label: 'Completed', icon: CheckCircle2, tone: 'green' },
};

export default function StatusBadge({
    status,
    className,
}: {
    status: string;
    className?: string;
}) {
    const meta = statuses[status] ?? {
        label: status,
        icon: Clock,
        tone: 'amber' as const,
    };
    const Icon = meta.icon;

    return (
        <Badge variant="outline" className={cn(tones[meta.tone], className)}>
            <Icon className={cn(meta.spin && 'animate-spin')} />
            {meta.label}
        </Badge>
    );
}
