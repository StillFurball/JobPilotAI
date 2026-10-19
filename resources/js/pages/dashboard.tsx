import { Head, Link, useForm, usePoll } from '@inertiajs/react';
import {
    ArrowUpRight,
    Briefcase,
    Loader2,
    MapPin,
    Play,
    Send,
    ShieldCheck,
    Sparkles,
} from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import type { FormEvent } from 'react';
import { useEffect, useState } from 'react';
import GuardrailBanner from '@/components/jobpilot/guardrail-banner';
import ScoreBadge from '@/components/jobpilot/score-badge';
import StatusBadge from '@/components/jobpilot/status-badge';
import InputError from '@/components/input-error';
import PendingInvitationsModal from '@/components/pending-invitations-modal';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { COUNTRIES, EMPLOYMENT_TYPES, employmentLabel } from '@/lib/jobpilot';
import { dashboard } from '@/routes';
import { index as draftsIndex } from '@/routes/drafts';
import { index as jobsIndex } from '@/routes/jobs';
import { store as storeDraft } from '@/routes/jobs/drafts';
import { store as storeWorkflow } from '@/routes/workflows';
import type {
    CandidateProfile,
    DashboardInvitation,
    JobSummary,
    WorkflowRun,
} from '@/types';

type Props = {
    pendingInvitations?: DashboardInvitation[];
    profile: Pick<
        CandidateProfile,
        | 'full_name'
        | 'preferred_keywords'
        | 'preferred_countries'
        | 'preferred_employment_types'
    >;
    stats: {
        matchedJobs: number;
        highMatches: number;
        pendingDrafts: number;
        sentApplications: number;
    };
    topMatches: { id: number; score: number; job: JobSummary }[];
    latestWorkflow: WorkflowRun | null;
    adzunaConfigured: boolean;
};

const ALL_TYPES = 'all';

