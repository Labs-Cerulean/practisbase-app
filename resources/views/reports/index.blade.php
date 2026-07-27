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
            <div style="display: flex; gap: 0.75rem; flex-wrap: wrap; margin-top: 0.75rem;">
                <a href="/exports/accountant" style="font-size: 0.8rem; font-weight: 600; color: var(--primary-cerulean); text-decoration: none;">Accountant</a>
                <a href="/expenses?year={{ $selectedYear }}" style="font-size: 0.8rem; font-weight: 600; color: var(--primary-cerulean); text-decoration: none;">Expense Ledger</a>
                @if($user->employment_type === 'part_time')
                    <a href="/reports/ta22.pdf?year={{ $selectedYear }}" style="font-size: 0.8rem; font-weight: 600; color: var(--primary-cerulean); text-decoration: none;">Download TA22 summary PDF</a>
                @endif
            </div>
        </div>
        
        <div style="display: flex; gap: 0.5rem;">
            @if($selectedYear > $earliestYear)
                <a href="/reports?year={{ $selectedYear - 1 }}" style="padding: 0.5rem 1rem; background: white; border: 1px solid #cbd5e1; border-radius: 6px; text-decoration: none; color: var(--primary-navy); font-weight: 600; box-shadow: var(--shadow-sm); transition: 0.2s;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='white'">&larr; {{ $selectedYear - 1 }}</a>
            @else
                <span style="padding: 0.5rem 1rem; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; color: #cbd5e1; font-weight: 600; cursor: not-allowed;" title="No fiscal data recorded prior to {{ $earliestYear }}">&larr; {{ $selectedYear - 1 }}</span>
            @endif

            @if($selectedYear < $currentYear)
                <a href="/reports?year={{ $selectedYear + 1 }}" style="padding: 0.5rem 1rem; background: white; border: 1px solid #cbd5e1; border-radius: 6px; text-decoration: none; color: var(--primary-navy); font-weight: 600; box-shadow: var(--shadow-sm); transition: 0.2s;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='white'">{{ $selectedYear + 1 }} &rarr;</a>
            @else
                <span style="padding: 0.5rem 1rem; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; color: #cbd5e1; font-weight: 600; cursor: not-allowed;" title="Cannot view future tax years">{{ $selectedYear + 1 }} &rarr;</span>
            @endif
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
                <div style="font-size: 0.8rem; color: var(--text-muted);">
                    {{ $expenseInfo['source'] === 'ledger' ? 'Expense ledger' : 'Estimated expenses' }}
                </div>
                <div style="font-size: 0.95rem; font-weight: 600; color: #ef4444;">-€{{ number_format($deductibleExpenses, 2) }}</div>
            </div>
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div style="font-size: 0.8rem; color: var(--text-muted);">Net Taxable Profit</div>
                <div style="font-size: 0.95rem; font-weight: 600; color: var(--primary-navy);">€{{ number_format($netProfit, 2) }}</div>
            </div>
            <div style="margin-top: 1rem; font-size: 0.75rem; color: var(--text-muted); padding-top: 0.5rem; border-top: 1px dashed #e2e8f0;">
                <strong>Actual Cash Collected:</strong> €{{ number_format($collectedRevenue, 2) }}
                @if($expenseInfo['source'] === 'estimate' && auth()->user()->canAccessStandardTools())
                    <div style="margin-top: 0.35rem;">Using Settings estimate — <a href="/expenses?year={{ $selectedYear }}" style="color: var(--primary-cerulean); font-weight: 600;">log expenses</a> to replace it.</div>
                @endif
            </div>
        </div>

        <div style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.5rem; box-shadow: var(--shadow-sm);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; border-bottom: 1px solid #e2e8f0; padding-bottom: 0.5rem;">
                <div style="color: var(--text-muted); font-size: 0.85rem; font-weight: 700; text-transform: uppercase;">Tax & SSC Settlement</div>
                <span style="font-size: 0.65rem; background: #f1f5f9; color: #64748b; padding: 0.15rem 0.4rem; border-radius: 4px; font-weight: 700;">USING {{ $appliedRatesYear }} RATES</span>
            </div>
            
            @if($user->employment_type === 'part_time')
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem;">
                    <div style="font-size: 0.85rem; color: var(--text-main); font-weight: 600; display: flex; align-items: center; gap: 0.4rem;">
                        TA22 (Part-Time)
                        <button onclick="showBreakdown('ta22', 'TA22 Scheme')" style="background: none; border: none; cursor: pointer; color: var(--primary-cerulean); padding: 0; font-size: 0.85rem;" title="View Calculation Breakdown">ℹ️</button>
                    </div>
                    <div style="font-size: 1.1rem; font-weight: 700; color: var(--primary-navy); cursor: pointer; border-bottom: 1px dotted var(--primary-navy);" onclick="showBreakdown('ta22', 'TA22 Scheme')" title="View Breakdown">
                        €{{ number_format($ta22Liability, 2) }}
                    </div>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem;">
                    <div style="font-size: 0.85rem; color: var(--text-main); font-weight: 600; display: flex; align-items: center; gap: 0.4rem;">
                        Income Tax (Spillover)
                        <button onclick="showBreakdown('income_tax', 'Spillover Income Tax')" style="background: none; border: none; cursor: pointer; color: var(--primary-cerulean); padding: 0; font-size: 0.85rem;" title="View Calculation Breakdown">ℹ️</button>
                    </div>
                    <div style="font-size: 1.1rem; font-weight: 700; color: var(--primary-navy); cursor: pointer; border-bottom: 1px dotted var(--primary-navy);" onclick="showBreakdown('income_tax', 'Spillover Income Tax')" title="View Breakdown">
                        €{{ number_format($incomeTaxLiability, 2) }}
                    </div>
                </div>
            @else
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem;">
                    <div style="font-size: 0.85rem; color: var(--text-main); font-weight: 600; display: flex; align-items: center; gap: 0.4rem;">
                        Income Tax
                        <button onclick="showBreakdown('income_tax', 'Income Tax')" style="background: none; border: none; cursor: pointer; color: var(--primary-cerulean); padding: 0; font-size: 0.85rem;" title="View Calculation Breakdown">ℹ️</button>
                    </div>
                    <div style="font-size: 1.1rem; font-weight: 700; color: var(--primary-navy); cursor: pointer; border-bottom: 1px dotted var(--primary-navy);" onclick="showBreakdown('income_tax', 'Income Tax')" title="View Breakdown">
                        €{{ number_format($incomeTaxLiability, 2) }}
                    </div>
                </div>
            @endif

            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                <div style="font-size: 0.85rem; color: var(--text-main); font-weight: 600; display: flex; align-items: center; gap: 0.4rem;">
                    Social Security (SSC)
                    <button onclick="showBreakdown('ssc', 'Social Security (SSC)')" style="background: none; border: none; cursor: pointer; color: var(--primary-cerulean); padding: 0; font-size: 0.85rem;" title="View Calculation Breakdown">ℹ️</button>
                </div>
                <div style="font-size: 1.1rem; font-weight: 700; color: var(--primary-navy); cursor: pointer; border-bottom: 1px dotted var(--primary-navy);" onclick="showBreakdown('ssc', 'Social Security (SSC)')" title="View Breakdown">
                    €{{ number_format($sscLiability, 2) }}
                </div>
            </div>

            <div style="padding-top: 1rem; border-top: 1px dashed #e2e8f0; margin-bottom: 0.5rem;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                    <div style="font-size: 0.85rem; color: #059669; font-weight: 600;">Less: PT Tax Paid</div>
                    <div style="font-size: 0.95rem; font-weight: 600; color: #059669;">-€{{ number_format($ptTaxPaid, 2) }}</div>
                </div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 1rem;">
                    <div style="font-size: 0.85rem; color: #059669; font-weight: 600;">Less: PT SSC Paid</div>
                    <div style="font-size: 0.95rem; font-weight: 600; color: #059669;">-€{{ number_format($ptSscPaid, 2) }}</div>
                </div>
            </div>

            <div style="background: {{ $taxBalance > 0 || $sscBalance > 0 ? '#fef2f2' : '#f0fdf4' }}; padding: 1rem; border-radius: var(--radius-md); border: 1px solid {{ $taxBalance > 0 || $sscBalance > 0 ? '#fecaca' : '#bbf7d0' }};">
                <div style="font-size: 0.75rem; text-transform: uppercase; font-weight: 700; color: {{ $taxBalance > 0 || $sscBalance > 0 ? '#991b1b' : '#166534' }}; margin-bottom: 0.5rem;">Final June Settlement</div>
                
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.25rem;">
                    <div style="font-size: 0.85rem; color: var(--text-main);">Income Tax Balance</div>
                    <div style="font-size: 1.1rem; font-weight: 700; color: {{ $taxBalance > 0 ? '#dc2626' : ($taxBalance < 0 ? '#059669' : 'var(--text-main)') }};">
                        {{ $taxBalance < 0 ? 'Refund: ' : '' }}€{{ number_format(abs($taxBalance), 2) }}
                    </div>
                </div>
                
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div style="font-size: 0.85rem; color: var(--text-main);">SSC Balance</div>
                    <div style="font-size: 1.1rem; font-weight: 700; color: {{ $sscBalance > 0 ? '#dc2626' : ($sscBalance < 0 ? '#059669' : 'var(--text-main)') }};">
                        {{ $sscBalance < 0 ? 'Refund: ' : '' }}€{{ number_format(abs($sscBalance), 2) }}
                    </div>
                </div>
            </div>
        </div>

        <div style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.5rem; box-shadow: var(--shadow-sm);">
            <div style="color: var(--text-muted); font-size: 0.85rem; font-weight: 700; text-transform: uppercase; margin-bottom: 1rem; border-bottom: 1px solid #e2e8f0; padding-bottom: 0.5rem;">VAT Tracking</div>
            
            @if($user->vat_status === 'article_10')
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem;">
                    <div style="font-size: 0.85rem; color: var(--text-main); font-weight: 600;">VAT Collected</div>
                    <div style="font-size: 1.1rem; font-weight: 700; color: var(--primary-navy);">€{{ number_format($vatLiability, 2) }}</div>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem;">
                    <div style="font-size: 0.85rem; color: #059669; font-weight: 600;">Less: VAT Paid</div>
                    <div style="font-size: 1.1rem; font-weight: 700; color: #059669;">-€{{ number_format($vatPaid, 2) }}</div>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center; padding-top: 0.5rem; border-top: 1px dashed #e2e8f0;">
                    <div style="font-size: 0.85rem; color: var(--text-main); font-weight: 600;">VAT Balance</div>
                    <div style="font-size: 1.1rem; font-weight: 700; color: {{ $vatBalance > 0 ? '#dc2626' : ($vatBalance < 0 ? '#059669' : 'var(--text-main)') }};">
                        {{ $vatBalance < 0 ? 'Refund: ' : '' }}€{{ number_format(abs($vatBalance), 2) }}
                    </div>
                </div>
                <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 1rem;">
                    Based on 18% standard rate. Adjust manually if using 5% or 7% rates.
                </div>
            @elseif($user->vat_status === 'article_11')
                @php
                    $threshold = 35000;
                    $percent = min(100, ($invoicedRevenue / $threshold) * 100);
                    $isOverThreshold = $invoicedRevenue > $threshold;
                @endphp
                <div style="margin-bottom: 1rem;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                        <span style="color: var(--text-muted); font-size: 0.9rem;">Article 11 Threshold Progress</span>
                        <strong style="color: {{ $isOverThreshold ? '#dc2626' : 'var(--text-main)' }};">{{ number_format($percent, 1) }}%</strong>
                    </div>
                    
                    <div style="width: 100%; background-color: #f1f5f9; border-radius: 9999px; height: 0.75rem; overflow: hidden;">
                        <div style="background-color: {{ $isOverThreshold ? '#dc2626' : ($percent > 90 ? '#ef4444' : ($percent > 75 ? '#f59e0b' : '#10b981')) }}; height: 100%; width: {{ $percent }}%;"></div>
                    </div>
                    
                    <div style="font-size: 0.75rem; color: {{ $isOverThreshold ? '#dc2626' : 'var(--text-muted)' }}; margin-top: 0.75rem; text-align: center; font-weight: {{ $isOverThreshold ? '700' : '400' }};">
                        Billed: €{{ number_format($invoicedRevenue, 2) }} / €35,000.00
                    </div>
                </div>
                
                <div style="border-top: 1px solid {{ $isOverThreshold ? '#fecaca' : 'var(--border-light)' }}; padding-top: 0.75rem; font-size: 0.85rem; color: {{ $isOverThreshold ? '#991b1b' : 'var(--text-muted)' }}; background: {{ $isOverThreshold ? '#fef2f2' : 'transparent' }}; margin: -1.5rem; margin-top: 1rem; padding: 1.5rem; border-bottom-left-radius: var(--radius-lg); border-bottom-right-radius: var(--radius-lg);">
                    @if($isOverThreshold)
                        <strong>🚨 CRITICAL ACTION REQUIRED:</strong> You have exceeded the €35,000 exempt threshold. By law, you must register for Article 10 (Standard VAT) within 30 days.
                    @else
                        <strong>Action Required:</strong> Submit your annual declaration confirming your revenue remains under the threshold.
                    @endif
                </div>
            @else
                <div style="margin-bottom: 1rem; text-align: center; color: #166534; background: #f0fdf4; padding: 1rem; border-radius: var(--radius-md);">
                    <strong>VAT Exempt</strong><br>
                    <span style="font-size: 0.85rem;">Fifth Schedule Exemption Active</span>
                </div>
            @endif
        </div>
    </div>

    <div style="margin-top: 3rem; background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); overflow: hidden;">
        <div style="padding: 1.5rem; border-bottom: 1px solid var(--border-light); display: flex; justify-content: space-between; align-items: center; background: #f8fafc;">
            <div>
                <h3 style="margin: 0; color: var(--primary-navy); font-size: 1.1rem;">Government Payment Ledger</h3>
                <p style="margin: 0; font-size: 0.85rem; color: var(--text-muted); margin-top: 0.25rem;">Log your Provisional Tax (PT) or VAT payments here to update your final settlement balance.</p>
            </div>
        </div>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem; padding: 1.5rem;">
            @if(!$isYearClosed)
                <div style="border-right: 1px dashed var(--border-light); padding-right: 2rem;">
                    <form method="POST" action="/reports/tax-payments">
                        @csrf
                        <input type="hidden" name="year" value="{{ $selectedYear }}">
                        
                        <div style="margin-bottom: 1rem;">
                            <label style="display: block; font-size: 0.85rem; font-weight: 600; color: var(--text-main); margin-bottom: 0.5rem;">Payment Type</label>
                            <select name="payment_type" id="payment_type_select" required style="width: 100%; padding: 0.6rem; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.9rem;">
                                <option value="income_tax">Provisional Tax (Income)</option>
                                <option value="ssc">Provisional Tax (SSC)</option>
                                <option value="vat">VAT Settlement</option>
                            </select>
                        </div>
                        
                        <div id="smartGuideBox" style="margin-bottom: 1.5rem; padding: 0.85rem; background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 6px; font-size: 0.8rem; color: #1e3a8a; line-height: 1.5;">
                            </div>
                        
                        <div style="margin-bottom: 1rem;">
                            <label style="display: block; font-size: 0.85rem; font-weight: 600; color: var(--text-main); margin-bottom: 0.5rem;">Amount Paid (€)</label>
                            <input type="number" name="amount" id="payment_amount_input" step="0.01" min="0.01" required placeholder="0.00" style="width: 100%; padding: 0.6rem; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.9rem;">
                        </div>

                        <div style="margin-bottom: 1.5rem;">
                            <label style="display: block; font-size: 0.85rem; font-weight: 600; color: var(--text-main); margin-bottom: 0.5rem;">Date Paid</label>
                            <input type="date" name="payment_date" value="{{ date('Y-m-d') }}" max="{{ date('Y-m-d') }}" required style="width: 100%; padding: 0.6rem; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.9rem;">
                        </div>
                        
                        <button type="submit" style="width: 100%; padding: 0.75rem; background: var(--primary-navy); color: white; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; transition: 0.2s;" onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">Log Payment</button>
                    </form>
                </div>
            @endif

            <div>
                <h4 style="margin: 0; font-size: 0.9rem; color: var(--text-muted); text-transform: uppercase; margin-bottom: 1rem;">{{ $selectedYear }} Payment History</h4>
                
                @if($taxPayments->isEmpty())
                    <div style="padding: 2rem; text-align: center; background: #f8fafc; border-radius: 8px; color: var(--text-muted); font-size: 0.9rem; border: 1px dashed #cbd5e1;">
                        No government payments logged for this year yet.
                    </div>
                @else
                    <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                        @foreach($taxPayments as $payment)
                            <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: 6px; background: white;">
                                <div>
                                    <div style="font-weight: 600; font-size: 0.9rem; color: var(--primary-navy);">
                                        {{ $payment->payment_type === 'income_tax' ? 'Provisional Income Tax' : ($payment->payment_type === 'ssc' ? 'Provisional SSC' : 'VAT Payment') }}
                                    </div>
                                    <div style="font-size: 0.75rem; color: var(--text-muted);">{{ date('M d, Y', strtotime($payment->payment_date)) }}</div>
                                </div>
                                <div style="display: flex; align-items: center; gap: 1rem;">
                                    <span style="font-weight: 700; color: #059669;">€{{ number_format($payment->amount, 2) }}</span>
                                    
                                    @if(!$isYearClosed)
                                        <form method="POST" action="/reports/tax-payments/{{ $payment->id }}" style="margin: 0;" onsubmit="return confirm('Are you sure you want to delete this payment record?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" style="background: none; border: none; color: #ef4444; font-size: 1rem; cursor: pointer;" title="Delete Payment">&times;</button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
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

    <div id="calcModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.6); z-index: 100; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
        <div style="background: white; border-radius: var(--radius-lg); width: 90%; max-width: 500px; padding: 2rem; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); position: relative;">
            <button onclick="closeModal()" style="position: absolute; top: 1rem; right: 1rem; background: none; border: none; font-size: 1.5rem; cursor: pointer; color: #94a3b8; transition: 0.2s;" onmouseover="this.style.color='#ef4444'" onmouseout="this.style.color='#94a3b8'">&times;</button>
            
            <h3 id="modalTitle" style="color: var(--primary-navy); margin-top: 0; margin-bottom: 0.5rem; font-size: 1.25rem; display: flex; align-items: center; gap: 0.5rem;">
                <span>🧮</span> <span id="modalTitleText">Calculation Breakdown</span>
            </h3>
            <p style="color: var(--text-muted); font-size: 0.85rem; margin-bottom: 1.5rem; border-bottom: 1px solid var(--border-light); padding-bottom: 1rem;">Here is exactly how this liability was calculated based on your legal tax profile.</p>
            
            <div id="modalContent" style="display: flex; flex-direction: column; gap: 0.85rem;">
                </div>
            
            <div style="margin-top: 2rem; text-align: right; border-top: 1px solid var(--border-light); padding-top: 1rem;">
                <button onclick="closeModal()" style="padding: 0.5rem 1.5rem; background: var(--primary-navy); color: white; border: none; border-radius: 6px; font-weight: 600; cursor: pointer;">Understood</button>
            </div>
        </div>
    </div>

    <script>
        // --- CALCULATION BREAKDOWN MODAL ENGINE ---
        const breakdowns = @json($breakdowns);

        function showBreakdown(type, title) {
            const modal = document.getElementById('calcModal');
            const modalTitleText = document.getElementById('modalTitleText');
            const modalContent = document.getElementById('modalContent');

            modalTitleText.innerText = title + ' Calculation';
            modalContent.innerHTML = '';

            const data = breakdowns[type];
            
            if (!data) {
                modalContent.innerHTML = '<div style="color: #64748b; font-size: 0.9rem; text-align: center; padding: 2rem 0;">No calculation data available.</div>';
            } else {
                for (const [key, value] of Object.entries(data)) {
                    const isTotal = key.includes('Final');
                    const isDeduction = key.includes('Less');
                    const valueColor = isDeduction ? '#dc2626' : (isTotal ? 'var(--primary-navy)' : 'var(--text-main)');
                    
                    modalContent.innerHTML += `
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; padding-bottom: 0.5rem; border-bottom: ${isTotal ? 'none' : '1px dashed #e2e8f0'}; font-weight: ${isTotal ? '700' : '400'}; margin-top: ${isTotal ? '0.5rem' : '0'};">
                            <span style="color: ${isTotal ? 'var(--primary-navy)' : 'var(--text-muted)'}; font-size: ${isTotal ? '0.95rem' : '0.85rem'}; padding-right: 1rem;">${key}</span>
                            <span style="color: ${valueColor}; font-size: ${isTotal ? '1.1rem' : '0.9rem'}; text-align: right;">${value}</span>
                        </div>
                    `;
                }
            }

            modal.style.display = 'flex';
        }

        function closeModal() {
            document.getElementById('calcModal').style.display = 'none';
        }
        
        window.onclick = function(event) {
            const modal = document.getElementById('calcModal');
            if (event.target == modal) {
                closeModal();
            }
        }

        // --- SMART PAYMENT GUIDE ENGINE ---
        const liveBalances = {
            'income_tax': {{ max(0, $taxBalance) }},
            'ssc': {{ max(0, $sscBalance) }},
            'vat': {{ max(0, $vatBalance) }}
        };

        const totalLiabilities = {
            'income_tax': {{ $totalTaxLiability }},
            'ssc': {{ $sscLiability }},
            'vat': {{ $vatLiability }}
        };

        function updateSmartGuide() {
            const select = document.getElementById('payment_type_select');
            if(!select) return;
            
            const type = select.value;
            const guideBox = document.getElementById('smartGuideBox');
            
            const balance = liveBalances[type].toFixed(2);
            const total = totalLiabilities[type].toFixed(2);
            
            if (type === 'income_tax') {
                guideBox.innerHTML = `<strong>💡 Income Tax Guide:</strong> Your estimated total liability for the year is €${total}. After prior payments, your remaining balance is <strong>€${balance}</strong>. <br><br><span style="color: #3b82f6; font-size: 0.75rem;"><strong>Schedule:</strong> Provisional Tax is normally paid in installments: 20% by Apr 30, 30% by Aug 31, and 50% by Dec 21.</span>`;
            } else if (type === 'ssc') {
                guideBox.innerHTML = `<strong>💡 SSC Guide:</strong> Your estimated total liability for the year is €${total}. After prior payments, your remaining balance is <strong>€${balance}</strong>. <br><br><span style="color: #3b82f6; font-size: 0.75rem;"><strong>Schedule:</strong> Provisional SSC is normally paid in 4-month chunks alongside your Income Tax deadlines.</span>`;
            } else if (type === 'vat') {
                guideBox.innerHTML = `<strong>💡 VAT Guide:</strong> You have an outstanding collected VAT balance of <strong>€${balance}</strong>. <br><br><span style="color: #3b82f6; font-size: 0.75rem;"><strong>Schedule:</strong> Standard Article 10 VAT is legally required to be settled quarterly with the VAT department.</span>`;
            }
        }

        const paymentTypeSelect = document.getElementById('payment_type_select');
        if(paymentTypeSelect) {
            paymentTypeSelect.addEventListener('change', updateSmartGuide);
            updateSmartGuide(); 
        }
    </script>
@endsection