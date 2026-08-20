@extends('layouts.app')

@section('page_title', 'Projects')

@section('content')
    @php
        $filterQuery = array_filter([
            'q' => $filters['q'] ?? '',
            'locality' => $filters['locality'] ?? '',
            'client_id' => $filters['client_id'] ?? '',
            'status' => $filters['status'] ?? '',
            'pa_status' => $filters['pa_status'] ?? '',
            'case_type' => $filters['case_type'] ?? '',
            'group' => $filters['group'] ?? '',
        ], fn ($v) => $v !== '' && $v !== null);

        $sortLink = function (string $key, string $label) use ($filters, $filterQuery) {
            $dir = ($filters['sort'] ?? '') === $key && ($filters['dir'] ?? '') === 'asc' ? 'desc' : 'asc';
            if (($filters['sort'] ?? '') !== $key) {
                $dir = in_array($key, ['name', 'client', 'locality', 'phase', 'status', 'reference'], true) ? 'asc' : 'desc';
            }
            $qs = http_build_query(array_merge($filterQuery, ['sort' => $key, 'dir' => $dir]));
            $active = ($filters['sort'] ?? '') === $key;
            $arrow = $active ? (($filters['dir'] ?? '') === 'asc' ? ' ↑' : ' ↓') : '';

            return '<a href="/pro/architect/projects?'.$qs.'" style="color: inherit; text-decoration: none; white-space: nowrap;">'
                .e($label).e($arrow)
                .'</a>';
        };

        $projectStatusStyle = function (?string $status): string {
            return match ($status) {
                'active' => 'background:#ecfdf5;color:#065f46;border:1px solid #a7f3d0;',
                'on_hold' => 'background:#fffbeb;color:#92400e;border:1px solid #fde68a;',
                'completed' => 'background:#eff6ff;color:#1e40af;border:1px solid #bfdbfe;',
                'archived' => 'background:#f8fafc;color:#64748b;border:1px solid #e2e8f0;',
                default => 'background:#f1f5f9;color:var(--primary-navy);border:1px solid var(--border-light);',
            };
        };

        $paStatusStyle = function (?string $status): string {
            $key = (string) $status;
            if (in_array($key, ['tracking', 'active'], true)) {
                return 'background:#f0f9ff;color:#075985;border:1px solid #bae6fd;';
            }
            if ($key === 'pending') {
                return 'background:#eef2ff;color:#3730a3;border:1px solid #c7d2fe;';
            }
            if ($key === 'recommended') {
                return 'background:#faf5ff;color:#6b21a8;border:1px solid #e9d5ff;';
            }
            if (in_array($key, ['decided', 'endorsed', 'approved', 'fee_payment'], true)) {
                return 'background:#ecfdf5;color:#065f46;border:1px solid #a7f3d0;';
            }
            if ($key === 'under_appeal') {
                return 'background:#fff7ed;color:#9a3412;border:1px solid #fed7aa;';
            }
            if (in_array($key, ['refused', 'revoked', 'withdrawn'], true)) {
                return 'background:#fef2f2;color:#991b1b;border:1px solid #fecaca;';
            }

            return 'background:#f8fafc;color:#64748b;border:1px solid #e2e8f0;';
        };

        $rowTint = function (?string $status): string {
            return match ($status) {
                'active' => 'background:#ffffff;',
                'on_hold' => 'background:#fffbeb;',
                'completed' => 'background:#f8fafc;',
                'archived' => 'background:#f1f5f9;',
                default => 'background:#ffffff;',
            };
        };

        $groupLabel = function ($project) use ($filters, $phases, $statuses) {
            return match ($filters['group'] ?? '') {
                'client' => $project->client->name ?? 'No client',
                'locality' => $project->site_locality ?: 'No locality',
                'status' => $statuses[$project->status] ?? $project->status,
                'phase' => $phases[$project->phase] ?? $project->phase,
                default => null,
            };
        };
    @endphp

    <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem; flex-wrap: wrap; margin-bottom: 1.25rem;">
        <div>
            <h1 style="margin: 0 0 0.25rem; color: var(--primary-navy); font-size: 1.5rem;">Portfolio</h1>
            <p style="margin: 0; color: var(--text-muted); font-size: 0.9rem;">Projects, PA/PC/DN cases, and sites — filter, sort, and group in the table.</p>
        </div>
        <a href="/pro/architect/projects/create" style="background: #3f6212; color: white; padding: 0.55rem 1rem; border-radius: var(--radius-md); font-weight: 600; text-decoration: none;">+ Project</a>
    </div>

    @if(session('success'))
        <div style="background: #ecfdf5; color: #065f46; padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1rem;">{{ session('success') }}</div>
    @endif

    <form method="GET" action="/pro/architect/projects" style="margin-bottom: 1rem; background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 0.9rem 1rem; box-shadow: var(--shadow-sm);">
        <input type="hidden" name="sort" value="{{ $filters['sort'] ?? 'updated' }}">
        <input type="hidden" name="dir" value="{{ $filters['dir'] ?? 'desc' }}">
        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap; margin-bottom: 0.65rem;">
            <input type="search" name="q" value="{{ $filters['q'] }}" placeholder="Search projects, PA, locality…"
                   style="flex: 1; min-width: 220px; padding: 0.65rem 0.85rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
            <button type="submit" style="background: var(--primary-navy); color: white; border: none; padding: 0.65rem 1rem; border-radius: var(--radius-md); font-weight: 700; cursor: pointer;">Filter</button>
            @if(collect($filters)->filter(fn ($v) => $v !== '' && $v !== null)->isNotEmpty())
                <a href="/pro/architect/projects" style="padding: 0.65rem 0.9rem; color: var(--text-muted); font-weight: 600; text-decoration: none; font-size: 0.85rem;">Clear</a>
            @endif
        </div>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 0.55rem;">
            <select name="locality" style="padding: 0.55rem 0.65rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                <option value="">All localities</option>
                @foreach($localities as $loc)
                    <option value="{{ $loc }}" @selected($filters['locality'] === $loc)>{{ $loc }}</option>
                @endforeach
            </select>
            <select name="client_id" style="padding: 0.55rem 0.65rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                <option value="">All clients</option>
                @foreach($clients as $client)
                    <option value="{{ $client->id }}" @selected((string) $filters['client_id'] === (string) $client->id)>{{ $client->name }}</option>
                @endforeach
            </select>
            <select name="status" style="padding: 0.55rem 0.65rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                <option value="">All project statuses</option>
                @foreach($statuses as $key => $label)
                    <option value="{{ $key }}" @selected($filters['status'] === $key)>{{ $label }}</option>
                @endforeach
            </select>
            <select name="pa_status" style="padding: 0.55rem 0.65rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                <option value="">All case statuses</option>
                @foreach($paStatuses as $key => $label)
                    <option value="{{ $key }}" @selected($filters['pa_status'] === $key)>{{ $label }}</option>
                @endforeach
            </select>
            <select name="case_type" style="padding: 0.55rem 0.65rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                <option value="">All case types</option>
                @foreach($caseTypes as $key => $label)
                    <option value="{{ $key }}" @selected($filters['case_type'] === $key)>{{ $key }}</option>
                @endforeach
            </select>
            <select name="group" style="padding: 0.55rem 0.65rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                <option value="">No grouping</option>
                <option value="client" @selected(($filters['group'] ?? '') === 'client')>Group by client</option>
                <option value="locality" @selected(($filters['group'] ?? '') === 'locality')>Group by locality</option>
                <option value="status" @selected(($filters['group'] ?? '') === 'status')>Group by status</option>
                <option value="phase" @selected(($filters['group'] ?? '') === 'phase')>Group by phase</option>
            </select>
        </div>
    </form>
    <script>
    (function () {
        var form = document.querySelector('form[action="/pro/architect/projects"]');
        if (!form) return;
        form.querySelectorAll('select').forEach(function (el) {
            el.addEventListener('change', function () { form.submit(); });
        });
    })();
    </script>

    <div style="margin-bottom: 1.15rem;">
        @include('pro.architect.partials.portfolio-map', [
            'mapId' => 'arch-portfolio-map',
            'pins' => $mapPins,
            'height' => '420px',
            'mapServerUrl' => $mapServerUrl,
        ])
    </div>

    @if($projects->isEmpty())
        <div style="padding: 3rem; border: 2px dashed var(--border-light); border-radius: var(--radius-md); text-align: center; background: white;">
            <p style="color: var(--text-muted);">No projects match. Start from a client, then add a project and pin the site.</p>
        </div>
    @else
        <div style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); overflow: hidden;">
            <div style="padding: 0.7rem 1rem; border-bottom: 1px solid var(--border-light); display: flex; justify-content: space-between; gap: 0.75rem; flex-wrap: wrap; align-items: center;">
                <div style="font-size: 0.82rem; font-weight: 700; color: var(--primary-navy);">
                    {{ $projects->count() }} project{{ $projects->count() === 1 ? '' : 's' }}
                    @if(($filters['group'] ?? '') !== '')
                        <span style="font-weight: 500; color: var(--text-muted);">· grouped by {{ $filters['group'] }}</span>
                    @endif
                </div>
                <div style="font-size: 0.72rem; color: var(--text-muted);">Click column headers to sort · PA numbers open the case</div>
            </div>
            <div style="overflow-x: auto;">
                <table class="arch-projects-table" style="width: 100%; border-collapse: collapse; min-width: 920px;">
                    <thead>
                        <tr style="background: #f8fafc; text-align: left;">
                            <th style="padding: 0.65rem 0.85rem; font-size: 0.72rem; font-weight: 700; color: var(--text-muted); border-bottom: 1px solid var(--border-light);">{!! $sortLink('name', 'Project') !!}</th>
                            <th style="padding: 0.65rem 0.85rem; font-size: 0.72rem; font-weight: 700; color: var(--text-muted); border-bottom: 1px solid var(--border-light);">{!! $sortLink('client', 'Client') !!}</th>
                            <th style="padding: 0.65rem 0.85rem; font-size: 0.72rem; font-weight: 700; color: var(--text-muted); border-bottom: 1px solid var(--border-light);">{!! $sortLink('locality', 'Locality') !!}</th>
                            <th style="padding: 0.65rem 0.85rem; font-size: 0.72rem; font-weight: 700; color: var(--text-muted); border-bottom: 1px solid var(--border-light);">{!! $sortLink('phase', 'Phase') !!}</th>
                            <th style="padding: 0.65rem 0.85rem; font-size: 0.72rem; font-weight: 700; color: var(--text-muted); border-bottom: 1px solid var(--border-light);">{!! $sortLink('status', 'Status') !!}</th>
                            <th style="padding: 0.65rem 0.85rem; font-size: 0.72rem; font-weight: 700; color: var(--text-muted); border-bottom: 1px solid var(--border-light);">{!! $sortLink('reference', 'Ref') !!}</th>
                            <th style="padding: 0.65rem 0.85rem; font-size: 0.72rem; font-weight: 700; color: var(--text-muted); border-bottom: 1px solid var(--border-light);">Cases</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $prevGroup = null; @endphp
                        @foreach($projects as $project)
                            @php
                                $currentGroup = $groupLabel($project);
                            @endphp
                            @if($currentGroup !== null && $currentGroup !== $prevGroup)
                                <tr>
                                    <td colspan="7" style="padding: 0.55rem 0.85rem; background: #eef2ff; border-bottom: 1px solid #c7d2fe; font-size: 0.78rem; font-weight: 700; color: #312e81;">
                                        {{ $currentGroup }}
                                        <span style="font-weight: 500; color: #6366f1; margin-left: 0.35rem;">
                                            · {{ $projects->filter(fn ($p) => $groupLabel($p) === $currentGroup)->count() }}
                                        </span>
                                    </td>
                                </tr>
                                @php $prevGroup = $currentGroup; @endphp
                            @endif
                            <tr style="{{ $rowTint($project->status) }} border-bottom: 1px solid var(--border-light);">
                                <td style="padding: 0.75rem 0.85rem; vertical-align: top;">
                                    <a href="/pro/architect/projects/{{ $project->id }}" style="font-weight: 700; color: var(--primary-navy); text-decoration: none;">{{ $project->name }}</a>
                                    @if($project->hasMapPin())
                                        <div style="font-size: 0.68rem; color: #3f6212; font-weight: 600; margin-top: 0.2rem;">Mapped</div>
                                    @endif
                                </td>
                                <td style="padding: 0.75rem 0.85rem; vertical-align: top; font-size: 0.85rem; color: var(--primary-navy);">
                                    {{ $project->client->name ?? '—' }}
                                </td>
                                <td style="padding: 0.75rem 0.85rem; vertical-align: top; font-size: 0.85rem; color: var(--text-muted);">
                                    {{ $project->site_locality ?: '—' }}
                                    @if($project->site_street)
                                        <div style="font-size: 0.72rem; margin-top: 0.15rem;">{{ $project->site_street }}</div>
                                    @endif
                                </td>
                                <td style="padding: 0.75rem 0.85rem; vertical-align: top; font-size: 0.82rem; color: var(--primary-navy);">
                                    {{ $phases[$project->phase] ?? $project->phase }}
                                </td>
                                <td style="padding: 0.75rem 0.85rem; vertical-align: top;">
                                    <span style="display: inline-block; font-size: 0.72rem; font-weight: 700; padding: 0.2rem 0.5rem; border-radius: 999px; {{ $projectStatusStyle($project->status) }}">
                                        {{ $statuses[$project->status] ?? $project->status }}
                                    </span>
                                </td>
                                <td style="padding: 0.75rem 0.85rem; vertical-align: top; font-size: 0.78rem; font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; color: var(--primary-navy);">
                                    {{ $project->reference_code ?: '—' }}
                                </td>
                                <td style="padding: 0.75rem 0.85rem; vertical-align: top;">
                                    @if($project->paApplications->isEmpty())
                                        <span style="font-size: 0.75rem; color: var(--text-muted);">No cases</span>
                                        <div style="margin-top: 0.3rem;">
                                            <a href="/pro/architect/projects/{{ $project->id }}/pa/create" style="font-size: 0.72rem; font-weight: 650; color: #3f6212; text-decoration: none;">+ Add case</a>
                                        </div>
                                    @else
                                        <div style="display: flex; flex-direction: column; gap: 0.35rem;">
                                            @foreach($project->paApplications->take(6) as $pa)
                                                @php
                                                    $label = $pa->canonicalNumber() ?: ($pa->resolvedCaseType().' pending');
                                                    $eapps = $pa->eappsUrl();
                                                @endphp
                                                <div style="display: flex; flex-wrap: wrap; gap: 0.3rem; align-items: center;">
                                                    <a href="/pro/architect/pa/{{ $pa->id }}"
                                                       style="display: inline-block; font-size: 0.72rem; font-weight: 700; padding: 0.18rem 0.45rem; border-radius: 4px; text-decoration: none; {{ $paStatusStyle($pa->status) }}">
                                                        {{ $label }}
                                                    </a>
                                                    <span style="font-size: 0.68rem; color: var(--text-muted);">{{ $pa->statusLabel() }}</span>
                                                    @if($eapps)
                                                        <a href="{{ $eapps }}" target="_blank" rel="noopener noreferrer" style="font-size: 0.68rem; color: #64748b; text-decoration: none;" title="Open in eApps">eApps ↗</a>
                                                    @endif
                                                </div>
                                            @endforeach
                                            @if($project->paApplications->count() > 6)
                                                <a href="/pro/architect/projects/{{ $project->id }}" style="font-size: 0.7rem; color: var(--text-muted);">+{{ $project->paApplications->count() - 6 }} more</a>
                                            @endif
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        <style>
            .arch-projects-table tbody tr:hover td { filter: brightness(0.985); }
            .arch-projects-table th a:hover { color: var(--primary-navy); text-decoration: underline; }
            @media (max-width: 720px) {
                .arch-projects-table { min-width: 780px; }
            }
        </style>
    @endif
@endsection
