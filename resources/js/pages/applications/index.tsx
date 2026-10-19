import { Head, Link, router, usePage } from '@inertiajs/react';
import {
    Building2,
    Hash,
    Loader2,
    Mail,
    Send,
    ShieldAlert,
} from 'lucide-react';
import { useState } from 'react';
import StatusBadge from '@/components/jobpilot/status-badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { formatDate, shortHash } from '@/lib/jobpilot';
import { index as applicationsIndex, send } from '@/routes/applications';
import { index as draftsIndex } from '@/routes/drafts';
import type { Application } from '@/types';

type Props = {
    applications: Application[];
    sendEnabled: boolean;
};

const FILTERS = [
    { value: '', label: 'All' },
    { value: 'approved', label: 'Ready' },
    { value: 'sent', label: 'Sent' },
    { value: 'uncertain', label: 'Needs attention' },
    { value: 'rejected', label: 'Rejected' },
];

export default function ApplicationsIndex({
    applications,
    sendEnabled,
}: Props) {
    const [filter, setFilter] = useState('');
    const [sending, setSending] = useState<number | null>(null);
    const { errors } = usePage().props as { errors: Record<string, string> };

    const visible = applications.filter((a) => !filter || a.status === filter);
    const count = (status: string) =>
        status
            ? applications.filter((a) => a.status === status).length
            : applications.length;

    const dispatch = (application: Application) =>
        router.visit(send(application.id), {
            preserveScroll: true,
            onStart: () => setSending(application.id),
            onFinish: () => setSending(null),
        });

    return (
        <>
            <Head title="Application tracker" />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <Card>
                    <CardHeader className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                        <div className="space-y-1.5">
                            <CardTitle className="flex items-center gap-2">
                                <Send className="size-4" />
                                Application pipeline
                            </CardTitle>
                            <CardDescription>
                                Lifecycle of approved applications and their
                                audit fingerprints.
                            </CardDescription>
                        </div>
                        <div className="flex flex-wrap gap-2">
                            {FILTERS.map((f) => (
                                <Button
                                    key={f.value}
                                    size="sm"
                                    variant={
                                        filter === f.value
                                            ? 'default'
                                            : 'outline'
                                    }
                                    onClick={() => setFilter(f.value)}
                                >
                                    {f.label} ({count(f.value)})
                                </Button>
                            ))}
                        </div>
                    </CardHeader>
                    {!sendEnabled && (
                        <CardContent>
                            <div className="flex items-start gap-2 rounded-md border border-blue-200 bg-blue-50 p-3 text-sm text-blue-900 dark:border-blue-900/50 dark:bg-blue-950/50 dark:text-blue-100">
                                <ShieldAlert className="mt-0.5 size-4 shrink-0" />
                                <span>
                                    Safe mode: sending re-verifies the approval
                                    but does not email anyone. Set{' '}
                                    <code>JOBPILOT_SEND_EMAILS=true</code> and
                                    configure your mailer to send for real.
                                </span>
                            </div>
                        </CardContent>
                    )}
                </Card>

                {errors.application && (
                    <div className="rounded-md border border-destructive/30 bg-destructive/10 p-3 text-sm text-destructive">
                        {errors.application}
                    </div>
                )}

                {visible.length === 0 ? (
                    <Card>
                        <CardContent className="py-12 text-center text-sm text-muted-foreground">
                            No applications here yet. Approve a draft on the{' '}
                            <Link
                                href={draftsIndex()}
                                className="text-primary underline-offset-4 hover:underline"
                            >
                                review page
                            </Link>{' '}
                            to add it to the pipeline.
                        </CardContent>
                    </Card>
                ) : (
                    visible.map((application) => (
                        <Card key={application.id} className="py-4">
                            <CardContent className="flex flex-col gap-4 px-4 lg:flex-row lg:items-center lg:justify-between">
                                <div className="min-w-0 space-y-2">
                                    <div className="flex flex-wrap items-center gap-2">
                                        <span className="font-semibold">
                                            {application.job_listing?.title ??
                                                `Application #${application.id}`}
                                        </span>
                                        <StatusBadge
                                            status={application.status}
                                        />
                                    </div>
                                    <div className="flex flex-wrap gap-x-4 gap-y-1 text-sm text-muted-foreground">
                                        <span className="flex items-center gap-1">
                                            <Building2 className="size-3.5" />
                                            {application.job_listing?.company ??
                                                'Unknown'}
                                        </span>
                                        <span className="flex items-center gap-1">
                                            <Mail className="size-3.5" />
                                            {application.draft
                                                ?.recipient_email ?? '—'}
                                        </span>
                                        <span className="flex items-center gap-1">
                                            <Hash className="size-3.5" />
                                            <code className="text-xs">
                                                {shortHash(
                                                    application.sent_content_hash,
                                                )}
                                            </code>
                                        </span>
                                    </div>
                                    <div className="text-xs text-muted-foreground">
                                        Approved{' '}
                                        {formatDate(application.approved_at)}
                                        {application.sent_at && (
                                            <>
                                                {' '}
                                                · Sent{' '}
                                                {formatDate(
                                                    application.sent_at,
                                                )}
                                            </>
                                        )}
                                    </div>
                                    {application.error_message && (
                                        <div className="text-xs text-destructive">
                                            {application.error_message}
                                        </div>
                                    )}
                                </div>
                                <div className="flex shrink-0 gap-2">
                                    <Button variant="outline" size="sm" asChild>
                                        <Link
                                            href={draftsIndex({
                                                query: {
                                                    draft: application.application_draft_id,
                                                },
                                            })}
                                        >
                                            View draft
                                        </Link>
                                    </Button>
                                    {(application.status === 'approved' ||
                                        application.status === 'uncertain') && (
                                        <Button
                                            size="sm"
                                            disabled={sending !== null}
                                            onClick={() =>
                                                dispatch(application)
                                            }
                                        >
                                            {sending === application.id ? (
                                                <Loader2 className="animate-spin" />
                                            ) : (
                                                <Send />
                                            )}
                                            {sendEnabled
                                                ? 'Send application'
                                                : 'Verify & dry-run send'}
                                        </Button>
                                    )}
                                </div>
                            </CardContent>
                        </Card>
                    ))
                )}
            </div>
        </>
    );
}

ApplicationsIndex.layout = {
    breadcrumbs: [{ title: 'Application tracker', href: applicationsIndex() }],
};
