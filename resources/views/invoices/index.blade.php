@extends('layouts.app')

@section('page_title', 'Financial Ledger')

@section('content')
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <div>
            <h1 style="font-size: 1.5rem; color: var(--primary-navy); margin-bottom: 0.25rem;">Ledger & Invoices</h1>
            <p style="color: var(--text-muted); font-size: 0.95rem;">Track your practice revenue and receivables.</p>
        </div>
        <a href="#" style="background: var(--primary-cerulean); color: white; padding: 0.6rem 1.25rem; border-radius: var(--radius-md); font-weight: 600; font-size: 0.9rem;">
            + New Invoice
        </a>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
        <div style="background: white; padding: 1.25rem; border-radius: var(--radius-md); border: 1px solid var(--border-light); box-shadow: var(--shadow-sm); border-left: 4px solid #10b981;">
            <div style="color: var(--text-muted); font-size: 0.8rem; font-weight: 600; text-transform: uppercase;">Collected Revenue</div>
            <div style="font-size: 1.75rem; font-weight: 700; color: #047857; margin-top: 0.25rem;">€{{ number_format($totalPaid, 2) }}</div>
        </div>
        <div style="background: white; padding: 1.25rem; border-radius: var(--radius-md); border: 1px solid var(--border-light); box-shadow: var(--shadow-sm); border-left: 4px solid #ef4444;">
            <div style="color: var(--text-muted); font-size: 0.8rem; font-weight: 600; text-transform: uppercase;">Outstanding Receivables</div>
            <div style="font-size: 1.75rem; font-weight: 700; color: #b91c1c; margin-top: 0.25rem;">€{{ number_format($totalUnpaid, 2) }}</div>
        </div>
    </div>

    @if($invoices->isEmpty())
        <div style="padding: 4rem 2rem; border: 2px dashed var(--border-light); border-radius: var(--radius-md); text-align: center; background: white;">
            <p style="color: var(--text-muted); margin-bottom: 1.25rem; font-size: 0.95rem;">No invoices generated yet.</p>
            <a href="#" style="background: var(--primary-navy); color: white; padding: 0.6rem 1.5rem; border-radius: var(--radius-md); text-decoration: none; font-weight: 600; font-size: 0.9rem;">Create Your First Invoice</a>
        </div>
    @else
        <div style="display: flex; flex-direction: column; gap: 1rem;">
            @foreach($invoices as $invoice)
                <div style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-md); padding: 1.25rem; box-shadow: var(--shadow-sm); display: flex; justify-content: space-between; align-items: center; gap: 1rem; flex-wrap: wrap;">
                    
                    <div>
                        <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.25rem;">
                            <strong style="color: var(--primary-navy); font-size: 1rem;">{{ $invoice->invoice_number }}</strong>
                            
                            @if($invoice->status === 'paid')
                                <span style="background: #e6f4ea; color: #137333; font-size: 0.7rem; font-weight: 700; padding: 0.15rem 0.5rem; border-radius: 4px; text-transform: uppercase;">Paid</span>
                            @elseif($invoice->status === 'unpaid')
                                <span style="background: #fef7e0; color: #b06000; font-size: 0.7rem; font-weight: 700; padding: 0.15rem 0.5rem; border-radius: 4px; text-transform: uppercase;">Unpaid</span>
                            @else
                                <span style="background: #fce8e6; color: #c5221f; font-size: 0.7rem; font-weight: 700; padding: 0.15rem 0.5rem; border-radius: 4px; text-transform: uppercase;">{{ $invoice->status }}</span>
                            @endif
                        </div>
                        <div style="color: var(--text-main); font-weight: 500; font-size: 0.95rem; margin-bottom: 0.25rem;">{{ $invoice->client->name }}</div>
                        <div style="color: var(--text-muted); font-size: 0.75rem;">Due by: {{ $invoice->due_date->format('d M Y') }}</div>
                    </div>

                    <div style="text-align: right; display: flex; flex-direction: column; align-items: flex-end; gap: 0.5rem;">
                        <div style="font-size: 1.15rem; font-weight: 700; color: var(--primary-navy);">€{{ number_format($invoice->total, 2) }}</div>
                        <a href="#" style="font-size: 0.8rem; font-weight: 600; color: var(--primary-cerulean); text-decoration: none;">Manage &rarr;</a>
                    </div>

                </div>
            @endforeach
        </div>
    @endif
@endsection