import { Link } from '@inertiajs/react';
import AppLogoIcon from '@/components/app-logo-icon';
import FlightStrip from '@/components/jobpilot/flight-strip';
import { home } from '@/routes';
import type { AuthLayoutProps } from '@/types';

function Wordmark({ className = '' }: { className?: string }) {
    return (
        <Link
            href={home()}
            className={`inline-flex items-center gap-2.5 rounded-md focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none ${className}`}
        >
            <span className="flex size-9 items-center justify-center rounded-md bg-taxiway text-taxiway-ink">
                <AppLogoIcon className="size-6 fill-current" />
            </span>
            <span className="font-display text-2xl font-bold tracking-wide">
                JobPilot
            </span>
        </Link>
    );
}

export default function AuthSplitLayout({
    children,
    title,
    description,
}: AuthLayoutProps) {
    return (
        <div className="grid min-h-svh lg:grid-cols-[minmax(0,1fr)_minmax(0,1.05fr)]">
            <aside className="relative hidden flex-col justify-between overflow-hidden bg-asphalt p-10 text-[#e8eaec] lg:flex xl:p-14">
                <Wordmark />

                <div className="space-y-6">
                    <p className="max-w-[16ch] font-display text-5xl leading-[0.95] font-bold xl:text-6xl">
                        Every application waits for you.
                    </p>
                    <div aria-hidden="true" className="space-y-5">
                        <FlightStrip
                            score={92}
                            role="Backend Engineer (Laravel)"
                            company="Kopi Labs"
                            location="Jakarta"
                            type="Full-time"
                            state="holding"
                        />
                        <div className="-mx-10 hold-line xl:-mx-14" />
                        <FlightStrip
                            score={84}
                            role="Frontend Developer Intern"
                            company="Lintas Pay"
                            location="Surabaya"
                            type="Internship"
                            state="approved"
                            hash="a3f91c07de4b82659f0e"
                        />
                    </div>
                </div>

                <p className="max-w-[48ch] text-sm text-[#aeb6bd]">
                    JobPilot drafts tailored CVs and emails from your profile.
                    You approve the exact version before anything is sent.
                </p>
            </aside>

            <main className="flex flex-col bg-background px-5 py-8 sm:px-10">
                <Wordmark className="lg:hidden" />

                <div className="flex flex-1 items-center justify-center py-10">
                    <div className="w-full max-w-sm">
                        <div className="mb-8 space-y-2">
                            <h1 className="font-display text-4xl leading-none font-bold">
                                {title}
                            </h1>
                            {description && (
                                <p className="text-muted-foreground">
                                    {description}
                                </p>
                            )}
                        </div>
                        {children}
                    </div>
                </div>
            </main>
        </div>
    );
}
