@extends('layouts.app')

@section('page_title', 'My stamps')

@section('content')
    <div style="max-width: 920px; margin: 0 auto;">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem; flex-wrap: wrap; margin-bottom: 1.25rem;">
            <div>
                <h1 style="color: var(--primary-navy); margin: 0 0 0.35rem; font-size: 1.5rem;">My stamps</h1>
                <p style="color: var(--text-muted); margin: 0; line-height: 1.45;">Build a stamp once, then place it on any PDF. Wet signature upload or draw in app.</p>
            </div>
            <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                <a href="/stamper" style="background: white; color: var(--primary-navy); border: 1px solid var(--border-light); padding: 0.55rem 1rem; border-radius: var(--radius-md); font-weight: 600; font-size: 0.85rem; text-decoration: none;">Stamp a PDF</a>
                <a href="/stamper/stamps/create" style="background: var(--primary-cerulean); color: white; padding: 0.55rem 1rem; border-radius: var(--radius-md); font-weight: 600; font-size: 0.85rem; text-decoration: none;">+ New stamp</a>
            </div>
        </div>

        @if($stamps->isEmpty())
            <div style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 2rem; box-shadow: var(--shadow-sm); text-align: center;">
                <p style="color: var(--text-muted); margin: 0 0 1rem;">No stamps yet. Create one with your name, role, and signature.</p>
                <a href="/stamper/stamps/create" style="display: inline-block; background: var(--primary-cerulean); color: white; padding: 0.7rem 1.25rem; border-radius: var(--radius-md); font-weight: 700; text-decoration: none;">Create your stamp</a>
            </div>
        @else
            <div style="display: grid; gap: 1rem;">
                @foreach($stamps as $stamp)
                    <div style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.25rem 1.5rem; box-shadow: var(--shadow-sm); display: flex; justify-content: space-between; gap: 1rem; flex-wrap: wrap; align-items: center;">
                        <div>
                            <div style="display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
                                <strong style="color: var(--primary-navy); font-size: 1.05rem;">{{ $stamp->label }}</strong>
                                @if($stamp->is_default)
                                    <span style="font-size: 0.7rem; font-weight: 700; letter-spacing: 0.03em; text-transform: uppercase; background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; padding: 0.15rem 0.45rem; border-radius: 999px;">Default</span>
                                @endif
                            </div>
                            <div style="color: var(--text-muted); font-size: 0.9rem; margin-top: 0.35rem; line-height: 1.45;">
                                {{ $stamp->displayName() }}@if($stamp->postnominals), {{ $stamp->postnominals }}@endif
                                · {{ $stamp->role_title }}
                                · {{ $stamp->presetLabel() }}
                                @if($stamp->signature_path) · Signature on file @else · No signature @endif
                            </div>
                        </div>
                        <div style="display: flex; gap: 0.45rem; flex-wrap: wrap;">
                            @unless($stamp->is_default)
                                <form action="/stamper/stamps/{{ $stamp->id }}/default" method="POST" style="margin: 0;">
                                    @csrf
                                    <button type="submit" style="background: white; color: var(--primary-navy); border: 1px solid var(--border-light); padding: 0.45rem 0.85rem; border-radius: var(--radius-md); font-weight: 600; font-size: 0.8rem; cursor: pointer;">Make default</button>
                                </form>
                            @endunless
                            <a href="/stamper/stamps/{{ $stamp->id }}/edit" style="background: white; color: var(--primary-navy); border: 1px solid var(--border-light); padding: 0.45rem 0.85rem; border-radius: var(--radius-md); font-weight: 600; font-size: 0.8rem; text-decoration: none;">Edit</a>
                            <form action="/stamper/stamps/{{ $stamp->id }}" method="POST" style="margin: 0;" onsubmit="return confirm('Delete this stamp?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" style="background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; padding: 0.45rem 0.85rem; border-radius: var(--radius-md); font-weight: 600; font-size: 0.8rem; cursor: pointer;">Delete</button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
@endsection
