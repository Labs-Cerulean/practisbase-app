<details class="expense-fold" style="background: {{ $expense->isReversed() ? '#f8fafc' : 'white' }}; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
    <summary style="display: flex; justify-content: space-between; gap: 0.75rem; align-items: center; padding: 0.7rem 0.85rem; cursor: pointer;">
        <span class="fold-mark" aria-hidden="true" style="color: var(--text-muted); font-size: 0.75rem; flex: 0 0 auto;">▸</span>
        <span style="min-width: 0; flex: 1;">
            <span style="display: block; font-weight: 700; color: var(--primary-navy); font-size: 0.92rem;">
                {{ $expense->supplier->name ?? 'No supplier' }}
                @if($expense->isOwedToDirector())
                    <span style="margin-left: 0.3rem; font-size: 0.65rem; font-weight: 700; letter-spacing: 0.03em; text-transform: uppercase; color: #92400e; background: #fffbeb; border: 1px solid #fde68a; border-radius: var(--radius-md); padding: 0.1rem 0.35rem; vertical-align: middle;">Owed</span>
                @endif
                @if(!$expense->supplier && !$expense->isReversed())
                    <span style="margin-left: 0.3rem; font-size: 0.65rem; font-weight: 700; letter-spacing: 0.03em; text-transform: uppercase; color: #92400e; background: #fffbeb; border: 1px solid #fde68a; border-radius: var(--radius-md); padding: 0.1rem 0.35rem; vertical-align: middle;">No supplier</span>
                @endif
                @if($expense->isReversed())
                    <span style="margin-left: 0.3rem; font-size: 0.65rem; font-weight: 700; letter-spacing: 0.03em; text-transform: uppercase; color: #991b1b; background: #fef2f2; border: 1px solid #fecaca; border-radius: var(--radius-md); padding: 0.1rem 0.35rem; vertical-align: middle;">Reversed</span>
                @endif
                @if($expense->is_reverse_charge)
                    <span style="margin-left: 0.3rem; font-size: 0.65rem; font-weight: 700; letter-spacing: 0.03em; text-transform: uppercase; color: #92400e; background: #fffbeb; border: 1px solid #fde68a; border-radius: var(--radius-md); padding: 0.1rem 0.35rem; vertical-align: middle;">Reverse charge</span>
                @endif
            </span>
            <span style="display: block; margin-top: 0.15rem; font-size: 0.78rem; color: var(--text-muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                {{ $expense->expense_date->format('d M Y') }}
                · {{ $expense->description }}
            </span>
        </span>
        <span style="text-align: right; flex: 0 0 auto;">
            <span style="display: block; font-weight: 700; color: var(--primary-navy);">€{{ number_format($expense->cashTotal(), 2) }}</span>
        </span>
    </summary>
    <div style="padding: 0.75rem 0.9rem 0.9rem; border-top: 1px solid var(--border-light);">
        <div style="font-size: 0.85rem; color: var(--primary-navy); line-height: 1.45;">{{ $expense->description }}</div>
        <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.25rem; line-height: 1.45;">
            {{ $expense->expense_date->format('d M Y') }}
            @if($expense->supplier_invoice_number)
                · {{ $expense->supplier_invoice_number }}
            @endif
            · {{ $categories[$expense->category] ?? $expense->category }}
            · {{ $expense->funded_by === 'director' ? 'Director-funded' : 'Company-paid' }}
            @if($expense->is_pre_incorporation) · pre-incorporation @endif
            <br>
            ex-VAT €{{ number_format((float) $expense->amount, 2) }}
            @if($expense->is_reverse_charge)
                · RC VAT €{{ number_format((float) $expense->vat_amount, 2) }} (out=in)
            @else
                · VAT €{{ number_format((float) $expense->vat_amount, 2) }}
            @endif
            @if($expense->isReversed())
                <br>Reversed {{ $expense->reversed_at->format('d M Y') }}@if($expense->reversal_note) · {{ $expense->reversal_note }}@endif
            @endif
            @if($expense->funded_by === 'director' && $expense->director_refunded_at && !$expense->isReversed())
                <br><span style="color: #059669;">Refunded {{ $expense->director_refunded_at->format('d M Y') }}</span>
                @if($expense->payment && $expense->payment->proof_path)
                    · <a href="/company/expenses/payments/{{ $expense->payment->id }}/proof" style="color: var(--primary-cerulean); font-weight: 600; text-decoration: none;">Payment proof</a>
                @endif
            @endif
        </div>
        <div style="display: flex; flex-wrap: wrap; gap: 0.5rem; margin-top: 0.75rem; align-items: center;">
            @if($expense->receipt_path)
                <a href="/company/expenses/{{ $expense->id }}/receipt" style="font-size: 0.8rem; font-weight: 600; color: var(--primary-cerulean); text-decoration: none;">Receipt</a>
            @endif
            @if(!$expense->isReversed() && !$expense->supplier && $suppliers->isNotEmpty())
                <form method="POST" action="/company/expenses/{{ $expense->id }}/supplier" style="display: flex; flex-wrap: wrap; gap: 0.35rem; align-items: center;">
                    @csrf
                    <select name="company_supplier_id" required style="padding: 0.35rem 0.5rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); font-size: 0.8rem; background: white;">
                        <option value="">Assign supplier</option>
                        @foreach($suppliers as $supplier)
                            <option value="{{ $supplier->id }}" @selected(($suggestions[$expense->id] ?? null) === $supplier->id)>{{ $supplier->name }}</option>
                        @endforeach
                    </select>
                    <button type="submit" style="font-size: 0.8rem; font-weight: 600; background: var(--primary-navy); color: white; border: none; border-radius: var(--radius-md); padding: 0.4rem 0.7rem; cursor: pointer;">Save supplier</button>
                </form>
            @endif
            @if($expense->isOwedToDirector())
                <form method="POST" action="/company/expenses/{{ $expense->id }}/refund" style="display: flex; flex-wrap: wrap; gap: 0.35rem; align-items: center;">
                    @csrf
                    <input type="date" name="director_refunded_at" value="{{ date('Y-m-d') }}" max="{{ date('Y-m-d') }}" required style="padding: 0.35rem 0.5rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); font-size: 0.8rem;">
                    <input type="text" name="refund_reference" placeholder="BOV ref (optional)" style="width: 8rem; padding: 0.35rem 0.5rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); font-size: 0.8rem;">
                    <button type="submit" style="font-size: 0.8rem; font-weight: 600; background: #059669; color: white; border: none; border-radius: var(--radius-md); padding: 0.4rem 0.7rem; cursor: pointer;">Mark refunded</button>
                </form>
            @endif
            @if(!$expense->isReversed())
                <form method="POST" action="/company/expenses/{{ $expense->id }}/reverse" style="display: flex; flex-wrap: wrap; gap: 0.35rem; align-items: center; width: 100%;" onsubmit="return confirm('Reverse this expense? The original journal stays, and an opposite entry removes it from the totals. You can then log the correct amount.');">
                    @csrf
                    <input type="date" name="reversed_at" value="{{ date('Y-m-d') }}" min="{{ $expense->expense_date->format('Y-m-d') }}" max="{{ date('Y-m-d') }}" required style="padding: 0.35rem 0.5rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); font-size: 0.8rem;">
                    <input type="text" name="reversal_note" placeholder="Why (optional)" maxlength="500" style="flex: 1; min-width: 8rem; padding: 0.35rem 0.5rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); font-size: 0.8rem;">
                    <button type="submit" style="font-size: 0.8rem; font-weight: 600; background: white; color: #991b1b; border: 1px solid #fecaca; border-radius: var(--radius-md); padding: 0.4rem 0.7rem; cursor: pointer;">Reverse</button>
                </form>
            @endif
        </div>
    </div>
</details>
