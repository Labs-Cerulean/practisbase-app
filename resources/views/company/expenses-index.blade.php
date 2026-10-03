@extends('layouts.app')

@section('page_title', 'Company expenses')

@section('content')
    @php
        $filterNote = match ($status) {
            'owed' => 'Director-funded costs still waiting for a refund.',
            'company' => 'Paid from the company bank or card.',
            'reversed' => 'These no longer count in totals. The original journal is still in Accounts.',
            default => 'Open a line for the receipt, a refund, or a reversal. Older months stay closed.',
        };
    @endphp

    <style>
        .expense-fold > summary { list-style: none; }
        .expense-fold > summary::-webkit-details-marker { display: none; }
        .expense-fold > summary::marker { content: ''; }
        .expense-fold[open] > summary .fold-mark { transform: rotate(90deg); }
        .expense-fold > summary .fold-mark { transition: transform 0.15s ease; }
    </style>

    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 1rem;">
        <div>
            <h1 style="font-size: 1.4rem; color: var(--primary-navy); margin: 0 0 0.25rem;">Company expenses</h1>
            <p style="margin: 0; color: var(--text-muted); font-size: 0.9rem;">
                {{ $invoiceCount }} {{ $invoiceCount === 1 ? 'invoice' : 'invoices' }}
                · cash <strong style="color: var(--primary-navy);">€{{ number_format($cashShown, 2) }}</strong>
                · owed to you: <strong style="color: {{ $owedToDirector > 0 ? '#b45309' : '#059669' }};">€{{ number_format($owedToDirector, 2) }}</strong>
                @if(($reverseChargeVat ?? 0) > 0.009)
                    · reverse charge VAT <strong style="color: var(--primary-navy);">€{{ number_format($reverseChargeVat, 2) }}</strong> out + in
                @endif
            </p>
        </div>
        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
            <a href="/company/suppliers?year={{ $year }}" style="background: white; color: var(--primary-navy); border: 1px solid var(--border-light); padding: 0.55rem 1rem; border-radius: var(--radius-md); font-weight: 600; font-size: 0.85rem; text-decoration: none;">Suppliers</a>
            <a href="/company/expenses/create" style="background: var(--primary-cerulean); color: white; padding: 0.55rem 1rem; border-radius: var(--radius-md); font-weight: 600; font-size: 0.85rem; text-decoration: none;">+ Expense</a>
        </div>
    </div>

    <form method="GET" action="/company/expenses" id="expenseFilters" style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 0.9rem 1rem; box-shadow: var(--shadow-sm); margin-bottom: 1rem;">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(10.5rem, 1fr)); gap: 0.6rem; align-items: end;">
            <div>
                <label for="expenseYear" style="display: block; font-weight: 600; margin-bottom: 0.3rem; font-size: 0.8rem;">Year</label>
                <select id="expenseYear" name="year" data-expense-filter style="width: 100%; padding: 0.55rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); background: white;">
                    @foreach($years as $optionYear)
                        <option value="{{ $optionYear }}" @selected((int) $optionYear === (int) $year)>{{ $optionYear }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="expenseSupplier" style="display: block; font-weight: 600; margin-bottom: 0.3rem; font-size: 0.8rem;">Supplier</label>
                <select id="expenseSupplier" name="supplier" data-expense-filter style="width: 100%; padding: 0.55rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); background: white;">
                    <option value="">All suppliers</option>
                    @foreach($suppliers as $supplier)
                        <option value="{{ $supplier->id }}" @selected($supplierFilter && (int) $supplierFilter->id === (int) $supplier->id)>{{ $supplier->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="expenseStatus" style="display: block; font-weight: 600; margin-bottom: 0.3rem; font-size: 0.8rem;">Show</label>
                <select id="expenseStatus" name="status" data-expense-filter style="width: 100%; padding: 0.55rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); background: white;">
                    <option value="active" @selected($status === 'active')>All live invoices</option>
                    <option value="owed" @selected($status === 'owed')>Owed to me</option>
                    <option value="company" @selected($status === 'company')>Company paid</option>
                    <option value="reversed" @selected($status === 'reversed')>Reversed</option>
                </select>
            </div>
            <div>
                <label for="expenseSearch" style="display: block; font-weight: 600; margin-bottom: 0.3rem; font-size: 0.8rem;">Find</label>
                <input id="expenseSearch" type="search" name="q" value="{{ $search }}" placeholder="Name, invoice number" style="width: 100%; padding: 0.55rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
            </div>
            <div style="display: flex; gap: 0.45rem; align-items: center;">
                <button type="submit" style="background: var(--primary-navy); color: white; border: none; padding: 0.55rem 0.9rem; border-radius: var(--radius-md); font-weight: 700; cursor: pointer;">Show</button>
                @if($filtersOn)
                    <a href="/company/expenses?year={{ $year }}" style="font-size: 0.8rem; font-weight: 600; color: var(--primary-cerulean); text-decoration: none;">Reset</a>
                @endif
            </div>
        </div>
        <p id="expenseFilterNote" style="margin: 0.65rem 0 0; font-size: 0.8rem; color: var(--text-muted); line-height: 1.4;">{{ $filterNote }}</p>
    </form>

    @if(session('success'))
        <div style="background: #ecfdf5; border: 1px solid #a7f3d0; color: #065f46; border-radius: var(--radius-lg); padding: 0.85rem 1.1rem; margin-bottom: 1rem; font-size: 0.9rem;">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div style="background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; border-radius: var(--radius-lg); padding: 0.85rem 1.1rem; margin-bottom: 1rem; font-size: 0.9rem;">{{ $errors->first() }}</div>
    @endif

    @if(($unassignedCount ?? 0) > 0)
        <div style="background: #fffbeb; border: 1px solid #fde68a; color: #92400e; border-radius: var(--radius-lg); padding: 0.85rem 1.1rem; margin-bottom: 1rem; font-size: 0.9rem;">
            {{ $unassignedCount }} {{ $unassignedCount === 1 ? 'expense is' : 'expenses are' }} not on a supplier yet. Open the line to assign one.
            @if($suppliers->isEmpty())
                <a href="/company/suppliers" style="color: var(--primary-navy); font-weight: 700;">Add the first supplier</a>
            @elseif(($suggestedCount ?? 0) > 0)
                <form method="POST" action="/company/expenses/assign-suggested" style="display: inline;">
                    @csrf
                    <input type="hidden" name="year" value="{{ $year }}">
                    <button type="submit" style="margin-left: 0.35rem; background: transparent; border: none; padding: 0; color: var(--primary-navy); font-weight: 700; cursor: pointer; font-size: 0.9rem; text-decoration: underline;">Link {{ $suggestedCount }} suggested</button>
                </form>
            @endif
        </div>
    @endif

    <div style="display: grid; gap: 0.75rem;">
        @forelse($months as $month)
            <details class="expense-fold" @if($month['open']) open @endif style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); box-shadow: var(--shadow-sm);">
                <summary style="display: flex; justify-content: space-between; gap: 0.75rem; align-items: center; flex-wrap: wrap; padding: 0.85rem 1rem; cursor: pointer;">
                    <span style="display: flex; align-items: center; gap: 0.45rem; min-width: 0;">
                        <span class="fold-mark" aria-hidden="true" style="color: var(--text-muted); font-size: 0.8rem;">▸</span>
                        <span style="font-weight: 700; color: var(--primary-navy);">{{ $month['label'] }}</span>
                    </span>
                    <span style="font-size: 0.8rem; color: var(--text-muted); text-align: right;">
                        @if($status === 'reversed')
                            {{ count($month['reversed_ids']) }} reversed · €{{ number_format($month['cash'], 2) }}
                        @else
                            {{ count($month['active_ids']) }} · €{{ number_format($month['cash'], 2) }}
                            @if($month['owed'] > 0.009)
                                · owed €{{ number_format($month['owed'], 2) }}
                            @endif
                            @if(count($month['reversed_ids']) > 0)
                                · {{ count($month['reversed_ids']) }} reversed
                            @endif
                        @endif
                    </span>
                </summary>
                <div style="display: grid; gap: 0.45rem; padding: 0 0.7rem 0.8rem;">
                    @foreach($month['active_ids'] as $expenseId)
                        @include('company._expense-row', ['expense' => $expensesById[$expenseId]])
                    @endforeach
                    @if($status === 'reversed')
                        @foreach($month['reversed_ids'] as $expenseId)
                            @include('company._expense-row', ['expense' => $expensesById[$expenseId]])
                        @endforeach
                    @elseif(count($month['reversed_ids']) > 0)
                        <details class="expense-fold" style="border: 1px dashed var(--border-light); border-radius: var(--radius-md);">
                            <summary style="padding: 0.55rem 0.75rem; cursor: pointer; font-size: 0.8rem; font-weight: 600; color: var(--text-muted);">
                                <span class="fold-mark" aria-hidden="true">▸</span>
                                Show {{ count($month['reversed_ids']) }} reversed
                            </summary>
                            <div style="display: grid; gap: 0.45rem; padding: 0 0.55rem 0.65rem;">
                                @foreach($month['reversed_ids'] as $expenseId)
                                    @include('company._expense-row', ['expense' => $expensesById[$expenseId]])
                                @endforeach
                            </div>
                        </details>
                    @endif
                </div>
            </details>
        @empty
            <div style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 2rem; text-align: center; color: var(--text-muted);">
                @if($filtersOn)
                    Nothing matches these filters.
                @else
                    No expenses in {{ $year }}. Log Railway, Workspace, Cursor, websites from January onward.
                @endif
            </div>
        @endforelse
    </div>

    <script>
        (function () {
            var form = document.getElementById('expenseFilters');
            var note = document.getElementById('expenseFilterNote');
            var status = document.getElementById('expenseStatus');
            if (!form || !status) return;
            var notes = {
                active: 'Open a line for the receipt, a refund, or a reversal. Older months stay closed.',
                owed: 'Director-funded costs still waiting for a refund.',
                company: 'Paid from the company bank or card.',
                reversed: 'These no longer count in totals. The original journal is still in Accounts.'
            };
            status.addEventListener('change', function () {
                if (note && notes[status.value]) note.textContent = notes[status.value];
            });
            form.querySelectorAll('[data-expense-filter]').forEach(function (el) {
                el.addEventListener('change', function () { form.submit(); });
            });
        })();
    </script>
@endsection
