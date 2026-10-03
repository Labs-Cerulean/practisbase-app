@extends('layouts.app')

@section('page_title', 'Record a payment')

@section('content')
    @php
        $picked = array_map('intval', (array) old('expense_ids', []));
        $openModal = $errors->has('proof') || $errors->has('paid_on') || $errors->has('reference') || $errors->has('books');
    @endphp

    <style>
        #payModal[hidden] { display: none !important; }
    </style>

    <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 1rem; margin-bottom: 1rem;">
        <div>
            <h1 style="font-size: 1.4rem; color: var(--primary-navy); margin: 0 0 0.25rem;">Record a payment</h1>
            <p style="margin: 0; color: var(--text-muted); font-size: 0.9rem; max-width: 40rem;">
                Tick the invoices this bank payment covers. Filter to one supplier when you are settling that supplier. For a director refund, leave every unrefunded invoice in the list and tick them all. One proof is saved for the whole payment.
            </p>
        </div>
        <a href="/company/expenses" style="background: white; color: var(--primary-navy); border: 1px solid var(--border-light); padding: 0.55rem 1rem; border-radius: var(--radius-md); font-weight: 600; font-size: 0.85rem; text-decoration: none;">Back to expenses</a>
    </div>

    @if($errors->any())
        <div style="background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; border-radius: var(--radius-lg); padding: 0.85rem 1.1rem; margin-bottom: 1rem; font-size: 0.9rem;">
            @foreach($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <div style="display: flex; flex-wrap: wrap; gap: 0.4rem; margin-bottom: 1rem;">
        <a href="/company/expenses/pay" style="text-decoration: none; font-size: 0.8rem; font-weight: 600; padding: 0.35rem 0.7rem; border-radius: 999px; border: 1px solid {{ $supplierFilter ? 'var(--border-light)' : 'var(--primary-navy)' }}; background: {{ $supplierFilter ? 'white' : 'var(--primary-navy)' }}; color: {{ $supplierFilter ? 'var(--text-muted)' : 'white' }};">All unrefunded</a>
        @foreach($suppliers as $supplier)
            <a href="/company/expenses/pay?supplier={{ $supplier->id }}" style="text-decoration: none; font-size: 0.8rem; font-weight: 600; padding: 0.35rem 0.7rem; border-radius: 999px; border: 1px solid {{ ($supplierFilter && $supplierFilter->id === $supplier->id) ? 'var(--primary-navy)' : 'var(--border-light)' }}; background: {{ ($supplierFilter && $supplierFilter->id === $supplier->id) ? 'var(--primary-navy)' : 'white' }}; color: {{ ($supplierFilter && $supplierFilter->id === $supplier->id) ? 'white' : 'var(--text-muted)' }};">{{ $supplier->name }}</a>
        @endforeach
    </div>

    <p style="margin: 0 0 0.75rem; color: var(--text-muted); font-size: 0.85rem;">
        {{ $expenses->count() }} {{ $expenses->count() === 1 ? 'invoice' : 'invoices' }} still open
        · €{{ number_format($openTotal, 2) }}
        @if($supplierFilter)
            · {{ $supplierFilter->name }}
        @endif
    </p>

    @if($expenses->isEmpty())
        <div style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.25rem; color: var(--text-muted); font-size: 0.9rem;">
            Nothing is waiting for a refund{{ $supplierFilter ? ' from '.$supplierFilter->name : '' }}.
        </div>
    @else
        <form id="payForm" method="POST" action="/company/expenses/pay" enctype="multipart/form-data">
            @csrf
            <div style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); overflow: hidden;">
                <label style="display: flex; align-items: center; gap: 0.75rem; padding: 0.85rem 1rem; border-bottom: 1px solid var(--border-light); font-size: 0.85rem; font-weight: 600; color: var(--primary-navy); cursor: pointer;">
                    <input type="checkbox" id="tickAll" style="width: 1.15rem; height: 1.15rem;">
                    Tick all in this list
                </label>
                @foreach($expenses as $expense)
                    <label style="display: flex; align-items: flex-start; gap: 0.75rem; padding: 0.85rem 1rem; border-bottom: 1px solid var(--border-light); cursor: pointer;">
                        <input type="checkbox" name="expense_ids[]" value="{{ $expense->id }}" data-cash="{{ number_format($expense->cashTotal(), 2, '.', '') }}" data-date="{{ $expense->expense_date->format('Y-m-d') }}" @checked(in_array((int) $expense->id, $picked, true)) style="width: 1.15rem; height: 1.15rem; margin-top: 0.15rem;">
                        <span style="flex: 1; min-width: 0;">
                            <span style="display: flex; justify-content: space-between; gap: 0.75rem; flex-wrap: wrap;">
                                <strong style="color: var(--primary-navy); font-size: 0.92rem;">{{ $expense->supplier->name ?? 'No supplier' }}</strong>
                                <strong style="color: var(--primary-navy); font-size: 0.92rem;">€{{ number_format($expense->cashTotal(), 2) }}</strong>
                            </span>
                            <span style="display: block; margin-top: 0.15rem; color: var(--text-muted); font-size: 0.82rem;">
                                {{ $expense->expense_date->format('d M Y') }}
                                @if($expense->supplier_invoice_number)
                                    · {{ $expense->supplier_invoice_number }}
                                @endif
                                · {{ $expense->description }}
                            </span>
                        </span>
                    </label>
                @endforeach
            </div>

            <div style="position: sticky; bottom: 1rem; margin-top: 1rem; background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); padding: 0.85rem 1rem; display: flex; justify-content: space-between; align-items: center; gap: 1rem; flex-wrap: wrap;">
                <div>
                    <div style="font-size: 0.8rem; color: var(--text-muted);"><span id="payCount">0</span> selected</div>
                    <div id="payTotal" style="font-size: 1.35rem; font-weight: 700; color: var(--primary-navy);">€0.00</div>
                    <div id="payHint" style="font-size: 0.8rem; color: #b45309;">Tick at least one invoice.</div>
                </div>
                <button type="button" id="openPay" style="background: var(--primary-cerulean); color: white; border: none; border-radius: var(--radius-md); padding: 0.7rem 1.1rem; font-weight: 700; font-size: 0.9rem; cursor: pointer;">Record payment</button>
            </div>

            <div id="payModal" @if(!$openModal) hidden @endif style="position: fixed; inset: 0; background: rgba(15, 23, 42, 0.45); backdrop-filter: blur(4px); display: flex; align-items: center; justify-content: center; padding: 1rem; z-index: 50;">
                <div style="background: white; border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); width: 100%; max-width: 28rem; padding: 1.25rem;">
                    <h2 style="margin: 0 0 0.35rem; font-size: 1.15rem; color: var(--primary-navy);">Proof of payment</h2>
                    <p style="margin: 0 0 1rem; color: var(--text-muted); font-size: 0.88rem;">
                        <span id="modalCount">0</span> invoices · <strong id="modalTotal" style="color: var(--primary-navy);">€0.00</strong>. This date is the day the money left the company bank.
                    </p>
                    <label style="display: block; font-size: 0.8rem; font-weight: 600; color: var(--primary-navy); margin-bottom: 0.35rem;">Payment date</label>
                    <input type="date" name="paid_on" value="{{ old('paid_on', date('Y-m-d')) }}" max="{{ date('Y-m-d') }}" required style="width: 100%; padding: 0.55rem 0.7rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); margin-bottom: 0.85rem;">
                    <label style="display: block; font-size: 0.8rem; font-weight: 600; color: var(--primary-navy); margin-bottom: 0.35rem;">Bank reference</label>
                    <input type="text" name="reference" value="{{ old('reference') }}" maxlength="120" placeholder="BOV reference" style="width: 100%; padding: 0.55rem 0.7rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); margin-bottom: 0.85rem;">
                    <label style="display: block; font-size: 0.8rem; font-weight: 600; color: var(--primary-navy); margin-bottom: 0.35rem;">Proof of payment</label>
                    <input type="file" name="proof" accept=".jpg,.jpeg,.png,.pdf,image/jpeg,image/png,application/pdf" required style="width: 100%; margin-bottom: 1rem; font-size: 0.85rem;">
                    <div style="display: flex; justify-content: flex-end; gap: 0.5rem;">
                        <button type="button" id="closePay" style="background: white; color: var(--primary-navy); border: 1px solid var(--border-light); border-radius: var(--radius-md); padding: 0.6rem 0.9rem; font-weight: 600; cursor: pointer;">Cancel</button>
                        <button type="submit" style="background: #059669; color: white; border: none; border-radius: var(--radius-md); padding: 0.6rem 0.9rem; font-weight: 700; cursor: pointer;">Finalise</button>
                    </div>
                </div>
            </div>
        </form>
        <script>
            (function () {
                var form = document.getElementById('payForm');
                var boxes = form.querySelectorAll('input[name="expense_ids[]"]');
                var all = document.getElementById('tickAll');
                var countEl = document.getElementById('payCount');
                var totalEl = document.getElementById('payTotal');
                var hint = document.getElementById('payHint');
                var modal = document.getElementById('payModal');
                var modalTotal = document.getElementById('modalTotal');
                var modalCount = document.getElementById('modalCount');
                var dateInput = form.querySelector('input[name="paid_on"]');
                var openBtn = document.getElementById('openPay');

                function money(amount) {
                    return '€' + amount.toFixed(2);
                }

                function selected() {
                    var rows = [];
                    boxes.forEach(function (box) {
                        if (box.checked) rows.push(box);
                    });
                    return rows;
                }

                function refresh() {
                    var rows = selected();
                    var total = 0;
                    var latest = '';
                    rows.forEach(function (box) {
                        total += parseFloat(box.getAttribute('data-cash') || '0');
                        var date = box.getAttribute('data-date') || '';
                        if (!latest || date > latest) latest = date;
                    });
                    total = Math.round(total * 100) / 100;
                    countEl.textContent = String(rows.length);
                    totalEl.textContent = money(total);
                    modalCount.textContent = String(rows.length);
                    modalTotal.textContent = money(total);
                    hint.hidden = rows.length > 0;
                    openBtn.disabled = rows.length === 0;
                    openBtn.style.opacity = rows.length === 0 ? '0.55' : '1';
                    if (dateInput && latest) dateInput.min = latest;
                    if (all) {
                        all.checked = rows.length > 0 && rows.length === boxes.length;
                        all.indeterminate = rows.length > 0 && rows.length < boxes.length;
                    }
                }

                boxes.forEach(function (box) {
                    box.addEventListener('change', refresh);
                });
                if (all) {
                    all.addEventListener('change', function () {
                        boxes.forEach(function (box) {
                            box.checked = all.checked;
                        });
                        refresh();
                    });
                }
                openBtn.addEventListener('click', function () {
                    if (selected().length === 0) return;
                    modal.hidden = false;
                });
                document.getElementById('closePay').addEventListener('click', function () {
                    modal.hidden = true;
                });
                modal.addEventListener('click', function (event) {
                    if (event.target === modal) modal.hidden = true;
                });
                refresh();
            })();
        </script>
    @endif
@endsection
