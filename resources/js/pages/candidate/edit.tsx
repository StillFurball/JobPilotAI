import { Head, useForm } from '@inertiajs/react';
import { Plus, Save, Trash2, UserRound } from 'lucide-react';
import type { FormEvent, ReactNode } from 'react';
import InputError from '@/components/input-error';
import TagInput from '@/components/jobpilot/tag-input';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { COUNTRIES, EMPLOYMENT_TYPES } from '@/lib/jobpilot';
import { edit, update } from '@/routes/candidate';
import type {
    CandidateProfile,
    CertificationEntry,
    EducationEntry,
    ExperienceEntry,
    ProjectEntry,
} from '@/types';

type Props = { profile: CandidateProfile };

type FieldDef<T> = {
    key: keyof T & string;
    label: string;
    placeholder?: string;
    textarea?: boolean;
    wide?: boolean;
};

export default function CandidateEdit({ profile }: Props) {
    const form = useForm({
        full_name: profile.full_name ?? '',
        email: profile.email ?? '',
        phone: profile.phone ?? '',
        location: profile.location ?? '',
        linkedin_url: profile.linkedin_url ?? '',
        github_url: profile.github_url ?? '',
        portfolio_url: profile.portfolio_url ?? '',
        professional_summary: profile.professional_summary ?? '',
        skills: profile.skills ?? [],
        education: profile.education ?? [],
        experience: profile.experience ?? [],
        projects: profile.projects ?? [],
        certifications: profile.certifications ?? [],
        preferred_locations: profile.preferred_locations ?? [],
        preferred_countries: profile.preferred_countries ?? [],
        preferred_employment_types: (profile.preferred_employment_types ??
            []) as string[],
        preferred_keywords: profile.preferred_keywords ?? [],
        min_salary: profile.min_salary ?? '',
        max_salary: profile.max_salary ?? '',
    });

    const errors = form.errors as Record<string, string | undefined>;

    const submit = (e: FormEvent) => {
        e.preventDefault();
        form.transform((data) => ({
            ...data,
            min_salary: data.min_salary === '' ? null : Number(data.min_salary),
            max_salary: data.max_salary === '' ? null : Number(data.max_salary),
        }));
        form.submit(update(), { preserveScroll: true });
    };

    const toggle = (
        key: 'preferred_countries' | 'preferred_employment_types',
        value: string,
    ) => {
        const list = form.data[key];
        form.setData(
            key,
            list.includes(value)
                ? list.filter((v) => v !== value)
                : [...list, value],
        );
    };

    const text = (
        key:
            | 'full_name'
            | 'email'
            | 'phone'
            | 'location'
            | 'linkedin_url'
            | 'github_url'
            | 'portfolio_url',
        label: string,
        type = 'text',
    ) => (
        <div className="grid gap-2">
            <Label htmlFor={key}>{label}</Label>
            <Input
                id={key}
                type={type}
                value={form.data[key]}
                onChange={(e) => form.setData(key, e.target.value)}
            />
            <InputError message={errors[key]} />
        </div>
    );

    return (
        <>
            <Head title="Candidate profile" />
            <form
                onSubmit={submit}
                className="flex h-full flex-1 flex-col gap-4 p-4"
            >
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h1 className="flex items-center gap-2 text-xl font-semibold">
                            <UserRound className="size-5" />
                            Candidate profile & evidence
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            The agents only use what is written here. Nothing is
                            invented for your CV or emails.
                        </p>
                    </div>
                    <Button type="submit" disabled={form.processing}>
                        <Save />
                        Save profile
                    </Button>
                </div>

                <div className="grid gap-4 xl:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <CardTitle>Contact & summary</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="grid gap-4 sm:grid-cols-2">
                                {text('full_name', 'Full name')}
                                {text('email', 'Email', 'email')}
                                {text('phone', 'Phone / WhatsApp')}
                                {text('location', 'Location / city')}
                                {text('linkedin_url', 'LinkedIn URL', 'url')}
                                {text('github_url', 'GitHub URL', 'url')}
                                {text('portfolio_url', 'Portfolio URL', 'url')}
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="professional_summary">
                                    Professional summary
                                </Label>
                                <Textarea
                                    id="professional_summary"
                                    className="min-h-28"
                                    value={form.data.professional_summary}
                                    onChange={(e) =>
                                        form.setData(
                                            'professional_summary',
                                            e.target.value,
                                        )
                                    }
                                />
                                <InputError
                                    message={errors.professional_summary}
                                />
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Skills & job preferences</CardTitle>
                            <CardDescription>
                                Used by the matching agent to score vacancies.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-5">
                            <div className="grid gap-2">
                                <Label htmlFor="skills">Skills</Label>
                                <TagInput
                                    id="skills"
                                    value={form.data.skills}
                                    onChange={(v) => form.setData('skills', v)}
                                    placeholder="e.g. Laravel — press Enter"
                                />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="keywords">
                                    Preferred keywords
                                </Label>
                                <TagInput
                                    id="keywords"
                                    lowercase
                                    value={form.data.preferred_keywords}
                                    onChange={(v) =>
                                        form.setData('preferred_keywords', v)
                                    }
                                    placeholder="e.g. backend engineer"
                                />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="locations">
                                    Preferred locations
                                </Label>
                                <TagInput
                                    id="locations"
                                    value={form.data.preferred_locations}
                                    onChange={(v) =>
                                        form.setData('preferred_locations', v)
                                    }
                                    placeholder="e.g. Surabaya, Remote"
                                />
                            </div>
                            <div className="grid gap-2">
                                <Label>Preferred countries</Label>
                                <div className="flex flex-wrap gap-4">
                                    {COUNTRIES.map((c) => (
                                        <label
                                            key={c.value}
                                            className="flex items-center gap-2 text-sm"
                                        >
                                            <Checkbox
                                                checked={form.data.preferred_countries.includes(
                                                    c.value,
                                                )}
                                                onCheckedChange={() =>
                                                    toggle(
                                                        'preferred_countries',
                                                        c.value,
                                                    )
                                                }
                                            />
                                            {c.label}
                                        </label>
                                    ))}
                                </div>
                            </div>
                            <div className="grid gap-2">
                                <Label>Employment types</Label>
                                <div className="flex flex-wrap gap-4">
                                    {EMPLOYMENT_TYPES.map((t) => (
                                        <label
                                            key={t.value}
                                            className="flex items-center gap-2 text-sm"
                                        >
                                            <Checkbox
                                                checked={form.data.preferred_employment_types.includes(
                                                    t.value,
                                                )}
                                                onCheckedChange={() =>
                                                    toggle(
                                                        'preferred_employment_types',
                                                        t.value,
                                                    )
                                                }
                                            />
                                            {t.label}
                                        </label>
                                    ))}
                                </div>
                            </div>
                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor="min_salary">
                                        Min monthly salary (IDR)
                                    </Label>
                                    <Input
                                        id="min_salary"
                                        type="number"
                                        min={0}
                                        value={form.data.min_salary}
                                        onChange={(e) =>
                                            form.setData(
                                                'min_salary',
                                                e.target.value,
                                            )
                                        }
                                    />
                                    <InputError message={errors.min_salary} />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="max_salary">
                                        Max monthly salary (IDR)
                                    </Label>
                                    <Input
                                        id="max_salary"
                                        type="number"
                                        min={0}
                                        value={form.data.max_salary}
                                        onChange={(e) =>
                                            form.setData(
                                                'max_salary',
                                                e.target.value,
                                            )
                                        }
                                    />
                                    <InputError message={errors.max_salary} />
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <EntryList<ExperienceEntry>
                    title="Work experience"
                    items={form.data.experience}
                    onChange={(v) => form.setData('experience', v)}
                    blank={{
                        title: '',
                        company: '',
                        location: '',
                        period: '',
                        description: '',
                    }}
                    errorPrefix="experience"
                    errors={errors}
                    fields={[
                        { key: 'title', label: 'Role / title' },
                        { key: 'company', label: 'Company' },
                        { key: 'location', label: 'Location' },
                        {
                            key: 'period',
                            label: 'Period',
                            placeholder: '2023 - Present',
                        },
                        {
                            key: 'description',
                            label: 'What you did',
                            textarea: true,
                            wide: true,
                        },
                    ]}
                />

                <EntryList<EducationEntry>
                    title="Education"
                    items={form.data.education}
                    onChange={(v) => form.setData('education', v)}
                    blank={{ degree: '', institution: '', year: '', gpa: '' }}
                    errorPrefix="education"
                    errors={errors}
                    fields={[
                        {
                            key: 'degree',
                            label: 'Degree',
                            placeholder: 'S.Kom Informatics',
                        },
                        { key: 'institution', label: 'Institution' },
                        {
                            key: 'year',
                            label: 'Years',
                            placeholder: '2023 - 2027',
                        },
                        {
                            key: 'gpa',
                            label: 'GPA',
                            placeholder: '3.80 / 4.00',
                        },
                    ]}
                />

                <EntryList<ProjectEntry>
                    title="Projects"
                    items={form.data.projects}
                    onChange={(v) => form.setData('projects', v)}
                    blank={{ name: '', tech: '', url: '', description: '' }}
                    errorPrefix="projects"
                    errors={errors}
                    fields={[
                        { key: 'name', label: 'Name' },
                        { key: 'tech', label: 'Tech stack' },
                        { key: 'url', label: 'URL' },
                        {
                            key: 'description',
                            label: 'Description',
                            textarea: true,
                            wide: true,
                        },
                    ]}
                />

                <EntryList<CertificationEntry>
                    title="Certifications"
                    items={form.data.certifications}
                    onChange={(v) => form.setData('certifications', v)}
                    blank={{ name: '', issuer: '', year: '' }}
                    errorPrefix="certifications"
                    errors={errors}
                    fields={[
                        { key: 'name', label: 'Certificate' },
                        { key: 'issuer', label: 'Issuer' },
                        { key: 'year', label: 'Year' },
                    ]}
                />

                <div className="flex justify-end">
                    <Button type="submit" disabled={form.processing}>
                        <Save />
                        Save profile
                    </Button>
                </div>
            </form>
        </>
    );
}

