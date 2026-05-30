@if($document->payments->count() > 0)
    <div style="margin-top: 1rem; margin-bottom: 1rem; background: #f8fafc; border: 1px solid var(--border-light); border-radius: 6px; padding: 0.75rem;">
        <div style="font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 0.5rem; border-bottom: 1px solid #e2e8f0; padding-bottom: 0.25rem;">Payment History</div>
        
        @foreach($document->payments as $payment)
            <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.25rem 0; font-size: 0.85rem;">
                <div>
                    <span style="color: var(--text-main); font-weight: 500;">€{{ number_format($payment->amount, 2) }}</span>
                    <span style="color: var(--text-muted); margin-left: 0.5rem;">on {{ $payment->payment_date->format('d M Y') }}</span>
                </div>
                
                <div style="display: flex; gap: 1rem; align-items: center;">
                    @if($document->type === 'invoice')
                        <a href="/ledger/payments/{{ $payment->id }}/receipt" style="color: var(--primary-cerulean); text-decoration: none; font-weight: 600; font-size: 0.8rem; background: #e0f2fe; padding: 0.2rem 0.5rem; border-radius: 4px; transition: 0.2s;" onmouseover="this.style.background='#bae6fd'" onmouseout="this.style.background='#e0f2fe'">
                            ↓ Receipt
                        </a>
                    @else
                        <span style="color: #94a3b8; font-size: 0.75rem; font-style: italic;" title="Convert RFP to Invoice to issue receipts.">Receipt Locked</span>
                    @endif

                    <form action="/ledger/payments/{{ $payment->id }}" method="POST" onsubmit="return confirm('Reverse this payment? This will alter your fiscal report.');" style="margin: 0;">
                        @csrf @method('DELETE')
                        <button type="submit" style="color: #ef4444; background: none; border: none; font-size: 0.8rem; cursor: pointer; font-weight: 600; padding: 0;">✖ Reverse</button>
                    </form>
                    
                    @if($document->type === 'rfp' && $document->childDocuments && $document->childDocuments->where('type', 'invoice')->count() > 0)
                        <form action="/ledger/payments/{{ $payment->id }}/transfer" method="POST" style="margin: 0;">
                            @csrf @method('PATCH')
                            <select name="target_invoice_id" onchange="this.form.submit()" style="font-size: 0.75rem; padding: 0.2rem; border-radius: 4px; border: 1px solid #cbd5e1; background: white; color: var(--primary-navy);">
                                <option value="">↳ Move to Invoice...</option>
                                @foreach($document->childDocuments->where('type', 'invoice') as $childInv)
                                    <option value="{{ $childInv->id }}">{{ $childInv->invoice_number }}</option>
                                @endforeach
                            </select>
                        </form>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
@endif

<div style="border-top: 1px solid var(--border-light); padding-top: 1rem; display: flex; gap: 0.5rem; flex-wrap: wrap;">
    <a href="/ledger/{{ $document->id }}/pdf" style="flex: 1; text-align: center; padding: 0.5rem; background: white; border: 1px solid var(--border-light); color: var(--text-main); border-radius: 6px; font-weight: 600; font-size: 0.85rem; text-decoration: none; transition: 0.2s;">Download PDF</a>
    
    @if(in_array($document->status, ['unpaid', 'overdue', 'partially_paid']))
        <form action="/ledger/{{ $document->id }}/pay" method="POST" style="flex: 1; margin: 0; display: flex; gap: 0.5rem; align-items: center;" onsubmit="return {{ $document->type === 'rfp' ? "confirm('Payments logged against an RFP will NOT appear on your Fiscal Report. Proceed?')" : 'true' }};">
            @csrf
            <input type="date" name="payment_date" value="{{ date('Y-m-d') }}" required style="padding: 0.5rem; border: 2px solid #cbd5e1; border-radius: 6px; font-size: 0.85rem; color: var(--primary-navy); outline: none;">
            
            <div style="position: relative; display: flex; align-items: center; width: 120px;">
                <span style="position: absolute; left: 10px; color: #64748b; font-weight: 600; font-size: 0.95rem;">€</span>
                <input type="number" name="payment_amount" step="0.01" max="{{ $maxPayable }}" value="{{ number_format($maxPayable, 2, '.', '') }}" required style="width: 100%; padding: 0.5rem 0.5rem 0.5rem 1.6rem; border: 2px solid #cbd5e1; border-radius: 6px; font-size: 0.95rem; font-weight: 700; color: var(--primary-navy); background-color: #f8fafc; outline: none;">
            </div>
            
            <button type="submit" style="flex: 1; padding: 0.55rem 1rem; background: #10b981; color: white; border: none; border-radius: 6px; font-weight: 600; font-size: 0.85rem; cursor: pointer;">Log Payment</button>
        </form>
    @endif

    @if(isset($familyBalance) && $familyBalance < 0 && is_null($document->parent_document_id))
        @php $maxRefund = abs($familyBalance); @endphp
        <form action="/ledger/{{ $document->id }}/refund" method="POST" style="flex: 1; margin: 0; display: flex; gap: 0.5rem; align-items: center;" onsubmit="return confirm('Log a refund to the client? This will securely deduct from your total collected revenue.');">
            @csrf
            <input type="date" name="refund_date" value="{{ date('Y-m-d') }}" required style="padding: 0.5rem; border: 2px solid #fde047; border-radius: 6px; font-size: 0.85rem; color: #854d0e; outline: none; background: #fefce8;">
            
            <div style="position: relative; display: flex; align-items: center; width: 120px;">
                <span style="position: absolute; left: 10px; color: #ca8a04; font-weight: 600; font-size: 0.95rem;">€</span>
                <input type="number" name="refund_amount" step="0.01" max="{{ $maxRefund }}" value="{{ number_format($maxRefund, 2, '.', '') }}" required style="width: 100%; padding: 0.5rem 0.5rem 0.5rem 1.6rem; border: 2px solid #fde047; border-radius: 6px; font-size: 0.95rem; font-weight: 700; color: #854d0e; background-color: #fefce8; outline: none;">
            </div>
            
            <button type="submit" style="flex: 1; padding: 0.55rem 1rem; background: #eab308; color: white; border: none; border-radius: 6px; font-weight: 600; font-size: 0.85rem; cursor: pointer;">Issue Refund</button>
        </form>
    @endif

    @if($document->type === 'rfp' && $document->status !== 'cancelled')
        @php
            $existingConversions = $document->childDocuments ? $document->childDocuments->where('type', 'invoice')->sum('total') : 0;
            $maxConvertible = $document->total - $existingConversions;
        @endphp
        
        @if($maxConvertible > 0)
            <form action="/ledger/{{ $document->id }}/convert" method="POST" style="flex: 1; margin: 0; display: flex; gap: 0.5rem; align-items: center;" onsubmit="return confirm('Generate an official Tax Invoice for this amount?');">
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

    @if($document->type === 'invoice' && $document->status !== 'cancelled')
        @php
            $existingCredits = $document->childDocuments ? $document->childDocuments->where('type', 'credit_note')->sum('total') : 0;
            $maxCredit = $document->total - $existingCredits;
        @endphp
        
        @if($maxCredit > 0)
            <form action="/ledger/{{ $document->id }}/credit" method="POST" style="flex: 1; margin: 0; display: flex; gap: 0.5rem; align-items: center;" onsubmit="return confirm('Issue a Credit Note for this amount? This will permanently reduce the taxable value of this invoice.');">
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