import { Sparkles } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { percent } from '@/lib/jobpilot';
import { cn } from '@/lib/utils';

export function scoreTone(score: number): string {
    if (score >= 0.75) {
        return 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900 dark:bg-emerald-950 dark:text-emerald-300';
    }

    if (score >= 0.5) {
        return 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-900 dark:bg-amber-950 dark:text-amber-300';
    }

    return 'border-rose-200 bg-rose-50 text-rose-700 dark:border-rose-900 dark:bg-rose-950 dark:text-rose-300';
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
        <Badge variant="outline" className={cn(scoreTone(score), className)}>
            <Sparkles />
            {percent(score)} {label}
        </Badge>
    );
}