export default function Dashboard({
    pendingInvitations = [],
    profile,
    stats,
    topMatches,
    latestWorkflow,
    adzunaConfigured,
}: Props) {
    const [showInvitations, setShowInvitations] = useState(
        pendingInvitations.length > 0,
    );

    const form = useForm({
        keywords: (profile.preferred_keywords?.length
            ? profile.preferred_keywords
            : ['php', 'laravel', 'react']
        ).join(', '),
        country: profile.preferred_countries?.[0] ?? 'id',
        employment_type: (profile.preferred_employment_types?.[0] ??
            'full_time') as string,
        max_jobs: 20,
        job_source: 'auto',
    });

    const isRunning =
        latestWorkflow?.status === 'pending' ||
        latestWorkflow?.status === 'running';

    const poll = usePoll(
        1500,
        { only: ['latestWorkflow', 'stats', 'topMatches', 'jobpilot'] },
        { autoStart: false },
    );

    useEffect(() => {
        if (isRunning) {
            poll.start();
        } else {
            poll.stop();
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [isRunning]);

    const launch = (e: FormEvent) => {
        e.preventDefault();
        form.transform((data) => ({
            keywords: data.keywords,
            countries: [data.country],
            employment_types:
                data.employment_type === ALL_TYPES
                    ? []
                    : [data.employment_type],
            max_jobs: Number(data.max_jobs),
            job_source: data.job_source,
            run_matching: true,
        }));
        form.submit(storeWorkflow(), { preserveScroll: true });
    };

    const statCards: {
        label: string;
        value: number;
        icon: LucideIcon;
        tone: string;
    }[] = [
        {
            label: 'Matched vacancies',
            value: stats.matchedJobs,
            icon: Briefcase,
            tone: 'bg-indigo-500/10 text-indigo-600 dark:text-indigo-400',
        },
        {
            label: 'High matches (≥70%)',
            value: stats.highMatches,
            icon: Sparkles,
            tone: 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400',
        },
        {
            label: 'Awaiting approval',
            value: stats.pendingDrafts,
            icon: ShieldCheck,
            tone: 'bg-amber-500/10 text-amber-600 dark:text-amber-400',
        },
        {
            label: 'Sent applications',
            value: stats.sentApplications,
            icon: Send,
            tone: 'bg-cyan-500/10 text-cyan-600 dark:text-cyan-400',
        },
    ];

    return (
        <>
            <Head title="Dashboard" />
            <PendingInvitationsModal
                invitations={pendingInvitations}
                open={pendingInvitations.length > 0 && showInvitations}
                onOpenChange={setShowInvitations}
            />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                {stats.pendingDrafts > 0 && (
                    <GuardrailBanner
                        title={`${stats.pendingDrafts} application draft(s) waiting for your approval`}
                        action={
                            <Button size="sm" asChild>
                                <Link href={draftsIndex()}>
                                    Review & approve
                                </Link>
                            </Button>
                        }
                    >
                        Tailored CVs and application emails are ready. Nothing
                        is sent until you approve the exact content.
                    </GuardrailBanner>
                )}

                <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    {statCards.map((card) => (
                        <Card key={card.label} className="py-4">
                            <CardContent className="flex items-center gap-4 px-4">
                                <div
                                    className={`flex size-11 items-center justify-center rounded-lg ${card.tone}`}
                                >
                                    <card.icon className="size-5" />
                                </div>
                                <div>
                                    <div className="text-2xl font-semibold tabular-nums">
                                        {card.value}
                                    </div>
                                    <div className="text-sm text-muted-foreground">
                                        {card.label}
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    ))}
                </div>

                <div className="grid gap-6 lg:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <Sparkles className="size-4 text-primary" />
                                Discovery & matching agent
                            </CardTitle>
                            <CardDescription>
                                Find vacancies, normalise them and score them
                                against {profile.full_name}'s profile.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <form onSubmit={launch} className="space-y-4">
                                <div className="grid gap-2">
                                    <Label htmlFor="keywords">
                                        Target keywords / roles (comma
                                        separated)
                                    </Label>
                                    <Input
                                        id="keywords"
                                        value={form.data.keywords}
                                        onChange={(e) =>
                                            form.setData(
                                                'keywords',
                                                e.target.value,
                                            )
                                        }
                                        placeholder="e.g. laravel, react, machine learning"
                                        required
                                    />
                                    <InputError
                                        message={form.errors.keywords}
                                    />
                                </div>

                                <div className="grid gap-4 sm:grid-cols-2">
                                    <div className="grid gap-2">
                                        <Label>Country</Label>
                                        <Select
                                            value={form.data.country}
                                            onValueChange={(v) =>
                                                form.setData('country', v)
                                            }
                                        >
                                            <SelectTrigger className="w-full">
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {COUNTRIES.map((c) => (
                                                    <SelectItem
                                                        key={c.value}
                                                        value={c.value}
                                                    >
                                                        {c.label}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                    </div>
                                    <div className="grid gap-2">
                                        <Label>Employment type</Label>
                                        <Select
                                            value={form.data.employment_type}
                                            onValueChange={(v) =>
                                                form.setData(
                                                    'employment_type',
                                                    v,
                                                )
                                            }
                                        >
                                            <SelectTrigger className="w-full">
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value={ALL_TYPES}>
                                                    All types
                                                </SelectItem>
                                                {EMPLOYMENT_TYPES.map((t) => (
                                                    <SelectItem
                                                        key={t.value}
                                                        value={t.value}
                                                    >
                                                        {t.label}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                    </div>
                                </div>

                                <div className="grid gap-4 sm:grid-cols-2">
                                    <div className="grid gap-2">
                                        <Label>Job source</Label>
                                        <Select
                                            value={form.data.job_source}
                                            onValueChange={(v) =>
                                                form.setData('job_source', v)
                                            }
                                        >
                                            <SelectTrigger className="w-full">
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="auto">
                                                    Auto (
                                                    {adzunaConfigured
                                                        ? 'Adzuna'
                                                        : 'built-in'}
                                                    )
                                                </SelectItem>
                                                <SelectItem value="mock">
                                                    Built-in simulator
                                                </SelectItem>
                                                <SelectItem
                                                    value="adzuna"
                                                    disabled={!adzunaConfigured}
                                                >
                                                    Adzuna live API
                                                </SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="max_jobs">
                                            Max jobs
                                        </Label>
                                        <Input
                                            id="max_jobs"
                                            type="number"
                                            min={1}
                                            max={50}
                                            value={form.data.max_jobs}
                                            onChange={(e) =>
                                                form.setData(
                                                    'max_jobs',
                                                    Number(e.target.value),
                                                )
                                            }
                                        />
                                        <InputError
                                            message={form.errors.max_jobs}
                                        />
                                    </div>
                                </div>

                                {latestWorkflow && (
                                    <div className="flex flex-wrap items-center gap-2 rounded-md border bg-muted/40 px-3 py-2 text-sm">
                                        <span className="font-medium">
                                            Run #{latestWorkflow.id}
                                        </span>
                                        <StatusBadge
                                            status={latestWorkflow.status}
                                        />
                                        <span className="text-muted-foreground">
                                            {latestWorkflow.status === 'failed'
                                                ? latestWorkflow.error_message
                                                : isRunning
                                                  ? `Stage: ${latestWorkflow.stage ?? 'queued'} — ${latestWorkflow.jobs_discovered} jobs found so far`
                                                  : `${latestWorkflow.jobs_discovered} vacancies (${latestWorkflow.jobs_new} new), ${latestWorkflow.jobs_matched} matched`}
                                        </span>
                                        {latestWorkflow.status ===
                                            'completed' &&
                                            latestWorkflow.error_message && (
                                                <span className="w-full text-amber-600 dark:text-amber-400">
                                                    {
                                                        latestWorkflow.error_message
                                                    }
                                                </span>
                                            )}
                                    </div>
                                )}

                                <Button
                                    type="submit"
                                    className="w-full"
                                    disabled={form.processing || isRunning}
                                >
                                    {form.processing || isRunning ? (
                                        <Loader2 className="animate-spin" />
                                    ) : (
                                        <Play />
                                    )}
                                    {isRunning
                                        ? 'Agent running…'
                                        : 'Launch discovery agent'}
                                </Button>
                            </form>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-start justify-between gap-4">
                            <div className="space-y-1.5">
                                <CardTitle>Top matched roles</CardTitle>
                                <CardDescription>
                                    Highest compatibility by skills, location
                                    and qualifications.
                                </CardDescription>
                            </div>
                            <Button variant="outline" size="sm" asChild>
                                <Link href={jobsIndex()}>View all</Link>
                            </Button>
                        </CardHeader>
                        <CardContent className="space-y-2">
                            {topMatches.length === 0 ? (
                                <div className="rounded-lg border border-dashed p-8 text-center text-sm text-muted-foreground">
                                    No matches yet. Run the discovery agent to
                                    evaluate vacancies.
                                </div>
                            ) : (
                                topMatches.map((match) => (
                                    <div
                                        key={match.id}
                                        className="flex items-center justify-between gap-3 rounded-lg border p-3"
                                    >
                                        <div className="min-w-0">
                                            <div className="flex items-center gap-2">
                                                <span className="truncate font-medium">
                                                    {match.job.title}
                                                </span>
                                                <ScoreBadge
                                                    score={match.score}
                                                />
                                            </div>
                                            <div className="mt-1 flex flex-wrap items-center gap-x-3 text-xs text-muted-foreground">
                                                <span>
                                                    {match.job.company ??
                                                        'Unknown'}
                                                </span>
                                                <span className="flex items-center gap-1">
                                                    <MapPin className="size-3" />
                                                    {match.job.location ??
                                                        match.job.country}
                                                </span>
                                                <span>
                                                    {employmentLabel(
                                                        match.job
                                                            .employment_type,
                                                    )}
                                                </span>
                                            </div>
                                        </div>
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            asChild
                                        >
                                            <Link
                                                href={storeDraft(match.job.id)}
                                                as="button"
                                            >
                                                Tailor & draft
                                                <ArrowUpRight />
                                            </Link>
                                        </Button>
                                    </div>
                                ))
                            )}
                        </CardContent>
                    </Card>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <ShieldCheck className="size-4 text-emerald-600" />
                            How the guardrails protect you
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="grid gap-4 md:grid-cols-3">
                        {[
                            {
                                title: 'Zero fabrication',
                                body: 'The CV and matching agents only reorganise facts from your profile. Gaps are flagged, never invented.',
                            },
                            {
                                title: 'Hash-bound approval',
                                body: 'Each draft has a SHA-256 fingerprint of recipient, subject, body and CV. Any edit invalidates prior approval.',
                            },
                            {
                                title: 'Verified recipients',
                                body: 'Emails only go to the application address published in the vacancy — addresses are never guessed.',
                            },
                        ].map((item) => (
                            <div
                                key={item.title}
                                className="rounded-lg border p-4"
                            >
                                <h3 className="text-sm font-medium">
                                    {item.title}
                                </h3>
                                <p className="mt-1 text-sm text-muted-foreground">
                                    {item.body}
                                </p>
                            </div>
                        ))}
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

Dashboard.layout = (props: { currentTeam?: { slug: string } | null }) => ({
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: props.currentTeam ? dashboard(props.currentTeam.slug) : '/',
        },
    ],
});
