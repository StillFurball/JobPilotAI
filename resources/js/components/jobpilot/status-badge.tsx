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
    green: 'border-clear/30 bg-clear-soft text-clear',
    amber: 'border-caution/25 bg-taxiway-soft text-caution',
    red: 'border-hold/30 bg-hold-soft text-hold',
    blue: 'border-strip/30 bg-strip-soft text-strip',
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
