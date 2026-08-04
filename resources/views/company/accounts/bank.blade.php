@extends('layouts.app')

@section('page_title', 'Bank recon')

@section('content')
    <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.25rem;">
        <div>
            <h1 style="font-size: 1.4rem; color: var(--primary-navy); margin: 0 0 0.25rem;">Bank reconciliation</h1>
            <p style="margin: 0; color: var(--text-muted); font-size: 0.9rem;">
                BOV statement lines vs ledger account 1000 · {{ $unreconciledCount }} unreconciled
            </p>
        </div>
        <a href="/company" style="background: white; color: var(--primary-navy); border: 1px solid var(--border-light); padding: 0.55rem 1rem; border-radius: var(--radius-md); font-weight: 600; font-size: 0.85rem; text-decoration: none;">← Desk</a>
    </div>

    @if(session('success'))
        <div style="background: #ecfdf5; border: 1px solid #a7f3d0; color: #065f46; border-radius: var(--radius-lg); padding: 0.85rem 1.1rem; margin-bottom: 1rem; font-size: 0.9rem;">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div style="background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; border-radius: var(--radius-lg); padding: 0.85rem 1.1rem; margin-bottom: 1rem; font-size: 0.9rem;">{{ $errors->first() }}</div>
    @endif

    <div style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.15rem 1.35rem; box-shadow: var(--shadow-sm); margin-bottom: 1.25rem;">
        <div style="font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.04em; margin-bottom: 0.75rem;">Add statement line</div>
        <form method="POST" action="/company/bank" style="display: flex; flex-wrap: wrap; gap: 0.65rem; align-items: end;">
            @csrf
            <div>
                <label style="display: block; font-size: 0.75rem; font-weight: 600; color: var(--text-muted); margin-bottom: 0.25rem;">Date</label>
                <input type="date" name="statement_date" value="{{ date('Y-m-d') }}" max="{{ date('Y-m-d') }}" required style="padding: 0.5rem 0.65rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
            </div>
            <div style="flex: 1; min-width: 12rem;">
                <label style="display: block; font-size: 0.75rem; font-weight: 600; color: var(--text-muted); margin-bottom: 0.25rem;">Description</label>
                <input type="text" name="description" required maxlength="500" placeholder="BOV narrative" style="width: 100%; padding: 0.5rem 0.65rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
            </div>
            <div>
                <label style="display: block; font-size: 0.75rem; font-weight: 600; color: var(--text-muted); margin-bottom: 0.25rem;">Amount (+in / −out)</label>
                <input type="number" name="amount" step="0.01" required style="width: 8.5rem; padding: 0.5rem 0.65rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
            </div>
            <button type="submit" style="background: var(--primary-cerulean); color: white; border: none; padding: 0.55rem 1rem; border-radius: var(--radius-md); font-weight: 600; font-size: 0.85rem; cursor: pointer;">Add</button>
        </form>
        <p style="margin: 0.75rem 0 0; font-size: 0.75rem; color: var(--text-muted); line-height: 1.4;">
            Money in is positive; payments out are negative. Match only when amounts agree exactly.
        </p>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 1.25rem;">
        <div style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.15rem 1.35rem; box-shadow: var(--shadow-sm);">
            <h2 style="font-size: 1.05rem; color: var(--primary-navy); margin: 0 0 1rem;">Statement lines</h2>
            <div style="display: grid; gap: 0.75rem;">
                @forelse($lines as $line)
                    <div style="border: 1px solid #e2e8f0; border-radius: var(--radius-md); padding: 0.85rem 1rem;">
                        <div style="display: flex; justify-content: space-between; gap: 0.75rem; flex-wrap: wrap;">
                            <div>
                                <div style="font-weight: 700; color: var(--primary-navy);">{{ $line->description }}</div>
                                <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.15rem;">
                                    {{ $line->statement_date->format('d M Y') }} · {{ $line->status }}
                                </div>
                            </div>
                            <div style="font-weight: 700; color: {{ (float) $line->amount >= 0 ? '#059669' : '#b91c1c' }};">
                                €{{ number_format((float) $line->amount, 2) }}
                            </div>
                        </div>
                        @if($line->status === 'unreconciled')
                            <form method="POST" action="/company/bank/{{ $line->id }}/match" style="margin-top: 0.65rem; display: flex; flex-wrap: wrap; gap: 0.4rem; align-items: end;">
                                @csrf
                                <div style="flex: 1; min-width: 10rem;">
                                    <label style="display: block; font-size: 0.7rem; font-weight: 600; color: var(--text-muted); margin-bottom: 0.2rem;">Match ledger line</label>
                                    <select name="journal_line_id" required style="width: 100%; padding: 0.4rem 0.5rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); font-size: 0.8rem;">
                                        <option value="">Select…</option>
                                        @foreach($unmatchedLedger as $jl)
                                            @php
                                                $signed = $jl->side === 'debit' ? (float) $jl->amount : -1 * (float) $jl->amount;
                                            @endphp
                                            <option value="{{ $jl->id }}">
                                                {{ $jl->entry->entry_date->format('d M') }} · €{{ number_format($signed, 2) }} · {{ \Illuminate\Support\Str::limit($jl->entry->description, 40) }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <button type="submit" style="background: #059669; color: white; border: none; padding: 0.45rem 0.75rem; border-radius: var(--radius-md); font-weight: 600; font-size: 0.8rem; cursor: pointer;">Match</button>
                            </form>
                        @elseif($line->matched_journal_line_id)
                            <div style="margin-top: 0.45rem; font-size: 0.8rem; color: #059669;">Matched to journal line #{{ $line->matched_journal_line_id }}</div>
                        @endif
                    </div>
                @empty
                    <div style="padding: 1.5rem; text-align: center; color: var(--text-muted); font-size: 0.9rem;">No statement lines yet. Paste BOV movements as you reconcile.</div>
                @endforelse
            </div>
        </div>

        <div style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.15rem 1.35rem; box-shadow: var(--shadow-sm);">
            <h2 style="font-size: 1.05rem; color: var(--primary-navy); margin: 0 0 1rem;">Unmatched bank ledger</h2>
            <div style="display: grid; gap: 0.55rem;">
                @forelse($unmatchedLedger as $jl)
                    @php $signed = $jl->side === 'debit' ? (float) $jl->amount : -1 * (float) $jl->amount; @endphp
                    <div style="display: flex; justify-content: space-between; gap: 0.75rem; padding: 0.55rem 0; border-bottom: 1px solid #f1f5f9; font-size: 0.85rem;">
                        <div>
                            <div style="color: var(--primary-navy); font-weight: 600;">{{ $jl->entry->description }}</div>
                            <div style="font-size: 0.75rem; color: var(--text-muted);">{{ $jl->entry->entry_date->format('d M Y') }} · line #{{ $jl->id }}</div>
                        </div>
                        <div style="font-weight: 700; font-variant-numeric: tabular-nums; color: {{ $signed >= 0 ? '#059669' : '#b91c1c' }};">€{{ number_format($signed, 2) }}</div>
                    </div>
                @empty
                    <div style="padding: 1.5rem; text-align: center; color: var(--text-muted); font-size: 0.9rem;">All bank journal lines are matched, or none posted yet.</div>
                @endforelse
            </div>
        </div>
    </div>
@endsection
