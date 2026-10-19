import { Deferred, Head, useForm, usePage } from '@inertiajs/react';
import { Cpu, Database, Loader2, Mail, Send, Server } from 'lucide-react';
import type { FormEvent, ReactNode } from 'react';
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
import { Skeleton } from '@/components/ui/skeleton';
import { Textarea } from '@/components/ui/textarea';
import { index as assistantIndex, test } from '@/routes/assistant';
import PageHeader from '@/components/page-header';

type Health = {
    env: string;
    llm_driver: string;
    ollama_url: string;
    ollama_model: string;
    adzuna_configured: boolean;
    send_enabled: boolean;
    mailer: string;
    queue: string;
    pdf_engine: string | null;
};

type LlmStatus = {
    status: 'ok' | 'offline';
    provider: string;
    model: string;
    base_url?: string;
    model_loaded?: boolean;
    available_models?: string[];
    error?: string;
};

type LlmTest = {
    prompt: string;
    response: string;
    model: string | null;
    fallback: boolean;
};

type Props = {
    health: Health;
    llm?: LlmStatus;
};

function Row({ label, children }: { label: string; children: ReactNode }) {
    return (
        <div className="flex items-center justify-between gap-4 border-b py-2 text-sm last:border-0">
            <span className="text-muted-foreground">{label}</span>
            <span className="text-right font-medium">{children}</span>
        </div>
    );
}

function OnOff({
    on,
    onLabel = 'Configured',
    offLabel = 'Not configured',
}: {
    on: boolean;
    onLabel?: string;
    offLabel?: string;
}) {
    return (
        <Badge
            variant="outline"
            className={on ? 'text-clear' : 'text-muted-foreground'}
        >
            {on ? onLabel : offLabel}
        </Badge>
    );
}

export default function AssistantIndex({ health, llm }: Props) {
    const page = usePage();
    const lastTest = (page.flash as { llmTest?: LlmTest } | undefined)?.llmTest;
    const form = useForm({ prompt: 'Say hello in one sentence.' });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        form.submit(test(), { preserveScroll: true, preserveState: true });
    };

    return (
        <>
            <Head title="System & local AI" />
            <div className="mx-auto flex w-full max-w-7xl flex-1 flex-col gap-6 p-4 sm:p-6">
                <PageHeader
                    title="System & local AI"
                    description="Check which model and services JobPilot is using, and test the local AI connection."
                />
                <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <Cpu className="size-4" />
                                Local LLM (Ollama)
                            </CardTitle>
                            <CardDescription>
                                When Ollama is unreachable, JobPilot falls back
                                to a built-in offline engine so every workflow
                                still runs.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <Row label="Driver">{health.llm_driver}</Row>
                            <Row label="Endpoint">
                                <code className="text-xs">
                                    {health.ollama_url}
                                </code>
                            </Row>
                            <Row label="Model">{health.ollama_model}</Row>
                            <Deferred
                                data="llm"
                                fallback={
                                    <div className="space-y-2 py-2">
                                        <Skeleton className="h-5 w-full" />
                                        <Skeleton className="h-5 w-2/3" />
                                    </div>
                                }
                            >
                                {llm && (
                                    <>
                                        <Row label="Status">
                                            <OnOff
                                                on={llm.status === 'ok'}
                                                onLabel="Online"
                                                offLabel="Offline — using fallback"
                                            />
                                        </Row>
                                        {llm.model_loaded !== undefined && (
                                            <Row label="Model pulled">
                                                <OnOff
                                                    on={llm.model_loaded}
                                                    onLabel="Yes"
                                                    offLabel={`Run: ollama pull ${llm.model}`}
                                                />
                                            </Row>
                                        )}
                                        {llm.available_models &&
                                            llm.available_models.length > 0 && (
                                                <Row label="Available models">
                                                    {llm.available_models.join(
                                                        ', ',
                                                    )}
                                                </Row>
                                            )}
                                        {llm.error && (
                                            <p className="mt-2 text-xs break-all text-muted-foreground">
                                                {llm.error}
                                            </p>
                                        )}
                                    </>
                                )}
                            </Deferred>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <Server className="size-4" />
                                Integrations
                            </CardTitle>
                            <CardDescription>
                                Configured through your <code>.env</code> file.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <Row label="Environment">{health.env}</Row>
                            <Row label="Adzuna job API">
                                <OnOff
                                    on={health.adzuna_configured}
                                    offLabel="Using built-in source"
                                />
                            </Row>
                            <Row label="CV PDF engine">
                                <OnOff
                                    on={health.pdf_engine !== null}
                                    onLabel={health.pdf_engine ?? ''}
                                    offLabel="HTML only (composer require dompdf/dompdf)"
                                />
                            </Row>
                            <Row label="Queue connection">
                                <span className="flex items-center gap-1.5">
                                    <Database className="size-3.5" />
                                    {health.queue}
                                </span>
                            </Row>
                            <Row label="Mailer">
                                <span className="flex items-center gap-1.5">
                                    <Mail className="size-3.5" />
                                    {health.mailer}
                                </span>
                            </Row>
                            <Row label="Outbound sending">
                                <OnOff
                                    on={health.send_enabled}
                                    onLabel="Enabled"
                                    offLabel="Safe mode (disabled)"
                                />
                            </Row>
                        </CardContent>
                    </Card>

                    <Card className="lg:col-span-2">
                        <CardHeader>
                            <CardTitle>Test prompt</CardTitle>
                            <CardDescription>
                                Send a prompt through the same client the agents
                                use.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <form onSubmit={submit} className="space-y-3">
                                <Textarea
                                    value={form.data.prompt}
                                    onChange={(e) =>
                                        form.setData('prompt', e.target.value)
                                    }
                                    className="min-h-20"
                                />
                                <InputError message={form.errors.prompt} />
                                <Button
                                    type="submit"
                                    disabled={form.processing}
                                >
                                    {form.processing ? (
                                        <Loader2 className="animate-spin" />
                                    ) : (
                                        <Send />
                                    )}
                                    Send prompt
                                </Button>
                            </form>
                            {lastTest && (
                                <div className="mt-4 rounded-lg border bg-muted/40 p-4">
                                    <div className="mb-2 flex items-center gap-2 text-xs text-muted-foreground">
                                        <span>
                                            {lastTest.model ?? 'unknown model'}
                                        </span>
                                        {lastTest.fallback && (
                                            <Badge variant="outline">
                                                offline fallback
                                            </Badge>
                                        )}
                                    </div>
                                    <p className="text-sm whitespace-pre-line">
                                        {lastTest.response}
                                    </p>
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </div>
            </div>
        </>
    );
}

AssistantIndex.layout = {
    breadcrumbs: [{ title: 'System & local AI', href: assistantIndex() }],
};
