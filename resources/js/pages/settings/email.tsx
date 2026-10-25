import { Form, Head, usePage } from '@inertiajs/react';
import { AlertTriangle, CheckCircle2, ExternalLink, Mail } from 'lucide-react';
import { useState } from 'react';
import MailAccountController from '@/actions/App/Http/Controllers/Settings/MailAccountController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { formatDate } from '@/lib/jobpilot';
import { edit } from '@/routes/mail-account';
import { send } from '@/routes/verification';

type MailProvider = {
    value: string;
    label: string;
    appPasswordUrl: string;
};

type Props = {
    emailVerified: boolean;
    justVerified: boolean;
    status?: string;
    providers: MailProvider[];
    suggestedProvider: string;
    mailAccount: { provider: string; connectedAt: string } | null;
};

export default function EmailSettings({
    emailVerified,
    justVerified,
    status,
    providers,
    suggestedProvider,
    mailAccount,
}: Props) {
    const { auth } = usePage().props;
    const [provider, setProvider] = useState(
        mailAccount?.provider ?? suggestedProvider,
    );
    const selected = providers.find((p) => p.value === provider);
    const connectedProvider = providers.find(
        (p) => p.value === mailAccount?.provider,
    );

    return (
        <>
            <Head title="Email settings" />

            <h1 className="sr-only">Email settings</h1>

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title="Sender address"
                    description="Applications are sent from your login email, so it has to be verified first"
                />

                {justVerified && emailVerified && (
                    <div className="rounded-md border border-clear/30 bg-clear-soft px-3 py-2 text-sm font-medium text-clear">
                        Email verified. Connect your mailbox below to start
                        sending applications.
                    </div>
                )}

                <div className="flex flex-wrap items-center gap-3">
                    <Mail className="size-4 text-muted-foreground" />
                    <span className="font-medium">{auth.user.email}</span>
                    {emailVerified ? (
                        <Badge
                            variant="outline"
                            className="border-clear/30 bg-clear-soft text-clear"
                        >
                            <CheckCircle2 />
                            Verified
                        </Badge>
                    ) : (
                        <Badge
                            variant="outline"
                            className="border-caution/25 bg-taxiway-soft text-caution"
                        >
                            <AlertTriangle />
                            Not verified
                        </Badge>
                    )}
                </div>

                {!emailVerified && (
                    <div className="space-y-3">
                        <p className="text-sm text-muted-foreground">
                            We emailed you a verification link. Click it to
                            confirm this address is yours. You can’t send
                            applications until you do.
                        </p>

                        <Form
                            {...send.form()}
                            options={{ preserveScroll: true }}
                        >
                            {({ processing }) => (
                                <Button
                                    variant="outline"
                                    disabled={processing}
                                    data-test="resend-verification-button"
                                >
                                    Resend verification email
                                </Button>
                            )}
                        </Form>

                        {status === 'verification-link-sent' && (
                            <p className="text-sm font-medium text-clear">
                                A new verification link has been sent to{' '}
                                {auth.user.email}.
                            </p>
                        )}
                    </div>
                )}
            </div>

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title="Mailbox"
                    description={`Connect your mailbox with an app password so applications are sent from ${auth.user.email} and show up in your Sent folder`}
                />

                {mailAccount && (
                    <div className="flex flex-col gap-3 rounded-lg border p-4 sm:flex-row sm:items-center sm:justify-between">
                        <div className="space-y-0.5">
                            <p className="font-medium">
                                {connectedProvider?.label ??
                                    mailAccount.provider}
                            </p>
                            <p className="text-sm text-muted-foreground">
                                Connected {formatDate(mailAccount.connectedAt)}
                            </p>
                        </div>

                        <Form
                            {...MailAccountController.destroy.form()}
                            options={{ preserveScroll: true }}
                        >
                            {({ processing }) => (
                                <Button
                                    variant="outline"
                                    disabled={processing}
                                    data-test="disconnect-mailbox-button"
                                >
                                    Disconnect
                                </Button>
                            )}
                        </Form>
                    </div>
                )}

                <Form
                    {...MailAccountController.update.form()}
                    options={{ preserveScroll: true }}
                    resetOnSuccess={['password']}
                    className="space-y-6"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="provider">Email provider</Label>

                                <Select
                                    name="provider"
                                    value={provider}
                                    onValueChange={setProvider}
                                >
                                    <SelectTrigger
                                        id="provider"
                                        className="w-full"
                                    >
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {providers.map((p) => (
                                            <SelectItem
                                                key={p.value}
                                                value={p.value}
                                            >
                                                {p.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>

                                <InputError message={errors.provider} />
                            </div>

                            <ol className="list-decimal space-y-1 pl-5 text-sm text-muted-foreground">
                                <li>
                                    Turn on 2-step verification for your{' '}
                                    {selected?.label ?? 'email'} account. App
                                    passwords need it.
                                </li>
                                <li>
                                    Create an app password for JobPilot
                                    {selected && (
                                        <>
                                            {' '}
                                            at{' '}
                                            <a
                                                href={selected.appPasswordUrl}
                                                target="_blank"
                                                rel="noreferrer"
                                                className="inline-flex items-center gap-1 text-foreground underline underline-offset-4"
                                            >
                                                your account settings
                                                <ExternalLink className="size-3" />
                                            </a>
                                        </>
                                    )}
                                    .
                                </li>
                                <li>
                                    Paste it below. We sign in once to check it,
                                    then store it encrypted and only use it to
                                    send applications you approve.
                                </li>
                            </ol>

                            <div className="grid gap-2">
                                <Label htmlFor="password">App password</Label>

                                <PasswordInput
                                    id="password"
                                    name="password"
                                    className="mt-1 block w-full"
                                    autoComplete="off"
                                    placeholder="abcd efgh ijkl mnop"
                                />

                                <InputError message={errors.password} />
                            </div>

                            <div className="flex items-center gap-4">
                                <Button
                                    disabled={processing}
                                    data-test="connect-mailbox-button"
                                >
                                    {processing
                                        ? 'Checking…'
                                        : mailAccount
                                          ? 'Update app password'
                                          : 'Connect mailbox'}
                                </Button>
                            </div>
                        </>
                    )}
                </Form>
            </div>
        </>
    );
}

EmailSettings.layout = {
    breadcrumbs: [
        {
            title: 'Email settings',
            href: edit(),
        },
    ],
};
