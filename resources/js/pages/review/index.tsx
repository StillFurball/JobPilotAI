import { Head, Link, useForm } from '@inertiajs/react';
import {
    AlertTriangle,
    CheckCircle2,
    Download,
    FileText,
    Hash,
    Loader2,
    Lock,
    RefreshCw,
    ShieldCheck,
    UserCheck,
    XCircle,
} from 'lucide-react';
import { useState } from 'react';
import GuardrailBanner from '@/components/jobpilot/guardrail-banner';
import StatusBadge from '@/components/jobpilot/status-badge';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { formatDate, shortHash } from '@/lib/jobpilot';
import { cn } from '@/lib/utils';
import {
    approve,
    cv,
    index as draftsIndex,
    reject,
    update,
} from '@/routes/drafts';
import { index as jobsIndex } from '@/routes/jobs';
import type { ApplicationDraft, ApprovalRecord } from '@/types';
import PageHeader from '@/components/page-header';

type Props = {
    drafts: ApplicationDraft[];
    selected: (ApplicationDraft & { approvals: ApprovalRecord[] }) | null;
};

export default function ReviewIndex({ drafts, selected }: Props) {
    if (drafts.length === 0 || !selected) {
        return (
            <>
                <Head title="Review & approval" />
                <div className="flex flex-1 items-center justify-center p-4">
                    <Card className="max-w-md text-center">
                        <CardContent className="space-y-3 py-10">
                            <ShieldCheck className="mx-auto size-10 text-muted-foreground" />
                            <p className="font-medium">
                                No drafts to review yet
                            </p>
                            <p className="text-sm text-muted-foreground">
                                Browse vacancies and choose{' '}
                                <em>Tailor CV & draft email</em> on any role.
                            </p>
                            <Button asChild>
                                <Link href={jobsIndex()}>
                                    Browse matched jobs
                                </Link>
                            </Button>
                        </CardContent>
                    </Card>
                </div>
            </>
        );
    }

    return (
        <>
            <Head title="Review & approval" />
            <div className="mx-auto flex h-full w-full max-w-7xl flex-1 flex-col gap-6 p-4 sm:p-6">
                <PageHeader
                    title="Review & approval"
                    description="Read each draft as the recruiter will see it. Edit anything you like, then approve the exact version you want sent."
                />

                <GuardrailBanner title="Nothing is sent without your approval">
                    JobPilot never sends an application automatically. Changing
                    the recipient, subject, body or CV changes the SHA-256
                    fingerprint and invalidates earlier approvals.
                </GuardrailBanner>

                <div className="flex gap-2 overflow-x-auto pb-1">
                    {drafts.map((d) => (
                        <Link
                            key={d.id}
                            href={draftsIndex({ query: { draft: d.id } })}
                            preserveScroll
                            className={cn(
                                'flex shrink-0 items-center gap-2 rounded-lg border px-3 py-2 text-sm transition-colors hover:bg-accent',
                                d.id === selected.id &&
                                    'border-primary bg-primary/5',
                            )}
                        >
                            <span className="max-w-48 truncate font-medium">
                                {d.job?.title ?? `Draft #${d.id}`}
                            </span>
                            <span className="text-muted-foreground">
                                v{d.version}
                            </span>
                            <StatusBadge status={d.status} />
                        </Link>
                    ))}
                </div>

                {/* Remount the editor whenever the draft or its version changes. */}
                <DraftEditor
                    key={`${selected.id}-${selected.version}-${selected.status}`}
                    draft={selected}
                />
            </div>
        </>
    );
}

