import { Head, Link, usePage } from '@inertiajs/react';
import { Check, RotateCcw } from 'lucide-react';
import { useState } from 'react';
import { flushSync } from 'react-dom';
import AppLogoIcon from '@/components/app-logo-icon';
import FlightStrip from '@/components/jobpilot/flight-strip';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import { dashboard, login, register } from '@/routes';

type DemoStrip = {
    id: number;
    score: number;
    role: string;
    company: string;
    location: string;
    type: string;
    recipient: string;
};

const WAITING: DemoStrip[] = [
    {
        id: 1,
        score: 92,
        role: 'Backend Engineer (Laravel)',
        company: 'Kopi Labs',
        location: 'Jakarta',
        type: 'Full-time',
        recipient: 'careers@kopilabs.example',
    },
    {
        id: 2,
        score: 84,
        role: 'Frontend Developer Intern',
        company: 'Lintas Pay',
        location: 'Surabaya',
        type: 'Internship',
        recipient: 'talent@lintaspay.example',
    },
    {
        id: 3,
        score: 71,
        role: 'Data Engineer',
        company: 'Nusantara Freight',
        location: 'Singapore',
        type: 'Contract',
        recipient: 'jobs@nusantarafreight.example',
    },
];

const ROUTE = [
    {
        title: 'Discover',
        body: 'Pulls vacancies from Adzuna, or from built-in sample listings when no API key is set.',
    },
    {
        title: 'Match',
        body: 'Scores every role against your skills, location, keywords and experience.',
    },
    {
        title: 'Tailor',
        body: 'Writes a CV and an application email using only facts from your profile.',
    },
    {
        title: 'You approve',
        body: 'You check the exact recipient, subject, body and CV, then approve that version.',
        hold: true,
    },
    {
        title: 'Send',
        body: 'Goes to the address published in the listing. Edit anything and the approval resets.',
    },
];

const WONT_DO = [
    {
        title: 'Invent experience',
        body: 'The CV and matching agents only rearrange facts from your profile. Gaps are flagged for you, never filled in.',
    },
    {
        title: 'Send without you',
        body: 'Every draft waits for approval. Changing a single word changes its fingerprint and cancels the earlier approval.',
    },
    {
        title: 'Guess recipients',
        body: 'Applications only go to the email address published in the vacancy. If there isn’t one, you add it yourself.',
    },
];

async function fingerprint(strip: DemoStrip): Promise<string> {
    const content = `${strip.recipient}|Application: ${strip.role}|${strip.company}`;

    if (!window.crypto?.subtle) {
        return Array.from(content)
            .reduce((h, c) => (h * 31 + c.charCodeAt(0)) >>> 0, 7)
            .toString(16)
            .padStart(12, '0');
    }

    const digest = await window.crypto.subtle.digest(
        'SHA-256',
        new TextEncoder().encode(content),
    );

    return Array.from(new Uint8Array(digest))
        .map((b) => b.toString(16).padStart(2, '0'))
        .join('');
}

function withTransition(update: () => void) {
    const reduceMotion = window.matchMedia(
        '(prefers-reduced-motion: reduce)',
    ).matches;

    if (reduceMotion || !document.startViewTransition) {
        update();

        return;
    }

    document.startViewTransition(() => flushSync(update));
}

