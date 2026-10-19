{{-- Tailored CV — rendered to PDF by dompdf (keep CSS simple: no flexbox/grid). --}}
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>{{ $candidate['full_name'] }} — CV</title>
<style>
  @page { margin: 32px 40px; }
  * { margin: 0; padding: 0; }
  body { font-family: 'DejaVu Sans', Helvetica, Arial, sans-serif; font-size: 10pt; color: #1a1a2e; line-height: 1.45; }
  .header { border-bottom: 3px solid #4361ee; padding-bottom: 12px; margin-bottom: 16px; }
  .name { font-size: 22pt; font-weight: bold; color: #4361ee; }
  .tagline { font-size: 10.5pt; color: #6c757d; margin-top: 2px; }
  .contacts { margin-top: 6px; font-size: 8.5pt; color: #555; }
  .contacts span { margin-right: 12px; }
  .contacts a { color: #4361ee; text-decoration: none; }
  .applied-for { background: #f8f9ff; border-left: 4px solid #4361ee; padding: 5px 10px; margin-bottom: 16px; font-size: 8.5pt; color: #555; }
  .section { margin-bottom: 14px; }
  .section-title { font-size: 10.5pt; font-weight: bold; color: #4361ee; text-transform: uppercase; letter-spacing: 1px; border-bottom: 1px solid #dee2e6; padding-bottom: 3px; margin-bottom: 8px; }
  .skill-tag { display: inline-block; background: #e8ecff; color: #4361ee; padding: 2px 8px; border-radius: 10px; font-size: 8.5pt; margin: 0 4px 4px 0; }
  .entry { margin-bottom: 10px; }
  .entry-title { font-weight: bold; }
  .entry-company { color: #4361ee; }
  .muted { font-size: 8.5pt; color: #888; }
  .desc { font-size: 9.5pt; color: #444; margin-top: 2px; }
  ul.bullets { margin: 3px 0 0 16px; }
  ul.bullets li { font-size: 9.5pt; color: #444; }
</style>
</head>
<body>
@php
    $summary = $tailored['professional_summary'] ?? null;
    $text = fn ($value) => is_array($value) ? implode(', ', $value) : (string) $value;
@endphp

<div class="header">
    <div class="name">{{ $candidate['full_name'] }}</div>
    @if ($summary)
        <div class="tagline">{{ \Illuminate\Support\Str::limit($summary, 120) }}</div>
    @endif
    <div class="contacts">
        @if ($candidate['email'])<span>{{ $candidate['email'] }}</span>@endif
        @if ($candidate['phone'])<span>{{ $candidate['phone'] }}</span>@endif
        @if ($candidate['location'])<span>{{ $candidate['location'] }}</span>@endif
        @if ($candidate['linkedin_url'])<span><a href="{{ $candidate['linkedin_url'] }}">LinkedIn</a></span>@endif
        @if ($candidate['github_url'])<span><a href="{{ $candidate['github_url'] }}">GitHub</a></span>@endif
        @if ($candidate['portfolio_url'])<span><a href="{{ $candidate['portfolio_url'] }}">Portfolio</a></span>@endif
    </div>
</div>

@if ($jobTitle)
    <div class="applied-for">
        Tailored for: <strong>{{ $jobTitle }}</strong>@if ($jobCompany) at <strong>{{ $jobCompany }}</strong>@endif
    </div>
@endif

@if ($summary)
    <div class="section">
        <div class="section-title">Professional Summary</div>
        <div>{{ $summary }}</div>
    </div>
@endif

@if (! empty($tailored['skills_ordered']))
    <div class="section">
        <div class="section-title">Skills</div>
        @foreach ($tailored['skills_ordered'] as $skill)
            <span class="skill-tag">{{ $skill }}</span>
        @endforeach
    </div>
@endif

@if (! empty($tailored['experience']))
    <div class="section">
        <div class="section-title">Work Experience</div>
        @foreach ($tailored['experience'] as $exp)
            <div class="entry">
                <span class="entry-title">{{ $exp['title'] ?? 'Role' }}</span>
                @if (! empty($exp['company'])) · <span class="entry-company">{{ $exp['company'] }}</span>@endif
                @if (! empty($exp['location'])) <span class="muted">({{ $exp['location'] }})</span>@endif
                @if (! empty($exp['period']))<div class="muted">{{ $exp['period'] }}</div>@endif
                @if (! empty($exp['bullets']) && is_array($exp['bullets']))
                    <ul class="bullets">
                        @foreach ($exp['bullets'] as $bullet)<li>{{ $bullet }}</li>@endforeach
                    </ul>
                @elseif (! empty($exp['description']))
                    <div class="desc">{{ $exp['description'] }}</div>
                @endif
            </div>
        @endforeach
    </div>
@endif

@if (! empty($tailored['education']))
    <div class="section">
        <div class="section-title">Education</div>
        @foreach ($tailored['education'] as $edu)
            <div class="entry">
                <span class="entry-title">{{ $edu['degree'] ?? 'Degree' }}</span>
                @if (! empty($edu['institution'])) · <span class="entry-company">{{ $edu['institution'] }}</span>@endif
                @if (! empty($edu['year']))<div class="muted">{{ $edu['year'] }}</div>@endif
                @if (! empty($edu['gpa']))<div class="muted">GPA: {{ $edu['gpa'] }}</div>@endif
            </div>
        @endforeach
    </div>
@endif

@if (! empty($tailored['projects']))
    <div class="section">
        <div class="section-title">Projects</div>
        @foreach ($tailored['projects'] as $project)
            <div class="entry">
                <span class="entry-title">{{ $project['name'] ?? 'Project' }}</span>
                @if (! empty($project['tech'])) <span class="muted">[{{ $text($project['tech']) }}]</span>@endif
                @if (! empty($project['description']))<div class="desc">{{ $project['description'] }}</div>@endif
                @if (! empty($project['url']))<div class="muted">{{ $project['url'] }}</div>@endif
            </div>
        @endforeach
    </div>
@endif

@if (! empty($tailored['certifications']))
    <div class="section">
        <div class="section-title">Certifications</div>
        @foreach ($tailored['certifications'] as $cert)
            <div class="entry">
                <span class="entry-title">{{ is_array($cert) ? ($cert['name'] ?? '') : $cert }}</span>
                @if (is_array($cert) && ! empty($cert['issuer'])) · <span class="muted">{{ $cert['issuer'] }}</span>@endif
                @if (is_array($cert) && ! empty($cert['year'])) <span class="muted">({{ $cert['year'] }})</span>@endif
            </div>
        @endforeach
    </div>
@endif
</body>
</html>
