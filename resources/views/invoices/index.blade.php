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

    @if(session('revenue_warning'))
        <div style="background: #fef2f2; border: 1px solid #f87171; border-left: 4px solid #dc2626; color: #b91c1c; padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1.5rem; font-weight: 500; font-size: 0.95rem; line-height: 1.4;">
            {!! session('revenue_warning') !!}
        </div>
    @endif

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
        <div style="background: white; padding: 1.25rem; border-radius: var(--radius-md); border: 1px solid var(--border-light); box-shadow: var(--shadow-sm); border-left: 4px solid #10b981;">
            <div style="color: var(--text-muted); font-size: 0.8rem; font-weight: 600; text-transform: uppercase;">Collected Revenue</div>
            <div style="font-size: 1.75rem; font-weight: 700; color: #047857; margin-top: 0.25rem;">€{{ number_format($totalPaid, 2) }}</div>
            <div style="font-size: 0.7rem; color: var(--text-muted); margin-top: 0.25rem;">Tax Invoices Only</div>
        </div>
        <div style="background: white; padding: 1.25rem; border-radius: var(--radius-md); border: 1px solid var(--border-light); box-shadow: var(--shadow-sm); border-left: 4px solid #ef4444;">
            <div style="color: var(--text-muted); font-size: 0.8rem; font-weight: 600; text-transform: uppercase;">Outstanding Receivables</div>
            <div style="font-size: 1.75rem; font-weight: 700; color: #b91c1c; margin-top: 0.25rem;">€{{ number_format($totalUnpaid, 2) }}</div>
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
                            @if($doc->vat_total > 0)
                                <div style="font-size: 0.7rem; color: var(--text-muted);">Inc. VAT</div>
                            @endif
                        </div>
                    </div>

                    <div style="border-top: 1px solid var(--border-light); padding-top: 1rem; display: flex; gap: 0.5rem; flex-wrap: wrap;">
                        <a href="/ledger/{{ $doc->id }}/pdf" style="flex: 1; text-align: center; padding: 0.5rem; background: white; border: 1px solid var(--border-light); color: var(--text-main); border-radius: 6px; font-weight: 600; font-size: 0.85rem; text-decoration: none; transition: 0.2s;">Download PDF</a>
                        
                        @if($doc->type === 'rfp' && $doc->status !== 'cancelled')
                            <form action="/ledger/{{ $doc->id }}/convert" method="POST" style="flex: 1; margin: 0;">
                                @csrf
                                <button type="submit" style="width: 100%; padding: 0.5rem; background: var(--primary-cerulean); color: white; border: none; border-radius: 6px; font-weight: 600; font-size: 0.85rem; cursor: pointer;">Convert to Invoice</button>
                            </form>
                        @elseif($doc->type === 'invoice' && $doc->status !== 'cancelled')
                            <form action="/ledger/{{ $doc->id }}/cancel" method="POST" style="flex: 1; margin: 0;" onsubmit="return confirm('Are you sure you want to cancel this invoice and issue a Credit Note? This action cannot be undone.');">
                                @csrf
                                <button type="submit" style="width: 100%; padding: 0.5rem; background: #fef2f2; color: #ef4444; border: 1px solid #fca5a5; border-radius: 6px; font-weight: 600; font-size: 0.85rem; cursor: pointer;">Issue Credit Note</button>
                            </form>
                        @endif
                    </div>

                    <div style="border-top: 1px solid var(--border-light); padding-top: 1rem; display: flex; gap: 0.5rem; flex-wrap: wrap;">
                        <a href="/ledger/{{ $doc->id }}/pdf" style="flex: 1; text-align: center; padding: 0.5rem; background: white; border: 1px solid var(--border-light); color: var(--text-main); border-radius: 6px; font-weight: 600; font-size: 0.85rem; text-decoration: none;">Download PDF</a>
                        
                        @if(in_array($doc->status, ['unpaid', 'overdue']))
                            <form action="/ledger/{{ $doc->id }}/pay" method="POST" style="flex: 1; margin: 0;">
                                @csrf
                                <button type="submit" style="width: 100%; padding: 0.5rem; background: #ecfdf5; color: #059669; border: 1px solid #10b981; border-radius: 6px; font-weight: 600; font-size: 0.85rem; cursor: pointer; transition: 0.2s;">Mark as Paid</button>
                            </form>
                        @endif

                        @if($doc->type === 'rfp' && $doc->status !== 'cancelled')
                            <form action="/ledger/{{ $doc->id }}/convert" method="POST" style="flex: 1; margin: 0;">
                                @csrf
                                <button type="submit" style="width: 100%; padding: 0.5rem; background: var(--primary-cerulean); color: white; border: none; border-radius: 6px; font-weight: 600; font-size: 0.85rem; cursor: pointer;">Convert to Invoice</button>
                            </form>
                        @elseif($doc->type === 'invoice' && $doc->status !== 'cancelled')
                            <form action="/ledger/{{ $doc->id }}/cancel" method="POST" style="flex: 1; margin: 0;" onsubmit="return confirm('Are you sure you want to cancel this invoice and issue a Credit Note? This action cannot be undone.');">
                                @csrf
                                <button type="submit" style="width: 100%; padding: 0.5rem; background: #fef2f2; color: #ef4444; border: 1px solid #fca5a5; border-radius: 6px; font-weight: 600; font-size: 0.85rem; cursor: pointer;">Issue Credit Note</button>
                            </form>
                        @endif
                    </div>

                </div>
            @endforeach
        </div>
    @endif
@endsection