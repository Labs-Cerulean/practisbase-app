@extends('layouts.app')

@section('page_title', 'Promotions')

@section('content')
    <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem; flex-wrap: wrap; margin-bottom: 1.5rem;">
        <div>
            <h1 style="font-size: 1.5rem; color: var(--primary-navy); margin: 0 0 0.25rem;">Promotion engine</h1>
            <p style="margin: 0; color: var(--text-muted); font-size: 0.95rem; max-width: 40rem;">
                Mint Founding codes here (Cerulean Labs). For the first 50: create unique codes with type Free months, value 6, max uses 1. Redeemed at registration; paid plans stay locked until a code or live billing.
            </p>
        </div>
        <a href="/company/promotions/create" style="background: var(--primary-cerulean); color: white; padding: 0.55rem 1rem; border-radius: var(--radius-md); font-weight: 600; text-decoration: none;">+ New promo</a>
    </div>

    @if(session('success'))
        <div style="background: #ecfdf5; color: #065f46; padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1.25rem; border: 1px solid #a7f3d0;">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div style="background: #fef2f2; color: #991b1b; padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1.25rem;">
            @foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach
        </div>
    @endif

    <div style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); overflow: hidden;">
        @if($promotions->isEmpty())
            <div style="padding: 2rem; color: var(--text-muted); text-align: center;">No promotions yet. Create Founding 50 or a seasonal code.</div>
        @else
            <table style="width: 100%; border-collapse: collapse; font-size: 0.88rem;">
                <thead>
                    <tr style="background: #f8fafc; text-align: left;">
                        <th style="padding: 0.8rem 1rem; border-bottom: 1px solid var(--border-light);">Code</th>
                        <th style="padding: 0.8rem 1rem; border-bottom: 1px solid var(--border-light);">Offer</th>
                        <th style="padding: 0.8rem 1rem; border-bottom: 1px solid var(--border-light);">Uses</th>
                        <th style="padding: 0.8rem 1rem; border-bottom: 1px solid var(--border-light);">Status</th>
                        <th style="padding: 0.8rem 1rem; border-bottom: 1px solid var(--border-light);"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($promotions as $promo)
                        <tr>
                            <td style="padding: 0.8rem 1rem; border-bottom: 1px solid #f1f5f9;">
                                <code style="font-weight: 700; color: var(--primary-navy);">{{ $promo->code }}</code>
                                @if($promo->label)
                                    <div style="font-size: 0.75rem; color: var(--text-muted);">{{ $promo->label }}</div>
                                @endif
                                <div style="font-size: 0.72rem; color: var(--text-muted);">Exp {{ optional($promo->expires_at)->format('d M Y') ?: '—' }}</div>
                            </td>
                            <td style="padding: 0.8rem 1rem; border-bottom: 1px solid #f1f5f9; color: var(--text-muted);">
                                {{ $promo->typeLabel() }} · {{ $promo->valueSummary() }}
                            </td>
                            <td style="padding: 0.8rem 1rem; border-bottom: 1px solid #f1f5f9;">
                                {{ $promo->current_uses }}{{ $promo->max_uses !== null ? ' / '.$promo->max_uses : '' }}
                            </td>
                            <td style="padding: 0.8rem 1rem; border-bottom: 1px solid #f1f5f9;">
                                <strong style="color: {{ $promo->isRedeemable() ? '#065f46' : '#991b1b' }};">
                                    {{ $promo->isRedeemable() ? 'Redeemable' : ($promo->is_active ? 'Unavailable' : 'Off') }}
                                </strong>
                            </td>
                            <td style="padding: 0.8rem 1rem; border-bottom: 1px solid #f1f5f9; text-align: right; white-space: nowrap;">
                                <a href="/company/promotions/{{ $promo->id }}/edit" style="color: var(--primary-cerulean); font-weight: 700; text-decoration: none; margin-right: 0.65rem;">Edit</a>
                                <form method="POST" action="/company/promotions/{{ $promo->id }}/toggle" style="display: inline;">
                                    @csrf
                                    <button type="submit" style="background: white; border: 1px solid var(--border-light); padding: 0.35rem 0.65rem; border-radius: var(--radius-md); font-weight: 600; cursor: pointer; font-size: 0.8rem;">
                                        {{ $promo->is_active ? 'Turn off' : 'Turn on' }}
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div style="padding: 0.85rem 1rem;">{{ $promotions->links() }}</div>
        @endif
    </div>
@endsection
