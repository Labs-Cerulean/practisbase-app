@extends('layouts.app')

@section('page_title', 'Expenses')

@section('content')
    <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem; flex-wrap: wrap; margin-bottom: 1.5rem;">
        <div>
            <h1 style="font-size: 1.5rem; color: var(--primary-navy); margin: 0 0 0.25rem;">Expense Ledger</h1>
            <p style="color: var(--text-muted); font-size: 0.95rem; margin: 0;">
                Logged expenses for {{ $year }} replace the Settings estimate in your Live Fiscal Report when the year total is greater than zero.
            </p>
        </div>
        <a href="/expenses/create" style="background: var(--primary-cerulean); color: white; padding: 0.6rem 1.25rem; border-radius: var(--radius-md); font-weight: 600; font-size: 0.9rem; text-decoration: none;">+ Log Expense</a>
    </div>

    @if(session('success'))
        <div style="background: #ecfdf5; color: #065f46; padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1.5rem; border: 1px solid #a7f3d0;">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div style="background: #fef2f2; color: #991b1b; padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1.5rem; border: 1px solid #fecaca;">
            @foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach
        </div>
    @endif

    <div style="display: flex; gap: 1rem; flex-wrap: wrap; align-items: center; margin-bottom: 1.5rem;">
        <form method="GET" action="/expenses" style="display: flex; gap: 0.5rem; align-items: center;">
            <label style="font-size: 0.85rem; font-weight: 600; color: var(--text-muted);">Year</label>
            <select name="year" onchange="this.form.submit()" style="padding: 0.5rem; border: 1px solid #cbd5e1; border-radius: 6px;">
                @foreach($years as $y)
                    <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                @endforeach
            </select>
        </form>
        <div style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-md); padding: 0.65rem 1rem; box-shadow: var(--shadow-sm); font-size: 0.9rem; font-weight: 600; color: var(--primary-navy);">
            Year total: €{{ number_format($yearTotal, 2) }}
        </div>
        <div style="font-size: 0.8rem; color: var(--text-muted);">
            Settings estimate fallback: €{{ number_format($user->estimated_expenses ?? 0, 2) }}
        </div>
    </div>

    @if($expenses->isEmpty())
        <div style="padding: 3rem; border: 2px dashed var(--border-light); border-radius: var(--radius-md); text-align: center; background: white;">
            <p style="color: var(--text-muted); margin-bottom: 1rem;">No expenses logged for {{ $year }}.</p>
            <a href="/expenses/create" style="color: var(--primary-cerulean); font-weight: 600;">Log your first expense &rarr;</a>
        </div>
    @else
        <div style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); overflow: hidden;">
            <table style="width: 100%; border-collapse: collapse; font-size: 0.9rem;">
                <thead>
                    <tr style="background: #f8fafc; text-align: left;">
                        <th style="padding: 0.85rem 1rem; border-bottom: 1px solid var(--border-light);">Date</th>
                        <th style="padding: 0.85rem 1rem; border-bottom: 1px solid var(--border-light);">Category</th>
                        <th style="padding: 0.85rem 1rem; border-bottom: 1px solid var(--border-light);">Description</th>
                        <th style="padding: 0.85rem 1rem; border-bottom: 1px solid var(--border-light); text-align: right;">Amount</th>
                        <th style="padding: 0.85rem 1rem; border-bottom: 1px solid var(--border-light);">Receipt</th>
                        <th style="padding: 0.85rem 1rem; border-bottom: 1px solid var(--border-light);"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($expenses as $expense)
                        <tr>
                            <td style="padding: 0.85rem 1rem; border-bottom: 1px solid #f1f5f9;">{{ $expense->expense_date->format('d M Y') }}</td>
                            <td style="padding: 0.85rem 1rem; border-bottom: 1px solid #f1f5f9;">{{ $categories[$expense->category] ?? $expense->category }}</td>
                            <td style="padding: 0.85rem 1rem; border-bottom: 1px solid #f1f5f9;">{{ $expense->description }}</td>
                            <td style="padding: 0.85rem 1rem; border-bottom: 1px solid #f1f5f9; text-align: right; font-weight: 600;">
                                €{{ number_format($expense->totalWithVat(), 2) }}
                                @if($expense->vat_amount > 0)
                                    <div style="font-size: 0.75rem; color: var(--text-muted); font-weight: 500;">ex VAT €{{ number_format($expense->amount, 2) }}</div>
                                @endif
                            </td>
                            <td style="padding: 0.85rem 1rem; border-bottom: 1px solid #f1f5f9;">
                                @if($expense->receipt_path)
                                    <a href="/expenses/{{ $expense->id }}/receipt" style="color: var(--primary-cerulean); font-weight: 600; text-decoration: none;">Download</a>
                                @else
                                    <span style="color: var(--text-muted);">—</span>
                                @endif
                            </td>
                            <td style="padding: 0.85rem 1rem; border-bottom: 1px solid #f1f5f9; text-align: right;">
                                <form action="/expenses/{{ $expense->id }}" method="POST" onsubmit="return confirm('Remove this expense?');" style="display: inline;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" style="background: none; border: none; color: #b91c1c; font-weight: 600; cursor: pointer; font-size: 0.85rem;">Remove</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
@endsection
