export const COUNTRIES: { value: string; label: string }[] = [
    { value: 'id', label: 'Indonesia (ID)' },
    { value: 'sg', label: 'Singapore (SG)' },
    { value: 'my', label: 'Malaysia (MY)' },
    { value: 'us', label: 'United States (US)' },
    { value: 'gb', label: 'United Kingdom (GB)' },
];

export const EMPLOYMENT_TYPES: { value: string; label: string }[] = [
    { value: 'full_time', label: 'Full-time' },
    { value: 'internship', label: 'Internship (Magang)' },
    { value: 'contract', label: 'Contract' },
    { value: 'part_time', label: 'Part-time' },
];

export function percent(value: number | null | undefined): string {
    return `${Math.round((value ?? 0) * 100)}%`;
}

export function employmentLabel(value: string | null | undefined): string {
    if (!value) {
        return 'Full-time';
    }

    return (
        EMPLOYMENT_TYPES.find((type) => type.value === value)?.label ??
        value.replace(/_/g, ' ')
    );
}

export function salaryRange(
    min: number | null,
    max: number | null,
    currency: string | null,
): string | null {
    if (!min && !max) {
        return null;
    }

    const fmt = (n: number) =>
        n >= 1_000_000
            ? `${(n / 1_000_000).toFixed(1).replace(/\.0$/, '')}M`
            : n.toLocaleString();

    return [
        currency,
        [min, max]
            .filter(Boolean)
            .map((n) => fmt(n!))
            .join(' – '),
    ]
        .filter(Boolean)
        .join(' ');
}

export function shortHash(
    hash: string | null | undefined,
    length = 16,
): string {
    return hash ? `${hash.slice(0, length)}…` : '—';
}

export function formatDate(value: string | null | undefined): string {
    if (!value) {
        return '—';
    }

    return new Date(value).toLocaleString(undefined, {
        dateStyle: 'medium',
        timeStyle: 'short',
    });
}
