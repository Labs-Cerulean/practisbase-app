@extends('layouts.app')

@section('page_title', 'Ledger')

@section('content')
    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 2rem;">
        <div>
            <h1 style="font-size: 1.5rem; color: var(--primary-navy); margin-bottom: 0.25rem;">Financial Ledger</h1>
            <p style="color: var(--text-muted); font-size: 0.95rem;">Manage your RFPs, Invoices, and payments.</p>
        </div>
        <a href="/ledger/create" style="background: var(--primary-cerulean); color: white; padding: 0.6rem 1.25rem; border-radius: var(--radius-md); font-weight: 600; font-size: 0.9rem; text-decoration: none; transition: 0.2s;">
            + New Document
        </a>
    </div>

    @if(session('success'))
        <div style="background: #ecfdf5; color: #065f46; padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1.5rem; border: 1px solid #a7f3d0;">
            {{ session('success') }}
        </div>
    @endif
    @if($errors->any())
        <div style="background: #fef2f2; color: #991b1b; padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1.5rem; border: 1px solid #fecaca;">
            @foreach($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; margin-bottom: 2.5rem;">
        <div style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.5rem; box-shadow: var(--shadow-sm);">
            <div style="color: var(--text-muted); font-size: 0.85rem; font-weight: 600; text-transform: uppercase; margin-bottom: 0.5rem;">Total Unpaid (Active)</div>
            <div style="font-size: 2rem; font-weight: 700; color: #dc2626;">€{{ number_format($totalUnpaid, 2) }}</div>
        </div>
        <div style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.5rem; box-shadow: var(--shadow-sm);">
            <div style="color: var(--text-muted); font-size: 0.85rem; font-weight: 600; text-transform: uppercase; margin-bottom: 0.5rem;">Total Collected</div>
            <div style="font-size: 2rem; font-weight: 700; color: #10b981;">€{{ number_format($totalPaid, 2) }}</div>
        </div>
    </div>

    @if($invoices->isEmpty())
        <div style="padding: 4rem 2rem; border: 2px dashed var(--border-light); border-radius: var(--radius-md); text-align: center; background: white;">
            <p style="color: var(--text-muted); margin-bottom: 1.25rem;">No documents generated yet.</p>
        </div>
    @else
        <div style="display: flex; flex-direction: column; gap: 2rem;">
            @foreach($invoices as $parent)
                @php
                    // --- THE PROJECT SITUATION MATH ---
                    $familyPaid = $parent->amount_paid + $parent->childDocuments->sum('amount_paid');
                    $familyBalance = max(0, $parent->total - $familyPaid);
                    $familyCredits = $parent->childDocuments->where('type', 'credit_note')->sum('total');
                @endphp

                <div style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); overflow: hidden; box-shadow: var(--shadow-sm);">
                    
                    <div style="padding: 1.5rem; border-bottom: 1px solid var(--border-light); border-left: 4px solid {{ $parent->type === 'rfp' ? '#4f46e5' : 'var(--primary-cerulean)' }};">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                            <div>
                                <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.25rem;">
                                    <strong style="color: var(--primary-navy); font-size: 1.2rem;">{{ $parent->invoice_number }}</strong>
                                    <span style="background: {{ $parent->type === 'rfp' ? '#e0e7ff' : '#e0f2fe' }}; color: {{ $parent->type === 'rfp' ? '#4338ca' : '#0369a1' }}; font-size: 0.7rem; font-weight: 700; padding: 0.15rem 0.5rem; border-radius: 4px;">
                                        {{ $parent->type === 'rfp' ? 'RFP / PROJECT MASTER' : 'TAX INVOICE' }}
                                    </span>
                                </div>
                                <div style="color: var(--text-main); font-weight: 600; font-size: 1rem;">{{ $parent->client->name }}</div>
                            </div>
                            <div style="text-align: right;">
                                <div style="font-size: 1.25rem; font-weight: 700; color: var(--primary-navy);">€{{ number_format($parent->total, 2) }}</div>
                            </div>
                        </div>

                        <div style="margin-top: 1rem;">
                            @include('invoices.partials.actions', ['document' => $parent, 'maxPayable' => $familyBalance])
                        </div>
                    </div>

                    @if($parent->childDocuments->count() > 0)
                        <div style="background: #f8fafc; padding: 1.5rem;">
                            <div style="font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 1rem;">Linked Documents</div>
                            
                            @foreach($parent->childDocuments as $child)
                                <div style="margin-left: 1rem; border-left: 2px solid #cbd5e1; padding-left: 1.5rem; margin-bottom: 1.5rem; position: relative;">
                                    <div style="position: absolute; left: 0; top: 20px; width: 1.5rem; height: 2px; background: #cbd5e1;"></div>
                                    
                                    <div style="background: white; border: 1px solid var(--border-light); border-radius: 8px; padding: 1rem; box-shadow: 0 1px 2px rgba(0,0,0,0.02);">
                                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                                            <div style="display: flex; align-items: center; gap: 0.5rem;">
                                                <strong style="color: var(--primary-navy); font-size: 1rem;">{{ $child->invoice_number }}</strong>
                                                <span style="background: {{ $child->type === 'credit_note' ? '#fee2e2' : '#f0fdf4' }}; color: {{ $child->type === 'credit_note' ? '#b91c1c' : '#166534' }}; font-size: 0.65rem; font-weight: 700; padding: 0.1rem 0.4rem; border-radius: 4px;">
                                                    {{ $child->type === 'credit_note' ? 'CREDIT NOTE' : 'MILESTONE INVOICE' }}
                                                </span>
                                            </div>
                                            <div style="font-weight: 700; color: {{ $child->type === 'credit_note' ? '#dc2626' : 'var(--primary-navy)' }};">
                                                {{ $child->type === 'credit_note' ? '-' : '' }}€{{ number_format($child->total, 2) }}
                                            </div>
                                        </div>
                                        
                                        @include('invoices.partials.actions', ['document' => $child, 'maxPayable' => $child->total - $child->amount_paid])
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <div style="background: var(--primary-navy); color: white; padding: 1rem 1.5rem; display: flex; justify-content: space-between; align-items: center;">
                        <div style="font-size: 0.85rem; text-transform: uppercase; font-weight: 600; color: #94a3b8;">Project Situation</div>
                        <div style="display: flex; gap: 2rem;">
                            <div style="text-align: right;">
                                <div style="font-size: 0.75rem; color: #cbd5e1;">Total Value</div>
                                <div style="font-weight: 600;">€{{ number_format($parent->total, 2) }}</div>
                            </div>
                            <div style="text-align: right;">
                                <div style="font-size: 0.75rem; color: #cbd5e1;">Total Paid</div>
                                <div style="font-weight: 600; color: #34d399;">€{{ number_format($familyPaid, 2) }}</div>
                            </div>
                            <div style="text-align: right;">
                                <div style="font-size: 0.75rem; color: #cbd5e1;">Outstanding</div>
                                <div style="font-weight: 700; color: {{ $familyBalance > 0 ? '#fca5a5' : '#cbd5e1' }};">€{{ number_format($familyBalance, 2) }}</div>
                            </div>
                        </div>
                    </div>

                </div>
            @endforeach
        </div>
    @endif
@endsection