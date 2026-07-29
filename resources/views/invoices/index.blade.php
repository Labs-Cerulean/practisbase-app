@extends('layouts.app')

@section('page_title', 'Invoices')

@section('content')
    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 2rem;">
        <div>
            <h1 style="font-size: 1.5rem; color: var(--primary-navy); margin-bottom: 0.25rem;">Financial Ledger</h1>
            <p style="color: var(--text-muted); font-size: 0.95rem; margin: 0;">Manage RFPs, invoices, and payments.</p>
            <p style="color: var(--text-muted); font-size: 0.8rem; margin: 0.4rem 0 0; line-height: 1.4;">
                RFP cash is <strong style="color: var(--text-main);">non-fiscal</strong> (€0.00 official weight) until converted to a tax invoice.
            </p>
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
    @if(session('revenue_warning'))
        <div style="background: #fef2f2; color: #991b1b; padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1.5rem; border: 1px solid #fecaca; border-left: 4px solid #dc2626;">
            <strong>Article 11 threshold:</strong> {{ session('revenue_warning') }}
        </div>
    @endif
    @if($errors->any())
        <div style="background: #fef2f2; color: #991b1b; padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1.5rem; border: 1px solid #fecaca;">
            @foreach($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
        
        <div style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.5rem; box-shadow: var(--shadow-sm);">
            <div style="color: var(--text-muted); font-size: 0.85rem; font-weight: 700; text-transform: uppercase; margin-bottom: 1rem; border-bottom: 1px solid #e2e8f0; padding-bottom: 0.5rem;">Pipeline & Revenue</div>
            
            <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 1rem;">
                <div style="font-size: 0.85rem; color: var(--text-main); font-weight: 600;">Total Projected</div>
                <div style="font-size: 1.5rem; font-weight: 700; color: var(--primary-navy);">€{{ number_format($totalPipeline, 2) }}</div>
            </div>
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem; padding-top: 0.5rem; border-top: 1px dashed #e2e8f0;">
                <div style="font-size: 0.8rem; color: var(--text-muted);">Official Invoiced</div>
                <div style="font-size: 0.95rem; font-weight: 600; color: #0369a1;">€{{ number_format($netInvoiced, 2) }}</div>
            </div>
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div style="font-size: 0.8rem; color: var(--text-muted);">Unbilled (RFP · non-fiscal)</div>
                <div style="font-size: 0.95rem; font-weight: 600; color: #4338ca;">€{{ number_format($unbilledPipeline, 2) }}</div>
            </div>
        </div>

        <div style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.5rem; box-shadow: var(--shadow-sm);">
            <div style="color: var(--text-muted); font-size: 0.85rem; font-weight: 700; text-transform: uppercase; margin-bottom: 1rem; border-bottom: 1px solid #e2e8f0; padding-bottom: 0.5rem;">Cash Collected</div>
            
            <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 1rem;">
                <div style="font-size: 0.85rem; color: var(--text-main); font-weight: 600;">Total Received</div>
                <div style="font-size: 1.5rem; font-weight: 700; color: #10b981;">€{{ number_format($totalCollected, 2) }}</div>
            </div>
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem; padding-top: 0.5rem; border-top: 1px dashed #e2e8f0;">
                <div style="font-size: 0.8rem; color: var(--text-muted);">Official (Invoices)</div>
                <div style="font-size: 0.95rem; font-weight: 600; color: #059669;">€{{ number_format($invoiceCash, 2) }}</div>
            </div>
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div style="font-size: 0.8rem; color: var(--text-muted);">Unofficial (RFPs · €0 fiscal)</div>
                <div style="font-size: 0.95rem; font-weight: 600; color: #64748b;">€{{ number_format($rfpCash, 2) }}</div>
            </div>
        </div>

        <div style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.5rem; box-shadow: var(--shadow-sm);">
            <div style="color: var(--text-muted); font-size: 0.85rem; font-weight: 700; text-transform: uppercase; margin-bottom: 1rem; border-bottom: 1px solid #e2e8f0; padding-bottom: 0.5rem;">Outstanding Dues</div>
            
            <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 1rem;">
                <div style="font-size: 0.85rem; color: var(--text-main); font-weight: 600;">Total Global Dues</div>
                <div style="font-size: 1.5rem; font-weight: 700; color: #dc2626;">€{{ number_format($totalDues, 2) }}</div>
            </div>
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem; padding-top: 0.5rem; border-top: 1px dashed #e2e8f0;">
                <div style="font-size: 0.8rem; color: var(--text-muted);">Official Dues</div>
                <div style="font-size: 0.95rem; font-weight: 600; color: #ef4444;">€{{ number_format($officialDues, 2) }}</div>
            </div>
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div style="font-size: 0.8rem; color: var(--text-muted);">Unbilled Dues</div>
                <div style="font-size: 0.95rem; font-weight: 600; color: #f87171;">€{{ number_format($unbilledDues, 2) }}</div>
            </div>
        </div>
    </div>

    <form method="GET" action="/ledger" style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-md); padding: 1rem; margin-bottom: 2rem; display: flex; gap: 1rem; flex-wrap: wrap; align-items: center; box-shadow: var(--shadow-sm);">
        
        <select name="client_id" style="flex: 1; min-width: 150px; padding: 0.5rem; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.85rem; color: var(--primary-navy); outline: none;">
            <option value="">All Clients</option>
            @foreach($clients as $client)
                <option value="{{ $client->id }}" {{ request('client_id') == $client->id ? 'selected' : '' }}>{{ $client->name }}</option>
            @endforeach
        </select>

        <select name="doc_type" style="flex: 1; min-width: 150px; padding: 0.5rem; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.85rem; color: var(--primary-navy); outline: none;">
            <option value="">All document types</option>
            <option value="invoice" {{ request('doc_type') == 'invoice' ? 'selected' : '' }}>Tax invoices only</option>
            <option value="rfp" {{ request('doc_type') == 'rfp' ? 'selected' : '' }}>RFPs only (non-fiscal)</option>
        </select>

        <select name="status" style="flex: 1; min-width: 150px; padding: 0.5rem; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.85rem; color: var(--primary-navy); outline: none;">
            <option value="">All Balances</option>
            <option value="open" {{ request('status') == 'open' ? 'selected' : '' }}>Outstanding / Open</option>
            <option value="balanced" {{ request('status') == 'balanced' ? 'selected' : '' }}>Balanced / Paid</option>
            <option value="overpaid" {{ request('status') == 'overpaid' ? 'selected' : '' }}>Overpaid / Needs Refund</option>
        </select>

        <select name="sort" style="flex: 1; min-width: 150px; padding: 0.5rem; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.85rem; color: var(--primary-navy); outline: none;">
            <option value="date_desc" {{ request('sort') == 'date_desc' ? 'selected' : '' }}>Newest First</option>
            <option value="date_asc" {{ request('sort') == 'date_asc' ? 'selected' : '' }}>Oldest First</option>
            <option value="value_desc" {{ request('sort') == 'value_desc' ? 'selected' : '' }}>Highest Value</option>
            <option value="value_asc" {{ request('sort') == 'value_asc' ? 'selected' : '' }}>Lowest Value</option>
        </select>

        <div style="display: flex; gap: 0.5rem;">
            <button type="submit" style="padding: 0.5rem 1rem; background: var(--primary-navy); color: white; border: none; border-radius: 6px; font-weight: 600; font-size: 0.85rem; cursor: pointer;">Filter</button>
            @if(request()->anyFilled(['client_id', 'status', 'sort', 'doc_type']))
                <a href="/ledger" style="padding: 0.5rem 1rem; background: #f1f5f9; color: #475569; text-decoration: none; border: 1px solid #cbd5e1; border-radius: 6px; font-weight: 600; font-size: 0.85rem;">Clear</a>
            @endif
        </div>
    </form>

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

                <div style="background: {{ $familyBalance == 0 ? '#f8fafc' : 'white' }}; border: 1px solid var(--border-light); border-radius: var(--radius-lg); overflow: hidden; box-shadow: var(--shadow-sm); opacity: {{ $familyBalance == 0 ? '0.75' : '1' }}; transition: 0.3s;" onmouseover="this.style.opacity='1'" onmouseout="this.style.opacity='{{ $familyBalance == 0 ? '0.75' : '1' }}'">
                    
                    <div style="padding: 1.5rem; border-bottom: 1px solid var(--border-light); border-left: 4px solid {{ $familyBalance == 0 ? '#10b981' : ($parent->type === 'rfp' ? '#4f46e5' : 'var(--primary-cerulean)') }};">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                            <div>
                                <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.25rem;">
                                    <strong style="color: var(--primary-navy); font-size: 1.2rem; text-decoration: {{ $familyBalance == 0 && $effectiveValue == 0 ? 'line-through' : 'none' }};">{{ $parent->invoice_number }}</strong>
                                    
                                    @if($familyBalance == 0)
                                        <span style="background: #d1fae5; color: #065f46; font-size: 0.7rem; font-weight: 700; padding: 0.15rem 0.5rem; border-radius: 4px;">✔ BALANCED & CLOSED</span>
                                    @else
                                        <span style="background: {{ $parent->type === 'rfp' ? '#e0e7ff' : '#e0f2fe' }}; color: {{ $parent->type === 'rfp' ? '#4338ca' : '#0369a1' }}; font-size: 0.7rem; font-weight: 700; padding: 0.15rem 0.5rem; border-radius: 4px;">
                                            {{ $parent->type === 'rfp' ? 'RFP / PROJECT MASTER' : 'TAX INVOICE' }}
                                        </span>
                                    @endif
                                </div>
                                <div style="color: var(--text-main); font-weight: 600; font-size: 1rem;">{{ $parent->client->name }}</div>
                            </div>
                            <div style="text-align: right;">
                                <div style="font-size: 1.25rem; font-weight: 700; color: var(--primary-navy);">€{{ number_format($parent->total, 2) }}</div>
                            </div>
                        </div>

                        <div style="margin-top: 1rem;">
                            @include('invoices.partials.actions', ['document' => $parent, 'maxPayable' => max(0, $familyBalance), 'familyBalance' => $familyBalance])
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
                                        
                                        @php
                                            $childCredits = $child->childDocuments ? $child->childDocuments->where('type', 'credit_note')->sum('total') : 0;
                                            $childBalance = ($child->total - $childCredits) - $child->amount_paid;
                                        @endphp
                                        @include('invoices.partials.actions', ['document' => $child, 'maxPayable' => max(0, $childBalance), 'familyBalance' => $childBalance])

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