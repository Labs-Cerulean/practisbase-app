@extends('layouts.app')

@section('page_title', 'Dividends')

@section('content')
    <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.25rem;">
        <div>
            <h1 style="font-size: 1.4rem; color: var(--primary-navy); margin: 0 0 0.25rem;">Dividends</h1>
            <p style="margin: 0; color: var(--text-muted); font-size: 0.9rem;">Declare against retained earnings · pay from BOV</p>
        </div>
        <a href="/company" style="background: white; color: var(--primary-navy); border: 1px solid var(--border-light); padding: 0.55rem 1rem; border-radius: var(--radius-md); font-weight: 600; font-size: 0.85rem; text-decoration: none;">← Desk</a>
    </div>

    @if(session('success'))
        <div style="background: #ecfdf5; border: 1px solid #a7f3d0; color: #065f46; border-radius: var(--radius-lg); padding: 0.85rem 1.1rem; margin-bottom: 1rem; font-size: 0.9rem;">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div style="background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; border-radius: var(--radius-lg); padding: 0.85rem 1.1rem; margin-bottom: 1rem; font-size: 0.9rem;">{{ $errors->first() }}</div>
    @endif

    <div style="background: #eff6ff; border: 1px solid #bfdbfe; border-radius: var(--radius-lg); padding: 0.85rem 1.1rem; margin-bottom: 1.25rem; font-size: 0.85rem; color: #1e3a8a; line-height: 1.45;">
        Declaration posts Dr Retained earnings / Cr Dividends payable. Payment posts Dr Dividends payable / Cr Bank. Only declare what distributable reserves support.
    </div>

    <div style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.15rem 1.35rem; box-shadow: var(--shadow-sm); margin-bottom: 1.25rem;">
        <div style="font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.04em; margin-bottom: 0.75rem;">Declare dividend</div>
        <form method="POST" action="/company/dividends" style="display: flex; flex-wrap: wrap; gap: 0.65rem; align-items: end;">
            @csrf
            <div>
                <label style="display: block; font-size: 0.75rem; font-weight: 600; color: var(--text-muted); margin-bottom: 0.25rem;">Declared on</label>
                <input type="date" name="declared_on" value="{{ date('Y-m-d') }}" max="{{ date('Y-m-d') }}" required style="padding: 0.5rem 0.65rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
            </div>
            <div>
                <label style="display: block; font-size: 0.75rem; font-weight: 600; color: var(--text-muted); margin-bottom: 0.25rem;">Amount €</label>
                <input type="number" name="amount" step="0.01" min="0.01" required style="width: 8rem; padding: 0.5rem 0.65rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
            </div>
            <div style="flex: 1; min-width: 12rem;">
                <label style="display: block; font-size: 0.75rem; font-weight: 600; color: var(--text-muted); margin-bottom: 0.25rem;">Description</label>
                <input type="text" name="description" required maxlength="500" placeholder="Interim dividend FY…" style="width: 100%; padding: 0.5rem 0.65rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
            </div>
            <button type="submit" style="background: var(--primary-cerulean); color: white; border: none; padding: 0.55rem 1rem; border-radius: var(--radius-md); font-weight: 600; font-size: 0.85rem; cursor: pointer;">Declare</button>
        </form>
    </div>

    <div style="display: grid; gap: 0.75rem;">
        @forelse($dividends as $dividend)
            <div style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1rem 1.15rem; box-shadow: var(--shadow-sm);">
                <div style="display: flex; justify-content: space-between; gap: 1rem; flex-wrap: wrap;">
                    <div>
                        <div style="font-weight: 700; color: var(--primary-navy);">{{ $dividend->description }}</div>
                        <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.2rem;">
                            Declared {{ $dividend->declared_on->format('d M Y') }}
                            · {{ $dividend->status }}
                            @if($dividend->paid_on) · paid {{ $dividend->paid_on->format('d M Y') }} @endif
                        </div>
                    </div>
                    <div style="font-weight: 700; color: var(--primary-navy); font-size: 1.1rem;">€{{ number_format((float) $dividend->amount, 2) }}</div>
                </div>
                @if($dividend->status === 'declared')
                    <form method="POST" action="/company/dividends/{{ $dividend->id }}/pay" style="margin-top: 0.75rem; display: flex; flex-wrap: wrap; gap: 0.5rem; align-items: end;">
                        @csrf
                        <div>
                            <label style="display: block; font-size: 0.7rem; font-weight: 600; color: var(--text-muted); margin-bottom: 0.2rem;">Paid on</label>
                            <input type="date" name="paid_on" value="{{ date('Y-m-d') }}" max="{{ date('Y-m-d') }}" min="{{ $dividend->declared_on->format('Y-m-d') }}" required style="padding: 0.4rem 0.55rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); font-size: 0.85rem;">
                        </div>
                        <button type="submit" style="background: #059669; color: white; border: none; padding: 0.45rem 0.85rem; border-radius: var(--radius-md); font-weight: 600; font-size: 0.8rem; cursor: pointer;">Mark paid</button>
                    </form>
                @endif
            </div>
        @empty
            <div style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 2rem; text-align: center; color: var(--text-muted);">
                No dividends declared yet.
            </div>
        @endforelse
    </div>
@endsection
