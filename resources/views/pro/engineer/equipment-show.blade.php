@extends('layouts.app')

@section('page_title', $equipment->name)

@section('content')
    @php $tone = $equipment->dueTone(); @endphp

    <div style="margin-bottom: 1rem;">
        <a href="/pro/engineer/equipment" style="color: var(--primary-cerulean); font-weight: 600; text-decoration: none; font-size: 0.9rem;">← Equipment</a>
    </div>

    <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem; flex-wrap: wrap; margin-bottom: 1.25rem;">
        <div>
            <h1 style="margin: 0 0 0.3rem; color: var(--primary-navy); font-size: 1.5rem;">{{ $equipment->name }}</h1>
            <div style="font-size: 0.9rem; color: var(--text-muted);">
                {{ $equipment->asset_code }} · {{ $equipment->categoryLabel() }} · {{ $equipment->client->name ?? '—' }}
                · <span style="display: inline-block; background: {{ $tone['bg'] }}; color: {{ $tone['fg'] }}; border: 1px solid {{ $tone['border'] }}; padding: 0.15rem 0.5rem; border-radius: 999px; font-size: 0.72rem; font-weight: 700;">{{ $tone['label'] }}</span>
            </div>
        </div>
        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
            <a href="/pro/engineer/equipment/{{ $equipment->id }}/edit" style="background: white; border: 1px solid var(--border-light); color: var(--primary-navy); padding: 0.55rem 1rem; border-radius: var(--radius-md); font-weight: 600; text-decoration: none;">Edit</a>
            <a href="/pro/engineer/equipment/{{ $equipment->id }}/certificates/create" style="background: var(--primary-cerulean); color: white; padding: 0.55rem 1rem; border-radius: var(--radius-md); font-weight: 600; text-decoration: none;">Issue certificate</a>
            <form method="POST" action="/pro/engineer/equipment/{{ $equipment->id }}/renew" style="margin: 0;">
                @csrf
                <button type="submit" style="background: white; border: 1px solid var(--border-light); color: var(--primary-navy); padding: 0.55rem 1rem; border-radius: var(--radius-md); font-weight: 600; cursor: pointer;">Renew</button>
            </form>
        </div>
    </div>

    @if(session('success'))
        <div style="background: #ecfdf5; color: #065f46; padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1rem; border: 1px solid #a7f3d0;">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div style="background: #fef2f2; color: #991b1b; padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1rem; border: 1px solid #fecaca;">
            @foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach
        </div>
    @endif

    <div style="display: grid; grid-template-columns: minmax(0, 1.4fr) minmax(260px, 0.8fr); gap: 1.15rem; align-items: start;">
        <div style="display: grid; gap: 1rem;">
            <section style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); padding: 1.2rem;">
                <h2 style="margin: 0 0 0.85rem; font-size: 1rem; color: var(--primary-navy);">Asset details</h2>
                <dl style="margin: 0; display: grid; grid-template-columns: minmax(110px, 0.7fr) 1.3fr; gap: 0.4rem 1rem; font-size: 0.9rem;">
                    <dt style="color: var(--text-muted);">Make / model</dt>
                    <dd style="margin: 0; color: var(--primary-navy);">{{ trim(($equipment->make ?? '').' '.($equipment->model ?? '')) ?: '—' }}</dd>
                    <dt style="color: var(--text-muted);">Serial</dt>
                    <dd style="margin: 0; color: var(--primary-navy);">{{ $equipment->serial_number ?: '—' }}</dd>
                    <dt style="color: var(--text-muted);">Capacity</dt>
                    <dd style="margin: 0; color: var(--primary-navy);">{{ $equipment->capacity_rating ?: '—' }}</dd>
                    <dt style="color: var(--text-muted);">Year</dt>
                    <dd style="margin: 0; color: var(--primary-navy);">{{ $equipment->year_of_manufacture ?: '—' }}</dd>
                    <dt style="color: var(--text-muted);">Location</dt>
                    <dd style="margin: 0; color: var(--primary-navy);">{{ $equipment->site_location ?: '—' }}</dd>
                    <dt style="color: var(--text-muted);">Status</dt>
                    <dd style="margin: 0; color: var(--primary-navy);">{{ $equipment->statusLabel() }}</dd>
                    <dt style="color: var(--text-muted);">Last certified</dt>
                    <dd style="margin: 0; color: var(--primary-navy);">{{ optional($equipment->last_certified_on)->format('d M Y') ?: '—' }}</dd>
                    <dt style="color: var(--text-muted);">Next due</dt>
                    <dd style="margin: 0; color: var(--primary-navy);">{{ optional($equipment->next_due_on)->format('d M Y') ?: '—' }}</dd>
                </dl>
                @if($equipment->notes)
                    <p style="margin: 1rem 0 0; padding-top: 0.85rem; border-top: 1px solid #e2e8f0; font-size: 0.9rem; color: #334155; white-space: pre-wrap;">{{ $equipment->notes }}</p>
                @endif
            </section>

            <section style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); padding: 1.2rem;">
                <div style="display: flex; justify-content: space-between; align-items: center; gap: 0.75rem; flex-wrap: wrap; margin-bottom: 0.85rem;">
                    <h2 style="margin: 0; font-size: 1rem; color: var(--primary-navy);">Certificates</h2>
                    <a href="/pro/engineer/equipment/{{ $equipment->id }}/certificates/create" style="color: var(--primary-cerulean); font-weight: 700; text-decoration: none; font-size: 0.85rem;">+ New inspection</a>
                </div>
                @if($equipment->certificates->isEmpty())
                    <p style="margin: 0; color: var(--text-muted); font-size: 0.9rem;">No certificates yet for this asset.</p>
                @else
                    <div style="display: grid; gap: 0.5rem;">
                        @foreach($equipment->certificates as $cert)
                            <a href="/pro/engineer/certificates/{{ $cert->id }}" style="display: block; padding: 0.75rem 0.85rem; border: 1px solid #e2e8f0; border-radius: var(--radius-md); text-decoration: none;">
                                <div style="font-weight: 700; color: var(--primary-navy);">{{ $cert->title }}</div>
                                <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.15rem;">
                                    {{ $cert->isStamped() ? 'Issued '.$cert->issue_code : 'Draft' }}
                                    · {{ optional($cert->issued_on)->format('d M Y') }}
                                    @if($cert->expires_on) · Exp {{ $cert->expires_on->format('d M Y') }} @endif
                                </div>
                            </a>
                        @endforeach
                    </div>
                @endif
            </section>
        </div>

        <aside style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); padding: 1.15rem;">
            <h2 style="margin: 0 0 0.75rem; font-size: 0.95rem; color: var(--primary-navy);">Create RFP</h2>
            <p style="margin: 0 0 0.85rem; font-size: 0.8rem; color: var(--text-muted); line-height: 1.45;">
                Raises a request for payment against <strong>{{ $equipment->client->name ?? 'this client' }}</strong>. Fiscal weight stays €0 until you convert it to an invoice.
            </p>
            <form method="POST" action="/pro/engineer/equipment/{{ $equipment->id }}/rfp">
                @csrf
                <label style="display: block; font-weight: 600; margin-bottom: 0.35rem; color: var(--text-muted); font-size: 0.8rem;">Description</label>
                <input type="text" name="description" required maxlength="500"
                       value="{{ old('description', 'Inspection / certification — '.$equipment->name.' ('.$equipment->asset_code.')') }}"
                       style="width: 100%; padding: 0.6rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); font: inherit; margin-bottom: 0.75rem;">
                <label style="display: block; font-weight: 600; margin-bottom: 0.35rem; color: var(--text-muted); font-size: 0.8rem;">Amount (excl. VAT) €</label>
                <input type="number" name="amount" required min="0.01" step="0.01" value="{{ old('amount', '100.00') }}"
                       style="width: 100%; padding: 0.6rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); font: inherit; margin-bottom: 0.75rem;">
                <label style="display: flex; align-items: center; gap: 0.45rem; font-size: 0.85rem; color: var(--primary-navy); margin-bottom: 0.85rem;">
                    <input type="checkbox" name="apply_vat" value="1" @checked(old('apply_vat'))>
                    Apply 18% VAT (Article 10 only)
                </label>
                <button type="submit" style="width: 100%; background: var(--primary-navy); color: white; border: none; padding: 0.65rem 1rem; border-radius: var(--radius-md); font-weight: 600; cursor: pointer;">Create RFP</button>
            </form>
        </aside>
    </div>

    <style>
        @media (max-width: 900px) {
            div[style*="grid-template-columns: minmax(0, 1.4fr)"] {
                display: flex !important;
                flex-direction: column !important;
            }
        }
    </style>
@endsection
