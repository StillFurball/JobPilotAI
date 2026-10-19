import type { ReactNode } from 'react';

export default function PageHeader({
    title,
    description,
    actions,
}: {
    title: string;
    description?: ReactNode;
    actions?: ReactNode;
}) {
    return (
        <div className="flex flex-wrap items-end justify-between gap-4">
            <div className="space-y-1.5">
                <h1 className="font-display text-3xl leading-none font-bold sm:text-4xl">
                    {title}
                </h1>
                {description && (
                    <p className="max-w-[68ch] text-muted-foreground">
                        {description}
                    </p>
                )}
            </div>
            {actions && <div className="flex flex-wrap gap-2">{actions}</div>}
        </div>
    );
}