function DraftEditor({
    draft,
}: {
    draft: ApplicationDraft & { approvals: ApprovalRecord[] };
}) {
    const edit = useForm({
        recipient_email: draft.recipient_email ?? '',
        subject: draft.subject ?? '',
        body: draft.body ?? '',
    });
    const review = useForm({
        content_hash: draft.content_hash ?? '',
        notes: '',
    });
    const [confirmReject, setConfirmReject] = useState(false);
    const locked = draft.status === 'approved' || draft.status === 'sent';

    const save = () => edit.submit(update(draft.id), { preserveScroll: true });

    const doApprove = () => {
        if (edit.isDirty) {
            return;
        }

        review.submit(approve(draft.id), { preserveScroll: true });
    };

    const doReject = () =>
        review.submit(reject(draft.id), {
            preserveScroll: true,
            onSuccess: () => setConfirmReject(false),
        });

    const errors = { ...review.errors, ...edit.errors } as Record<
        string,
        string | undefined
    >;

    return (
        <div className="grid grid-cols-1 gap-4 xl:grid-cols-2">
            <Card>
                <CardHeader className="flex flex-row items-start justify-between gap-4">
                    <div className="space-y-1.5">
                        <CardTitle>Application email</CardTitle>
                        <CardDescription>
                            Draft #{draft.id} · version {draft.version} ·{' '}
                            {draft.job?.company ?? 'Unknown company'}
                        </CardDescription>
                    </div>
                    <StatusBadge status={draft.status} />
                </CardHeader>
                <CardContent className="space-y-4">
                    {(errors.draft || errors.content_hash) && (
                        <div className="flex items-start gap-2 rounded-md border border-destructive/30 bg-destructive/10 p-3 text-sm text-destructive">
                            <AlertTriangle className="mt-0.5 size-4 shrink-0" />
                            <span>{errors.draft ?? errors.content_hash}</span>
                        </div>
                    )}

                    <div className="grid gap-2">
                        <div className="flex items-center justify-between">
                            <Label htmlFor="recipient_email">Recipient</Label>
                            {draft.recipient_verified ? (
                                <Badge
                                    variant="outline"
                                    className="border-clear/30 bg-clear-soft text-clear"
                                >
                                    <UserCheck />
                                    From the job listing
                                </Badge>
                            ) : (
                                <Badge
                                    variant="outline"
                                    className="border-caution/25 bg-taxiway-soft text-caution"
                                >
                                    <AlertTriangle />
                                    Needs manual entry
                                </Badge>
                            )}
                        </div>
                        <Input
                            id="recipient_email"
                            type="email"
                            disabled={locked}
                            value={edit.data.recipient_email}
                            onChange={(e) =>
                                edit.setData('recipient_email', e.target.value)
                            }
                            placeholder="recruitment@company.com"
                        />
                        <InputError message={errors.recipient_email} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="subject">Subject</Label>
                        <Input
                            id="subject"
                            disabled={locked}
                            value={edit.data.subject}
                            onChange={(e) =>
                                edit.setData('subject', e.target.value)
                            }
                        />
                        <InputError message={errors.subject} />
                    </div>

                    <div className="grid gap-2">
                        <div className="flex items-center justify-between">
                            <Label htmlFor="body">Body</Label>
                            {edit.isDirty && (
                                <span className="text-sm font-bold text-caution">
                                    Unsaved — save to recompute the hash
                                </span>
                            )}
                        </div>
                        <Textarea
                            id="body"
                            disabled={locked}
                            className="min-h-80 text-base leading-relaxed"
                            value={edit.data.body}
                            onChange={(e) =>
                                edit.setData('body', e.target.value)
                            }
                        />
                        <InputError message={errors.body} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="notes">Review notes (optional)</Label>
                        <Input
                            id="notes"
                            value={review.data.notes}
                            onChange={(e) =>
                                review.setData('notes', e.target.value)
                            }
                            placeholder="e.g. Checked salary expectations and portfolio link"
                        />
                    </div>

                    <div className="rounded-md border bg-muted/40 p-3">
                        <div className="mb-1 flex items-center gap-1.5 text-xs font-medium text-muted-foreground">
                            <Hash className="size-3" />
                            Bound content fingerprint (SHA-256)
                        </div>
                        <code className="font-mono text-sm break-all">
                            {draft.content_hash ?? 'pending'}
                        </code>
                    </div>

                    {edit.isDirty && (
                        <Button
                            variant="secondary"
                            className="w-full"
                            disabled={edit.processing}
                            onClick={save}
                        >
                            {edit.processing ? (
                                <Loader2 className="animate-spin" />
                            ) : (
                                <RefreshCw />
                            )}
                            Save & re-hash (becomes v{draft.version + 1})
                        </Button>
                    )}

                    <div className="grid gap-2 sm:grid-cols-2">
                        <Button
                            variant="outline"
                            disabled={
                                review.processing ||
                                draft.status === 'rejected' ||
                                draft.status === 'sent'
                            }
                            onClick={() => setConfirmReject(true)}
                        >
                            <XCircle />
                            Reject draft
                        </Button>
                        <Button
                            className="bg-clear font-bold text-white hover:bg-clear/90 dark:text-[#10241a]"
                            disabled={
                                review.processing || locked || edit.isDirty
                            }
                            onClick={doApprove}
                            title={
                                edit.isDirty
                                    ? 'Save your edits first'
                                    : undefined
                            }
                        >
                            {review.processing ? (
                                <Loader2 className="animate-spin" />
                            ) : locked ? (
                                <CheckCircle2 />
                            ) : (
                                <ShieldCheck />
                            )}
                            {locked ? 'Approved' : 'Approve application'}
                        </Button>
                    </div>

                    {draft.approvals.length > 0 && (
                        <div className="space-y-2 border-t pt-4">
                            <div className="flex items-center gap-1.5 text-sm font-medium">
                                <Lock className="size-3.5" />
                                Audit trail
                            </div>
                            {draft.approvals.map((a) => (
                                <div
                                    key={a.id}
                                    className="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-muted-foreground"
                                >
                                    <StatusBadge status={a.action} />
                                    <span>{formatDate(a.created_at)}</span>
                                    <code>
                                        {shortHash(a.approved_content_hash)}
                                    </code>
                                    {a.notes && <span>“{a.notes}”</span>}
                                </div>
                            ))}
                        </div>
                    )}
                </CardContent>
            </Card>

            <Card className="flex flex-col">
                <CardHeader className="flex flex-row items-start justify-between gap-4">
                    <div className="space-y-1.5">
                        <CardTitle className="flex items-center gap-2">
                            <FileText className="size-4" />
                            Tailored CV
                        </CardTitle>
                        <CardDescription>
                            Built only from your profile evidence.
                        </CardDescription>
                    </div>
                    {draft.has_cv && (
                        <Button variant="outline" size="sm" asChild>
                            <a
                                href={cv.url(draft.id, {
                                    query: { download: 1 },
                                })}
                            >
                                <Download />
                                Download {draft.cv_format?.toUpperCase()}
                            </a>
                        </Button>
                    )}
                </CardHeader>
                <CardContent className="flex flex-1 flex-col gap-3">
                    <div className="rounded-md border bg-muted/40 p-2">
                        <code className="font-mono text-sm break-all text-muted-foreground">
                            CV SHA-256: {draft.cv_file_hash ?? '—'}
                        </code>
                    </div>
                    {draft.has_cv ? (
                        <iframe
                            src={cv.url(draft.id)}
                            title="CV preview"
                            className="min-h-[560px] w-full flex-1 rounded-md border bg-white"
                        />
                    ) : (
                        <div className="flex flex-1 items-center justify-center rounded-md border border-dashed text-sm text-muted-foreground">
                            No CV generated for this draft.
                        </div>
                    )}
                </CardContent>
            </Card>

            <Dialog open={confirmReject} onOpenChange={setConfirmReject}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Reject this draft?</DialogTitle>
                        <DialogDescription>
                            The draft will be marked as rejected and recorded in
                            the audit trail. You can generate a new draft for
                            the job later.
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <DialogClose asChild>
                            <Button variant="outline">Cancel</Button>
                        </DialogClose>
                        <Button
                            variant="destructive"
                            disabled={review.processing}
                            onClick={doReject}
                        >
                            Reject draft
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </div>
    );
}

ReviewIndex.layout = {
    breadcrumbs: [{ title: 'Review & approval', href: draftsIndex() }],
};
