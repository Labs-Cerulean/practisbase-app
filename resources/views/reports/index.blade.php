@extends('layouts.app')

@section('page_title', 'Fiscal Report')

@section('content')

    @if(session('success'))
        <div style="background: #ecfdf5; color: #065f46; padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1.5rem; border: 1px solid #a7f3d0;">
            {{ session('success') }}
        </div>
    @endif
    @if($errors->has('fiscal_error'))
        <div style="background: #fef2f2; color: #991b1b; padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1.5rem; border: 1px solid #fecaca; font-weight: 600;">
            {{ $errors->first('fiscal_error') }}
        </div>
    @endif

    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <div>
            <h1 style="font-size: 1.5rem; color: var(--primary-navy); margin-bottom: 0.25rem;">
                Fiscal Report: {{ $selectedYear }}
                @if($isYearClosed)
                    <span style="background: #e2e8f0; color: #475569; font-size: 0.75rem; padding: 0.25rem 0.5rem; border-radius: 4px; vertical-align: middle; margin-left: 0.5rem;">🔒 CLOSED & FINAL</span>
                @else
                    <span style="background: #fef08a; color: #854d0e; font-size: 0.75rem; padding: 0.25rem 0.5rem; border-radius: 4px; vertical-align: middle; margin-left: 0.5rem;">📝 LIVE DRAFT</span>
                @endif
            </h1>
            <p style="color: var(--text-muted); font-size: 0.95rem;">Review your strict fiscal tax and VAT liabilities.</p>
        </div>
        
        <div style="display: flex; gap: 0.5rem;">
            <a href="/reports?year={{ $selectedYear - 1 }}" style="padding: 0.5rem 1rem; background: white; border: 1px solid #cbd5e1; border-radius: 6px; text-decoration: none; color: var(--primary-navy); font-weight: 600; box-shadow: var(--shadow-sm); transition: 0.2s;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='white'">&larr; {{ $selectedYear - 1 }}</a>
            <a href="/reports?year={{ $selectedYear + 1 }}" style="padding: 0.5rem 1rem; background: white; border: 1px solid #cbd5e1; border-radius: 6px; text-decoration: none; color: var(--primary-navy); font-weight: 600; box-shadow: var(--shadow-sm); transition: 0.2s;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='white'">{{ $selectedYear + 1 }} &rarr;</a>
        </div>
    </div>

    @if(!$isYearClosed && $uninvoicedRfpCount > 0)
        <div style="background: #fef2f2; border: 1px solid #fecaca; border-radius: var(--radius-md); padding: 1.25rem; margin-bottom: 2rem; display: flex; align-items: flex-start; gap: 1rem; box-shadow: 0 4px 6px -1px rgba(239, 68, 68, 0.1);">
            <div style="font-size: 1.75rem;">⚠️</div>
            <div>
                <h3 style="color: #991b1b; font-size: 1rem; font-weight: 700; margin-bottom: 0.25rem;">Disclaimer: Uninvoiced Cash Detected</h3>
                <p style="color: #b91c1c; font-size: 0.85rem; margin: 0; margin-bottom: 0.75rem;">You have <strong>{{ $uninvoicedRfpCount }} Pro-Forma RFP(s)</strong> holding <strong>€{{ number_format($uninvoicedRfpCash, 2) }}</strong> in cash. Because these are not converted into Official Tax Invoices, this cash is completely excluded from this Fiscal Report. PractisBase strongly advises converting these prior to closing your year. If you proceed, you accept full liability for any tax reporting discrepancies.</p>
                <a href="/ledger?type=rfp&status=open" style="display: inline-block; background: #ef4444; color: white; padding: 0.4rem 0.8rem; border-radius: 4px; text-decoration: none; font-size: 0.8rem; font-weight: 600;">Review Uninvoiced RFPs &rarr;</a>
            </div>
        </div>
    @else
        <div style="background: #eff6ff; border: 1px solid #bfdbfe; border-radius: var(--radius-md); padding: 1rem; margin-bottom: 2rem; display: flex; align-items: flex-start; gap: 1rem;">
            <div style="font-size: 1.5rem;">💡</div>
            <div>
                <h3 style="color: #1e3a8a; font-size: 0.95rem; font-weight: 700; margin-bottom: 0.25rem;">Strict Fiscal Mode Active</h3>
                <p style="color: #1e40af; font-size: 0.85rem; margin: 0;">This report calculates your tax liabilities using strictly <strong>Official Tax Invoices</strong> and their associated payments. Pro-Forma RFPs are safely excluded until officially converted.</p>
            </div>
        </div>
    @endif

    @if($appliedRatesYear && $appliedRatesYear != $selectedYear)
        <div style="background: #fefce8; border: 1px solid #fef08a; border-radius: var(--radius-md); padding: 1rem; margin-bottom: 2rem; display: flex; align-items: flex-start; gap: 1rem;">
            <div style="font-size: 1.5rem;">📊</div>
            <div>
                <h3 style="color: #854d0e; font-size: 0.95rem; font-weight: 700; margin-bottom: 0.25rem;">Estimated Tax Rates Applied</h3>
                <p style="color: #a16207; font-size: 0.85rem; margin: 0;">The official government tax and SSC brackets for <strong>{{ $selectedYear }}</strong> have not been published/loaded into the system yet. Your liabilities are currently being estimated using the <strong>{{ $appliedRatesYear }}</strong> rates.</p>
            </div>
        </div>
    @endif

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem;">
        
        <div style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.5rem; box-shadow: var(--shadow-sm);">
            <div style="color: var(--text-muted); font-size: 0.85rem; font-weight: 700; text-transform: uppercase; margin-bottom: 1rem; border-bottom: 1px solid #e2e8f0; padding-bottom: 0.5rem;">Income Overview (Accrual)</div>
            
            <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 1rem;">
                <div style="font-size: 0.85rem; color: var(--text-main); font-weight: 600;">Official Invoiced</div>
                <div style="font-size: 1.5rem; font-weight: 700; color: #10b981;">€{{ number_format($invoicedRevenue, 2) }}</div>
            </div>
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem; padding-top: 0.5rem; border-top: 1px dashed #e2e8f0;">
                <div style="font-size: 0.8rem; color: var(--text-muted);">Estimated Expenses</div>
                <div style="font-size: 0.95rem; font-weight: 600; color: #ef4444;">-€{{ number_format($user->estimated_expenses, 2) }}</div>
            </div>
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div style="font-size: 0.8rem; color: var(--text-muted);">Net Taxable Profit</div>
                <div style="font-size: 0.95rem; font-weight: 600; color: var(--primary-navy);">€{{ number_format($netProfit, 2) }}</div>
            </div>
            <div style="margin-top: 1rem; font-size: 0.75rem; color: var(--text-muted); padding-top: 0.5rem; border-top: 1px dashed #e2e8f0;">
                <strong>Actual Cash Collected:</strong> €{{ number_format($collectedRevenue, 2) }}
            </div>
        </div>

        <div style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.5rem; box-shadow: var(--shadow-sm);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; border-bottom: 1px solid #e2e8f0; padding-bottom: 0.5rem;">
                <div style="color: var(--text-muted); font-size: 0.85rem; font-weight: 700; text-transform: uppercase;">Tax Liabilities</div>
                <span style="font-size: 0.65rem; background: #f1f5f9; color: #64748b; padding: 0.15rem 0.4rem; border-radius: 4px; font-weight: 700;">USING {{ $appliedRatesYear }} RATES</span>
            </div>
            
            @if($user->employment_type === 'part_time')
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem;">
                    <div style="font-size: 0.85rem; color: var(--text-main); font-weight: 600;">TA22 (Part-Time)</div>
                    <div style="font-size: 1.1rem; font-weight: 700; color: #dc2626;">€{{ number_format($ta22Liability, 2) }}</div>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem; padding-top: 0.5rem; border-top: 1px dashed #e2e8f0;">
                    <div style="font-size: 0.85rem; color: var(--text-main); font-weight: 600;">Income Tax (Spillover)</div>
                    <div style="font-size: 1.1rem; font-weight: 700; color: #dc2626;">€{{ number_format($incomeTaxLiability, 2) }}</div>
                </div>
            @else
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem;">
                    <div style="font-size: 0.85rem; color: var(--text-main); font-weight: 600;">Income Tax</div>
                    <div style="font-size: 1.1rem; font-weight: 700; color: #dc2626;">€{{ number_format($incomeTaxLiability, 2) }}</div>
                </div>
            @endif
            
            <div style="display: flex; justify-content: space-between; align-items: center; padding-top: 0.5rem; border-top: 1px dashed #e2e8f0;">
                <div style="font-size: 0.85rem; color: var(--text-main); font-weight: 600;">Social Security (SSC)</div>
                <div style="font-size: 1.1rem; font-weight: 700; color: #dc2626;">€{{ number_format($sscLiability, 2) }}</div>
            </div>
        </div>

        <div style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.5rem; box-shadow: var(--shadow-sm);">
            <div style="color: var(--text-muted); font-size: 0.85rem; font-weight: 700; text-transform: uppercase; margin-bottom: 1rem; border-bottom: 1px solid #e2e8f0; padding-bottom: 0.5rem;">VAT Tracking</div>
            
            @if($user->vat_status === 'article_10')
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem;">
                    <div style="font-size: 0.85rem; color: var(--text-main); font-weight: 600;">VAT Collected</div>
                    <div style="font-size: 1.1rem; font-weight: 700; color: #dc2626;">€{{ number_format($vatLiability, 2) }}</div>
                </div>
                <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 1rem;">
                    Based on 18% standard rate. Adjust manually if using 5% or 7% rates.
                </div>
            @elseif($user->vat_status === 'article_11')
                @php
                    $threshold = 35000;
                    $percent = min(100, ($invoicedRevenue / $threshold) * 100);
                @endphp
                <div style="margin-bottom: 1rem;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                        <span style="color: var(--text-muted); font-size: 0.9rem;">Article 11 Threshold Progress</span>
                        <strong style="color: var(--text-main);">{{ number_format($percent, 1) }}%</strong>
                    </div>
                    
                    <div style="width: 100%; background-color: #f1f5f9; border-radius: 9999px; height: 0.75rem; overflow: hidden;">
                        <div style="background-color: {{ $percent > 90 ? '#ef4444' : ($percent > 75 ? '#f59e0b' : '#10b981') }}; height: 100%; width: {{ $percent }}%;"></div>
                    </div>
                    
                    <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.75rem; text-align: center;">
                        Billed: €{{ number_format($invoicedRevenue, 2) }} / €35,000.00
                    </div>
                </div>
                <div style="border-top: 1px solid var(--border-light); padding-top: 0.75rem; font-size: 0.85rem; color: var(--text-muted);">
                    <strong>Action Required:</strong> Submit your annual declaration confirming your revenue remains under the threshold.
                </div>
            @else
                <div style="margin-bottom: 1rem; text-align: center; color: #166534; background: #f0fdf4; padding: 1rem; border-radius: var(--radius-md);">
                    <strong>VAT Exempt</strong><br>
                    <span style="font-size: 0.85rem;">Fifth Schedule Exemption Active</span>
                </div>
            @endif
        </div>

    </div>

    <div style="margin-top: 3rem; padding-top: 2rem; border-top: 1px solid var(--border-light);">
        <div style="display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 1rem; background: {{ $isYearClosed ? '#f8fafc' : 'white' }}; border: 1px solid {{ $isYearClosed ? '#e2e8f0' : '#cbd5e1' }}; padding: 1.5rem; border-radius: var(--radius-lg); box-shadow: var(--shadow-sm);">
            <div style="flex: 1; min-width: 300px;">
                <h3 style="font-size: 1.1rem; color: var(--primary-navy); margin-bottom: 0.25rem; font-weight: 700;">{{ $isYearClosed ? 'Final Fiscal Report' : 'Close Fiscal Year' }}</h3>
                <p style="color: var(--text-muted); font-size: 0.85rem; margin: 0; line-height: 1.5;">
                    {{ $isYearClosed ? "This year is permanently locked. You can safely send this official report to your accountant." : "Lock this year to generate your official, unchangeable report for your accountant. Once locked, no documents or payments can be backdated to " . $selectedYear . "." }}
                </p>
            </div>
            
            <div style="display: flex; gap: 1rem; align-items: center;">
                <button style="padding: 0.6rem 1.25rem; background: white; border: 1px solid var(--border-light); color: var(--primary-navy); border-radius: 6px; font-weight: 600; cursor: pointer; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
                    Download {{ $isYearClosed ? 'Final' : 'Draft' }} PDF
                </button>
                
                @if(!$isYearClosed)
                    @if($selectedYear >= date('Y'))
                        <button disabled style="padding: 0.6rem 1.25rem; background: #f1f5f9; color: #94a3b8; border: 1px solid #e2e8f0; border-radius: 6px; font-weight: 600; cursor: not-allowed;" title="You cannot close a year until it has ended.">
                            Year Ends Dec 31
                        </button>
                    @else
                        @php
                            $warningMsg = "Are you absolutely sure? This will permanently lock {$selectedYear}. You will not be able to issue invoices or backdate payments to this year.";
                            if ($uninvoicedRfpCount > 0) {
                                $warningMsg = "LIABILITY WARNING:\n\nYou have €" . number_format($uninvoicedRfpCash, 2) . " sitting in unofficial RFPs.\n\nThis money will NOT be included in your official Fiscal Report. PractisBase accepts no liability for resulting tax discrepancies. \n\nAre you absolutely sure you want to proceed and lock {$selectedYear}?";
                            }
                        @endphp
                        
                        <form method="POST" action="/reports/close-year" style="margin: 0;" onsubmit="return confirm('{{ $warningMsg }}');">
                            @csrf
                            <input type="hidden" name="year" value="{{ $selectedYear }}">
                            <button type="submit" style="padding: 0.6rem 1.25rem; background: #dc2626; color: white; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; box-shadow: 0 1px 2px rgba(0,0,0,0.1); transition: 0.2s;" onmouseover="this.style.background='#b91c1c'" onmouseout="this.style.background='#dc2626'">
                                🔒 Lock {{ $selectedYear }}
                            </button>
                        </form>
                    @endif
                @endif
            </div>
        </div>
    </div>

@endsection