export default function Welcome() {
    const { auth } = usePage().props;
    const dashboardUrl = dashboard();

    const [approved, setApproved] = useState<Record<number, string>>({});

    const waiting = WAITING.filter((s) => !(s.id in approved));
    const cleared = WAITING.filter((s) => s.id in approved);

    const approve = async (strip: DemoStrip) => {
        const hash = await fingerprint(strip);
        withTransition(() =>
            setApproved((current) => ({ ...current, [strip.id]: hash })),
        );
    };

    const reset = () => withTransition(() => setApproved({}));

    return (
        <>
            <Head title="Job applications you approve" />

            <div className="min-h-screen bg-concrete text-foreground">
                <header className="mx-auto flex max-w-6xl items-center justify-between gap-4 px-4 py-5 sm:px-6">
                    <Link
                        href="/"
                        className="flex items-center gap-2.5 rounded-md focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none"
                    >
                        <span className="flex size-9 items-center justify-center rounded-md bg-taxiway text-taxiway-ink">
                            <AppLogoIcon className="size-6 fill-current" />
                        </span>
                        <span className="font-display text-2xl font-bold tracking-wide">
                            JobPilot
                        </span>
                    </Link>

                    <nav className="flex items-center gap-2">
                        {auth.user ? (
                            <Button asChild>
                                <Link href={dashboardUrl}>Open dashboard</Link>
                            </Button>
                        ) : (
                            <>
                                <Button
                                    variant="ghost"
                                    asChild
                                    className="hover:bg-black/5 dark:hover:bg-white/10"
                                >
                                    <Link href={login()}>Log in</Link>
                                </Button>
                                <Button
                                    asChild
                                    className="bg-asphalt text-white hover:bg-asphalt/90 dark:bg-taxiway dark:text-taxiway-ink dark:hover:bg-taxiway/90"
                                >
                                    <Link href={register()}>
                                        Create account
                                    </Link>
                                </Button>
                            </>
                        )}
                    </nav>
                </header>

                <main>
                    <section className="mx-auto grid max-w-6xl gap-8 px-4 pt-10 pb-14 sm:px-6 lg:grid-cols-[minmax(0,1.5fr)_minmax(0,1fr)] lg:items-end lg:gap-14 lg:pt-16 lg:pb-16">
                        <h1 className="font-display text-[clamp(3.25rem,9vw,7rem)] leading-[0.88] font-bold tracking-[-0.01em] text-balance">
                            Nothing gets sent until you say so.
                        </h1>
                        <div className="max-w-[46ch] space-y-6 lg:pb-2">
                            <p className="text-lg leading-relaxed text-muted-foreground">
                                JobPilot finds vacancies, scores them against
                                your profile and drafts a tailored CV and email
                                for each one. Then it stops at the line and
                                waits for you to read, edit and approve.
                            </p>
                            <div className="flex flex-wrap gap-3">
                                {auth.user ? (
                                    <Button size="lg" asChild>
                                        <Link href={dashboardUrl}>
                                            Open dashboard
                                        </Link>
                                    </Button>
                                ) : (
                                    <>
                                        <Button
                                            size="lg"
                                            asChild
                                            className="h-12 bg-taxiway px-6 text-base font-bold text-taxiway-ink hover:bg-taxiway/90"
                                        >
                                            <Link href={register()}>
                                                Create your account
                                            </Link>
                                        </Button>
                                        <Button
                                            size="lg"
                                            variant="outline"
                                            asChild
                                            className="h-12 border-foreground/25 bg-transparent px-6 text-base"
                                        >
                                            <Link href={login()}>Log in</Link>
                                        </Button>
                                    </>
                                )}
                            </div>
                        </div>
                    </section>

                    <section
                        aria-labelledby="board-heading"
                        className="bg-asphalt py-10 text-[#e8eaec] sm:py-14"
                    >
                        <h2 id="board-heading" className="sr-only">
                            Try the approval step
                        </h2>
                        <div className="mx-auto max-w-6xl px-4 sm:px-6">
                            <div className="mb-4 flex flex-wrap items-baseline justify-between gap-2">
                                <p className="font-display text-xl font-semibold tracking-wide">
                                    Drafts waiting for you
                                    <span className="ml-2 text-[#aeb6bd] tabular-nums">
                                        {waiting.length}
                                    </span>
                                </p>
                                <p className="text-sm text-[#aeb6bd]">
                                    Try it: approve a draft to move it past the
                                    line.
                                </p>
                            </div>

                            <div className="space-y-2.5">
                                {waiting.length === 0 && (
                                    <div className="flex min-h-[4.5rem] items-center justify-between gap-4 rounded-md border border-dashed border-white/25 px-4 text-sm text-[#aeb6bd]">
                                        Every draft is approved. In the app,
                                        they now wait in your tracker until you
                                        send them.
                                        <Button
                                            size="sm"
                                            variant="ghost"
                                            onClick={reset}
                                            className="shrink-0 text-[#e8eaec] hover:bg-white/10 hover:text-white"
                                        >
                                            <RotateCcw />
                                            Reset demo
                                        </Button>
                                    </div>
                                )}
                                {waiting.map((strip) => (
                                    <FlightStrip
                                        key={strip.id}
                                        style={{
                                            viewTransitionName: `strip-${strip.id}`,
                                        }}
                                        score={strip.score}
                                        role={strip.role}
                                        company={strip.company}
                                        location={strip.location}
                                        type={strip.type}
                                        state="holding"
                                        action={
                                            <Button
                                                size="sm"
                                                onClick={() => approve(strip)}
                                                aria-label={`Approve the ${strip.role} application to ${strip.company}`}
                                                className="w-full bg-asphalt text-white hover:bg-asphalt/85 sm:w-auto dark:bg-taxiway dark:text-taxiway-ink dark:hover:bg-taxiway/90"
                                            >
                                                <Check />
                                                Approve
                                            </Button>
                                        }
                                    />
                                ))}
                            </div>
                        </div>

                        <div className="relative my-8 sm:my-10">
                            <div className="hold-line w-full" />
                            <div className="mx-auto max-w-6xl px-4 sm:px-6">
                                <p className="mt-3 inline-flex items-center gap-3 text-sm text-[#aeb6bd]">
                                    <span className="rounded-sm bg-hold px-2 py-0.5 font-display text-base font-bold tracking-wide text-white">
                                        Hold
                                    </span>
                                    Nothing crosses this line without your
                                    approval.
                                </p>
                            </div>
                        </div>

                        <div className="mx-auto max-w-6xl px-4 sm:px-6">
                            <p className="mb-4 font-display text-xl font-semibold tracking-wide">
                                Approved
                                <span className="ml-2 text-[#aeb6bd] tabular-nums">
                                    {cleared.length}
                                </span>
                            </p>
                            <div className="space-y-2.5">
                                {cleared.length === 0 && (
                                    <div className="flex min-h-[4.5rem] items-center rounded-md border border-dashed border-white/25 px-4 text-sm text-[#aeb6bd]">
                                        Approved drafts land here with the
                                        fingerprint of the exact version you
                                        signed off.
                                    </div>
                                )}
                                {cleared.map((strip) => (
                                    <FlightStrip
                                        key={strip.id}
                                        style={{
                                            viewTransitionName: `strip-${strip.id}`,
                                        }}
                                        score={strip.score}
                                        role={strip.role}
                                        company={strip.company}
                                        location={strip.location}
                                        type={strip.type}
                                        state="approved"
                                        hash={approved[strip.id]}
                                    />
                                ))}
                            </div>
                        </div>
                    </section>

                    <section
                        aria-labelledby="route-heading"
                        className="mx-auto max-w-6xl px-4 py-16 sm:px-6 lg:py-24"
                    >
                        <h2
                            id="route-heading"
                            className="max-w-[18ch] font-display text-4xl leading-none font-bold sm:text-5xl"
                        >
                            From vacancy to sent application
                        </h2>
                        <ol className="mt-10 grid gap-8 sm:grid-cols-2 lg:grid-cols-5 lg:gap-6">
                            {ROUTE.map((step, index) => (
                                <li key={step.title} className="relative">
                                    <div
                                        className="mb-4 flex h-10 items-center"
                                        aria-hidden="true"
                                    >
                                        <div
                                            className={cn(
                                                'w-full',
                                                step.hold
                                                    ? 'hold-line bg-asphalt'
                                                    : 'h-1 bg-foreground/80',
                                            )}
                                        />
                                    </div>
                                    <p className="flex items-baseline gap-2 font-display text-2xl font-bold">
                                        <span className="text-muted-foreground tabular-nums">
                                            {index + 1}
                                        </span>
                                        {step.title}
                                    </p>
                                    <p className="mt-2 max-w-[34ch] text-muted-foreground">
                                        {step.body}
                                    </p>
                                </li>
                            ))}
                        </ol>
                    </section>

                    <section
                        aria-labelledby="wont-heading"
                        className="border-y border-foreground/15 bg-background"
                    >
                        <div className="mx-auto grid max-w-6xl gap-10 px-4 py-16 sm:px-6 lg:grid-cols-[minmax(0,1fr)_minmax(0,2.2fr)] lg:py-20">
                            <h2
                                id="wont-heading"
                                className="font-display text-4xl leading-none font-bold sm:text-5xl"
                            >
                                Three things JobPilot won’t do
                            </h2>
                            <dl className="grid gap-8 sm:grid-cols-3">
                                {WONT_DO.map((item) => (
                                    <div key={item.title}>
                                        <dt className="flex items-center gap-2 text-lg font-bold">
                                            <span
                                                className="size-2.5 rounded-full bg-hold"
                                                aria-hidden="true"
                                            />
                                            {item.title}
                                        </dt>
                                        <dd className="mt-2 text-muted-foreground">
                                            {item.body}
                                        </dd>
                                    </div>
                                ))}
                            </dl>
                        </div>
                    </section>

                    <section
                        aria-labelledby="local-heading"
                        className="mx-auto grid max-w-6xl gap-10 px-4 py-16 sm:px-6 lg:grid-cols-2 lg:items-center lg:py-24"
                    >
                        <div className="max-w-[52ch]">
                            <h2
                                id="local-heading"
                                className="font-display text-4xl leading-none font-bold sm:text-5xl"
                            >
                                Your profile stays on your machine
                            </h2>
                            <p className="mt-5 text-lg leading-relaxed text-muted-foreground">
                                Matching and drafting run on a local model
                                through Ollama. No model installed? JobPilot
                                switches to its built-in offline engine, so
                                everything still works.
                            </p>
                        </div>
                        <figure className="overflow-hidden rounded-md bg-asphalt text-[#e8eaec] ring-1 ring-black/20">
                            <figcaption className="border-b border-white/10 px-4 py-2.5 text-sm text-[#aeb6bd]">
                                Turn on local AI
                            </figcaption>
                            <pre className="overflow-x-auto p-4 font-mono text-sm leading-relaxed">
                                <code>
                                    <span className="text-[#aeb6bd]">$ </span>
                                    ollama pull qwen3:8b
                                    {'\n\n'}
                                    <span className="text-taxiway">
                                        JOBPILOT_LLM_DRIVER
                                    </span>
                                    =ollama{'\n'}
                                    <span className="text-taxiway">
                                        OLLAMA_MODEL
                                    </span>
                                    =qwen3:8b
                                </code>
                            </pre>
                        </figure>
                    </section>

                    <section className="bg-taxiway text-taxiway-ink">
                        <div className="mx-auto flex max-w-6xl flex-col gap-6 px-4 py-14 sm:px-6 md:flex-row md:items-center md:justify-between">
                            <div>
                                <h2 className="font-display text-4xl leading-none font-bold sm:text-5xl">
                                    Set up your profile once.
                                </h2>
                                <p className="mt-3 max-w-[50ch] text-lg">
                                    Every CV and email JobPilot drafts is built
                                    from it, and every one waits for you.
                                </p>
                            </div>
                            <Button
                                size="lg"
                                asChild
                                className="h-12 shrink-0 bg-asphalt px-6 text-base font-bold text-white hover:bg-asphalt/90"
                            >
                                <Link
                                    href={auth.user ? dashboardUrl : register()}
                                >
                                    {auth.user
                                        ? 'Open dashboard'
                                        : 'Create your account'}
                                </Link>
                            </Button>
                        </div>
                    </section>
                </main>

                <footer className="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-3 px-4 py-8 text-sm text-muted-foreground sm:px-6">
                    <span>JobPilot AI</span>
                    <span>Built with Laravel, Inertia and React.</span>
                </footer>
            </div>
        </>
    );
}
