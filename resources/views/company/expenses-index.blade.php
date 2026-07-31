@extends('layouts.app')

@section('page_title', 'Company expenses')

@section('content')
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.25rem;">
        <div>
            <h1 style="font-size: 1.4rem; color: var(--primary-navy); margin: 0 0 0.25rem;">Company expenses</h1>
            <p style="margin: 0; color: var(--text-muted); font-size: 0.9rem;">
                {{ $year }} · Owed to you: <strong style="color: {{ $owedToDirector > 0 ? '#b45309' : '#059669' }};">€{{ number_format($owedToDirector, 2) }}</strong>
            </p>
        </div>
        <a href="/company/expenses/create" style="background: var(--primary-cerulean); color: white; padding: 0.55rem 1rem; border-radius: var(--radius-md); font-weight: 600; font-size: 0.85rem; text-decoration: none;">+ Expense</a>
    </div>

    <div style="display: grid; gap: 0.75rem;">
        @forelse($expenses as $expense)
            <div style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1rem 1.15rem; box-shadow: var(--shadow-sm);">
                <div style="display: flex; justify-content: space-between; gap: 1rem; flex-wrap: wrap;">
                    <div>
                        <div style="font-weight: 700; color: var(--primary-navy);">{{ $expense->description }}</div>
                        <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.2rem;">
                            {{ $expense->expense_date->format('d M Y') }}
                            · {{ $categories[$expense->category] ?? $expense->category }}
                            · {{ $expense->funded_by === 'director' ? 'Director-funded' : 'Company-paid' }}
                            @if($expense->is_pre_incorporation) · pre-incorporation @endif
                        </div>
                    </div>
                    <div style="text-align: right;">
                        <div style="font-weight: 700; color: var(--primary-navy);">€{{ number_format($expense->totalWithVat(), 2) }}</div>
                        <div style="font-size: 0.75rem; color: var(--text-muted);">ex-VAT €{{ number_format((float) $expense->amount, 2) }} · VAT €{{ number_format((float) $expense->vat_amount, 2) }}</div>
                    </div>
                </div>
                <div style="display: flex; flex-wrap: wrap; gap: 0.5rem; margin-top: 0.75rem; align-items: center;">
                    @if($expense->receipt_path)
                        <a href="/company/expenses/{{ $expense->id }}/receipt" style="font-size: 0.8rem; font-weight: 600; color: var(--primary-cerulean); text-decoration: none;">Receipt</a>
                    @endif
                    @if($expense->isOwedToDirector())
                        <form method="POST" action="/company/expenses/{{ $expense->id }}/refund" style="display: flex; flex-wrap: wrap; gap: 0.35rem; align-items: center; margin-left: auto;">
                            @csrf
                            <input type="date" name="director_refunded_at" value="{{ date('Y-m-d') }}" max="{{ date('Y-m-d') }}" required style="padding: 0.35rem 0.5rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); font-size: 0.8rem;">
                            <input type="text" name="refund_reference" placeholder="BOV ref (optional)" style="width: 8rem; padding: 0.35rem 0.5rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); font-size: 0.8rem;">
                            <button type="submit" style="font-size: 0.8rem; font-weight: 600; background: #059669; color: white; border: none; border-radius: var(--radius-md); padding: 0.4rem 0.7rem; cursor: pointer;">Mark refunded</button>
                        </form>
                    @elseif($expense->funded_by === 'director' && $expense->director_refunded_at)
                        <div style="margin-left: auto; font-size: 0.8rem; color: #059669;">Refunded {{ $expense->director_refunded_at->format('d M Y') }}</div>
                    @endif
                </div>
            </div>
        @empty
            <div style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 2rem; text-align: center; color: var(--text-muted);">
                No expenses in {{ $year }}. Log Railway, Workspace, Cursor, websites from January onward.
            </div>
        @endforelse
    </div>
@endsection
