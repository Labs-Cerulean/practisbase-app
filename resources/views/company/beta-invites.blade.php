@extends('layouts.app')

@section('page_title', 'Beta invites')

@section('content')
    <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem; flex-wrap: wrap; margin-bottom: 1.5rem;">
        <div>
            <h1 style="font-size: 1.5rem; color: var(--primary-navy); margin: 0 0 0.25rem;">Beta invite codes</h1>
            <p style="margin: 0; color: var(--text-muted); font-size: 0.95rem; max-width: 36rem;">
                Mint one-use codes locked to Engineer, Architect, or Medical Pro. Registration without a valid code is blocked.
            </p>
        </div>
    </div>

    @if(session('success'))
        <div style="background: #ecfdf5; color: #065f46; padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1.25rem; border: 1px solid #a7f3d0;">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div style="background: #fef2f2; color: #991b1b; padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1.25rem; border: 1px solid #fecaca;">
            @foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach
        </div>
    @endif

    <div style="display: grid; grid-template-columns: minmax(260px, 340px) minmax(0, 1fr); gap: 1.25rem; align-items: start;">
        <form method="POST" action="/company/beta-invites" style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); padding: 1.25rem;">
            @csrf
            <h2 style="margin: 0 0 0.85rem; font-size: 1rem; color: var(--primary-navy);">Create invite</h2>
            <label style="display: block; font-weight: 600; margin-bottom: 0.35rem; color: var(--text-muted); font-size: 0.8rem;">Pro package</label>
            <select name="pro_package" required style="width: 100%; padding: 0.65rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); font: inherit; margin-bottom: 0.85rem;">
                @foreach($packages as $key => $label)
                    <option value="{{ $key }}" @selected(old('pro_package') === $key)>{{ $label }}</option>
                @endforeach
            </select>
            <label style="display: block; font-weight: 600; margin-bottom: 0.35rem; color: var(--text-muted); font-size: 0.8rem;">Label (who it’s for)</label>
            <input type="text" name="label" maxlength="120" value="{{ old('label') }}" placeholder="e.g. Maria — engineer friend" style="width: 100%; padding: 0.65rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); font: inherit; margin-bottom: 0.85rem;">
            <label style="display: block; font-weight: 600; margin-bottom: 0.35rem; color: var(--text-muted); font-size: 0.8rem;">Expires in (days)</label>
            <input type="number" name="expires_days" min="1" max="365" value="{{ old('expires_days', 30) }}" style="width: 100%; padding: 0.65rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); font: inherit; margin-bottom: 1rem;">
            <p style="margin: 0 0 0.85rem; font-size: 0.78rem; color: var(--text-muted); line-height: 1.4;">Each code is single-use and unlocks Full Pro for that profession only.</p>
            <button type="submit" style="width: 100%; background: var(--primary-cerulean); color: white; border: none; padding: 0.7rem 1rem; border-radius: var(--radius-md); font-weight: 600; cursor: pointer;">Generate code</button>
        </form>

        <div style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); overflow: hidden;">
            @if($invites->isEmpty())
                <div style="padding: 2rem; color: var(--text-muted); text-align: center;">No invites yet. Generate one for each beta tester.</div>
            @else
                <table style="width: 100%; border-collapse: collapse; font-size: 0.88rem;">
                    <thead>
                        <tr style="background: #f8fafc; text-align: left;">
                            <th style="padding: 0.8rem 1rem; border-bottom: 1px solid var(--border-light);">Code</th>
                            <th style="padding: 0.8rem 1rem; border-bottom: 1px solid var(--border-light);">Package</th>
                            <th style="padding: 0.8rem 1rem; border-bottom: 1px solid var(--border-light);">Status</th>
                            <th style="padding: 0.8rem 1rem; border-bottom: 1px solid var(--border-light);">Redeemed</th>
                            <th style="padding: 0.8rem 1rem; border-bottom: 1px solid var(--border-light);"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($invites as $invite)
                            <tr>
                                <td style="padding: 0.8rem 1rem; border-bottom: 1px solid #f1f5f9;">
                                    <code style="font-weight: 700; color: var(--primary-navy); letter-spacing: 0.03em;">{{ $invite->code }}</code>
                                    @if($invite->label)
                                        <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.15rem;">{{ $invite->label }}</div>
                                    @endif
                                    <div style="font-size: 0.72rem; color: var(--text-muted);">Exp {{ optional($invite->expires_at)->format('d M Y') ?: '—' }}</div>
                                </td>
                                <td style="padding: 0.8rem 1rem; border-bottom: 1px solid #f1f5f9; color: var(--text-muted);">{{ $invite->packageLabel() }}</td>
                                <td style="padding: 0.8rem 1rem; border-bottom: 1px solid #f1f5f9;">
                                    <strong style="color: {{ $invite->isRedeemable() ? '#065f46' : '#991b1b' }};">{{ $invite->statusLabel() }}</strong>
                                </td>
                                <td style="padding: 0.8rem 1rem; border-bottom: 1px solid #f1f5f9; color: var(--text-muted);">
                                    @if($invite->redeemedBy)
                                        {{ $invite->redeemedBy->name }}
                                        <div style="font-size: 0.72rem;">{{ $invite->redeemedBy->email }}</div>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td style="padding: 0.8rem 1rem; border-bottom: 1px solid #f1f5f9; text-align: right;">
                                    @if($invite->isRedeemable())
                                        <form method="POST" action="/company/beta-invites/{{ $invite->id }}/revoke" onsubmit="return confirm('Revoke {{ $invite->code }}?');">
                                            @csrf
                                            <button type="submit" style="background: none; border: none; color: #b91c1c; font-weight: 600; cursor: pointer; font-size: 0.8rem;">Revoke</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <div style="padding: 0.75rem 1rem;">{{ $invites->links() }}</div>
            @endif
        </div>
    </div>

    <style>
        @media (max-width: 900px) {
            div[style*="grid-template-columns: minmax(260px, 340px)"] {
                display: flex !important;
                flex-direction: column !important;
            }
        }
    </style>
@endsection
