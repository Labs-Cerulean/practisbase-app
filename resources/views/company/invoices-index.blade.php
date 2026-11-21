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

    @isset($clients)
        <form method="GET" action="/company/invoices" style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1rem; margin-bottom: 1rem; display: flex; gap: 0.75rem; flex-wrap: wrap; align-items: center; box-shadow: var(--shadow-sm);">
            <input type="search" name="q" value="{{ request('q') }}" placeholder="Search number, client, or note" style="flex: 2; min-width: 12rem; padding: 0.5rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); font-size: 0.85rem; color: var(--primary-navy);">
            <select name="client_id" style="flex: 1; min-width: 10rem; padding: 0.5rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); font-size: 0.85rem; color: var(--primary-navy); background: white;">
                <option value="">All clients</option>
                @foreach($clients as $client)
                    <option value="{{ $client->id }}" @selected((string) request('client_id') === (string) $client->id)>{{ $client->name }}</option>
                @endforeach
            </select>
            <select name="type" style="min-width: 8rem; padding: 0.5rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); font-size: 0.85rem; background: white;">
                <option value="">All types</option>
                <option value="rfp" @selected(request('type') === 'rfp')>Proformas</option>
                <option value="invoice" @selected(request('type') === 'invoice')>Tax invoices</option>
            </select>
            <select name="status" style="min-width: 8rem; padding: 0.5rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); font-size: 0.85rem; background: white;">
                <option value="">All balances</option>
                <option value="open" @selected(request('status') === 'open')>Open</option>
                <option value="settled" @selected(request('status') === 'settled')>Settled</option>
            </select>
            <select name="sort" style="min-width: 9rem; padding: 0.5rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); font-size: 0.85rem; background: white;">
                <option value="date_desc" @selected(request('sort', 'date_desc') === 'date_desc')>Newest first</option>
                <option value="date_asc" @selected(request('sort') === 'date_asc')>Oldest first</option>
                <option value="value_desc" @selected(request('sort') === 'value_desc')>Highest value</option>
                <option value="value_asc" @selected(request('sort') === 'value_asc')>Lowest value</option>
                <option value="client_asc" @selected(request('sort') === 'client_asc')>Client name</option>
            </select>
            <select name="group" style="min-width: 9rem; padding: 0.5rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); font-size: 0.85rem; background: white;">
                <option value="">No grouping</option>
                <option value="client" @selected(request('group') === 'client')>Group by client</option>
            </select>
            <button type="submit" style="padding: 0.5rem 0.9rem; background: var(--primary-navy); color: white; border: none; border-radius: var(--radius-md); font-weight: 600; font-size: 0.85rem; cursor: pointer;">Filter</button>
            @if(request()->anyFilled(['q', 'client_id', 'type', 'status', 'sort', 'group']))
                <a href="/company/invoices" style="padding: 0.5rem 0.9rem; background: #f1f5f9; color: #475569; text-decoration: none; border: 1px solid var(--border-light); border-radius: var(--radius-md); font-weight: 600; font-size: 0.85rem;">Clear</a>
            @endif
        </form>
    @endisset

    <div style="display: grid; gap: 1rem;">
        @php $companyClientHeading = null; @endphp
        @forelse($documents as $doc)
            @if(!empty($groupByClient) && $companyClientHeading !== $doc->company_client_id)
                @php $companyClientHeading = $doc->company_client_id; @endphp
                <h2 style="margin: 0.25rem 0 0; font-size: 1rem; color: var(--primary-navy); border-bottom: 1px solid var(--border-light); padding-bottom: 0.35rem;">{{ $doc->client->name ?? 'Client' }}</h2>
            @endif
            @php
                $superseded = $doc->type === 'rfp' && $doc->status === 'converted';
                $balance = $superseded ? 0.0 : $doc->balance();
                $typeLabel = match ($doc->type) {
                    'rfp' => 'RFP',
                    'invoice' => 'Invoice',
                    default => strtoupper($doc->type),
                };
                $proforma = ($doc->type === 'invoice' && $doc->linkedDocument && $doc->linkedDocument->type === 'rfp')
                    ? $doc->linkedDocument
                    : null;
            @endphp
            <div style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.15rem 1.25rem; box-shadow: var(--shadow-sm);">
                <div style="display: flex; justify-content: space-between; gap: 1rem; flex-wrap: wrap; margin-bottom: 0.75rem;">
                    <div>
                        <div style="font-weight: 700; color: var(--primary-navy);">{{ $doc->document_number }} · {{ $typeLabel }}</div>
                        <div style="font-size: 0.85rem; color: var(--text-muted); margin-top: 0.2rem;">
                            {{ $doc->client->name ?? 'Client' }} · {{ $doc->issue_date->format('d M Y') }}
                            @if($label = $doc->coverageLabel())
                                · {{ $label }}
                            @endif
                            @if($superseded) · superseded @endif
                        </div>
                    </div>
                    <div style="text-align: right;">
                        <div style="font-size: 1.2rem; font-weight: 700; color: var(--primary-navy);">€{{ number_format((float) $doc->total, 2) }}</div>
                        @if($superseded)
                            <div style="font-size: 0.8rem; color: var(--text-muted);">Superseded</div>
                        @else
                            <div style="font-size: 0.8rem; color: {{ $balance > 0.009 ? '#b45309' : '#059669' }};">
                                Balance €{{ number_format($balance, 2) }}
                            </div>
                        @endif
                    </div>
                </div>

                <div style="display: flex; flex-wrap: wrap; gap: 0.5rem; align-items: center;">
                    <a href="/company/invoices/{{ $doc->id }}/pdf" style="font-size: 0.8rem; font-weight: 600; color: var(--primary-cerulean); text-decoration: none; padding: 0.4rem 0.7rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">PDF</a>

                    @if($doc->canDelete())
                        <form method="POST" action="/company/invoices/{{ $doc->id }}" onsubmit="return confirm('Delete this unpaid proforma? It has no tax effect.');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" style="font-size: 0.8rem; font-weight: 600; background: white; color: #b91c1c; border: 1px solid #fecaca; border-radius: var(--radius-md); padding: 0.4rem 0.7rem; cursor: pointer;">Delete</button>
                        </form>
                    @endif

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

                @if($proforma)
                    <div style="margin-top: 0.75rem; padding-top: 0.75rem; border-top: 1px dashed var(--border-light); font-size: 0.8rem; color: var(--text-muted);">
                        Superseded by this invoice:
                        <a href="/company/invoices/{{ $proforma->id }}/pdf" style="color: var(--primary-cerulean); font-weight: 600; text-decoration: none;">{{ $proforma->document_number }}</a>
                        · proforma {{ $proforma->issue_date->format('d M Y') }}
                    </div>
                @endif

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
                {{ request()->anyFilled(['q', 'client_id', 'type', 'status', 'sort', 'group']) ? 'No documents match this search.' : 'No documents yet. Create a proforma (RFP) for your first B2B client — it converts to a tax invoice when paid.' }}
            </div>
        @endforelse
    </div>
@endsection
