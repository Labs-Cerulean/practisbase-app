@extends('layouts.app')

@section('page_title', 'Company invoices')

@section('content')
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.25rem;">
        <div>
            <h1 style="font-size: 1.4rem; color: var(--primary-navy); margin: 0 0 0.25rem;">Invoices &amp; RFPs</h1>
            <p style="margin: 0; color: var(--text-muted); font-size: 0.9rem;">Proforma until paid · VAT commits on tax invoice conversion.</p>
        </div>
        <a href="/company/invoices/create" style="background: var(--primary-cerulean); color: white; padding: 0.55rem 1rem; border-radius: var(--radius-md); font-weight: 600; font-size: 0.85rem; text-decoration: none;">+ Proforma</a>
    </div>

    @if(session('success'))
        <div style="margin-bottom: 1rem; padding: 0.85rem 1rem; background: #ecfdf5; border: 1px solid #a7f3d0; border-radius: var(--radius-md); color: #065f46; font-size: 0.9rem;">{{ session('success') }}</div>
    @endif
    @if(session('warning'))
        <div style="margin-bottom: 1rem; padding: 0.85rem 1rem; background: #fffbeb; border: 1px solid #fde68a; border-radius: var(--radius-md); color: #92400e; font-size: 0.9rem;">{{ session('warning') }}</div>
    @endif
    @if($errors->any())
        <div style="margin-bottom: 1rem; padding: 0.85rem 1rem; background: #fef2f2; border: 1px solid #fecaca; border-radius: var(--radius-md); color: #991b1b; font-size: 0.9rem;">{{ $errors->first() }}</div>
    @endif

    <div style="display: grid; gap: 1rem;">
        @forelse($documents as $doc)
            @php
                $balance = $doc->balance();
                $typeLabel = match ($doc->type) {
                    'rfp' => 'RFP',
                    'invoice' => 'Invoice',
                    default => strtoupper($doc->type),
                };
            @endphp
            <div style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.15rem 1.25rem; box-shadow: var(--shadow-sm);">
                <div style="display: flex; justify-content: space-between; gap: 1rem; flex-wrap: wrap; margin-bottom: 0.75rem;">
                    <div>
                        <div style="font-weight: 700; color: var(--primary-navy);">{{ $doc->document_number }} · {{ $typeLabel }}</div>
                        <div style="font-size: 0.85rem; color: var(--text-muted); margin-top: 0.2rem;">
                            {{ $doc->client->name ?? 'Client' }} · {{ $doc->issue_date->format('d M Y') }}
                            @if($doc->status === 'converted') · converted @endif
                        </div>
                    </div>
                    <div style="text-align: right;">
                        <div style="font-size: 1.2rem; font-weight: 700; color: var(--primary-navy);">€{{ number_format((float) $doc->total, 2) }}</div>
                        <div style="font-size: 0.8rem; color: {{ $balance > 0.009 ? '#b45309' : '#059669' }};">
                            Balance €{{ number_format($balance, 2) }}
                        </div>
                    </div>
                </div>

                <div style="display: flex; flex-wrap: wrap; gap: 0.5rem; align-items: center;">
                    <a href="/company/invoices/{{ $doc->id }}/pdf" style="font-size: 0.8rem; font-weight: 600; color: var(--primary-cerulean); text-decoration: none; padding: 0.4rem 0.7rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">PDF</a>

                    @if($doc->type === 'rfp' && $doc->status !== 'converted')
                        <form method="POST" action="/company/invoices/{{ $doc->id }}/convert" onsubmit="return confirm('Convert this RFP to a tax invoice with 18% VAT?');">
                            @csrf
                            <button type="submit" style="font-size: 0.8rem; font-weight: 600; background: white; color: var(--primary-navy); border: 1px solid var(--border-light); border-radius: var(--radius-md); padding: 0.4rem 0.7rem; cursor: pointer;">Convert to invoice</button>
                        </form>
                    @endif

                    @if($doc->type === 'invoice')
                        <form method="POST" action="/company/invoices/{{ $doc->id }}/credit" onsubmit="return confirm('Issue a full credit note against this invoice?');">
                            @csrf
                            <button type="submit" style="font-size: 0.8rem; font-weight: 600; background: white; color: #b91c1c; border: 1px solid #fecaca; border-radius: var(--radius-md); padding: 0.4rem 0.7rem; cursor: pointer;">Credit note</button>
                        </form>
                    @endif

                    @if(in_array($doc->type, ['invoice', 'rfp'], true) && $doc->status !== 'converted' && $balance > 0.009)
                        <form method="POST" action="/company/invoices/{{ $doc->id }}/pay" style="display: flex; flex-wrap: wrap; gap: 0.35rem; align-items: center; margin-left: auto;">
                            @csrf
                            <input type="number" name="amount" step="0.01" min="0.01" value="{{ number_format($balance, 2, '.', '') }}" required style="width: 6.5rem; padding: 0.35rem 0.5rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); font-size: 0.8rem;">
                            <input type="date" name="payment_date" value="{{ date('Y-m-d') }}" max="{{ date('Y-m-d') }}" required style="padding: 0.35rem 0.5rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); font-size: 0.8rem;">
                            <select name="payment_method" style="padding: 0.35rem 0.5rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); font-size: 0.8rem; background: white;">
                                <option value="bank_transfer">Bank</option>
                                <option value="stripe">Stripe</option>
                                <option value="other">Other</option>
                            </select>
                            <button type="submit" style="font-size: 0.8rem; font-weight: 600; background: #059669; color: white; border: none; border-radius: var(--radius-md); padding: 0.4rem 0.7rem; cursor: pointer;">Log payment</button>
                        </form>
                    @endif
                </div>

                @if($doc->childDocuments->where('type', 'credit_note')->count())
                    <div style="margin-top: 0.75rem; font-size: 0.8rem; color: var(--text-muted);">
                        Credit notes:
                        @foreach($doc->childDocuments->where('type', 'credit_note') as $cn)
                            <a href="/company/invoices/{{ $cn->id }}/pdf" style="color: var(--primary-cerulean); font-weight: 600; text-decoration: none;">{{ $cn->document_number }}</a>
                            (€{{ number_format((float) $cn->total, 2) }})@if(! $loop->last), @endif
                        @endforeach
                    </div>
                @endif
            </div>
        @empty
            <div style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 2rem; text-align: center; color: var(--text-muted);">
                No documents yet. Create a proforma (RFP) for your first B2B client — it converts to a tax invoice when paid.
            </div>
        @endforelse
    </div>
@endsection