function EntryList<T extends Record<string, unknown>>({
    title,
    items,
    onChange,
    blank,
    fields,
    errorPrefix,
    errors,
}: {
    title: string;
    items: T[];
    onChange: (items: T[]) => void;
    blank: T;
    fields: FieldDef<T>[];
    errorPrefix: string;
    errors: Record<string, string | undefined>;
}): ReactNode {
    const set = (index: number, key: string, value: string) =>
        onChange(
            items.map((item, i) =>
                i === index ? { ...item, [key]: value } : item,
            ),
        );

    return (
        <Card>
            <CardHeader className="flex flex-row items-center justify-between">
                <CardTitle>{title}</CardTitle>
                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    onClick={() => onChange([...items, { ...blank }])}
                >
                    <Plus />
                    Add
                </Button>
            </CardHeader>
            <CardContent className="space-y-4">
                {items.length === 0 && (
                    <p className="text-sm text-muted-foreground">
                        Nothing added yet.
                    </p>
                )}
                {items.map((item, index) => (
                    <div
                        key={index}
                        className="relative rounded-lg border p-4 pr-12"
                    >
                        <Button
                            type="button"
                            variant="ghost"
                            size="icon"
                            className="absolute top-2 right-2"
                            onClick={() =>
                                onChange(items.filter((_, i) => i !== index))
                            }
                            aria-label={`Remove ${title} entry`}
                        >
                            <Trash2 />
                        </Button>
                        <div className="grid gap-3 sm:grid-cols-2">
                            {fields.map((field) => {
                                const id = `${errorPrefix}-${index}-${field.key}`;
                                const value =
                                    (item[field.key] as
                                        | string
                                        | null
                                        | undefined) ?? '';

                                return (
                                    <div
                                        key={field.key}
                                        className={
                                            field.wide
                                                ? 'grid gap-1.5 sm:col-span-2'
                                                : 'grid gap-1.5'
                                        }
                                    >
                                        <Label htmlFor={id} className="text-xs">
                                            {field.label}
                                        </Label>
                                        {field.textarea ? (
                                            <Textarea
                                                id={id}
                                                value={value}
                                                placeholder={field.placeholder}
                                                onChange={(e) =>
                                                    set(
                                                        index,
                                                        field.key,
                                                        e.target.value,
                                                    )
                                                }
                                            />
                                        ) : (
                                            <Input
                                                id={id}
                                                value={value}
                                                placeholder={field.placeholder}
                                                onChange={(e) =>
                                                    set(
                                                        index,
                                                        field.key,
                                                        e.target.value,
                                                    )
                                                }
                                            />
                                        )}
                                        <InputError
                                            message={
                                                errors[
                                                    `${errorPrefix}.${index}.${field.key}`
                                                ]
                                            }
                                        />
                                    </div>
                                );
                            })}
                        </div>
                    </div>
                ))}
            </CardContent>
        </Card>
    );
}

CandidateEdit.layout = {
    breadcrumbs: [{ title: 'Candidate profile', href: edit() }],
};
