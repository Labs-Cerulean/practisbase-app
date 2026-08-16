@extends('layouts.app')

@section('page_title', 'PractisBase dashboard')

@section('content')
    @php
        $t = $kpi['totals'];
        $a = $kpi['access'];
        $u = $kpi['usage'];
        $v = $kpi['volume'];
        $b = $kpi['beta_codes'];
    @endphp

    <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.25rem;">
        <div>
            <div style="font-size: 0.75rem; font-weight: 700; letter-spacing: 0.04em; text-transform: uppercase; color: var(--text-muted); margin-bottom: 0.25rem;">Company only · PractisBase</div>
            <h1 style="font-size: 1.5rem; color: var(--primary-navy); margin: 0 0 0.25rem;">Platform dashboard</h1>
            <p style="color: var(--text-muted); font-size: 0.95rem; margin: 0;">Users, plans, usage, and access economics. Updated {{ $kpi['generated_at']->timezone(config('app.timezone'))->format('d M Y H:i') }}.</p>
        </div>
        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
            <a href="/company/beta-invites" style="background: white; color: var(--primary-navy); border: 1px solid var(--border-light); padding: 0.55rem 1rem; border-radius: var(--radius-md); font-weight: 600; font-size: 0.85rem; text-decoration: none;">Access codes</a>
            <a href="/company/promotions" style="background: white; color: var(--primary-navy); border: 1px solid var(--border-light); padding: 0.55rem 1rem; border-radius: var(--radius-md); font-weight: 600; font-size: 0.85rem; text-decoration: none;">Promotions</a>
            <a href="/community/feedback/inbox" style="background: var(--primary-cerulean); color: white; padding: 0.55rem 1rem; border-radius: var(--radius-md); font-weight: 600; font-size: 0.85rem; text-decoration: none;">Feedback inbox</a>
        </div>
    </div>

    <div style="background: #fffbeb; border: 1px solid #fde68a; border-radius: var(--radius-lg); padding: 0.85rem 1.1rem; margin-bottom: 1.25rem; font-size: 0.85rem; color: #92400e; line-height: 1.45;">
        Card billing is not live yet. <strong>List MRR</strong> is a planning proxy from selected plans × public list prices (ex-VAT). Mark fake accounts as <strong>Test</strong> (drop from counts and MRR). Mark real beta accounts as <strong>Beta</strong> (stay in counts, drop from MRR).
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 0.85rem; margin-bottom: 1.25rem;">
        @foreach([
            ['Users', number_format($t['users']), 'Counted end users (test excluded)'],
            ['Paid plans', number_format($t['paid']), 'Any non-free tier'],
            ['Free', number_format($t['free']), 'Free financial layer'],
            ['Active 7d', number_format($t['active_7d']), 'Sessions in last 7 days'],
            ['Signups 7d', number_format($t['signups_7d']), 'New accounts'],
            ['Onboarded', $t['onboarding_rate'].'%', number_format($t['onboarded']).' complete'],
            ['List MRR', '€'.number_format($a['list_mrr_ex_vat'], 2), 'Proxy ex-VAT · beta/test excluded'],
            ['Beta / Test', number_format($t['beta_users']).' / '.number_format($t['excluded_test_users']), 'Beta in counts · Test hidden'],
        ] as [$label, $value, $hint])
            <div style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1rem 1.1rem; box-shadow: var(--shadow-sm);">
                <div style="font-size: 0.72rem; font-weight: 700; letter-spacing: 0.04em; text-transform: uppercase; color: var(--text-muted);">{{ $label }}</div>
                <div style="font-size: 1.45rem; font-weight: 700; color: var(--primary-navy); margin-top: 0.25rem;">{{ $value }}</div>
                <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.3rem; line-height: 1.35;">{{ $hint }}</div>
            </div>
        @endforeach
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1rem; margin-bottom: 1.25rem;">
        <div style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.25rem 1.35rem; box-shadow: var(--shadow-sm);">
            <div style="font-weight: 700; color: var(--primary-navy); margin-bottom: 0.85rem;">Plan mix</div>
            <div style="display: grid; gap: 0.55rem;">
                @foreach($kpi['tier_cards'] as $card)
                    @if($card['count'] > 0 || in_array($card['tier'], ['free','standard','practice-med','practice-arch','practice-eng','pro-med','pro-arch','pro-eng'], true))
                        <div style="display: flex; justify-content: space-between; gap: 0.75rem; align-items: baseline; font-size: 0.9rem;">
                            <div>
                                <div style="font-weight: 600; color: var(--primary-navy);">{{ $card['label'] }}</div>
                                <div style="font-size: 0.75rem; color: var(--text-muted);">
                                    @if($card['unit_price'] > 0)
                                        €{{ number_format($card['unit_price'], 2) }}/mo list · €{{ number_format($card['list_mrr'], 2) }} MRR
                                        @if(($card['mrr_count'] ?? $card['count']) !== $card['count'])
                                            ({{ number_format($card['mrr_count']) }} billed proxy)
                                        @endif
                                    @else
                                        No list price
                                    @endif
                                </div>
                            </div>
                            <div style="font-weight: 700; color: var(--primary-navy);">{{ number_format($card['count']) }}</div>
                        </div>
                    @endif
                @endforeach
            </div>
            <p style="margin: 0.9rem 0 0; font-size: 0.75rem; color: var(--text-muted); line-height: 1.4;">
                List MRR inc VAT proxy: €{{ number_format($a['list_mrr_inc_vat'], 2) }}. Active 30d: {{ number_format($t['active_30d']) }}. Signups 30d: {{ number_format($t['signups_30d']) }}.
            </p>
        </div>

        <div style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.25rem 1.35rem; box-shadow: var(--shadow-sm);">
            <div style="font-weight: 700; color: var(--primary-navy); margin-bottom: 0.85rem;">Access and wallet</div>
            <div style="display: grid; gap: 0.65rem; font-size: 0.9rem;">
                <div style="display: flex; justify-content: space-between;"><span style="color: var(--text-muted);">Access code unlocks</span><strong>{{ number_format($a['beta_unlocked']) }}</strong></div>
                <div style="display: flex; justify-content: space-between;"><span style="color: var(--text-muted);">Promo applied</span><strong>{{ number_format($a['promo_applied']) }}</strong></div>
                <div style="display: flex; justify-content: space-between;"><span style="color: var(--text-muted);">Referral credit wallet</span><strong>€{{ number_format($a['credit_balance'], 2) }}</strong></div>
                <div style="display: flex; justify-content: space-between;"><span style="color: var(--text-muted);">Referrals pending payment</span><strong>{{ number_format($kpi['referrals']['pending_payment']) }}</strong></div>
                <div style="display: flex; justify-content: space-between;"><span style="color: var(--text-muted);">Referrals rewarded</span><strong>{{ number_format($kpi['referrals']['rewarded']) }}</strong></div>
                <div style="display: flex; justify-content: space-between;"><span style="color: var(--text-muted);">Codes available</span><strong>{{ number_format($b['available']) }}</strong></div>
                <div style="display: flex; justify-content: space-between;"><span style="color: var(--text-muted);">Codes redeemed / revoked</span><strong>{{ number_format($b['redeemed']) }} / {{ number_format($b['revoked']) }}</strong></div>
                <div style="display: flex; justify-content: space-between;"><span style="color: var(--text-muted);">Backups overdue (&gt;7d)</span><strong style="color: {{ $t['backup_overdue'] > 0 ? '#b45309' : '#059669' }};">{{ number_format($t['backup_overdue']) }}</strong></div>
            </div>
        </div>

        <div style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.25rem 1.35rem; box-shadow: var(--shadow-sm);">
            <div style="font-weight: 700; color: var(--primary-navy); margin-bottom: 0.85rem;">Profession mix</div>
            @if(empty($kpi['by_profession']))
                <p style="margin: 0; color: var(--text-muted); font-size: 0.9rem;">No users yet.</p>
            @else
                <div style="display: grid; gap: 0.5rem;">
                    @foreach($kpi['by_profession'] as $profession => $count)
                        <div style="display: flex; justify-content: space-between; gap: 0.75rem; font-size: 0.9rem;">
                            <span style="color: var(--text-muted);">{{ $profession }}</span>
                            <strong style="color: var(--primary-navy);">{{ number_format($count) }}</strong>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1rem; margin-bottom: 1.25rem;">
        <div style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.25rem 1.35rem; box-shadow: var(--shadow-sm);">
            <div style="font-weight: 700; color: var(--primary-navy); margin-bottom: 0.85rem;">Activation (users who used…)</div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.65rem 1rem; font-size: 0.88rem;">
                <div>Clients <strong style="float: right;">{{ number_format($u['with_clients']) }}</strong></div>
                <div>Invoices <strong style="float: right;">{{ number_format($u['with_invoices']) }}</strong></div>
                <div>Expenses <strong style="float: right;">{{ number_format($u['with_expenses']) }}</strong></div>
                <div>Stamper <strong style="float: right;">{{ number_format($u['with_stamps']) }}</strong></div>
                <div>Patients <strong style="float: right;">{{ number_format($u['with_patients']) }}</strong></div>
                <div>Arch projects <strong style="float: right;">{{ number_format($u['with_arch_projects']) }}</strong></div>
                <div>Eng projects <strong style="float: right;">{{ number_format($u['with_eng_projects']) }}</strong></div>
                <div>Certificates <strong style="float: right;">{{ number_format($u['with_certificates']) }}</strong></div>
            </div>
        </div>

        <div style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.25rem 1.35rem; box-shadow: var(--shadow-sm);">
            <div style="font-weight: 700; color: var(--primary-navy); margin-bottom: 0.85rem;">Platform volume</div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.65rem 1rem; font-size: 0.88rem;">
                <div>Clients <strong style="float: right;">{{ number_format($v['clients']) }}</strong></div>
                <div>Fiscal invoices <strong style="float: right;">{{ number_format($v['invoices']) }}</strong></div>
                <div>RFPs <strong style="float: right;">{{ number_format($v['rfps']) }}</strong></div>
                <div>Expenses <strong style="float: right;">{{ number_format($v['expenses']) }}</strong></div>
                <div>Patients <strong style="float: right;">{{ number_format($v['patients']) }}</strong></div>
                <div>Stampables issued <strong style="float: right;">{{ number_format($v['clinical_issued']) }}</strong></div>
                <div>Arch / eng projects <strong style="float: right;">{{ number_format($v['arch_projects'] + $v['eng_projects']) }}</strong></div>
                <div>Certs stamped <strong style="float: right;">{{ number_format($v['certificates_stamped']) }}</strong></div>
                <div>Stamp profiles <strong style="float: right;">{{ number_format($v['document_stamps']) }}</strong></div>
                <div>Open feedback <strong style="float: right;">{{ number_format($v['feedback_open']) }}</strong></div>
            </div>
        </div>
    </div>

    @if(count($kpi['promotions']) > 0)
        <div style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.25rem 1.35rem; box-shadow: var(--shadow-sm); margin-bottom: 1.25rem;">
            <div style="font-weight: 700; color: var(--primary-navy); margin-bottom: 0.85rem;">Promotions</div>
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; font-size: 0.85rem;">
                    <thead>
                        <tr style="text-align: left; color: var(--text-muted); border-bottom: 1px solid var(--border-light);">
                            <th style="padding: 0.5rem 0.4rem;">Code</th>
                            <th style="padding: 0.5rem 0.4rem;">Type</th>
                            <th style="padding: 0.5rem 0.4rem;">Used</th>
                            <th style="padding: 0.5rem 0.4rem;">Remaining</th>
                            <th style="padding: 0.5rem 0.4rem;">Status</th>
                            <th style="padding: 0.5rem 0.4rem;">Expires</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($kpi['promotions'] as $promo)
                            <tr style="border-bottom: 1px solid var(--border-light);">
                                <td style="padding: 0.55rem 0.4rem; font-weight: 600; color: var(--primary-navy);">{{ $promo['code'] }}</td>
                                <td style="padding: 0.55rem 0.4rem;">{{ $promo['type'] }} · {{ $promo['value'] }}</td>
                                <td style="padding: 0.55rem 0.4rem;">{{ $promo['used'] }}{{ $promo['max'] !== null ? ' / '.$promo['max'] : '' }}</td>
                                <td style="padding: 0.55rem 0.4rem;">{{ $promo['remaining'] === null ? 'Unlimited' : $promo['remaining'] }}</td>
                                <td style="padding: 0.55rem 0.4rem;">{{ $promo['is_active'] ? 'Active' : 'Off' }}</td>
                                <td style="padding: 0.55rem 0.4rem;">{{ $promo['expires_at'] ?: '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <div style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.25rem 1.35rem; box-shadow: var(--shadow-sm);">
        <div style="display: flex; justify-content: space-between; align-items: baseline; gap: 1rem; flex-wrap: wrap; margin-bottom: 0.85rem;">
            <div>
                <div style="font-weight: 700; color: var(--primary-navy);">Users</div>
                <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.2rem;">{{ $users->total() }} shown · {{ number_format($t['beta_users']) }} beta · {{ number_format($t['excluded_test_users']) }} test</div>
            </div>
            <div style="display: flex; gap: 0.4rem; flex-wrap: wrap;">
                @foreach(['all' => 'All', 'counted' => 'In MRR', 'beta' => 'Beta', 'test' => 'Test'] as $key => $label)
                    <a href="/company/platform?view={{ $key }}" style="padding: 0.35rem 0.7rem; border-radius: var(--radius-md); font-size: 0.78rem; font-weight: 600; text-decoration: none; border: 1px solid var(--border-light); {{ ($userView ?? 'all') === $key ? 'background: var(--primary-navy); color: white; border-color: var(--primary-navy);' : 'background: white; color: var(--primary-navy);' }}">{{ $label }}</a>
                @endforeach
            </div>
        </div>
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; font-size: 0.82rem; min-width: 980px;">
                <thead>
                    <tr style="text-align: left; color: var(--text-muted); border-bottom: 1px solid var(--border-light);">
                        <th style="padding: 0.5rem 0.4rem;">User</th>
                        <th style="padding: 0.5rem 0.4rem;">Plan</th>
                        <th style="padding: 0.5rem 0.4rem;">Access</th>
                        <th style="padding: 0.5rem 0.4rem;">Usage</th>
                        <th style="padding: 0.5rem 0.4rem;">MRR €</th>
                        <th style="padding: 0.5rem 0.4rem;">Joined</th>
                        <th style="padding: 0.5rem 0.4rem;">Last active</th>
                        <th style="padding: 0.5rem 0.4rem;">Cohort</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $row)
                        <tr style="border-bottom: 1px solid var(--border-light); vertical-align: top; {{ $row['cohort'] === 'test' ? 'background: #f8fafc;' : ($row['cohort'] === 'beta' ? 'background: #eff6ff;' : '') }}">
                            <td style="padding: 0.65rem 0.4rem;">
                                <div style="font-weight: 600; color: var(--primary-navy);">
                                    {{ $row['name'] }}
                                    @if($row['cohort'] === 'test')
                                        <span style="font-size: 0.68rem; font-weight: 700; letter-spacing: 0.03em; text-transform: uppercase; background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; padding: 0.1rem 0.4rem; border-radius: 999px; margin-left: 0.35rem;">Test</span>
                                    @elseif($row['cohort'] === 'beta')
                                        <span style="font-size: 0.68rem; font-weight: 700; letter-spacing: 0.03em; text-transform: uppercase; background: #dbeafe; color: #1e40af; border: 1px solid #bfdbfe; padding: 0.1rem 0.4rem; border-radius: 999px; margin-left: 0.35rem;">Beta</span>
                                    @endif
                                </div>
                                <div style="color: var(--text-muted);">{{ $row['email'] }}</div>
                                <div style="color: var(--text-muted);">{{ $row['profession'] }}</div>
                            </td>
                            <td style="padding: 0.65rem 0.4rem;">
                                <div style="font-weight: 600;">{{ $row['tier_label'] }}</div>
                                @if($row['promo_code'])
                                    <div style="color: var(--text-muted);">Promo {{ $row['promo_code'] }}</div>
                                @endif
                                @if($row['credit_balance'] > 0)
                                    <div style="color: #059669;">Credit €{{ number_format($row['credit_balance'], 2) }}</div>
                                @endif
                            </td>
                            <td style="padding: 0.65rem 0.4rem;">
                                <div>{{ $row['access'] }}</div>
                                @if($row['beta_code'])
                                    <div style="color: var(--text-muted);">{{ $row['beta_code'] }}</div>
                                @endif
                                @if($row['trial_ends_at'])
                                    <div style="color: var(--text-muted);">Trial to {{ $row['trial_ends_at']->format('d M Y') }}</div>
                                @endif
                            </td>
                            <td style="padding: 0.65rem 0.4rem;">
                                {{ $row['clients'] }} clients · {{ $row['invoices'] }} inv · {{ $row['expenses'] }} exp
                                @if($row['backup_overdue'])
                                    <div style="color: #b45309;">Backup overdue</div>
                                @endif
                            </td>
                            <td style="padding: 0.65rem 0.4rem;">
                                @if($row['list_price'] > 0)
                                    €{{ number_format($row['list_price'], 2) }}
                                @elseif($row['plan_price'] > 0)
                                    <span style="color: var(--text-muted);">€{{ number_format($row['plan_price'], 2) }} off</span>
                                @else
                                    —
                                @endif
                            </td>
                            <td style="padding: 0.65rem 0.4rem;">{{ optional($row['created_at'])->format('d M Y') }}</td>
                            <td style="padding: 0.65rem 0.4rem;">
                                {{ $row['last_activity'] ? $row['last_activity']->diffForHumans() : '—' }}
                            </td>
                            <td style="padding: 0.65rem 0.4rem;">
                                <div style="display: flex; flex-direction: column; gap: 0.3rem;">
                                    @foreach(['counted' => 'Counted', 'beta' => 'Beta', 'test' => 'Test'] as $cohort => $label)
                                        <form action="/company/platform/users/{{ $row['id'] }}/kpi-cohort?view={{ urlencode($userView ?? 'all') }}" method="POST" style="margin: 0;">
                                            @csrf
                                            <input type="hidden" name="cohort" value="{{ $cohort }}">
                                            <button type="submit" {{ $row['cohort'] === $cohort ? 'disabled' : '' }} style="width: 100%; padding: 0.3rem 0.55rem; border-radius: var(--radius-md); font-size: 0.72rem; font-weight: 700; cursor: {{ $row['cohort'] === $cohort ? 'default' : 'pointer' }}; {{ $row['cohort'] === $cohort ? 'background: var(--primary-navy); color: white; border: 1px solid var(--primary-navy);' : 'background: white; color: var(--primary-navy); border: 1px solid var(--border-light);' }}">
                                                {{ $label }}
                                            </button>
                                        </form>
                                    @endforeach
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" style="padding: 1rem 0.4rem; color: var(--text-muted);">No users in this view.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($users->hasPages())
            <div style="margin-top: 1rem; display: flex; gap: 0.5rem; flex-wrap: wrap;">
                @if($users->onFirstPage())
                    <span style="padding: 0.4rem 0.75rem; color: var(--text-muted); border: 1px solid var(--border-light); border-radius: var(--radius-md); font-size: 0.8rem;">Previous</span>
                @else
                    <a href="{{ $users->previousPageUrl() }}" style="padding: 0.4rem 0.75rem; color: var(--primary-navy); border: 1px solid var(--border-light); border-radius: var(--radius-md); font-size: 0.8rem; text-decoration: none; font-weight: 600;">Previous</a>
                @endif
                @if($users->hasMorePages())
                    <a href="{{ $users->nextPageUrl() }}" style="padding: 0.4rem 0.75rem; color: var(--primary-navy); border: 1px solid var(--border-light); border-radius: var(--radius-md); font-size: 0.8rem; text-decoration: none; font-weight: 600;">Next</a>
                @else
                    <span style="padding: 0.4rem 0.75rem; color: var(--text-muted); border: 1px solid var(--border-light); border-radius: var(--radius-md); font-size: 0.8rem;">Next</span>
                @endif
            </div>
        @endif
    </div>
@endsection
