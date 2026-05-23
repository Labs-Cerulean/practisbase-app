@extends('layouts.app')

@section('page_title', 'Live Fiscal Report')

@section('content')
    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1.5rem;">
        <div>
            <h1 style="font-size: 1.5rem; color: var(--primary-navy); margin-bottom: 0.25rem;">Live Fiscal Report ({{ $currentYear }})</h1>
            <p style="color: var(--text-muted); font-size: 0.95rem;">Real-time estimates of your tax, VAT, and SSC liabilities.</p>
        </div>
        <a href="/settings" style="background: white; color: var(--primary-navy); border: 1px solid var(--border-light); padding: 0.6rem 1.25rem; border-radius: var(--radius-md); font-weight: 600; font-size: 0.9rem; text-decoration: none;">
            ⚙️ Edit Tax Settings
        </a>
    </div>

    <div style="background: #fffbeb; border: 1px solid #fef3c7; border-left: 4px solid #f59e0b; color: #92400e; padding: 1rem; border-radius: var(--radius-md); margin-bottom: 2rem; font-size: 0.9rem; line-height: 1.5;">
        <strong>Disclaimer:</strong> This report provides a live estimate based strictly on your PractisBase ledger and saved profile settings. It does not account for external personal wealth factors (e.g., rental income, dividends, specific allowances). Always consult your accountant for final CFR submissions.
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
        <div style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-md); padding: 1.5rem; box-shadow: var(--shadow-sm);">
            <div style="color: var(--text-muted); font-size: 0.85rem; font-weight: 600; text-transform: uppercase; margin-bottom: 0.5rem;">YTD Collected Revenue</div>
            <div style="font-size: 2rem; font-weight: 700; color: var(--primary-navy);">€{{ number_format($collectedRevenue, 2) }}</div>
            @if($user->vat_status === 'article_10')
                <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.25rem;">Includes 18% VAT</div>
            @endif
        </div>

        <div style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-md); padding: 1.5rem; box-shadow: var(--shadow-sm);">
            <div style="color: var(--text-muted); font-size: 0.85rem; font-weight: 600; text-transform: uppercase; margin-bottom: 0.5rem;">Estimated Net Profit</div>
            <div style="font-size: 2rem; font-weight: 700; color: #059669;">€{{ number_format($netProfit, 2) }}</div>
            <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.25rem;">After €{{ number_format($user->estimated_expenses, 2) }} estimated expenses</div>
        </div>
    </div>

    <h2 style="font-size: 1.25rem; color: var(--primary-navy); border-bottom: 1px solid var(--border-light); padding-bottom: 0.5rem; margin-bottom: 1.5rem;">Estimated Liabilities</h2>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem;">
        
        <div style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-md); padding: 1.5rem; box-shadow: var(--shadow-sm); border-top: 4px solid var(--primary-cerulean);">
            <h3 style="margin-top: 0; margin-bottom: 1rem; color: var(--primary-navy); font-size: 1.1rem;">Income Tax</h3>
            
            @if($user->employment_type === 'part_time')
                <div style="margin-bottom: 1rem;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 0.25rem;">
                        <span style="color: var(--text-muted); font-size: 0.9rem;">TA22 Flat Tax (10%)</span>
                        <strong style="color: var(--text-main);">€{{ number_format($ta22Liability, 2) }}</strong>
                    </div>
                    @if($incomeTaxLiability > 0)
                    <div style="display: flex; justify-content: space-between; margin-bottom: 0.25rem;">
                        <span style="color: var(--text-muted); font-size: 0.9rem;">Progressive Tax (Spillover)</span>
                        <strong style="color: var(--text-main);">€{{ number_format($incomeTaxLiability, 2) }}</strong>
                    </div>
                    @endif
                </div>
                <div style="border-top: 1px solid var(--border-light); padding-top: 0.75rem; display: flex; justify-content: space-between; font-size: 1.1rem;">
                    <strong>Total Income Tax:</strong>
                    <strong style="color: #dc2626;">€{{ number_format($ta22Liability + $incomeTaxLiability, 2) }}</strong>
                </div>
            @else
                <div style="margin-bottom: 1rem;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 0.25rem;">
                        <span style="color: var(--text-muted); font-size: 0.9rem;">Progressive Tax Rates</span>
                        <strong style="color: var(--text-main);">€{{ number_format($incomeTaxLiability, 2) }}</strong>
                    </div>
                    <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.5rem;">Based on {{ ucfirst($user->tax_computation) }} computation with €{{ number_format($user->primary_salary, 2) }} base salary.</div>
                </div>
                <div style="border-top: 1px solid var(--border-light); padding-top: 0.75rem; display: flex; justify-content: space-between; font-size: 1.1rem;">
                    <strong>Total Due:</strong>
                    <strong style="color: #dc2626;">€{{ number_format($incomeTaxLiability, 2) }}</strong>
                </div>
            @endif
        </div>

        <div style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-md); padding: 1.5rem; box-shadow: var(--shadow-sm); border-top: 4px solid #8b5cf6;">
            <h3 style="margin-top: 0; margin-bottom: 1rem; color: var(--primary-navy); font-size: 1.1rem;">Social Security (SSC)</h3>
            
            @if($user->employment_type === 'part_time' && $user->max_ssc_paid)
                <div style="background: #f0fdf4; color: #166534; padding: 0.75rem; border-radius: var(--radius-md); font-size: 0.9rem; text-align: center; margin-bottom: 1rem;">
                    <strong>Exempt</strong><br>You indicated maximum SSC is paid by your primary employer.
                </div>
                <div style="border-top: 1px solid var(--border-light); padding-top: 0.75rem; display: flex; justify-content: space-between; font-size: 1.1rem;">
                    <strong>Total SSC:</strong>
                    <strong style="color: #166534;">€0.00</strong>
                </div>
            @else
                <div style="margin-bottom: 1rem;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 0.25rem;">
                        <span style="color: var(--text-muted); font-size: 0.9rem;">
                            {{ $user->employment_type === 'part_time' ? '15% Pro-rata' : 'Class 2 Contributions' }}
                        </span>
                        <strong style="color: var(--text-main);">€{{ number_format($sscLiability, 2) }}</strong>
                    </div>
                </div>
                <div style="border-top: 1px solid var(--border-light); padding-top: 0.75rem; display: flex; justify-content: space-between; font-size: 1.1rem;">
                    <strong>Total SSC:</strong>
                    <strong style="color: #dc2626;">€{{ number_format($sscLiability, 2) }}</strong>
                </div>
            @endif
        </div>

        <div style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-md); padding: 1.5rem; box-shadow: var(--shadow-sm); border-top: 4px solid #f59e0b;">
            <h3 style="margin-top: 0; margin-bottom: 1rem; color: var(--primary-navy); font-size: 1.1rem;">VAT Tracking</h3>
            
            @if($user->vat_status === 'article_10')
                <div style="margin-bottom: 1rem;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 0.25rem;">
                        <span style="color: var(--text-muted); font-size: 0.9rem;">Standard VAT (18%)</span>
                        <strong style="color: var(--text-main);">€{{ number_format($vatLiability, 2) }}</strong>
                    </div>
                    <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.5rem;">Payable quarterly. You may deduct input VAT on your final return.</div>
                </div>
                <div style="border-top: 1px solid var(--border-light); padding-top: 0.75rem; display: flex; justify-content: space-between; font-size: 1.1rem;">
                    <strong>Est. VAT Due:</strong>
                    <strong style="color: #dc2626;">€{{ number_format($vatLiability, 2) }}</strong>
                </div>
            @else
                @php
                    $threshold = 35000;
                    $percent = min(100, ($collectedRevenue / $threshold) * 100);
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
                        €{{ number_format($collectedRevenue, 2) }} / €35,000.00
                    </div>
                </div>
                <div style="border-top: 1px solid var(--border-light); padding-top: 0.75rem; font-size: 0.85rem; color: var(--text-muted);">
                    <strong>Action Required:</strong> Submit your annual declaration confirming your revenue remains under the threshold.
                </div>
            @endif
        </div>
    </div>
@endsection