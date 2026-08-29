@extends('layouts.app')

@section('page_title', 'Log company expense')

@section('content')
    <div style="max-width: 640px; margin: 0 auto; background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.75rem; box-shadow: var(--shadow-sm);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
            <h1 style="font-size: 1.3rem; color: var(--primary-navy); margin: 0;">Log company expense</h1>
            <a href="/company/expenses" style="color: var(--text-muted); font-weight: 600; font-size: 0.85rem; text-decoration: none;">Cancel</a>
        </div>

        <p style="font-size: 0.85rem; color: var(--text-muted); line-height: 1.45; margin: 0 0 1.25rem;">
            Costs from January before incorporation ({{ $profile->first_period_start->format('d M Y') }}) can be logged as pre-incorporation and funded by you for later refund.
        </p>

        @if($errors->any())
            <div style="margin-bottom: 1rem; padding: 0.85rem 1rem; background: #fef2f2; border: 1px solid #fecaca; border-radius: var(--radius-md); color: #991b1b; font-size: 0.9rem;">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="/company/expenses" enctype="multipart/form-data" id="companyExpenseForm">
            @csrf
            <div style="margin-bottom: 1rem;">
                <label style="display: block; font-weight: 600; margin-bottom: 0.4rem; font-size: 0.9rem;">Date</label>
                <input type="date" name="expense_date" value="{{ old('expense_date', date('Y-m-d')) }}" max="{{ date('Y-m-d') }}" min="2026-01-01" required style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
            </div>
            <div style="margin-bottom: 1rem;">
                <label style="display: block; font-weight: 600; margin-bottom: 0.4rem; font-size: 0.9rem;">Category</label>
                <select name="category" required style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); background: white;">
                    @foreach($categories as $key => $label)
                        <option value="{{ $key }}" @selected(old('category', 'software') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div style="margin-bottom: 1rem;">
                <label style="display: block; font-weight: 600; margin-bottom: 0.4rem; font-size: 0.9rem;">Description</label>
                <input type="text" name="description" value="{{ old('description') }}" required placeholder="e.g. Amazon AWS Jul 2026" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                <div>
                    <label style="display: block; font-weight: 600; margin-bottom: 0.4rem; font-size: 0.9rem;">Amount ex-VAT (€)</label>
                    <input type="number" name="amount" id="expenseAmount" step="0.01" min="0.01" value="{{ old('amount') }}" required style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                </div>
                <div>
                    <label style="display: block; font-weight: 600; margin-bottom: 0.4rem; font-size: 0.9rem;">Supplier VAT (€)</label>
                    <input type="number" name="vat_amount" id="expenseVatAmount" step="0.01" min="0" value="{{ old('vat_amount', '0') }}" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                    <div id="vatHint" style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.3rem;">Maltese supplier invoice VAT, if any. Leave €0 for US SaaS with no VAT.</div>
                </div>
            </div>

            @if($canReverseCharge ?? false)
                <div style="margin-bottom: 1rem; padding: 0.85rem 1rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); background: #f8fafc;">
                    <label style="display: flex; align-items: flex-start; gap: 0.65rem; cursor: pointer;">
                        <input type="checkbox" name="is_reverse_charge" id="isReverseCharge" value="1" @checked(old('is_reverse_charge')) style="margin-top: 0.2rem;">
                        <span>
                            <span style="font-weight: 700; color: var(--primary-navy); font-size: 0.9rem;">Reverse charge (EU / B2B)</span>
                            <span id="rcGuide" style="display: block; font-size: 0.8rem; color: var(--text-muted); line-height: 1.45; margin-top: 0.25rem;">
                                Tick when the invoice has no Maltese VAT but you must self-assess 18% on the VAT return (e.g. Amazon Business, EU SaaS). Posts matching output + input VAT — net €0, boxes filled.
                            </span>
                        </span>
                    </label>
                    <div id="rcPreview" style="display: none; margin-top: 0.65rem; font-size: 0.82rem; color: var(--primary-navy); line-height: 1.4;">
                        Self-assessed VAT (18%): <strong id="rcVatFigure">€0.00</strong>
                        · cash paid stays at the ex-VAT amount.
                    </div>
                </div>
            @endif

            <div style="margin-bottom: 1rem;">
                <label style="display: block; font-weight: 600; margin-bottom: 0.4rem; font-size: 0.9rem;">Who paid?</label>
                <select name="funded_by" required style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); background: white;">
                    <option value="director" @selected(old('funded_by', 'director') === 'director')>I paid personally (company owes me a refund)</option>
                    <option value="company" @selected(old('funded_by') === 'company')>Company bank / card</option>
                </select>
            </div>
            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; font-weight: 600; margin-bottom: 0.4rem; font-size: 0.9rem;">Receipt (photo or PDF)</label>
                <input type="file" name="receipt" accept=".jpg,.jpeg,.png,.pdf,image/*" capture="environment" style="width: 100%; padding: 0.5rem 0;">
            </div>
            <button type="submit" style="background: var(--primary-cerulean); color: white; border: none; padding: 0.7rem 1.25rem; border-radius: var(--radius-md); font-weight: 700; cursor: pointer;">Save expense</button>
        </form>
    </div>

    @if($canReverseCharge ?? false)
        <script>
            (function () {
                var rate = {{ \App\Models\CompanyExpense::REVERSE_CHARGE_RATE }};
                var amountEl = document.getElementById('expenseAmount');
                var vatEl = document.getElementById('expenseVatAmount');
                var rcEl = document.getElementById('isReverseCharge');
                var hint = document.getElementById('vatHint');
                var preview = document.getElementById('rcPreview');
                var figure = document.getElementById('rcVatFigure');
                if (!amountEl || !vatEl || !rcEl) return;

                function euro(n) {
                    return '€' + n.toFixed(2);
                }

                function sync() {
                    var rc = rcEl.checked;
                    var net = parseFloat(amountEl.value) || 0;
                    var rcVat = Math.round(Math.max(0, net) * rate * 100) / 100;
                    if (rc) {
                        vatEl.value = rcVat.toFixed(2);
                        vatEl.readOnly = true;
                        vatEl.style.background = '#f1f5f9';
                        hint.textContent = 'Supplier VAT stays €0 — self-assessed 18% is stored for the VAT return.';
                        preview.style.display = 'block';
                        figure.textContent = euro(rcVat);
                    } else {
                        vatEl.readOnly = false;
                        vatEl.style.background = '';
                        hint.textContent = 'Maltese supplier invoice VAT, if any. Leave €0 for US SaaS with no VAT.';
                        preview.style.display = 'none';
                    }
                }

                rcEl.addEventListener('change', sync);
                amountEl.addEventListener('input', sync);
                sync();
            })();
        </script>
    @endif
@endsection
