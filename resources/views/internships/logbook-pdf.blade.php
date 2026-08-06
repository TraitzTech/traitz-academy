<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Internship Logbook</title>
    <style>
        * { font-family: DejaVu Sans, sans-serif; }
        body { color: #1f2937; font-size: 12px; margin: 0; }
        .header { border-bottom: 3px solid #381998; padding-bottom: 10px; margin-bottom: 16px; }
        .site { font-size: 18px; font-weight: bold; color: #000928; }
        .title { font-size: 14px; color: #381998; margin-top: 2px; }
        .meta { width: 100%; margin-bottom: 18px; }
        .meta td { padding: 3px 6px; vertical-align: top; }
        .meta .label { color: #6b7280; width: 90px; }
        .meta .value { font-weight: bold; color: #111827; }
        .entry { border: 1px solid #e5e7eb; border-radius: 6px; margin-bottom: 10px; padding: 10px; }
        .entry-head { border-bottom: 1px solid #f3f4f6; padding-bottom: 5px; margin-bottom: 6px; }
        .date { font-weight: bold; color: #000928; }
        .badge { float: right; font-size: 10px; padding: 2px 8px; border-radius: 10px; background: #f3f4f6; color: #374151; }
        .badge.approved { background: #dcfce7; color: #15803d; }
        .field-label { color: #6b7280; font-size: 10px; text-transform: uppercase; margin-top: 6px; }
        .content { white-space: pre-wrap; }
        .sign { margin-top: 30px; width: 100%; }
        .sign td { width: 50%; padding-top: 30px; }
        .sign .line { border-top: 1px solid #9ca3af; padding-top: 4px; color: #6b7280; font-size: 11px; }
        .empty { color: #9ca3af; font-style: italic; }
    </style>
</head>
<body>
    <div class="header">
        <div class="site">{{ $siteName }}</div>
        <div class="title">Internship Logbook</div>
    </div>

    <table class="meta">
        <tr>
            <td class="label">Intern</td><td class="value">{{ $internship->intern?->name ?? '—' }}</td>
            <td class="label">Program</td><td class="value">{{ $internship->program?->title ?? '—' }}</td>
        </tr>
        <tr>
            <td class="label">Cohort</td><td class="value">{{ $internship->cohort?->name ?? '—' }}</td>
            <td class="label">Supervisor</td><td class="value">{{ $supervisor?->name ?? '—' }}</td>
        </tr>
        <tr>
            <td class="label">Period</td>
            <td class="value" colspan="3">
                {{ optional($internship->start_date)->format('d M Y') ?? '—' }}
                &nbsp;–&nbsp;
                {{ optional($internship->end_date)->format('d M Y') ?? '—' }}
            </td>
        </tr>
    </table>

    @forelse ($entries as $entry)
        <div class="entry">
            <div class="entry-head">
                <span class="date">{{ $entry->date->format('l, d M Y') }}</span>
                <span class="badge {{ $entry->status === 'approved' ? 'approved' : '' }}">{{ str_replace('_', ' ', $entry->status) }}</span>
            </div>
            <div class="content">{{ $entry->content }}</div>

            @if ($entry->hours_spent)
                <div class="field-label">Hours</div>{{ $entry->hours_spent }}
            @endif
            @if ($entry->learnings)
                <div class="field-label">Learnings</div><div class="content">{{ $entry->learnings }}</div>
            @endif
            @if ($entry->blockers)
                <div class="field-label">Blockers</div><div class="content">{{ $entry->blockers }}</div>
            @endif
            @if ($entry->supervisor_feedback)
                <div class="field-label">Supervisor feedback</div><div class="content">{{ $entry->supervisor_feedback }}</div>
            @endif
        </div>
    @empty
        <p class="empty">No logbook entries have been recorded yet.</p>
    @endforelse

    <table class="sign">
        <tr>
            <td><div class="line">Intern signature &amp; date</div></td>
            <td><div class="line">Supervisor signature &amp; date</div></td>
        </tr>
    </table>
</body>
</html>
