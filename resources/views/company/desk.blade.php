@extends('layouts.app')

@section('page_title', 'Cerulean Labs')

@section('content')
    <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.25rem;">
        <div style="display: flex; gap: 1rem; align-items: flex-start;">
            @if($profile->logoDataUri())
                <img src="{{ $profile->logoDataUri() }}" alt="Company logo" style="max-height: 56px; max-width: 140px; object-fit: contain;">
            @endif
            <div>
                <div style="font-size: 0.75rem; font-weight: 700; letter-spacing: 0.04em; text-transform: uppercase; color: var(--text-muted); margin-bottom: 0.25rem;">Internal company desk</div>
                <h1 style="font-size: 1.5rem; color: var(--primary-navy); margin: 0 0 0.25rem;">{{ $profile->legal_name }}</h1>
                <p style="color: var(--text-muted); font-size: 0.95rem; margin: 0;">
                    {{ $profile->registration_number }} · First period {{ $periodLabel }} · Art 10 · {{ ucfirst($profile->vat_filing_frequency) }} VAT
                </p>
            </div>
        </div>
        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
            <a href="/company/invoices/create" style="background: var(--primary-cerulean); color: white; padding: 0.55rem 1rem; border-radius: var(--radius-md); font-weight: 600; font-size: 0.85rem; text-decoration: none;">+ Invoice / RFP</a>
            <a href="/company/expenses/create" style="background: white; color: var(--primary-navy); border: 1px solid var(--border-light); padding: 0.55rem 1rem; border-radius: var(--radius-md); font-weight: 600; font-size: 0.85rem; text-decoration: none;">+ Expense</a>
            <a href="/company/clients/create" style="background: white; color: var(--primary-navy); border: 1px solid var(--border-light); padding: 0.55rem 1rem; border-radius: var(--radius-md); font-weight: 600; font-size: 0.85rem; text-decoration: none;">+ Client</a>
        </div>
    </div>

    <div style="background: #eff6ff; border: 1px solid #bfdbfe; border-radius: var(--radius-lg); padding: 0.85rem 1.1rem; margin-bottom: 1.25rem; font-size: 0.85rem; color: #1e3a8a; line-height: 1.45;">
        This login is locked to Cerulean Labs Ltd company books (Art 10 double-entry ledger). Sole-trader Tax &amp; VAT / SSC screens stay off so you cannot mix the two.
    </div>

    @if(! $profile->shareCapitalReceived())
        <div style="background: #fffbeb; border: 1px solid #fde68a; border-radius: var(--radius-lg); padding: 1.1rem 1.25rem; margin-bottom: 1.25rem;">
            <div style="font-weight: 700; color: #92400e; margin-bottom: 0.35rem;">Share capital €{{ number_format((float) $profile->share_capital_eur, 2) }} not yet marked at BOV</div>
            <p style="margin: 0 0 0.75rem; font-size: 0.85rem; color: #78350f; line-height: 1.45;">
                When BOV posts the formation funds into the company account, record the date here.
            </p>
            <form method="POST" action="/company/profile/capital-received" style="display: flex; flex-wrap: wrap; gap: 0.5rem; align-items: end;">
                @csrf
                <div>
                    <label style="display: block; font-size: 0.75rem; font-weight: 600; color: #92400e; margin-bottom: 0.25rem;">Received date</label>
                    <input type="date" name="share_capital_received_at" value="{{ date('Y-m-d') }}" max="{{ date('Y-m-d') }}" min="{{ $profile->first_period_start->format('Y-m-d') }}" required style="padding: 0.5rem 0.65rem; border: 1px solid #fde68a; border-radius: var(--radius-md);">
                </div>
                <button type="submit" style="background: #b45309; color: white; border: none; padding: 0.55rem 1rem; border-radius: var(--radius-md); font-weight: 600; font-size: 0.85rem; cursor: pointer;">Mark capital received</button>
            </form>
        </div>
    @endif

    @if(! $profile->hasVatNumber())
        <div style="background: #fef2f2; border: 1px solid #fecaca; border-radius: var(--radius-lg); padding: 0.85rem 1.1rem; margin-bottom: 1.25rem; font-size: 0.85rem; color: #991b1b; line-height: 1.45;">
            VAT number not on file yet.
            <a href="/company/profile" style="color: #991b1b; font-weight: 700;">Add it in Company profile</a>
            before issuing tax invoices with 18% VAT. RFPs are fine meanwhile.
        </div>
    @endif

    <div style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.35rem 1.5rem; box-shadow: var(--shadow-sm); margin-bottom: 1.5rem;">
        <div style="font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.04em; margin-bottom: 1rem;">{{ $year }} monthly glance · {{ date('F') }}</div>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 1rem;">
            <div>
                <div style="font-size: 0.75rem; color: var(--text-muted);">Billed this month</div>
                <div style="font-size: 1.35rem; font-weight: 700; color: #0369a1;">€{{ number_format($monthBilled, 2) }}</div>
            </div>
            <div>
                <div style="font-size: 0.75rem; color: var(--text-muted);">Billed YTD (fiscal)</div>
                <div style="font-size: 1.35rem; font-weight: 700; color: var(--primary-navy);">€{{ number_format($netBilled, 2) }}</div>
            </div>
            <div>
                <div style="font-size: 0.75rem; color: var(--text-muted);">Cash collected YTD</div>
                <div style="font-size: 1.35rem; font-weight: 700; color: #059669;">€{{ number_format($collected, 2) }}</div>
            </div>
            <div>
                <div style="font-size: 0.75rem; color: var(--text-muted);">Expenses ex-VAT YTD</div>
                <div style="font-size: 1.35rem; font-weight: 700; color: var(--primary-navy);">€{{ number_format($expensesExVat, 2) }}</div>
            </div>
            <div>
                <div style="font-size: 0.75rem; color: var(--text-muted);">VAT balance (draft)</div>
                <div style="font-size: 1.35rem; font-weight: 700; color: {{ $vatBalance > 0 ? '#b45309' : '#059669' }};">€{{ number_format($vatBalance, 2) }}</div>
            </div>
            <div>
                <div style="font-size: 0.75rem; color: var(--text-muted);">Net profit (YTD books)</div>
                <div style="font-size: 1.35rem; font-weight: 700; color: {{ $netProfit >= 0 ? '#059669' : '#b91c1c' }};">€{{ number_format($netProfit, 2) }}</div>
            </div>
            <div>
                <div style="font-size: 0.75rem; color: var(--text-muted);">BOV ledger balance</div>
                <div style="font-size: 1.35rem; font-weight: 700; color: var(--primary-navy);">€{{ number_format($bankBalance, 2) }}</div>
            </div>
            <div>
                <div style="font-size: 0.75rem; color: var(--text-muted);">Balance sheet</div>
                <div style="font-size: 1.05rem; font-weight: 700; color: {{ $booksBalanced ? '#059669' : '#b91c1c' }};">{{ $booksBalanced ? 'Balanced' : 'Out of balance' }}</div>
            </div>
        </div>
        <p style="margin: 0.85rem 0 0; font-size: 0.75rem; color: var(--text-muted); line-height: 1.4;">
            Operational billing plus posted double-entry journals. Output VAT €{{ number_format($outputVat, 2) }} − input VAT €{{ number_format($inputVat, 2) }}.
            Open RFPs: {{ $openRfps }}. Share capital: {{ $profile->shareCapitalReceived() ? 'received '.$profile->share_capital_received_at->format('d M Y') : 'pending at BOV' }}.
        </p>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1rem;">
        <a href="/company/accounts" style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.1rem 1.25rem; text-decoration: none; box-shadow: var(--shadow-sm);">
            <div style="font-weight: 700; color: var(--primary-navy);">Accounts</div>
            <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.25rem;">TB · P&amp;L · Balance sheet · journals</div>
        </a>
        <a href="/company/invoices" style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.1rem 1.25rem; text-decoration: none; box-shadow: var(--shadow-sm);">
            <div style="font-weight: 700; color: var(--primary-navy);">Invoices &amp; RFPs</div>
            <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.25rem;">Bill B2B clients · convert RFPs · PDF</div>
        </a>
        <a href="/company/recurring" style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.1rem 1.25rem; text-decoration: none; box-shadow: var(--shadow-sm);">
            <div style="font-weight: 700; color: var(--primary-navy);">Monthly billing</div>
            <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.25rem;">Recurring B2B invoice schedules</div>
        </a>
        <a href="/company/expenses" style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.1rem 1.25rem; text-decoration: none; box-shadow: var(--shadow-sm);">
            <div style="font-weight: 700; color: var(--primary-navy);">Expenses</div>
            <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.25rem;">Photo/PDF · director loan postings</div>
        </a>
        <a href="/company/bank" style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.1rem 1.25rem; text-decoration: none; box-shadow: var(--shadow-sm);">
            <div style="font-weight: 700; color: var(--primary-navy);">Bank recon</div>
            <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.25rem;">Match BOV lines to the ledger</div>
        </a>
        <a href="/company/dividends" style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.1rem 1.25rem; text-decoration: none; box-shadow: var(--shadow-sm);">
            <div style="font-weight: 700; color: var(--primary-navy);">Dividends</div>
            <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.25rem;">Declare · pay from retained earnings</div>
        </a>
        <a href="/company/clients" style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.1rem 1.25rem; text-decoration: none; box-shadow: var(--shadow-sm);">
            <div style="font-weight: 700; color: var(--primary-navy);">Clients</div>
            <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.25rem;">Company customers + statements</div>
        </a>
        <a href="/company/profile" style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.1rem 1.25rem; text-decoration: none; box-shadow: var(--shadow-sm);">
            <div style="font-weight: 700; color: var(--primary-navy);">Company profile</div>
            <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.25rem;">VAT number · IBAN · letterhead details</div>
        </a>
        <a href="/company/promotions" style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.1rem 1.25rem; text-decoration: none; box-shadow: var(--shadow-sm);">
            <div style="font-weight: 700; color: var(--primary-navy);">Promotions</div>
            <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.25rem;">Mint Founding 50 · toggle capacity · discounts</div>
        </a>
        <a href="/company/beta-invites" style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.1rem 1.25rem; text-decoration: none; box-shadow: var(--shadow-sm);">
            <div style="font-weight: 700; color: var(--primary-navy);">Access codes</div>
            <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.25rem;">Profession-locked Pro codes (free unlock)</div>
        </a>
    </div>
@endsection
