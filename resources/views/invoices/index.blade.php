@extends('layouts.app')

@section('page_title', 'Financial Ledger')

@section('content')
    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1.5rem;">
        <div>
            <h1 style="font-size: 1.5rem; color: var(--primary-navy); margin-bottom: 0.25rem;">Ledger & Documents</h1>
            <p style="color: var(--text-muted); font-size: 0.95rem;">Track your practice revenue and requests.</p>
        </div>
        <a href="/ledger/create" style="background: var(--primary-cerulean); color: white; padding: 0.6rem 1.25rem; border-radius: var(--radius-md); font-weight: 600; font-size: 0.9rem; text-decoration: none;">
            + New Document
        </a>
    </div>

    @if(session('success'))
        <div style="background: #ecfdf5; border: 1px solid #10b981; border-left: 4px solid #059669; color: #047857; padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1.5rem; font-weight: 500; font-size: 0.9rem;">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->has('payment_error'))
        <div style="background: #fef2f2; border: 1px solid #f87171; border-left: 4px solid #dc2626; color: #b91c1c; padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1.5rem; font-weight: 500; font-size: 0.95rem;">
            {{ $errors->first('payment_error') }}
        </div>
    @endif

    @if(session('revenue_warning'))
        <div style="background: #fef2f2; border: 1px solid #f87171; border-left: 4px solid #dc2626; color: #b91c1c; padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1.5rem; font-weight: 500; font-size: 0.95rem; line-height: 1.4;">
            {!! session('revenue_warning') !!}
        </div>
    @endif

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
        <div style="background: white; padding: 1.25rem; border-radius: var(--radius-md); border: 1px solid var(--border-light); box-shadow: var(--shadow-sm); border-left: 4px solid #10b981;">
            <div style="color: var(--text-muted); font-size: 0.8rem; font-weight: 600; text-transform: uppercase;">Collected Revenue</div>
            <div style="font-size: 1.75rem; font-weight: 700; color: #047857; margin-top: 0.25rem;">€{{ number_format($totalPaid ?? 0, 2) }}</div>
            <div style="font-size: 0.7rem; color: var(--text-muted); margin-top: 0.25rem;">Tax Invoices Only</div>
        </div>
        <div style="background: white; padding: 1.25rem; border-radius: var(--radius-md); border: 1px solid var(--border-light); box-shadow: var(--shadow-sm); border-left: 4px solid #ef4444;">
            <div style="color: var(--text-muted); font-size: 0.8rem; font-weight: 600; text-transform: uppercase;">Outstanding Balance</div>
            <div style="font-size: 1.75rem; font-weight: 700; color: #b91c1c; margin-top: 0.25rem;">€{{ number_format($totalUnpaid ?? 0, 2) }}</div>
            <div style="font-size: 0.7rem; color: var(--text-muted); margin-top: 0.25rem;">Tax Invoices Only</div>
        </div>
    </div>

    @if($invoices->isEmpty())
        <div style="padding: 4rem 2rem; border: 2px dashed var(--border-light); border-radius: var(--radius-md); text-align: center; background: white;">
            <p style="color: var(--text-muted); margin-bottom: 1.25rem; font-size: 0.95rem;">No documents generated yet.</p>
            <a href="/ledger/create" style="background: var(--primary-navy); color: white; padding: 0.6rem 1.5rem; border-radius: var(--radius-md); text-decoration: none; font-weight: 600; font-size: 0.9rem;">Create Your First Document</a>
        </div>
    @else
        <div style="display: flex; flex-direction: column; gap: 1rem;">
            @foreach($invoices as $doc)
                <div style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-md); padding: 1.25rem; box-shadow: var(--shadow-sm);">
                    
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem;">
                        @if($doc->type === 'rfp')
                            <span style="background: #e0e7ff; color: #4338ca; font-size: 0.75rem; font-weight: 700; padding: 0.2rem 0.6rem; border-radius: 20px;">📨 Request for Payment</span>
                        @elseif($doc->type === 'invoice')
                            <span style="background: #e0f2fe; color: #0369a1; font-size: 0.75rem; font-weight: 700; padding: 0.2rem 0.6rem; border-radius: 20px;">📄 Tax Invoice</span>
                        @elseif($doc->type === 'credit_note')
                            <span style="background: #f3f4f6; color: #4b5563; font-size: 0.75rem; font-weight: 700; padding: 0.2rem 0.6rem; border-radius: 20px;">↩️ Credit Note</span>
                        @endif

                        @if($doc->status === 'paid')
                            <span style="background: #e6f4ea; color: #137333; font-size: 0.7rem; font-weight: 700; padding: 0.15rem 0.5rem; border-radius: 4px; text-transform: uppercase;">Paid</span>
                        @elseif($doc->status === 'partially_paid')
                            <span style="background: #fef08a; color: #854d0e; font-size: 0.7rem; font-weight: 700; padding: 0.15rem 0.5rem; border-radius: 4px; text-transform: uppercase;">Partial</span>
                        @elseif($doc->status === 'unpaid')
                            <span style="background: #fef7e0; color: #b06000; font-size: 0.7rem; font-weight: 700; padding: 0.15rem 0.5rem; border-radius: 4px; text-transform: uppercase;">Unpaid</span>
                        @elseif($doc->status === 'cancelled')
                            <span style="background: #f1f5f9; color: #64748b; font-size: 0.7rem; font-weight: 700; padding: 0.15rem 0.5rem; border-radius: 4px; text-transform: uppercase;">Cancelled</span>
                        @else
                            <span style="background: #fce8e6; color: #c5221f; font-size: 0.7rem; font-weight: 700; padding: 0.15rem 0.5rem; border-radius: 4px; text-transform: uppercase;">{{ $doc->status }}</span>
                        @endif
                    </div>

                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem;">
                        <div>
                            <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.25rem;">
                                <strong style="color: var(--primary-navy); font-size: 1.05rem;">{{ $doc->invoice_number }}</strong>
                            </div>
                            <div style="color: var(--text-main); font-weight: 500; font-size: 0.95rem; margin-bottom: 0.25rem;">{{ $doc->client->name }}</div>
                            <div style="color: var(--text-muted); font-size: 0.75rem;">Issued: {{ $doc->issue_date->format('d M Y') }}</div>
                        </div>
                        <div style="text-align: right;">
                            <div style="font-size: 1.25rem; font-weight: 700; color: var(--primary-navy);">€{{ number_format($doc->total, 2) }}</div>
                            
                            @if($doc->status === 'partially_paid' || ($doc->amount_paid > 0 && $doc->status !== 'paid'))
                                <div style="font-size: 0.75rem; color: #059669; margin-top: 0.15rem;">Paid: €{{ number_format($doc->amount_paid, 2) }}</div>
                                <div style="font-size: 0.75rem; color: #dc2626; font-weight: 600;">Due: €{{ number_format($doc->total - $doc->amount_paid, 2) }}</div>
                            @elseif($doc->vat_total > 0)
                                <div style="font-size: 0.7rem; color: var(--text-muted);">Inc. VAT</div>
                            @endif
                        </div>
                    </div>

                    @if($doc->payments->count() > 0)
                        <div style="margin-top: 1rem; margin-bottom: 1rem; background: #f8fafc; border: 1px solid var(--border-light); border-radius: 6px; padding: 0.75rem;">
                            <div style="font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 0.5rem; border-bottom: 1px solid #e2e8f0; padding-bottom: 0.25rem;">Payment History</div>
                            
                            @foreach($doc->payments as $payment)
                                <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.25rem 0; font-size: 0.85rem;">
                                    <div>
                                        <span style="color: var(--text-main); font-weight: 500;">€{{ number_format($payment->amount, 2) }}</span>
                                        <span style="color: var(--text-muted); margin-left: 0.5rem;">on {{ $payment->payment_date->format('d M Y') }}</span>
                                    </div>
                                    
                                    @if($doc->type === 'invoice')
                                        <a href="/ledger/payments/{{ $payment->id }}/receipt" style="color: var(--primary-cerulean); text-decoration: none; font-weight: 600; font-size: 0.8rem; background: #e0f2fe; padding: 0.2rem 0.5rem; border-radius: 4px; transition: 0.2s;" onmouseover="this.style.background='#bae6fd'" onmouseout="this.style.background='#e0f2fe'">
                                            ↓ Receipt
                                        </a>
                                    @else
                                        <span style="color: #94a3b8; font-size: 0.75rem; font-style: italic;" title="Convert RFP to Invoice to issue receipts.">Receipt Locked</span>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <div style="border-top: 1px solid var(--border-light); padding-top: 1rem; display: flex; gap: 0.5rem; flex-wrap: wrap;">
                        <a href="/ledger/{{ $doc->id }}/pdf" style="flex: 1; text-align: center; padding: 0.5rem; background: white; border: 1px solid var(--border-light); color: var(--text-main); border-radius: 6px; font-weight: 600; font-size: 0.85rem; text-decoration: none; transition: 0.2s;">Download PDF</a>
                        
                        @if(in_array($doc->status, ['unpaid', 'overdue', 'partially_paid']))
                            <form action="/ledger/{{ $doc->id }}/pay" method="POST" style="flex: 1; margin: 0; display: flex; gap: 0.5rem; align-items: center;" 
                                onsubmit="return {{ $doc->type === 'rfp' ? "confirm('Note: Payments logged against an RFP will NOT appear on your Fiscal Report. You must convert this RFP to a Tax Invoice to officially log the revenue. Proceed?')" : 'true' }};">
                                @csrf
                                <div style="position: relative; display: flex; align-items: center; width: 120px;">
                                    <span style="position: absolute; left: 10px; color: #64748b; font-weight: 600; font-size: 0.95rem;">€</span>
                                    <input type="number" name="payment_amount" step="0.01" max="{{ $doc->total - $doc->amount_paid }}" value="{{ number_format($doc->total - $doc->amount_paid, 2, '.', '') }}" required 
                                        style="width: 100%; padding: 0.5rem 0.5rem 0.5rem 1.6rem; border: 2px solid #cbd5e1; border-radius: 6px; font-size: 0.95rem; font-weight: 700; color: var(--primary-navy); background-color: #f8fafc; outline: none;">
                                </div>
                                <button type="submit" style="flex: 1; padding: 0.55rem 1rem; background: #10b981; color: white; border: none; border-radius: 6px; font-weight: 600; font-size: 0.85rem; cursor: pointer;">
                                    Log Payment
                                </button>
                            </form>
                        @endif

                        @if($doc->type === 'rfp' && $doc->status !== 'cancelled')
                            @php
                                $existingConversions = $doc->childDocuments ? $doc->childDocuments->where('type', 'invoice')->sum('total') : 0;
                                $maxConvertible = $doc->total - $existingConversions;
                            @endphp
                            
                            @if($maxConvertible > 0)
                                <form action="/ledger/{{ $doc->id }}/convert" method="POST" style="flex: 1; margin: 0; display: flex; gap: 0.5rem; align-items: center;" onsubmit="return confirm('Generate an official Tax Invoice for this amount?');">
                                    @csrf
                                    <div style="position: relative; display: flex; align-items: center; width: 120px;">
                                        <span style="position: absolute; left: 10px; color: #4338ca; font-weight: 600; font-size: 0.95rem;">€</span>
                                        <input type="number" name="conversion_amount" step="0.01" max="{{ $maxConvertible }}" value="{{ number_format($maxConvertible, 2, '.', '') }}" required 
                                            style="width: 100%; padding: 0.5rem 0.5rem 0.5rem 1.6rem; border: 2px solid #c7d2fe; border-radius: 6px; font-size: 0.95rem; font-weight: 700; color: #3730a3; background-color: #eef2ff; outline: none; transition: 0.2s;" 
                                            onfocus="this.style.borderColor='#6366f1'; this.style.backgroundColor='#ffffff';" 
                                            onblur="this.style.borderColor='#c7d2fe'; this.style.backgroundColor='#eef2ff';">
                                    </div>
                                    <button type="submit" style="flex: 1; padding: 0.55rem 1rem; background: #4f46e5; color: white; border: none; border-radius: 6px; font-weight: 600; font-size: 0.85rem; cursor: pointer; box-shadow: 0 1px 2px rgba(0,0,0,0.05); transition: 0.2s;" onmouseover="this.style.background='#4338ca'" onmouseout="this.style.background='#4f46e5'">
                                        Generate Invoice
                                    </button>
                                </form>
                            @endif
                        @endif
                        @if($doc->type === 'invoice' && $doc->status !== 'cancelled')
                            @php
                                $existingCredits = $doc->childDocuments ? $doc->childDocuments->where('type', 'credit_note')->sum('total') : 0;
                                $maxCredit = $doc->total - $existingCredits;
                            @endphp
                            
                            @if($maxCredit > 0)
                                <form action="/ledger/{{ $doc->id }}/credit" method="POST" style="flex: 1; margin: 0; display: flex; gap: 0.5rem; align-items: center;" onsubmit="return confirm('Issue a Credit Note for this amount? This will permanently reduce the taxable value of this invoice.');">
                                    @csrf
                                    <div style="position: relative; display: flex; align-items: center; width: 120px;">
                                        <span style="position: absolute; left: 10px; color: #ef4444; font-weight: 600; font-size: 0.95rem;">-€</span>
                                        <input type="number" name="credit_amount" step="0.01" max="{{ $maxCredit }}" value="{{ number_format($maxCredit, 2, '.', '') }}" required 
                                            style="width: 100%; padding: 0.5rem 0.5rem 0.5rem 1.8rem; border: 2px solid #fca5a5; border-radius: 6px; font-size: 0.95rem; font-weight: 700; color: #b91c1c; background-color: #fef2f2; outline: none; transition: 0.2s;" 
                                            onfocus="this.style.borderColor='#ef4444'; this.style.backgroundColor='#ffffff';" 
                                            onblur="this.style.borderColor='#fca5a5'; this.style.backgroundColor='#fef2f2';">
                                    </div>
                                    <button type="submit" style="flex: 1; padding: 0.55rem 1rem; background: #ef4444; color: white; border: none; border-radius: 6px; font-weight: 600; font-size: 0.85rem; cursor: pointer; box-shadow: 0 1px 2px rgba(0,0,0,0.05); transition: 0.2s;" onmouseover="this.style.background='#dc2626'" onmouseout="this.style.background='#ef4444'">
                                        Issue Credit
                                    </button>
                                </form>
                            @endif
                        @endif
                    </div>

                </div>
            @endforeach
        </div>
    @endif
@endsection