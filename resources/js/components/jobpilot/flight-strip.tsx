import type { CSSProperties, ReactNode } from 'react';
import { cn } from '@/lib/utils';

export type StripState = 'holding' | 'approved' | 'sent';

const rails: Record<StripState, string> = {
    holding: 'bg-taxiway',
    approved: 'bg-clear',
    sent: 'bg-strip',
};

const stateLabels: Record<StripState, string> = {
    holding: 'Waiting for you',
    approved: 'Approved',
    sent: 'Sent',
};

type Props = {
    score: number;
    role: string;
    company: string;
    location: string;
    type: string;
    state: StripState;
    hash?: string | null;
    action?: ReactNode;
    className?: string;
    style?: CSSProperties;
};

/**
 * A job rendered as an air-traffic "flight strip": one row of boxed fields
 * with a coloured rail that shows where the application is in its journey.
 */
export default function FlightStrip({
    score,
    role,
    company,
    location,
    type,
    state,
    hash,
    action,
    className,
    style,
}: Props) {
    return (
        <article
            style={style}
            className={cn(
                'grid grid-cols-[6px_4.75rem_minmax(0,1fr)] overflow-hidden rounded-md bg-card text-card-foreground ring-1 ring-black/15 sm:grid-cols-[6px_5.5rem_minmax(0,1fr)_10rem_auto] dark:ring-white/10',
                className,
            )}
        >
            <div className={cn('row-span-2 sm:row-span-1', rails[state])} />

            <div className="flex flex-col justify-center border-r border-border px-3 py-2.5">
                <span className="font-display text-3xl leading-none font-bold tabular-nums">
                    {score}%
                </span>
                <span className="mt-0.5 text-xs text-muted-foreground">
                    match
                </span>
            </div>

            <div className="flex min-w-0 flex-col justify-center px-4 py-2.5">
                <p className="truncate font-bold">{role}</p>
                <p className="truncate text-sm text-muted-foreground">
                    {company}, {location}
                </p>
                {hash && (
                    <p className="truncate text-xs text-muted-foreground sm:hidden">
                        Fingerprint{' '}
                        <code className="font-mono text-foreground">
                            {hash.slice(0, 12)}
                        </code>
                    </p>
                )}
            </div>

            <div className="hidden flex-col justify-center border-l border-border px-3 py-2.5 text-sm sm:flex">
                {hash ? (
                    <>
                        <span className="text-xs text-muted-foreground">
                            Fingerprint
                        </span>
                        <code className="truncate font-mono text-sm">
                            {hash.slice(0, 12)}
                        </code>
                    </>
                ) : (
                    <>
                        <span>{type}</span>
                        <span className="text-xs text-muted-foreground">
                            {stateLabels[state]}
                        </span>
                    </>
                )}
            </div>

            {action && (
                <div className="col-span-2 flex items-center border-t border-border px-3 py-2 sm:col-span-1 sm:border-t-0 sm:border-l">
                    {action}
                </div>
            )}
        </article>
    );
}
