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
        <div style="display: flex; flex-direction: column; gap: 1.5rem;">
            @foreach($invoices as $doc)
                <div style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-md); padding: 1.25rem; box-shadow: var(--shadow-sm); border-left: 4px solid {{ $doc->type === 'rfp' ? '#4f46e5' : 'var(--primary-cerulean)' }};">
                    
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem;">
                        <div>
                            <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.25rem;">
                                <strong style="color: var(--primary-navy); font-size: 1.1rem;">{{ $doc->invoice_number }}</strong>
                                
                                @if($doc->type === 'rfp')
                                    <span style="background: #e0e7ff; color: #4338ca; font-size: 0.7rem; font-weight: 700; padding: 0.15rem 0.5rem; border-radius: 4px;">RFP</span>
                                @else
                                    <span style="background: #e0f2fe; color: #0369a1; font-size: 0.7rem; font-weight: 700; padding: 0.15rem 0.5rem; border-radius: 4px;">TAX INVOICE</span>
                                @endif

                                @if($doc->status === 'paid')
                                    <span style="background: #e6f4ea; color: #137333; font-size: 0.7rem; font-weight: 700; padding: 0.15rem 0.5rem; border-radius: 4px; text-transform: uppercase;">Paid</span>
                                @elseif($doc->status === 'partially_paid')
                                    <span style="background: #fef08a; color: #854d0e; font-size: 0.7rem; font-weight: 700; padding: 0.15rem 0.5rem; border-radius: 4px; text-transform: uppercase;">Partial</span>
                                @elseif($doc->status === 'converted')
                                    <span style="background: #f1f5f9; color: #475569; font-size: 0.7rem; font-weight: 700; padding: 0.15rem 0.5rem; border-radius: 4px; text-transform: uppercase;">Converted</span>
                                @else
                                    <span style="background: #fef7e0; color: #b06000; font-size: 0.7rem; font-weight: 700; padding: 0.15rem 0.5rem; border-radius: 4px; text-transform: uppercase;">Unpaid</span>
                                @endif
                            </div>
                            <div style="color: var(--text-main); font-weight: 500; font-size: 0.95rem;">{{ $doc->client->name }}</div>
                        </div>
                        <div style="text-align: right;">
                            <div style="font-size: 1.25rem; font-weight: 700; color: var(--primary-navy);">€{{ number_format($doc->total, 2) }}</div>
                            @if($doc->amount_paid > 0 && $doc->status !== 'paid' && $doc->type !== 'rfp')
                                <div style="font-size: 0.75rem; color: #dc2626; font-weight: 600;">Due: €{{ number_format($doc->total - $doc->amount_paid, 2) }}</div>
                            @endif
                        </div>
                    </div>

                    @include('invoices.partials.actions', ['document' => $doc])

                </div>
            @endforeach
        </div>
    @endif
@endsection