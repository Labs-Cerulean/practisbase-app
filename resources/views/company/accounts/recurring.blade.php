@extends('layouts.app')

@section('page_title', 'Monthly billing')

@section('content')
    <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.25rem;">
        <div>
            <h1 style="font-size: 1.4rem; color: var(--primary-navy); margin: 0 0 0.25rem;">Monthly billing</h1>
            <p style="margin: 0; color: var(--text-muted); font-size: 0.9rem;">Recurring B2B invoices · auto-posted to the ledger when generated</p>
        </div>
        <form method="POST" action="/company/recurring/generate">
            @csrf
            <button type="submit" style="background: var(--primary-cerulean); color: white; border: none; padding: 0.55rem 1rem; border-radius: var(--radius-md); font-weight: 600; font-size: 0.85rem; cursor: pointer;">Generate due invoices</button>
        </form>
    </div>

    @if(session('success'))
        <div style="background: #ecfdf5; border: 1px solid #a7f3d0; color: #065f46; border-radius: var(--radius-lg); padding: 0.85rem 1.1rem; margin-bottom: 1rem; font-size: 0.9rem;">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div style="background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; border-radius: var(--radius-lg); padding: 0.85rem 1.1rem; margin-bottom: 1rem; font-size: 0.9rem;">
            <ul style="margin: 0; padding-left: 1.1rem;">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.25rem 1.4rem; box-shadow: var(--shadow-sm); margin-bottom: 1.5rem;">
        <div style="font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.04em; margin-bottom: 1rem;">New schedule</div>
        <form method="POST" action="/company/recurring" id="recurring-form">
            @csrf
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 0.75rem; margin-bottom: 1rem;">
                <div style="grid-column: span 2;">
                    <label style="display: block; font-size: 0.75rem; font-weight: 600; color: var(--text-muted); margin-bottom: 0.25rem;">Client</label>
                    <select name="company_client_id" required style="width: 100%; padding: 0.5rem 0.65rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                        <option value="">Select…</option>
                        @foreach($clients as $client)
                            <option value="{{ $client->id }}">{{ $client->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div style="grid-column: span 2;">
                    <label style="display: block; font-size: 0.75rem; font-weight: 600; color: var(--text-muted); margin-bottom: 0.25rem;">Title</label>
                    <input type="text" name="title" required maxlength="255" placeholder="e.g. PractisBase monthly retainer" style="width: 100%; padding: 0.5rem 0.65rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                </div>
                <div>
                    <label style="display: block; font-size: 0.75rem; font-weight: 600; color: var(--text-muted); margin-bottom: 0.25rem;">Day of month (1–28)</label>
                    <input type="number" name="day_of_month" min="1" max="28" value="1" required style="width: 100%; padding: 0.5rem 0.65rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                </div>
                <div>
                    <label style="display: block; font-size: 0.75rem; font-weight: 600; color: var(--text-muted); margin-bottom: 0.25rem;">Next issue on</label>
                    <input type="date" name="next_issue_on" value="{{ date('Y-m-d') }}" required style="width: 100%; padding: 0.5rem 0.65rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                </div>
                <div>
                    <label style="display: block; font-size: 0.75rem; font-weight: 600; color: var(--text-muted); margin-bottom: 0.25rem;">Due days</label>
                    <input type="number" name="due_days" min="0" max="90" value="14" required style="width: 100%; padding: 0.5rem 0.65rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                </div>
            </div>

            <div style="font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.04em; margin-bottom: 0.5rem;">Line items (ex-VAT)</div>
            <div id="items" style="display: grid; gap: 0.5rem; margin-bottom: 0.75rem;">
                <div class="item-row" style="display: grid; grid-template-columns: 2fr 0.7fr 0.9fr; gap: 0.5rem;">
                    <input type="text" name="item_desc[]" required placeholder="Description" style="padding: 0.5rem 0.65rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                    <input type="number" name="item_qty[]" step="0.01" min="0.01" value="1" required placeholder="Qty" style="padding: 0.5rem 0.65rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                    <input type="number" name="item_price[]" step="0.01" min="0" value="0" required placeholder="Unit €" style="padding: 0.5rem 0.65rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                </div>
            </div>
            <button type="button" id="add-item" style="background: white; color: var(--primary-navy); border: 1px solid var(--border-light); padding: 0.4rem 0.75rem; border-radius: var(--radius-md); font-weight: 600; font-size: 0.8rem; cursor: pointer; margin-bottom: 1rem;">+ Line</button>

            <div style="margin-bottom: 1rem;">
                <label style="display: block; font-size: 0.75rem; font-weight: 600; color: var(--text-muted); margin-bottom: 0.25rem;">Notes</label>
                <textarea name="notes" rows="2" maxlength="2000" style="width: 100%; padding: 0.5rem 0.65rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); resize: vertical;"></textarea>
            </div>
            <button type="submit" style="background: var(--primary-navy); color: white; border: none; padding: 0.55rem 1.1rem; border-radius: var(--radius-md); font-weight: 600; font-size: 0.85rem; cursor: pointer;">Save schedule</button>
        </form>
    </div>

    <div style="display: grid; gap: 0.75rem;">
        @forelse($schedules as $schedule)
            @php
                $subtotal = collect($schedule->items ?? [])->sum(fn ($row) => (float) ($row['line_total'] ?? 0));
            @endphp
            <div style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1rem 1.15rem; box-shadow: var(--shadow-sm);">
                <div style="display: flex; justify-content: space-between; gap: 1rem; flex-wrap: wrap;">
                    <div>
                        <div style="font-weight: 700; color: var(--primary-navy);">{{ $schedule->title }}</div>
                        <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.2rem;">
                            {{ $schedule->client->name ?? 'Client' }}
                            · day {{ $schedule->day_of_month }}
                            · next {{ $schedule->next_issue_on->format('d M Y') }}
                            · due +{{ $schedule->due_days }}d
                            · {{ $schedule->is_active ? 'active' : 'paused' }}
                        </div>
                        @if($schedule->last_generated_on)
                            <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.15rem;">Last generated {{ $schedule->last_generated_on->format('d M Y') }}</div>
                        @endif
                    </div>
                    <div style="text-align: right;">
                        <div style="font-weight: 700; color: var(--primary-navy);">€{{ number_format($subtotal, 2) }} <span style="font-size: 0.75rem; font-weight: 500; color: var(--text-muted);">ex-VAT</span></div>
                        <form method="POST" action="/company/recurring/{{ $schedule->id }}/toggle" style="margin-top: 0.5rem;">
                            @csrf
                            <button type="submit" style="font-size: 0.8rem; font-weight: 600; background: white; color: var(--primary-navy); border: 1px solid var(--border-light); border-radius: var(--radius-md); padding: 0.35rem 0.7rem; cursor: pointer;">
                                {{ $schedule->is_active ? 'Pause' : 'Activate' }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @empty
            <div style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 2rem; text-align: center; color: var(--text-muted);">
                No recurring schedules yet. Add your first B2B monthly pattern above.
            </div>
        @endforelse
    </div>

    <script>
        document.getElementById('add-item')?.addEventListener('click', function () {
            const wrap = document.getElementById('items');
            const row = document.createElement('div');
            row.className = 'item-row';
            row.style.cssText = 'display: grid; grid-template-columns: 2fr 0.7fr 0.9fr; gap: 0.5rem;';
            row.innerHTML = '<input type="text" name="item_desc[]" required placeholder="Description" style="padding: 0.5rem 0.65rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">' +
                '<input type="number" name="item_qty[]" step="0.01" min="0.01" value="1" required placeholder="Qty" style="padding: 0.5rem 0.65rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">' +
                '<input type="number" name="item_price[]" step="0.01" min="0" value="0" required placeholder="Unit €" style="padding: 0.5rem 0.65rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">';
            wrap.appendChild(row);
        });
    </script>
@endsection
