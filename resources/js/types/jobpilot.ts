export type EmploymentType =
    | 'full_time'
    | 'part_time'
    | 'internship'
    | 'contract';

export type EducationEntry = {
    degree: string;
    institution: string;
    year?: string | null;
    gpa?: string | null;
};

export type ExperienceEntry = {
    title: string;
    company: string;
    location?: string | null;
    period?: string | null;
    description?: string | null;
};

export type ProjectEntry = {
    name: string;
    description?: string | null;
    tech?: string | null;
    url?: string | null;
};

export type CertificationEntry = {
    name: string;
    issuer?: string | null;
    year?: string | null;
};

export type CandidateProfile = {
    id: number;
    full_name: string;
    email: string;
    phone: string | null;
    location: string | null;
    linkedin_url: string | null;
    github_url: string | null;
    portfolio_url: string | null;
    professional_summary: string | null;
    skills: string[] | null;
    education: EducationEntry[] | null;
    experience: ExperienceEntry[] | null;
    projects: ProjectEntry[] | null;
    certifications: CertificationEntry[] | null;
    preferred_locations: string[] | null;
    preferred_countries: string[] | null;
    preferred_employment_types: EmploymentType[] | null;
    preferred_keywords: string[] | null;
    min_salary: number | null;
    max_salary: number | null;
};

export type JobMatch = {
    id: number;
    score: number;
    skill_score: number | null;
    experience_score: number | null;
    education_score: number | null;
    location_score: number | null;
    keyword_score: number | null;
    employment_type_score: number | null;
    explanation: string | null;
    missing_requirements: string[] | null;
    matched_skills: string[] | null;
    llm_analysis: string | null;
};

export type JobListing = {
    id: number;
    source: string;
    source_id: string;
    title: string;
    company: string | null;
    location: string | null;
    country: string | null;
    employment_type: string | null;
    description: string | null;
    salary_min: number | null;
    salary_max: number | null;
    salary_currency: string | null;
    application_url: string | null;
    application_email: string | null;
    posted_at: string | null;
    user_match: JobMatch | null;
};

export type JobSummary = Pick<
    JobListing,
    'id' | 'title' | 'company' | 'location' | 'country'
> & {
    employment_type?: string | null;
    application_url?: string | null;
};

export type WorkflowRun = {
    id: number;
    status: 'pending' | 'running' | 'completed' | 'failed';
    stage: string | null;
    jobs_discovered: number;
    jobs_new: number;
    jobs_matched: number;
    error_message: string | null;
    started_at: string | null;
    completed_at: string | null;
};

export type DraftStatus =
    | 'draft'
    | 'awaiting_approval'
    | 'approved'
    | 'rejected'
    | 'sent'
    | 'failed';

export type ApplicationDraft = {
    id: number;
    job_listing_id: number;
    version: number;
    recipient_email: string | null;
    recipient_verified: boolean;
    subject: string | null;
    body: string | null;
    cv_file_hash: string | null;
    content_hash: string | null;
    status: DraftStatus;
    created_at: string;
    updated_at: string;
    has_cv: boolean;
    cv_format: string | null;
    job: JobSummary | null;
};

export type ApprovalRecord = {
    id: number;
    action: 'approved' | 'rejected';
    approved_content_hash: string;
    notes: string | null;
    created_at: string;
};

export type ApplicationStatus =
    | 'approved'
    | 'sending'
    | 'sent'
    | 'failed'
    | 'uncertain'
    | 'rejected';

export type Application = {
    id: number;
    application_draft_id: number;
    job_listing_id: number;
    sent_content_hash: string | null;
    status: ApplicationStatus;
    provider_message_id: string | null;
    error_message: string | null;
    approved_at: string | null;
    sent_at: string | null;
    created_at: string;
    job_listing: JobSummary | null;
    draft: Pick<
        ApplicationDraft,
        | 'id'
        | 'version'
        | 'recipient_email'
        | 'subject'
        | 'content_hash'
        | 'status'
    > | null;
};

export type Paginated<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    links: { url: string | null; label: string; active: boolean }[];
    prev_page_url: string | null;
    next_page_url: string | null;
};
