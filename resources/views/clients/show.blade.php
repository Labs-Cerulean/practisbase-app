@extends('layouts.app')

@section('page_title', 'Client')

@section('content')
    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 2rem; gap: 1rem; flex-wrap: wrap;">
        <div>
            <a href="{{ $client->trashed() ? '/clients?archived=1' : '/clients' }}" style="color: var(--text-muted); font-size: 0.85rem; font-weight: 600; text-decoration: none; margin-bottom: 0.5rem; display: inline-block;">&larr; Back to Directory</a>
            <h1 style="font-size: 1.75rem; color: var(--primary-navy); margin-bottom: 0.25rem;">{{ $client->name }}</h1>
            <span style="display: inline-block; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; color: var(--primary-cerulean); background: rgba(2, 132, 199, 0.1); padding: 0.25rem 0.75rem; border-radius: 20px;">
                {{ ucfirst($client->type) }}{{ $client->trashed() ? ' · Archived' : '' }}
            </span>
        </div>
        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
            @if($client->trashed())
                <form action="/clients/{{ $client->id }}/restore" method="POST">
                    @csrf
                    <button type="submit" style="background: var(--primary-cerulean); color: white; border: none; padding: 0.5rem 1rem; border-radius: var(--radius-md); font-weight: 600; cursor: pointer; box-shadow: var(--shadow-sm);">
                        Restore Client
                    </button>
                </form>
            @else
                <a href="/clients/{{ $client->id }}/edit" style="display: inline-block; background: white; border: 1px solid var(--border-light); color: var(--text-main); text-decoration: none; padding: 0.5rem 1rem; border-radius: var(--radius-md); font-weight: 600; cursor: pointer; box-shadow: var(--shadow-sm);">
                    Edit Details
                </a>
                <form action="/clients/{{ $client->id }}" method="POST" onsubmit="return confirm('Archive this client? Invoice history is kept. This does not free a Free-plan slot.');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" style="background: white; border: 1px solid #fecaca; color: #b91c1c; padding: 0.5rem 1rem; border-radius: var(--radius-md); font-weight: 600; cursor: pointer; box-shadow: var(--shadow-sm);">
                        Archive
                    </button>
                </form>
            @endif
        </div>
    </div>

    @if($errors->any())
        <div style="background: #fef2f2; border: 1px solid #f87171; color: #b91c1c; padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1.5rem; font-size: 0.9rem;">
            @foreach($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    @if(session('success'))
        <div style="background: #d1fae5; border: 1px solid #10b981; color: #047857; padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1.5rem; font-weight: 500;">
            {{ session('success') }}
        </div>
    @endif

    @if($client->trashed())
        <div style="margin-bottom: 1.5rem; padding: 0.85rem 1rem; background: #fffbeb; border: 1px solid #fef3c7; border-left: 4px solid #f59e0b; border-radius: var(--radius-md); color: #92400e; font-size: 0.9rem; line-height: 1.45;">
            This client is archived and hidden from the active directory. Ledger documents remain linked. Archiving does not restore a Free-plan client slot.
        </div>
    @endif

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">

        <div style="background: white; padding: 1.5rem; border-radius: var(--radius-md); border: 1px solid var(--border-light); box-shadow: var(--shadow-sm);">
            <h3 style="color: var(--primary-navy); margin-top: 0; margin-bottom: 1.25rem; font-size: 1.1rem; border-bottom: 1px solid var(--border-light); padding-bottom: 0.5rem;">Contact Information</h3>

            <div style="margin-bottom: 1rem;">
                <div style="font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted); font-weight: 700; margin-bottom: 0.25rem;">Phone Number</div>
                <div style="font-weight: 500; color: var(--text-main);">{{ $client->phone ?? 'Not provided' }}</div>
            </div>

            <div style="margin-bottom: 1rem;">
                <div style="font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted); font-weight: 700; margin-bottom: 0.25rem;">Email Address</div>
                <div style="font-weight: 500; color: var(--text-main);">{{ $client->email ?? 'Not provided' }}</div>
            </div>

            <div style="margin-bottom: 0;">
                <div style="font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted); font-weight: 700; margin-bottom: 0.25rem;">Billing Address</div>
                <div style="font-weight: 500; color: var(--text-main); line-height: 1.4;">{!! nl2br(e($client->billing_address ?? 'Not provided')) !!}</div>
            </div>
        </div>

        <div style="background: white; padding: 1.5rem; border-radius: var(--radius-md); border: 1px solid var(--border-light); box-shadow: var(--shadow-sm);">
            <h3 style="color: var(--primary-navy); margin-top: 0; margin-bottom: 1.25rem; font-size: 1.1rem; border-bottom: 1px solid var(--border-light); padding-bottom: 0.5rem;">Billing Profile</h3>

            @php
                $billingLabels = [
                    'vat_number' => 'VAT Number',
                    'registration_number' => 'Registration Number',
                    'contact_person' => 'Contact Person',
                    'id_card_number' => 'ID Card Number',
                ];
                $profile = is_array($client->profile_data) ? $client->profile_data : [];
                $hasBilling = false;
            @endphp
            @foreach($billingLabels as $key => $label)
                @if(!empty($profile[$key]))
                    @php $hasBilling = true; @endphp
                    <div style="margin-bottom: 1rem;">
                        <div style="font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted); font-weight: 700; margin-bottom: 0.25rem;">
                            {{ $label }}
                        </div>
                        <div style="font-weight: 500; color: var(--text-main);">{{ $profile[$key] }}</div>
                    </div>
                @endif
            @endforeach
            @unless($hasBilling)
                <p style="color: var(--text-muted); font-size: 0.9rem; margin: 0;">No additional billing data on file.</p>
            @endunless
        </div>
    </div>

    <div style="background: white; padding: 1.5rem; border-radius: var(--radius-md); border: 1px solid var(--border-light); box-shadow: var(--shadow-sm); margin-bottom: 2rem;">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 1rem; margin-bottom: 1rem;">
            <div>
                <h3 style="color: var(--primary-navy); margin: 0 0 0.35rem; font-size: 1.1rem;">What they owe</h3>
                <p style="margin: 0; font-size: 0.8rem; color: var(--text-muted); line-height: 1.4;">
                    Tax invoices affect official balance. RFP amounts are tracked separately until converted.
                </p>
            </div>
            <div style="display: flex; gap: 1.25rem; flex-wrap: wrap;">
                <div>
                    <div style="font-size: 0.7rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">On tax invoices</div>
                    <div style="font-size: 1.2rem; font-weight: 700; color: {{ $statement['official_owed'] > 0 ? '#dc2626' : '#059669' }};">€{{ number_format($statement['official_owed'], 2) }}</div>
                </div>
                <div>
                    <div style="font-size: 0.7rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">On RFPs</div>
                    <div style="font-size: 1.2rem; font-weight: 700; color: #4338ca;">€{{ number_format($statement['rfp_owed'], 2) }}</div>
                </div>
                <div>
                    <div style="font-size: 0.7rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Total</div>
                    <div style="font-size: 1.2rem; font-weight: 700; color: var(--primary-navy);">€{{ number_format($statement['total_owed'], 2) }}</div>
                </div>
            </div>
        </div>

        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; font-size: 0.85rem;">
                <thead>
                    <tr style="text-align: left; border-bottom: 2px solid var(--border-light); color: var(--text-muted); font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.03em;">
                        <th style="padding: 0.5rem 0.35rem;">Date</th>
                        <th style="padding: 0.5rem 0.35rem;">Item</th>
                        <th style="padding: 0.5rem 0.35rem; text-align: right;">Billed</th>
                        <th style="padding: 0.5rem 0.35rem; text-align: right;">Paid</th>
                        <th style="padding: 0.5rem 0.35rem; text-align: right;">Invoice bal.</th>
                        <th style="padding: 0.5rem 0.35rem; text-align: right;">RFP bal.</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($statement['rows'] as $row)
                        <tr style="border-bottom: 1px solid #f1f5f9;">
                            <td style="padding: 0.45rem 0.35rem; white-space: nowrap;">{{ $row['date']->format('d M Y') }}</td>
                            <td style="padding: 0.45rem 0.35rem; color: var(--primary-navy);">{{ $row['label'] }}</td>
                            <td style="padding: 0.45rem 0.35rem; text-align: right; font-variant-numeric: tabular-nums;">{{ $row['debit'] > 0 ? '€'.number_format($row['debit'], 2) : '' }}</td>
                            <td style="padding: 0.45rem 0.35rem; text-align: right; font-variant-numeric: tabular-nums;">{{ $row['credit'] > 0 ? '€'.number_format($row['credit'], 2) : '' }}</td>
                            <td style="padding: 0.45rem 0.35rem; text-align: right; font-variant-numeric: tabular-nums; font-weight: 600;">€{{ number_format($row['official_balance'], 2) }}</td>
                            <td style="padding: 0.45rem 0.35rem; text-align: right; font-variant-numeric: tabular-nums; color: #4338ca;">€{{ number_format($row['rfp_balance'], 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="padding: 1.5rem; text-align: center; color: var(--text-muted);">No documents yet for this client.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div style="margin-top: 1rem;">
            <a href="/ledger?client_id={{ $client->id }}" style="font-size: 0.85rem; font-weight: 600; color: var(--primary-cerulean); text-decoration: none;">Open in ledger →</a>
        </div>
    </div>
@endsection
