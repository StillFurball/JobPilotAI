import { Head, Link, router } from '@inertiajs/react';
import {
    AlertTriangle,
    Building2,
    CheckCircle2,
    ChevronDown,
    ChevronUp,
    FileText,
    Loader2,
    MapPin,
    Search,
    Sparkles,
    Wallet,
} from 'lucide-react';
import { useState } from 'react';
import ScoreBadge from '@/components/jobpilot/score-badge';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    COUNTRIES,
    EMPLOYMENT_TYPES,
    employmentLabel,
    percent,
    salaryRange,
} from '@/lib/jobpilot';
import { index as jobsIndex } from '@/routes/jobs';
import { store as storeDraft } from '@/routes/jobs/drafts';
import type { JobListing, Paginated } from '@/types';

type Filters = {
    search: string;
    country: string;
    employment_type: string;
    min_score: string;
};

type Props = {
    jobs: Paginated<JobListing>;
    filters: Filters;
    candidateName: string;
};

const ANY = 'any';

export default function JobsIndex({ jobs, filters, candidateName }: Props) {
    const [search, setSearch] = useState(filters.search);
    const [expanded, setExpanded] = useState<number | null>(null);
    const [generating, setGenerating] = useState<number | null>(null);

    const applyFilters = (next: Partial<Filters>) => {
        const merged = { ...filters, search, ...next };
        router.get(
            jobsIndex.url({
                query: Object.fromEntries(
                    Object.entries(merged).filter(
                        ([, v]) => v !== '' && v !== ANY && v !== '0',
                    ),
                ),
            }),
            {},
            { preserveState: true, preserveScroll: true, replace: true },
        );
    };

    const generateDraft = (job: JobListing) => {
        router.visit(storeDraft(job.id), {
            onStart: () => setGenerating(job.id),
            onFinish: () => setGenerating(null),
        });
    };

    return (
        <>
            <Head title="Job discovery" />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <Card className="py-4">
                    <CardContent className="space-y-3 px-4">
                        <div className="grid gap-3 md:grid-cols-[1.5fr_1fr_1fr_1fr]">
                            <form
                                className="relative"
                                onSubmit={(e) => {
                                    e.preventDefault();
                                    applyFilters({});
                                }}
                            >
                                <Search className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                                <Input
                                    className="pl-9"
                                    placeholder="Search title, stack or company… (Enter)"
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                />
                            </form>
                            <Select
                                value={filters.country || ANY}
                                onValueChange={(v) =>
                                    applyFilters({ country: v })
                                }
                            >
                                <SelectTrigger className="w-full">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value={ANY}>
                                        All countries
                                    </SelectItem>
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
                            <Select
                                value={filters.employment_type || ANY}
                                onValueChange={(v) =>
                                    applyFilters({ employment_type: v })
                                }
                            >
                                <SelectTrigger className="w-full">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value={ANY}>
                                        All job types
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
                            <Select
                                value={filters.min_score || '0'}
                                onValueChange={(v) =>
                                    applyFilters({ min_score: v })
                                }
                            >
                                <SelectTrigger className="w-full">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="0">
                                        Any match score
                                    </SelectItem>
                                    <SelectItem value="0.5">
                                        Score ≥ 50%
                                    </SelectItem>
                                    <SelectItem value="0.7">
                                        Score ≥ 70% (high)
                                    </SelectItem>
                                    <SelectItem value="0.85">
                                        Score ≥ 85% (top)
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <div className="flex flex-wrap justify-between gap-2 text-sm text-muted-foreground">
                            <span>
                                Showing {jobs.from ?? 0}–{jobs.to ?? 0} of{' '}
                                {jobs.total} vacancies
                            </span>
                            <span>
                                Match evaluation for{' '}
                                <span className="font-medium text-foreground">
                                    {candidateName}
                                </span>
                            </span>
                        </div>
                    </CardContent>
                </Card>

                {jobs.data.length === 0 ? (
                    <Card>
                        <CardContent className="py-12 text-center text-muted-foreground">
                            <Search className="mx-auto mb-3 size-8 opacity-40" />
                            <p className="font-medium text-foreground">
                                No vacancies match these filters
                            </p>
                            <p className="mt-1 text-sm">
                                Clear the filters or launch the discovery agent
                                from the dashboard.
                            </p>
                        </CardContent>
                    </Card>
                ) : (
                    jobs.data.map((job) => {
                        const match = job.user_match;
                        const isOpen = expanded === job.id;
                        const salary = salaryRange(
                            job.salary_min,
                            job.salary_max,
                            job.salary_currency,
                        );

                        return (
                            <Card key={job.id} className="gap-0 py-4">
                                <CardContent className="space-y-3 px-4">
                                    <div className="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                                        <div className="min-w-0 space-y-2">
                                            <div className="flex flex-wrap items-center gap-2">
                                                <h3 className="font-semibold">
                                                    {job.title}
                                                </h3>
                                                {match && (
                                                    <ScoreBadge
                                                        score={match.score}
                                                        label="compatibility"
                                                    />
                                                )}
                                                <Badge variant="secondary">
                                                    {employmentLabel(
                                                        job.employment_type,
                                                    )}
                                                </Badge>
                                            </div>
                                            <div className="flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-muted-foreground">
                                                <span className="flex items-center gap-1 text-foreground">
                                                    <Building2 className="size-3.5" />
                                                    {job.company ??
                                                        'Confidential'}
                                                </span>
                                                <span className="flex items-center gap-1">
                                                    <MapPin className="size-3.5" />
                                                    {job.location ??
                                                        'Unspecified'}{' '}
                                                    ({job.country ?? '—'})
                                                </span>
                                                {salary && (
                                                    <span className="flex items-center gap-1 text-emerald-600 dark:text-emerald-400">
                                                        <Wallet className="size-3.5" />
                                                        {salary}
                                                    </span>
                                                )}
                                                <span className="uppercase">
                                                    {job.source}
                                                </span>
                                            </div>
                                        </div>
                                        <div className="flex shrink-0 gap-2">
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                onClick={() =>
                                                    setExpanded(
                                                        isOpen ? null : job.id,
                                                    )
                                                }
                                            >
                                                {isOpen
                                                    ? 'Hide details'
                                                    : 'Match analysis'}
                                                {isOpen ? (
                                                    <ChevronUp />
                                                ) : (
                                                    <ChevronDown />
                                                )}
                                            </Button>
                                            <Button
                                                size="sm"
                                                disabled={generating !== null}
                                                onClick={() =>
                                                    generateDraft(job)
                                                }
                                            >
                                                {generating === job.id ? (
                                                    <Loader2 className="animate-spin" />
                                                ) : (
                                                    <FileText />
                                                )}
                                                {generating === job.id
                                                    ? 'Tailoring…'
                                                    : 'Tailor CV & draft email'}
                                            </Button>
                                        </div>
                                    </div>

                                    {!isOpen && (
                                        <p className="line-clamp-2 text-sm text-muted-foreground">
                                            {job.description}
                                        </p>
                                    )}

                                    {isOpen && (
                                        <div className="space-y-4 border-t pt-4">
                                            {match ? (
                                                <>
                                                    <div className="grid grid-cols-2 gap-3 sm:grid-cols-4">
                                                        {[
                                                            [
                                                                'Skills',
                                                                match.skill_score,
                                                            ],
                                                            [
                                                                'Location',
                                                                match.location_score,
                                                            ],
                                                            [
                                                                'Keywords',
                                                                match.keyword_score,
                                                            ],
                                                            [
                                                                'Experience',
                                                                match.experience_score,
                                                            ],
                                                        ].map(
                                                            ([
                                                                label,
                                                                value,
                                                            ]) => (
                                                                <div
                                                                    key={
                                                                        label as string
                                                                    }
                                                                    className="rounded-lg border p-3"
                                                                >
                                                                    <div className="text-xs text-muted-foreground">
                                                                        {label}
                                                                    </div>
                                                                    <div className="text-lg font-semibold tabular-nums">
                                                                        {percent(
                                                                            value as
                                                                                | number
                                                                                | null,
                                                                        )}
                                                                    </div>
                                                                </div>
                                                            ),
                                                        )}
                                                    </div>

                                                    <div className="grid gap-4 md:grid-cols-2">
                                                        <div>
                                                            <div className="mb-2 flex items-center gap-1.5 text-sm font-medium text-emerald-600 dark:text-emerald-400">
                                                                <CheckCircle2 className="size-4" />
                                                                Skills verified
                                                                in your profile
                                                            </div>
                                                            <div className="flex flex-wrap gap-1.5">
                                                                {match
                                                                    .matched_skills
                                                                    ?.length ? (
                                                                    match.matched_skills.map(
                                                                        (s) => (
                                                                            <Badge
                                                                                key={
                                                                                    s
                                                                                }
                                                                                variant="outline"
                                                                            >
                                                                                {
                                                                                    s
                                                                                }
                                                                            </Badge>
                                                                        ),
                                                                    )
                                                                ) : (
                                                                    <span className="text-sm text-muted-foreground">
                                                                        No
                                                                        direct
                                                                        skill
                                                                        overlap
                                                                        detected
                                                                    </span>
                                                                )}
                                                            </div>
                                                        </div>
                                                        <div>
                                                            <div className="mb-2 flex items-center gap-1.5 text-sm font-medium text-amber-600 dark:text-amber-400">
                                                                <AlertTriangle className="size-4" />
                                                                Gaps to address
                                                            </div>
                                                            <div className="flex flex-wrap gap-1.5">
                                                                {match
                                                                    .missing_requirements
                                                                    ?.length ? (
                                                                    match.missing_requirements.map(
                                                                        (r) => (
                                                                            <Badge
                                                                                key={
                                                                                    r
                                                                                }
                                                                                variant="outline"
                                                                            >
                                                                                {
                                                                                    r
                                                                                }
                                                                            </Badge>
                                                                        ),
                                                                    )
                                                                ) : (
                                                                    <span className="text-sm text-muted-foreground">
                                                                        No gaps
                                                                        flagged
                                                                    </span>
                                                                )}
                                                            </div>
                                                        </div>
                                                    </div>

                                                    {match.llm_analysis && (
                                                        <div className="rounded-lg border border-primary/20 bg-primary/5 p-3">
                                                            <div className="mb-1 flex items-center gap-1.5 text-xs font-semibold tracking-wide uppercase">
                                                                <Sparkles className="size-3" />
                                                                Local AI career
                                                                advisor
                                                            </div>
                                                            <p className="text-sm">
                                                                {
                                                                    match.llm_analysis
                                                                }
                                                            </p>
                                                        </div>
                                                    )}
                                                </>
                                            ) : (
                                                <p className="text-sm text-muted-foreground">
                                                    Not scored for your profile
                                                    yet — run the discovery
                                                    agent to compute a match.
                                                </p>
                                            )}

                                            <div>
                                                <div className="mb-1 text-sm font-medium">
                                                    Full description
                                                </div>
                                                <div className="max-h-60 overflow-y-auto rounded-lg border bg-muted/40 p-3 text-sm whitespace-pre-line text-muted-foreground">
                                                    {job.description}
                                                </div>
                                                {job.application_url && (
                                                    <a
                                                        href={
                                                            job.application_url
                                                        }
                                                        target="_blank"
                                                        rel="noreferrer"
                                                        className="mt-2 inline-block text-sm text-primary underline-offset-4 hover:underline"
                                                    >
                                                        Open original listing
                                                    </a>
                                                )}
                                            </div>
                                        </div>
                                    )}
                                </CardContent>
                            </Card>
                        );
                    })
                )}

                {jobs.last_page > 1 && (
                    <div className="flex items-center justify-center gap-2">
                        <Button
                            variant="outline"
                            size="sm"
                            disabled={!jobs.prev_page_url}
                            asChild={!!jobs.prev_page_url}
                        >
                            {jobs.prev_page_url ? (
                                <Link href={jobs.prev_page_url} preserveScroll>
                                    Previous
                                </Link>
                            ) : (
                                <span>Previous</span>
                            )}
                        </Button>
                        <span className="text-sm text-muted-foreground">
                            Page {jobs.current_page} of {jobs.last_page}
                        </span>
                        <Button
                            variant="outline"
                            size="sm"
                            disabled={!jobs.next_page_url}
                            asChild={!!jobs.next_page_url}
                        >
                            {jobs.next_page_url ? (
                                <Link href={jobs.next_page_url} preserveScroll>
                                    Next
                                </Link>
                            ) : (
                                <span>Next</span>
                            )}
                        </Button>
                    </div>
                )}
            </div>
        </>
    );
}

JobsIndex.layout = {
    breadcrumbs: [{ title: 'Job discovery', href: jobsIndex() }],
};
