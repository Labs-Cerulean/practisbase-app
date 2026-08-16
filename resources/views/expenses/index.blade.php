@extends('layouts.app')

@section('page_title', 'Expenses')

@section('content')
    <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem; flex-wrap: wrap; margin-bottom: 1.5rem;">
        <div>
            <h1 style="font-size: 1.5rem; color: var(--primary-navy); margin: 0 0 0.25rem;">Expenses</h1>
            <p style="color: var(--text-muted); font-size: 0.95rem; margin: 0;">
                {{ $year }} practice costs —
                <button type="button" data-open-expense-guide style="background: none; border: none; padding: 0; font: inherit; color: var(--primary-navy); font-weight: 600; cursor: pointer; border-bottom: 1px dotted var(--primary-navy);">what is claimable?</button>
            </p>
        </div>
        <div style="display: flex; gap: 0.6rem; flex-wrap: wrap; align-items: center;">
            <a href="/expenses/create" style="background: var(--primary-cerulean); color: white; padding: 0.6rem 1.25rem; border-radius: var(--radius-md); font-weight: 600; font-size: 0.9rem; text-decoration: none;">+ Log Expense</a>
            <details style="position: relative;">
                <summary style="list-style: none; cursor: pointer; background: white; color: var(--primary-navy); border: 1px solid var(--border-light); padding: 0.6rem 1rem; border-radius: var(--radius-md); font-weight: 600; font-size: 0.9rem; box-shadow: var(--shadow-sm);">
                    Set parameters
                </summary>
                <div style="position: absolute; right: 0; top: calc(100% + 0.35rem); z-index: 20; min-width: 11rem; background: white; border: 1px solid var(--border-light); border-radius: var(--radius-md); box-shadow: var(--shadow-md); padding: 0.35rem; display: flex; flex-direction: column; gap: 0.25rem;">
                    <button type="button" data-open-car-helper style="background: none; border: none; text-align: left; padding: 0.55rem 0.75rem; border-radius: var(--radius-md); font-weight: 600; font-size: 0.85rem; color: var(--primary-navy); cursor: pointer;">Car / fuel %</button>
                    <button type="button" data-open-wfh-helper style="background: none; border: none; text-align: left; padding: 0.55rem 0.75rem; border-radius: var(--radius-md); font-weight: 600; font-size: 0.85rem; color: var(--primary-navy); cursor: pointer;">Home office %</button>
                </div>
            </details>
        </div>
    </div>

    <div style="background: #f8fafc; border: 1px solid var(--border-light); border-radius: var(--radius-md); padding: 0.85rem 1rem; margin-bottom: 1.5rem; font-size: 0.85rem; color: var(--text-muted); line-height: 1.45;">
        Defaults: car/fuel <strong style="color: var(--primary-navy);">{{ $user->car_business_use_percent !== null ? number_format((float) $user->car_business_use_percent, 0).'%' : 'not set' }}</strong>
        · home office <strong style="color: var(--primary-navy);">{{ $user->home_office_percent !== null ? number_format((float) $user->home_office_percent, 0).'%' : 'not set' }}</strong>
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
                            <td style="padding: 0.85rem 1rem; border-bottom: 1px solid #f1f5f9;">
                                {{ $categories[$expense->category] ?? $expense->category }}
                                @if($expense->business_use_percent !== null)
                                    <div style="font-size: 0.75rem; color: var(--text-muted);">{{ number_format((float) $expense->business_use_percent, 0) }}% practice / home share</div>
                                @elseif(in_array($expense->category, ['laptop', 'equipment', 'car'], true))
                                    <div style="font-size: 0.75rem; color: var(--text-muted);">Capital — wear &amp; tear</div>
                                @endif
                            </td>
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
                                    <form action="/expenses/{{ $expense->id }}/receipt" method="POST" enctype="multipart/form-data" style="display: flex; gap: 0.35rem; align-items: center; flex-wrap: wrap;">
                                        @csrf
                                        <input type="file" name="receipt" accept=".jpg,.jpeg,.png,.pdf" required style="font-size: 0.75rem; max-width: 10rem;">
                                        <button type="submit" style="background: none; border: 1px solid #cbd5e1; border-radius: 4px; padding: 0.2rem 0.45rem; font-size: 0.75rem; font-weight: 600; cursor: pointer; color: var(--primary-navy);">Attach</button>
                                    </form>
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

    @if(!empty($assets) && count($assets) > 0)
        <div style="margin-top: 1.5rem; background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); padding: 1.25rem;">
            <h3 style="margin: 0 0 0.35rem; color: var(--primary-navy); font-size: 1rem;">Capital assets — wear &amp; tear {{ $year }}</h3>
            <p style="margin: 0 0 1rem; font-size: 0.8rem; color: var(--text-muted);">Full purchase cost is not deducted in year one. These allowances feed your Live Fiscal Report.</p>
            <div style="overflow: auto;">
                <table style="width: 100%; border-collapse: collapse; font-size: 0.85rem;">
                    <thead>
                        <tr style="text-align: left; background: #f8fafc;">
                            <th style="padding: 0.65rem 0.75rem; border-bottom: 1px solid var(--border-light);">Asset</th>
                            <th style="padding: 0.65rem 0.75rem; border-bottom: 1px solid var(--border-light);">Bought</th>
                            <th style="padding: 0.65rem 0.75rem; border-bottom: 1px solid var(--border-light); text-align: right;">Cost basis</th>
                            <th style="padding: 0.65rem 0.75rem; border-bottom: 1px solid var(--border-light); text-align: right;">Rate</th>
                            <th style="padding: 0.65rem 0.75rem; border-bottom: 1px solid var(--border-light); text-align: right;">{{ $year }} allowance</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($assets as $asset)
                            <tr>
                                <td style="padding: 0.65rem 0.75rem; border-bottom: 1px solid #f1f5f9;">
                                    {{ $asset['description'] }}
                                    <div style="font-size: 0.75rem; color: var(--text-muted);">{{ $asset['asset_class'] }}@if($asset['business_use_percent'] < 99.9) · {{ number_format($asset['business_use_percent'], 0) }}% practice use @endif</div>
                                </td>
                                <td style="padding: 0.65rem 0.75rem; border-bottom: 1px solid #f1f5f9;">{{ \Illuminate\Support\Carbon::parse($asset['purchase_date'])->format('d M Y') }}</td>
                                <td style="padding: 0.65rem 0.75rem; border-bottom: 1px solid #f1f5f9; text-align: right;">€{{ number_format($asset['cost_basis'], 2) }}</td>
                                <td style="padding: 0.65rem 0.75rem; border-bottom: 1px solid #f1f5f9; text-align: right;">{{ number_format($asset['annual_rate'] * 100, 0) }}%</td>
                                <td style="padding: 0.65rem 0.75rem; border-bottom: 1px solid #f1f5f9; text-align: right; font-weight: 700; color: var(--primary-navy);">€{{ number_format($asset['allowance_this_year'], 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    @include('expenses.partials.claim-guide-modal')
    @include('expenses.partials.business-use-helpers', [
        'user' => $user,
        'redirectTo' => '/expenses?year='.$year,
    ])
@endsection
