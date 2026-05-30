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
                    
                    // 1. Sum direct credit notes (Level 1)
                    $familyCredits = $parent->childDocuments->where('type', 'credit_note')->sum('total');
                    
                    // 2. Sum grandchild credit notes (Level 2)
                    foreach($parent->childDocuments as $child) {
                        $familyCredits += $child->childDocuments ? $child->childDocuments->where('type', 'credit_note')->sum('total') : 0;
                    }

                    // 3. The actual legal value of the project after all credits are applied
                    $effectiveValue = $parent->total - $familyCredits;
                    
                    // 4. The Balance (If negative, the client overpaid!)
                    $familyBalance = $effectiveValue - $familyPaid;
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
                            @include('invoices.partials.actions', ['document' => $parent, 'maxPayable' => max(0, $familyBalance)])
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

                                        @if($child->childDocuments && $child->childDocuments->count() > 0)
                                            <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px dashed #e2e8f0;">
                                                @foreach($child->childDocuments as $grandchild)
                                                    <div style="margin-left: 1rem; border-left: 2px solid #fca5a5; padding-left: 1rem; margin-bottom: 0.5rem;">
                                                        <div style="display: flex; justify-content: space-between; align-items: center; font-size: 0.9rem;">
                                                            <div style="display: flex; align-items: center; gap: 0.5rem;">
                                                                <strong style="color: #b91c1c;">{{ $grandchild->invoice_number }}</strong>
                                                                <span style="background: #fee2e2; color: #b91c1c; font-size: 0.6rem; padding: 0.1rem 0.3rem; border-radius: 3px;">CREDIT NOTE</span>
                                                            </div>
                                                            <strong style="color: #dc2626;">-€{{ number_format($grandchild->total, 2) }}</strong>
                                                        </div>
                                                        <div style="margin-top: 0.5rem;">
                                                            @include('invoices.partials.actions', ['document' => $grandchild, 'maxPayable' => 0])
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif
                                        
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <div style="background: var(--primary-navy); color: white; padding: 1rem 1.5rem; display: flex; justify-content: space-between; align-items: center;">
                        <div style="font-size: 0.85rem; text-transform: uppercase; font-weight: 600; color: #94a3b8;">Summary:</div>
                        <div style="display: flex; gap: 2rem;">
                            <div style="text-align: right;">
                                <div style="font-size: 0.75rem; color: #cbd5e1;">Effective Value</div>
                                <div style="font-weight: 600;">€{{ number_format($effectiveValue, 2) }}</div>
                            </div>
                            <div style="text-align: right;">
                                <div style="font-size: 0.75rem; color: #cbd5e1;">Total Paid</div>
                                <div style="font-weight: 600; color: #34d399;">€{{ number_format($familyPaid, 2) }}</div>
                            </div>
                            <div style="text-align: right;">
                                <div style="font-size: 0.75rem; color: {{ $familyBalance < 0 ? '#fde047' : '#cbd5e1' }};">
                                    {{ $familyBalance < 0 ? 'Overpaid (Credit Due)' : 'Outstanding' }}
                                </div>
                                <div style="font-weight: 700; color: {{ $familyBalance > 0 ? '#fca5a5' : ($familyBalance < 0 ? '#fde047' : '#cbd5e1') }};">
                                    {{ $familyBalance < 0 ? '-' : '' }}€{{ number_format(abs($familyBalance), 2) }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
@endsection