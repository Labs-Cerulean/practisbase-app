@extends('layouts.app')

@section('page_title', 'Overview')

@section('content')
    @php
        $firstName = $user->name ? explode(' ', $user->name)[0] : '';
        $deskAccent = match ($package) {
            'med' => '#0f766e',
            'arch' => '#3f6212',
            'eng' => '#0c4a6e',
            default => 'var(--primary-cerulean)',
        };
        $subtitle = match (true) {
            ($peritHome ?? false) => 'Projects, planning cases, and sites — day to day for the practice.',
            $mode === 'practice' => match ($package) {
                'med' => 'Clinical tools first. Free invoicing sits underneath.',
                'arch' => 'Projects, planning cases, and sites — day to day for the practice.',
                'eng' => 'Technical tools first. Free invoicing sits underneath.',
                default => 'Practice tools first. Free invoicing sits underneath.',
            },
            $mode === 'pro' && ($package ?? null) === 'arch' => 'Studio desk first. Billing and tax live under Accounts.',
            $mode === 'pro' => 'Practice desk and Tax and VAT in one place.',
            $mode === 'standard' => 'Your '.$year.' accounts at a glance. Official invoices only count for tax.',
            default => 'Free invoicing layer. Upgrade when you need Tax and VAT or profession tools.',
        };
        $showMoneyOnOverview = $showMoneyOnOverview ?? ! ($peritHome ?? false);
    @endphp

    <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.25rem;">
        <div>
            <div style="font-size: 0.75rem; font-weight: 700; letter-spacing: 0.04em; text-transform: uppercase; color: var(--text-muted); margin-bottom: 0.25rem;">{{ $tierLabel }}</div>
            <h1 style="font-size: 1.5rem; color: var(--primary-navy); margin: 0 0 0.25rem;">Hello{{ $firstName ? ', '.$firstName : '' }}</h1>
            <p style="color: var(--text-muted); font-size: 0.95rem; margin: 0;">{{ $subtitle }}</p>
        </div>
        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
            @if($practiceDesk && ($practiceDesk['kind'] ?? null) === 'med')
                @if($practiceDesk['vault_unlocked'])
                    <a href="/pro/medical/patients/create" style="background: {{ $deskAccent }}; color: white; padding: 0.55rem 1rem; border-radius: var(--radius-md); font-weight: 600; font-size: 0.85rem; text-decoration: none;">+ Patient</a>
                @elseif($practiceDesk['vault_setup'])
                    <a href="/pro/medical/vault/unlock" style="background: {{ $deskAccent }}; color: white; padding: 0.55rem 1rem; border-radius: var(--radius-md); font-weight: 600; font-size: 0.85rem; text-decoration: none;">Unlock vault</a>
                @else
                    <a href="/pro/medical/vault/setup" style="background: {{ $deskAccent }}; color: white; padding: 0.55rem 1rem; border-radius: var(--radius-md); font-weight: 600; font-size: 0.85rem; text-decoration: none;">Set up vault</a>
                @endif
            @elseif($practiceDesk && ($practiceDesk['kind'] ?? null) === 'arch')
                <a href="/pro/architect/projects/create" style="background: {{ $deskAccent }}; color: white; padding: 0.55rem 1rem; border-radius: var(--radius-md); font-weight: 600; font-size: 0.85rem; text-decoration: none;">+ Project</a>
                <a href="/pro/architect/projects" style="background: white; color: var(--primary-navy); border: 1px solid var(--border-light); padding: 0.55rem 1rem; border-radius: var(--radius-md); font-weight: 600; font-size: 0.85rem; text-decoration: none;">Portfolio map</a>
                <a href="/accounts" style="background: white; color: var(--primary-navy); border: 1px solid var(--border-light); padding: 0.55rem 1rem; border-radius: var(--radius-md); font-weight: 600; font-size: 0.85rem; text-decoration: none;">Accounts</a>
            @elseif($practiceDesk && ($practiceDesk['kind'] ?? null) === 'eng')
                <a href="/clients/create" style="background: {{ $deskAccent }}; color: white; padding: 0.55rem 1rem; border-radius: var(--radius-md); font-weight: 600; font-size: 0.85rem; text-decoration: none;">+ Client</a>
                <a href="/pro/engineer/certificates" style="background: white; color: var(--primary-navy); border: 1px solid var(--border-light); padding: 0.55rem 1rem; border-radius: var(--radius-md); font-weight: 600; font-size: 0.85rem; text-decoration: none;">Field certificates</a>
            @endif

            @if($showMoneyOnOverview && ($hasFinancial || $mode === 'free'))
                <a href="/ledger/create" style="background: {{ $hasPractice && ! $hasFinancial ? 'white' : 'var(--primary-cerulean)' }}; color: {{ $hasPractice && ! $hasFinancial ? 'var(--primary-navy)' : 'white' }}; border: {{ $hasPractice && ! $hasFinancial ? '1px solid var(--border-light)' : 'none' }}; padding: 0.55rem 1rem; border-radius: var(--radius-md); font-weight: 600; font-size: 0.85rem; text-decoration: none;">+ Invoice / RFP</a>
                <a href="/clients/create" style="background: white; color: var(--primary-navy); border: 1px solid var(--border-light); padding: 0.55rem 1rem; border-radius: var(--radius-md); font-weight: 600; font-size: 0.85rem; text-decoration: none;">{{ $hasPractice ? '+ Billing client' : '+ Client' }}</a>
            @elseif($showMoneyOnOverview && $practiceOnly)
                <a href="/ledger/create" style="background: white; color: var(--primary-navy); border: 1px solid var(--border-light); padding: 0.55rem 1rem; border-radius: var(--radius-md); font-weight: 600; font-size: 0.85rem; text-decoration: none;">+ Invoice / RFP</a>
            @endif

            @if($showMoneyOnOverview && $hasFinancial)
                <a href="/expenses/create" style="background: white; color: var(--primary-navy); border: 1px solid var(--border-light); padding: 0.55rem 1rem; border-radius: var(--radius-md); font-weight: 600; font-size: 0.85rem; text-decoration: none;">+ Expense</a>
            @endif
        </div>
    </div>

    @if($user->canAccessCompanyBooks())
        <div style="background: #0c4a6e; border-radius: var(--radius-lg); padding: 1rem 1.25rem; margin-bottom: 1.25rem; display: flex; justify-content: space-between; gap: 1rem; flex-wrap: wrap; align-items: center;">
            <div>
                <div style="font-weight: 700; color: white;">Cerulean Labs Ltd</div>
                <div style="font-size: 0.85rem; color: #bae6fd; line-height: 1.45; margin-top: 0.2rem;">
                    Internal company desk — Art 10 books, separate from your sole-trader ledger.
                </div>
            </div>
            <a href="/company" style="background: white; color: #0c4a6e; border: none; padding: 0.55rem 1rem; border-radius: var(--radius-md); font-weight: 700; font-size: 0.85rem; text-decoration: none; white-space: nowrap;">Open company desk →</a>
        </div>
    @endif

    @if($showMoneyOnOverview && $practiceOnly)
        <div style="background: #f8fafc; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1rem 1.25rem; margin-bottom: 1.25rem; display: flex; justify-content: space-between; gap: 1rem; flex-wrap: wrap; align-items: center;">
            <div>
                <div style="font-weight: 700; color: var(--primary-navy);">Free billing layer</div>
                <div style="font-size: 0.85rem; color: var(--text-muted); line-height: 1.45; margin-top: 0.2rem;">
                    {{ $clientsUsed }}/{{ $clientCap }} lifetime clients on invoices.
                    Full Pro adds unlimited clients, expenses, and Tax and VAT.
                </div>
            </div>
            <a href="/settings#plan" style="background: white; color: var(--primary-navy); border: 1px solid var(--border-light); padding: 0.55rem 1rem; border-radius: var(--radius-md); font-weight: 700; font-size: 0.85rem; text-decoration: none; white-space: nowrap;">Upgrade to Full Pro</a>
        </div>
    @endif

    @if($backupOverdue ?? $user->isDataBackupOverdue())
        <div style="background: #f0f9ff; border: 1px solid #bae6fd; border-left: 4px solid var(--primary-cerulean); border-radius: var(--radius-lg); padding: 1rem 1.25rem; margin-bottom: 1.25rem; display: flex; justify-content: space-between; gap: 1rem; flex-wrap: wrap; align-items: center;">
            <div>
                <div style="font-weight: 700; color: var(--primary-navy);">Weekly backup reminder</div>
                <div style="font-size: 0.85rem; color: var(--text-muted); line-height: 1.45; margin-top: 0.2rem;">
                    Download practice data{{ $user->canAccessProPackage('med') ? ' and medical vault' : '' }} from one place.
                </div>
            </div>
            <a href="/exports/backup" style="background: var(--primary-cerulean); color: white; border: none; padding: 0.55rem 1rem; border-radius: var(--radius-md); font-weight: 700; font-size: 0.85rem; text-decoration: none; white-space: nowrap;">Open backup</a>
        </div>
    @endif

    <div id="pb-dashboard-install-card" class="pb-install-card-mobile" style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1rem 1.25rem; margin-bottom: 1.25rem; display: none; justify-content: space-between; gap: 1rem; flex-wrap: wrap; align-items: center; box-shadow: var(--shadow-sm);">
        <div>
            <div style="font-weight: 700; color: var(--primary-navy);">Download app</div>
            <div style="font-size: 0.85rem; color: var(--text-muted); line-height: 1.45; margin-top: 0.2rem;">
                One tap from your phone — no app store.
            </div>
        </div>
        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
            <button type="button" data-open-install-app style="background: white; color: var(--primary-navy); border: 1px solid var(--border-light); padding: 0.55rem 1rem; border-radius: var(--radius-md); font-weight: 600; font-size: 0.85rem; cursor: pointer; white-space: nowrap;">Download app</button>
            <button type="button" id="pb-dismiss-install-card" style="background: white; color: var(--text-muted); border: 1px solid var(--border-light); padding: 0.55rem 0.85rem; border-radius: var(--radius-md); font-weight: 600; font-size: 0.85rem; cursor: pointer;">Hide</button>
        </div>
    </div>
    <script>
        (function () {
            var card = document.getElementById('pb-dashboard-install-card');
            var dismiss = document.getElementById('pb-dismiss-install-card');
            if (!card) return;
            try {
                if (localStorage.getItem('pb_hide_install_card') === '1'
                    || window.matchMedia('(display-mode: standalone)').matches
                    || window.navigator.standalone) {
                    card.style.display = 'none';
                    return;
                }
            } catch (e) {}
            if (window.matchMedia('(max-width: 900px)').matches) {
                card.style.display = 'flex';
            }
            if (dismiss) {
                dismiss.addEventListener('click', function () {
                    card.style.display = 'none';
                    try { localStorage.setItem('pb_hide_install_card', '1'); } catch (e) {}
                });
            }
        })();
    </script>

    @if($growth || $practiceDesk)
        <div style="background: white; border: 1px solid var(--border-light); border-left: 5px solid {{ $deskAccent }}; border-radius: var(--radius-lg); padding: 1.35rem 1.5rem; box-shadow: var(--shadow-sm); margin-bottom: 1.5rem;">
            <div style="display: flex; justify-content: space-between; align-items: baseline; gap: 1rem; flex-wrap: wrap; margin-bottom: 1rem;">
                <div style="font-size: 0.75rem; font-weight: 700; color: {{ $deskAccent }}; text-transform: uppercase; letter-spacing: 0.04em;">
                    {{ $practiceDesk['title'] ?? 'Practice growth' }}
                </div>
                @if(($practiceDesk['kind'] ?? null) === 'med')
                    <a href="/pro/medical/patients" style="font-size: 0.8rem; font-weight: 600; color: {{ $deskAccent }}; text-decoration: none;">Open patients →</a>
                @elseif(($practiceDesk['kind'] ?? null) === 'arch')
                    <a href="/pro/architect/projects" style="font-size: 0.8rem; font-weight: 600; color: {{ $deskAccent }}; text-decoration: none;">Open portfolio →</a>
                @elseif($practiceDesk)
                    <a href="/clients" style="font-size: 0.8rem; font-weight: 600; color: {{ $deskAccent }}; text-decoration: none;">Open clients →</a>
                @endif
            </div>

            @if($growth)
                @php
                    $maxBar = max(1, ...array_column($growth['monthly'], 'count'));
                    $delta = (int) $growth['growth_delta'];
                    $deltaLabel = $delta === 0 ? 'same as last month' : (($delta > 0 ? '+' : '').$delta.' vs last month');
                @endphp
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 1rem; margin-bottom: 1.15rem;">
                    <div>
                        <div style="font-size: 0.75rem; color: var(--text-muted);">{{ $growth['panel_label'] }}</div>
                        <div style="font-size: 1.45rem; font-weight: 700; color: var(--primary-navy);">{{ number_format($growth['panel_size']) }}</div>
                        <div style="font-size: 0.72rem; color: var(--text-muted); margin-top: 0.15rem;">Last 12 months</div>
                    </div>
                    <div>
                        <div style="font-size: 0.75rem; color: var(--text-muted);">{{ $growth['return_label'] }}</div>
                        <div style="font-size: 1.45rem; font-weight: 700; color: var(--primary-navy);">
                            @if($growth['return_rate'] === null)
                                —
                            @else
                                {{ number_format($growth['return_rate'], 0) }}%
                            @endif
                        </div>
                        <div style="font-size: 0.72rem; color: var(--text-muted); margin-top: 0.15rem;">Second visit within 90 days</div>
                    </div>
                    <div>
                        <div style="font-size: 0.75rem; color: var(--text-muted);">New this month</div>
                        <div style="font-size: 1.45rem; font-weight: 700; color: var(--primary-navy);">{{ number_format($growth['new_this_month']) }}</div>
                        <div style="font-size: 0.72rem; color: var(--text-muted); margin-top: 0.15rem;">{{ $deltaLabel }}</div>
                    </div>
                </div>
                <div style="display: flex; align-items: flex-end; gap: 0.45rem; height: 56px; margin-bottom: 1rem;">
                    @foreach($growth['monthly'] as $bar)
                        <div style="flex: 1; display: flex; flex-direction: column; align-items: center; gap: 0.25rem; height: 100%; justify-content: flex-end;">
                            <div style="width: 100%; max-width: 28px; background: {{ $deskAccent }}; opacity: 0.85; border-radius: 3px 3px 0 0; height: {{ max(4, (int) round(40 * ($bar['count'] / $maxBar))) }}px;" title="{{ $bar['count'] }}"></div>
                            <div style="font-size: 0.65rem; color: var(--text-muted);">{{ $bar['label'] }}</div>
                        </div>
                    @endforeach
                </div>
            @endif

            @if(($practiceDesk['kind'] ?? null) === 'med')
                <div style="display: flex; gap: 0.5rem; flex-wrap: wrap; align-items: center;">
                    @if($practiceDesk['vault_unlocked'])
                        <a href="/pro/medical/patients" style="padding: 0.55rem 0.85rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); text-decoration: none; color: var(--primary-navy); font-weight: 600; font-size: 0.85rem;">Patients</a>
                        <a href="/pro/medical/stampables" style="padding: 0.55rem 0.85rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); text-decoration: none; color: var(--primary-navy); font-weight: 600; font-size: 0.85rem;">Documents</a>
                    @elseif($practiceDesk['vault_setup'])
                        <a href="/pro/medical/vault/unlock" style="padding: 0.55rem 0.85rem; background: {{ $deskAccent }}; color: white; border-radius: var(--radius-md); text-decoration: none; font-weight: 600; font-size: 0.85rem;">Unlock to continue</a>
                    @else
                        <a href="/pro/medical/vault/setup" style="padding: 0.55rem 0.85rem; background: {{ $deskAccent }}; color: white; border-radius: var(--radius-md); text-decoration: none; font-weight: 600; font-size: 0.85rem;">Set up vault</a>
                    @endif
                    @if($practiceDesk['vault_setup'] && $practiceDesk['backup_overdue'])
                        <a href="/exports/backup#medical" style="font-size: 0.8rem; color: #b45309; font-weight: 600; text-decoration: none;">Vault backup overdue</a>
                    @endif
                </div>
            @elseif(($practiceDesk['kind'] ?? null) === 'arch')
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(110px, 1fr)); gap: 0.85rem; margin-bottom: 1rem;">
                    <div>
                        <div style="font-size: 0.72rem; color: var(--text-muted);">Projects</div>
                        <div style="font-size: 1.3rem; font-weight: 700; color: var(--primary-navy);">{{ $practiceDesk['project_count'] }}</div>
                    </div>
                    <div>
                        <div style="font-size: 0.72rem; color: var(--text-muted);">Open cases</div>
                        <div style="font-size: 1.3rem; font-weight: 700; color: var(--primary-navy);">{{ $practiceDesk['open_pa_count'] ?? 0 }}</div>
                    </div>
                    <div>
                        <div style="font-size: 0.72rem; color: var(--text-muted);">Endorsed</div>
                        <div style="font-size: 1.3rem; font-weight: 700; color: var(--primary-navy);">{{ $practiceDesk['endorsed_count'] ?? 0 }}</div>
                    </div>
                    <div>
                        <div style="font-size: 0.72rem; color: var(--text-muted);">Mapped</div>
                        <div style="font-size: 1.3rem; font-weight: 700; color: var(--primary-navy);">{{ $practiceDesk['pinned_count'] ?? 0 }}</div>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1.15fr 1fr; gap: 1rem; align-items: start;" class="arch-desk-grid">
                    <div>
                        @include('pro.architect.partials.portfolio-map', [
                            'mapId' => 'arch-desk-map',
                            'pins' => $practiceDesk['map_pins'] ?? [],
                            'height' => '260px',
                            'mapServerUrl' => $practiceDesk['map_server_url'] ?? null,
                        ])
                    </div>
                    <div>
                        <div style="font-size: 0.72rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.04em; margin-bottom: 0.55rem;">Needs attention</div>
                        @if(($practiceDesk['attention_cases'] ?? collect())->isEmpty())
                            <p style="margin: 0 0 0.85rem; color: var(--text-muted); font-size: 0.88rem;">No open PA/PC/DN cases waiting. Add a case on a project when submitted.</p>
                        @else
                            <div style="display: grid; gap: 0.35rem; margin-bottom: 0.85rem;">
                                @foreach($practiceDesk['attention_cases'] as $case)
                                    <a href="/pro/architect/pa/{{ $case->id }}" style="display: block; padding: 0.55rem 0.65rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); text-decoration: none;">
                                        <div style="font-weight: 650; color: var(--primary-navy); font-size: 0.88rem;">{{ $case->displayLabel() }}</div>
                                        <div style="font-size: 0.75rem; color: var(--text-muted);">{{ $case->statusLabel() }}@if($case->project) · {{ $case->project->name }}@endif</div>
                                    </a>
                                @endforeach
                            </div>
                        @endif
                        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                            <a href="/pro/architect/projects" style="padding: 0.5rem 0.8rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); text-decoration: none; color: var(--primary-navy); font-weight: 600; font-size: 0.82rem;">All projects</a>
                            <a href="/pro/architect/templates" style="padding: 0.5rem 0.8rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); text-decoration: none; color: var(--primary-navy); font-weight: 600; font-size: 0.82rem;">BCA templates</a>
                        </div>
                    </div>
                </div>
                <style>
                    @media (max-width: 860px) {
                        .arch-desk-grid { grid-template-columns: 1fr !important; }
                    }
                </style>
            @elseif($practiceDesk && ($practiceDesk['kind'] ?? null) === 'eng')
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 1rem; margin-bottom: 1rem;">
                    <div>
                        <div style="font-size: 0.75rem; color: var(--text-muted);">Projects</div>
                        <div style="font-size: 1.35rem; font-weight: 700; color: var(--primary-navy);">{{ $practiceDesk['project_count'] }}</div>
                    </div>
                    <div>
                        <div style="font-size: 0.75rem; color: var(--text-muted);">PA applications</div>
                        <div style="font-size: 1.35rem; font-weight: 700; color: var(--primary-navy);">{{ $practiceDesk['pa_count'] ?? 0 }}</div>
                    </div>
                </div>

                @if($practiceDesk['recent_projects']->isEmpty())
                    <p style="margin: 0; color: var(--text-muted); font-size: 0.9rem;">No projects yet. Create one to start the desk.</p>
                @else
                    @foreach($practiceDesk['recent_projects'] as $project)
                        @php
                            $phaseLabel = \App\Models\EngineerProject::PHASES[$project->phase] ?? $project->phase;
                            $projectHref = '/pro/engineer/projects/'.$project->id;
                        @endphp
                        <a href="{{ $projectHref }}" style="display: flex; justify-content: space-between; gap: 1rem; padding: 0.45rem 0; border-bottom: 1px dashed var(--border-light); font-size: 0.9rem; text-decoration: none;">
                            <span style="font-weight: 600; color: var(--primary-navy);">{{ $project->name }}</span>
                            <span style="color: var(--text-muted);">{{ $phaseLabel }}</span>
                        </a>
                    @endforeach
                @endif
            @endif
        </div>
    @endif

    @if($showMoneyOnOverview)
        @if(!empty($deadlines))
            <div style="display: flex; flex-wrap: wrap; gap: 0.5rem; margin-bottom: 1.25rem;">
                @foreach($deadlines as $chip)
                    <a href="{{ $chip['href'] }}" style="display: inline-flex; align-items: center; gap: 0.45rem; text-decoration: none; background: {{ $chip['urgent'] ? '#fffbeb' : 'white' }}; border: 1px solid {{ $chip['urgent'] ? '#fde68a' : 'var(--border-light)' }}; color: {{ $chip['urgent'] ? '#92400e' : 'var(--primary-navy)' }}; padding: 0.45rem 0.75rem; border-radius: var(--radius-md); font-size: 0.8rem; box-shadow: var(--shadow-sm);">
                        <strong style="font-weight: 700;">{{ $chip['label'] }}</strong>
                        <span style="opacity: 0.85;">{{ $chip['hint'] }}</span>
                    </a>
                @endforeach
            </div>
        @endif

        @if($glance)
            <div style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.35rem 1.5rem; box-shadow: var(--shadow-sm); margin-bottom: 1.5rem;">
                <div style="display: flex; justify-content: space-between; align-items: baseline; gap: 1rem; flex-wrap: wrap; margin-bottom: 1rem;">
                    <div style="font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.04em;">{{ $year }} at a glance</div>
                    <a href="/reports" style="font-size: 0.8rem; font-weight: 600; color: var(--primary-cerulean); text-decoration: none;">Open Tax and VAT →</a>
                </div>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 1rem;">
                    <div>
                        <div style="font-size: 0.75rem; color: var(--text-muted);">Billed (fiscal)</div>
                        <div style="font-size: 1.35rem; font-weight: 700; color: #0369a1;">€{{ number_format($glance['fiscal_revenue'], 2) }}</div>
                    </div>
                    <div>
                        <div style="font-size: 0.75rem; color: var(--text-muted);">Profit so far</div>
                        <div style="font-size: 1.35rem; font-weight: 700; color: var(--primary-navy);">€{{ number_format($glance['net_profit'], 2) }}</div>
                    </div>
                    <div>
                        <div style="font-size: 0.75rem; color: var(--text-muted);">Income tax still to set aside</div>
                        <div style="font-size: 1.35rem; font-weight: 700; color: {{ $glance['tax_only_set_aside'] > 0 ? '#b45309' : '#059669' }};">€{{ number_format($glance['tax_only_set_aside'], 2) }}</div>
                    </div>
                    <div>
                        <div style="font-size: 0.75rem; color: var(--text-muted);">SSC still to set aside</div>
                        <div style="font-size: 1.35rem; font-weight: 700; color: {{ $glance['ssc_set_aside'] > 0 ? '#b45309' : '#059669' }};">€{{ number_format($glance['ssc_set_aside'], 2) }}</div>
                    </div>
                    @if($glance['has_article_10'])
                        <div>
                            <div style="font-size: 0.75rem; color: var(--text-muted);">VAT balance</div>
                            <div style="font-size: 1.35rem; font-weight: 700; color: {{ $glance['vat_balance'] > 0 ? '#dc2626' : '#059669' }};">
                                {{ $glance['vat_balance'] < 0 ? 'Refund ' : '' }}€{{ number_format(abs($glance['vat_balance']), 2) }}
                            </div>
                        </div>
                    @endif
                </div>
                <p style="margin: 0.85rem 0 0; font-size: 0.75rem; color: var(--text-muted); line-height: 1.4;">
                    Live draft from your invoices and expenses. Open Tax and VAT for the full breakdown.
                    @if(!empty($glance['ssc_minimum_band']))
                        The SSC figure is the Class 2 minimum band (weekly rate × 52), which can apply even at €0 profit. If your maximum SSC is already paid through primary employment, tick that in Settings → tax setup.
                    @endif
                </p>
            </div>
        @endif
    @endif

    @if($showMoneyOnOverview && !($checklist['all_done'] ?? true))
        <div style="background: #eff6ff; border: 1px solid #bfdbfe; border-radius: var(--radius-lg); padding: 1.25rem 1.5rem; margin-bottom: 1.5rem;">
            <div style="display: flex; justify-content: space-between; gap: 1rem; flex-wrap: wrap; margin-bottom: 0.75rem;">
                <div>
                    <div style="font-weight: 700; color: #1e3a8a;">Get set this week</div>
                    <div style="font-size: 0.8rem; color: #1e40af;">{{ $checklist['complete'] }} of {{ $checklist['total'] }} done</div>
                </div>
            </div>
            <ul style="list-style: none; margin: 0; padding: 0; display: grid; gap: 0.45rem;">
                @foreach($checklist['items'] as $item)
                    <li>
                        <a href="{{ $item['href'] }}" style="display: flex; align-items: center; gap: 0.65rem; text-decoration: none; color: {{ $item['done'] ? '#64748b' : '#1e3a8a' }}; font-size: 0.9rem; font-weight: {{ $item['done'] ? '500' : '600' }};">
                            <span style="width: 1.15rem; height: 1.15rem; border-radius: 999px; border: 2px solid {{ $item['done'] ? '#86efac' : '#93c5fd' }}; background: {{ $item['done'] ? '#dcfce7' : 'white' }}; display: inline-flex; align-items: center; justify-content: center; font-size: 0.7rem; color: #166534;">{{ $item['done'] ? '✓' : '' }}</span>
                            <span style="{{ $item['done'] ? 'text-decoration: line-through; opacity: 0.75;' : '' }}">{{ $item['label'] }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    @if($showMoneyOnOverview)
    {{-- Practice-only: compact free billing strip (not the full finance cockpit) --}}
    @if($practiceOnly && $billing)
        <div style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.15rem 1.35rem; box-shadow: var(--shadow-sm); margin-bottom: 1.5rem;">
            <div style="display: flex; justify-content: space-between; align-items: baseline; gap: 1rem; flex-wrap: wrap; margin-bottom: 0.85rem;">
                <div style="font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.04em;">Free billing</div>
                <a href="/ledger" style="font-size: 0.8rem; font-weight: 600; color: var(--primary-cerulean); text-decoration: none;">Open ledger →</a>
            </div>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); gap: 0.85rem;">
                <div>
                    <div style="font-size: 0.75rem; color: var(--text-muted);">Clients</div>
                    <div style="font-size: 1.15rem; font-weight: 700; color: var(--primary-navy);">{{ $clientsUsed }}/{{ $clientCap }}</div>
                </div>
                <div>
                    <div style="font-size: 0.75rem; color: var(--text-muted);">{{ $year }} invoiced</div>
                    <div style="font-size: 1.15rem; font-weight: 700; color: #0369a1;">€{{ number_format($billing['ytdNetInvoiced'], 2) }}</div>
                </div>
                <div>
                    <div style="font-size: 0.75rem; color: var(--text-muted);">Open invoices</div>
                    <div style="font-size: 1.15rem; font-weight: 700; color: {{ $billing['overdueCount'] > 0 ? '#dc2626' : 'var(--primary-navy)' }};">€{{ number_format($billing['unpaidTotal'], 2) }}</div>
                </div>
            </div>
        </div>
    @endif

    {{-- Standard / Full Pro / Free: full financial KPI grid --}}
    @if(($hasFinancial || $mode === 'free') && $billing)
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(230px, 1fr)); gap: 1.25rem; margin-bottom: 1.5rem;">
            <div style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.35rem; box-shadow: var(--shadow-sm);">
                <div style="color: var(--text-muted); font-size: 0.8rem; font-weight: 700; text-transform: uppercase; margin-bottom: 0.85rem; border-bottom: 1px solid #e2e8f0; padding-bottom: 0.45rem;">Clients</div>
                <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 0.35rem;">
                    <div style="font-size: 0.85rem; color: var(--text-main); font-weight: 600;">Active</div>
                    <div style="font-size: 1.45rem; font-weight: 700; color: var(--primary-navy);">
                        {{ $clientCount }}@if($clientCap !== null)<span style="font-size: 0.9rem; color: var(--text-muted); font-weight: 600;"> / {{ $clientCap }}</span>@endif
                    </div>
                </div>
                @if($archivedCount > 0)
                    <div style="font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.5rem;">{{ $archivedCount }} archived</div>
                @endif
                <a href="/clients" style="font-size: 0.8rem; color: var(--primary-cerulean); text-decoration: none; font-weight: 600;">View clients →</a>
            </div>

            <div style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.35rem; box-shadow: var(--shadow-sm);">
                <div style="color: var(--text-muted); font-size: 0.8rem; font-weight: 700; text-transform: uppercase; margin-bottom: 0.85rem; border-bottom: 1px solid #e2e8f0; padding-bottom: 0.45rem;">{{ $year }} invoiced</div>
                <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 0.75rem;">
                    <div style="font-size: 0.85rem; color: var(--text-main); font-weight: 600;">Net official</div>
                    <div style="font-size: 1.45rem; font-weight: 700; color: #0369a1;">€{{ number_format($billing['ytdNetInvoiced'], 2) }}</div>
                </div>
                <div style="display: flex; justify-content: space-between; font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.35rem;">
                    <span>Cash on tax invoices</span>
                    <span style="font-weight: 600; color: #059669;">€{{ number_format($billing['ytdInvoiceCash'], 2) }}</span>
                </div>
                <div style="display: flex; justify-content: space-between; font-size: 0.8rem; color: var(--text-muted);">
                    <span>Still owed on tax invoices</span>
                    <span style="font-weight: 600; color: #dc2626;">€{{ number_format($billing['ytdOfficialDues'], 2) }}</span>
                </div>
            </div>

            <div style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.35rem; box-shadow: var(--shadow-sm);">
                <div style="color: var(--text-muted); font-size: 0.8rem; font-weight: 700; text-transform: uppercase; margin-bottom: 0.85rem; border-bottom: 1px solid #e2e8f0; padding-bottom: 0.45rem;">Collections</div>
                <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 0.75rem;">
                    <div style="font-size: 0.85rem; color: var(--text-main); font-weight: 600;">Overdue</div>
                    <div style="font-size: 1.45rem; font-weight: 700; color: {{ $billing['overdueCount'] > 0 ? '#dc2626' : 'var(--primary-navy)' }};">€{{ number_format($billing['overdueTotal'], 2) }}</div>
                </div>
                <div style="display: flex; justify-content: space-between; font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.35rem;">
                    <span>{{ $billing['overdueCount'] }} overdue · {{ $billing['unpaidCount'] }} unpaid</span>
                    <span style="font-weight: 600;">€{{ number_format($billing['unpaidTotal'], 2) }} open</span>
                </div>
                <a href="/ledger?status=open&doc_type=invoice" style="font-size: 0.8rem; color: var(--primary-cerulean); text-decoration: none; font-weight: 600;">Review open invoices →</a>
            </div>

            <div style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.35rem; box-shadow: var(--shadow-sm);">
                <div style="color: var(--text-muted); font-size: 0.8rem; font-weight: 700; text-transform: uppercase; margin-bottom: 0.85rem; border-bottom: 1px solid #e2e8f0; padding-bottom: 0.45rem;">Pro-formas (RFP)</div>
                <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 0.75rem;">
                    <div style="font-size: 0.85rem; color: var(--text-main); font-weight: 600;">Not yet invoiced</div>
                    <div style="font-size: 1.45rem; font-weight: 700; color: #4338ca;">€{{ number_format($billing['unbilledPipeline'], 2) }}</div>
                </div>
                <div style="display: flex; justify-content: space-between; font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.5rem;">
                    <span>Cash sitting on RFPs {{ $year }}</span>
                    <span style="font-weight: 600;">€{{ number_format($billing['ytdRfpCash'], 2) }}</span>
                </div>
                <p style="margin: 0; font-size: 0.75rem; color: var(--text-muted); line-height: 1.4;">RFP cash is real money received, but it does <strong>not</strong> count for tax until you convert the RFP into a tax invoice.</p>
            </div>
        </div>
    @endif

    @if(($hasFinancial || $mode === 'free') && $billing)
        @if($clientCount === 0 && $archivedCount === 0 && ! $hasPractice)
            <div style="padding: 3rem; border: 2px dashed var(--border-light); background: rgba(255,255,255,0.5); border-radius: var(--radius-md); text-align: center;">
                <h3 style="color: var(--primary-navy); margin-bottom: 0.5rem;">Start with a client</h3>
                <p style="color: var(--text-muted); margin-bottom: 1.5rem;">Add who you bill, then create your first invoice or RFP.</p>
                <a href="/clients/create" style="display: inline-block; background: var(--primary-cerulean); color: white; text-decoration: none; padding: 0.75rem 1.5rem; border-radius: var(--radius-md); font-weight: 600;">
                    + Add client
                </a>
            </div>
        @elseif($clientCount > 0 || $archivedCount > 0 || $hasFinancial)
            <div class="dash-split" style="display: grid; grid-template-columns: 1.4fr 1fr; gap: 1.25rem;">
                <div style="background: white; border-radius: var(--radius-lg); border: 1px solid var(--border-light); box-shadow: var(--shadow-sm); padding: 1.5rem;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                        <h3 style="color: var(--primary-navy); margin: 0; font-size: 1.05rem;">Open invoices</h3>
                        <a href="/ledger?status=open&doc_type=invoice" style="font-size: 0.8rem; color: var(--primary-cerulean); font-weight: 600; text-decoration: none;">All invoices</a>
                    </div>

                    @if($billing['recentOpen']->isEmpty())
                        <p style="color: var(--text-muted); margin: 0; font-size: 0.9rem;">Nothing outstanding. Nice work.</p>
                    @else
                        <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                            @foreach($billing['recentOpen'] as $doc)
                                <div style="display: flex; justify-content: space-between; gap: 1rem; padding: 0.75rem 0; border-bottom: 1px dashed var(--border-light);">
                                    <div>
                                        <div style="font-weight: 700; color: var(--primary-navy);">{{ $doc->invoice_number }}</div>
                                        <div style="font-size: 0.8rem; color: var(--text-muted);">
                                            {{ $doc->client->name ?? 'Client' }}
                                            · due {{ $doc->due_date?->format('d M Y') ?? '—' }}
                                            @if($doc->is_overdue)
                                                <span style="color: #dc2626; font-weight: 700;"> · OVERDUE</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div style="font-weight: 700; color: {{ $doc->is_overdue ? '#dc2626' : 'var(--primary-navy)' }}; white-space: nowrap;">
                                        €{{ number_format($doc->open_balance, 2) }}
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div style="background: white; border-radius: var(--radius-lg); border: 1px solid var(--border-light); box-shadow: var(--shadow-sm); padding: 1.5rem;">
                    <h3 style="color: var(--primary-navy); margin: 0 0 1rem; font-size: 1.05rem;">Shortcuts</h3>
                    <div style="display: flex; flex-direction: column; gap: 0.65rem;">
                        @if($hasPractice && ($practiceDesk['kind'] ?? null) === 'med')
                            <a href="/pro/medical/patients" style="padding: 0.75rem 1rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); text-decoration: none; color: var(--primary-navy); font-weight: 600; font-size: 0.9rem;">Patients</a>
                            <a href="/pro/medical/stampables" style="padding: 0.75rem 1rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); text-decoration: none; color: var(--primary-navy); font-weight: 600; font-size: 0.9rem;">Documents</a>
                        @elseif($hasPractice && ($practiceDesk['kind'] ?? null) === 'arch')
                            <a href="/pro/architect/projects" style="padding: 0.75rem 1rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); text-decoration: none; color: var(--primary-navy); font-weight: 600; font-size: 0.9rem;">Portfolio & map</a>
                            <a href="/pro/architect/condition-reports" style="padding: 0.75rem 1rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); text-decoration: none; color: var(--primary-navy); font-weight: 600; font-size: 0.9rem;">Condition reports</a>
                            <a href="/pro/architect/method-statements" style="padding: 0.75rem 1rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); text-decoration: none; color: var(--primary-navy); font-weight: 600; font-size: 0.9rem;">Method statements</a>
                            <a href="/pro/architect/templates" style="padding: 0.75rem 1rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); text-decoration: none; color: var(--primary-navy); font-weight: 600; font-size: 0.9rem;">BCA templates</a>
                        @elseif($hasPractice && ($practiceDesk['kind'] ?? null) === 'eng')
                            <a href="/clients" style="padding: 0.75rem 1rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); text-decoration: none; color: var(--primary-navy); font-weight: 600; font-size: 0.9rem;">Engineering clients</a>
                            <a href="/pro/engineer/projects" style="padding: 0.75rem 1rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); text-decoration: none; color: var(--primary-navy); font-weight: 600; font-size: 0.9rem;">Engineering projects</a>
                            <a href="/pro/engineer/certificates" style="padding: 0.75rem 1rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); text-decoration: none; color: var(--primary-navy); font-weight: 600; font-size: 0.9rem;">Field certificates</a>
                        @endif
                        <a href="/ledger/create" style="padding: 0.75rem 1rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); text-decoration: none; color: var(--primary-navy); font-weight: 600; font-size: 0.9rem;">Create invoice or RFP</a>
                        <a href="/clients" style="padding: 0.75rem 1rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); text-decoration: none; color: var(--primary-navy); font-weight: 600; font-size: 0.9rem;">Manage clients</a>
                        @if($hasFinancial)
                            <a href="/reports" style="padding: 0.75rem 1rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); text-decoration: none; color: var(--primary-navy); font-weight: 600; font-size: 0.9rem;">Tax and VAT report</a>
                            <a href="/expenses" style="padding: 0.75rem 1rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); text-decoration: none; color: var(--primary-navy); font-weight: 600; font-size: 0.9rem;">Expense ledger</a>
                            <a href="/settings#tax-setup" style="padding: 0.75rem 1rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); text-decoration: none; color: var(--primary-navy); font-weight: 600; font-size: 0.9rem;">Tax setup</a>
                        @endif
                    </div>
                </div>
            </div>
        @endif
    @elseif($practiceOnly)
        <div style="background: white; border-radius: var(--radius-lg); border: 1px solid var(--border-light); box-shadow: var(--shadow-sm); padding: 1.5rem;">
            <h3 style="color: var(--primary-navy); margin: 0 0 1rem; font-size: 1.05rem;">Shortcuts</h3>
            <div style="display: flex; flex-direction: column; gap: 0.65rem;">
                @if(($practiceDesk['kind'] ?? null) === 'med')
                    <a href="/pro/medical/patients" style="padding: 0.75rem 1rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); text-decoration: none; color: var(--primary-navy); font-weight: 600; font-size: 0.9rem;">Patients</a>
                    <a href="/pro/medical/stampables" style="padding: 0.75rem 1rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); text-decoration: none; color: var(--primary-navy); font-weight: 600; font-size: 0.9rem;">Documents</a>
                @elseif(($practiceDesk['kind'] ?? null) === 'arch')
                    <a href="/pro/architect/projects" style="padding: 0.75rem 1rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); text-decoration: none; color: var(--primary-navy); font-weight: 600; font-size: 0.9rem;">Portfolio & map</a>
                    <a href="/pro/architect/condition-reports" style="padding: 0.75rem 1rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); text-decoration: none; color: var(--primary-navy); font-weight: 600; font-size: 0.9rem;">Condition reports</a>
                    <a href="/pro/architect/method-statements" style="padding: 0.75rem 1rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); text-decoration: none; color: var(--primary-navy); font-weight: 600; font-size: 0.9rem;">Method statements</a>
                @elseif(($practiceDesk['kind'] ?? null) === 'eng')
                    <a href="/clients" style="padding: 0.75rem 1rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); text-decoration: none; color: var(--primary-navy); font-weight: 600; font-size: 0.9rem;">Engineering clients</a>
                    <a href="/pro/engineer/projects" style="padding: 0.75rem 1rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); text-decoration: none; color: var(--primary-navy); font-weight: 600; font-size: 0.9rem;">Engineering projects</a>
                    <a href="/pro/engineer/certificates" style="padding: 0.75rem 1rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); text-decoration: none; color: var(--primary-navy); font-weight: 600; font-size: 0.9rem;">Field certificates</a>
                @endif
                <a href="/clients" style="padding: 0.75rem 1rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); text-decoration: none; color: var(--primary-navy); font-weight: 600; font-size: 0.9rem;">Billing clients (Free layer)</a>
                <a href="/ledger/create" style="padding: 0.75rem 1rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); text-decoration: none; color: var(--primary-navy); font-weight: 600; font-size: 0.9rem;">Create invoice or RFP</a>
                <a href="/settings#plan" style="padding: 0.75rem 1rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); text-decoration: none; color: var(--text-muted); font-weight: 600; font-size: 0.9rem;">Upgrade for Tax and VAT</a>
            </div>
        </div>
    @endif
    @endif

    <style>
        @media (max-width: 800px) {
            .dash-split { grid-template-columns: 1fr !important; }
        }
    </style>
@endsection
