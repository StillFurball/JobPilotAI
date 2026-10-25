import { Form, Head } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { logout } from '@/routes';
import { send } from '@/routes/verification';

export default function VerifyEmail({ status }: { status?: string }) {
    return (
        <>
            <Head title="Verify email" />

            {status === 'verification-link-sent' && (
                <div className="mb-6 rounded-md border border-clear/30 bg-clear-soft px-3 py-2 text-sm font-medium text-clear">
                    A new verification link has been sent to your email address.
                </div>
            )}

            <div className="space-y-6 text-center">
                <Form {...send.form()}>
                    {({ processing }) => (
                        <Button
                            size="lg"
                            className="w-full"
                            disabled={processing}
                            data-test="resend-verification-button"
                        >
                            {processing && (
                                <LoaderCircle className="h-4 w-4 animate-spin" />
                            )}
                            Resend verification email
                        </Button>
                    )}
                </Form>

                <TextLink
                    href={logout()}
                    as="button"
                    className="mx-auto block text-sm"
                >
                    Log out
                </TextLink>
            </div>
        </>
    );
}

VerifyEmail.layout = {
    title: 'Verify your email',
    description:
        'Applications are sent from your login email, so we need to confirm it’s yours. Click the link we just emailed you.',
};
