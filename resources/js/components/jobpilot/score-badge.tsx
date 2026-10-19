import { Badge } from '@/components/ui/badge';
import { percent } from '@/lib/jobpilot';
import { cn } from '@/lib/utils';

export function scoreTone(score: number): string {
    if (score >= 0.75) {
        return 'border-clear/30 bg-clear-soft text-clear';
    }

    if (score >= 0.5) {
        return 'border-caution/25 bg-taxiway-soft text-caution';
    }

    return 'border-border bg-muted text-muted-foreground';
}

export default function ScoreBadge({
    score,
    label = 'match',
    className,
}: {
    score: number;
    label?: string;
    className?: string;
}) {
    return (
        <Badge
            variant="outline"
            className={cn('text-sm tabular-nums', scoreTone(score), className)}
        >
            <span className="font-bold">{percent(score)}</span> {label}
        </Badge>
    );
}
