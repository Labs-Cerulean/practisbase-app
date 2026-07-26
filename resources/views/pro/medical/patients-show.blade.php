@extends('layouts.app')

@section('page_title', 'Patient')

@section('content')
    <a href="/pro/medical/patients" style="color: var(--text-muted); font-weight: 600; text-decoration: none; font-size: 0.85rem;">&larr; Patients</a>
    <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem; margin: 0.75rem 0 1.25rem;">
        <div>
            <h1 style="margin: 0; color: var(--primary-navy);">{{ $payload['display_name'] ?? 'Patient' }}</h1>
            <div style="font-size: 0.85rem; color: var(--text-muted);">{{ $patient->public_ref }}</div>
        </div>
        <a href="/pro/medical/patients/{{ $patient->id }}/entries/create" style="background: var(--primary-cerulean); color: white; padding: 0.55rem 1rem; border-radius: var(--radius-md); font-weight: 600; text-decoration: none;">+ Clinical entry</a>
    </div>

    @if(session('success'))
        <div style="background: #ecfdf5; color: #065f46; padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1rem;">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div style="background: #fef2f2; color: #991b1b; padding: 0.85rem; border-radius: var(--radius-md); margin-bottom: 1rem;">
            @foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach
        </div>
    @endif

    <div style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.25rem; margin-bottom: 1.25rem; box-shadow: var(--shadow-sm);">
        <div style="font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted); font-weight: 700;">Date of birth</div>
        <div style="margin-bottom: 0.75rem;">{{ !empty($payload['date_of_birth']) ? \Illuminate\Support\Carbon::parse($payload['date_of_birth'])->format('d M Y') : '—' }}</div>
        <div style="font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted); font-weight: 700;">Notes</div>
        <div style="white-space: pre-wrap;">{{ $payload['notes'] ?: '—' }}</div>
    </div>

    <h3 style="color: var(--primary-navy);">Clinical entries</h3>
    @if($entries->isEmpty())
        <p style="color: var(--text-muted);">No journal / prescription / referral entries yet.</p>
    @else
        <div style="display: grid; gap: 0.75rem;">
            @foreach($entries as $entry)
                <div style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-md); padding: 1rem; box-shadow: var(--shadow-sm);">
                    <div style="display: flex; justify-content: space-between; gap: 1rem; flex-wrap: wrap;">
                        <strong style="color: var(--primary-navy);">{{ $entry['title'] }}</strong>
                        <span style="font-size: 0.75rem; font-weight: 700; color: var(--text-muted);">{{ $entry['type_label'] }} · {{ $entry['model']->entry_date->format('d M Y') }}</span>
                    </div>
                    <div style="margin-top: 0.5rem; color: var(--text-main); white-space: pre-wrap; font-size: 0.9rem;">{{ $entry['body'] }}</div>

                    @if(in_array($entry['model']->entry_type, ['prescription', 'referral'], true))
                        <div style="margin-top: 0.75rem;">
                            <a href="/pro/medical/patients/{{ $patient->id }}/entries/{{ $entry['model']->id }}/pdf"
                               style="display: inline-block; padding: 0.4rem 0.75rem; border: 1px solid var(--primary-navy); color: var(--primary-navy); border-radius: var(--radius-md); font-size: 0.8rem; font-weight: 700; text-decoration: none;">
                                Download signed PDF
                            </a>
                        </div>
                    @endif

                    @if(!empty($entry['attachments']))
                        <div style="margin-top: 0.85rem; padding-top: 0.75rem; border-top: 1px solid var(--border-light);">
                            <div style="font-size: 0.7rem; text-transform: uppercase; color: var(--text-muted); font-weight: 700; margin-bottom: 0.4rem;">Encrypted attachments</div>
                            <ul style="margin: 0; padding-left: 1.1rem;">
                                @foreach($entry['attachments'] as $att)
                                    <li style="margin-bottom: 0.25rem;">
                                        <a href="/pro/medical/patients/{{ $patient->id }}/attachments/{{ $att['id'] }}/download"
                                           style="color: var(--primary-cerulean); font-weight: 600; text-decoration: none; border-bottom: 1px dotted var(--primary-navy);">
                                            {{ $att['name'] }}
                                        </a>
                                        <span style="font-size: 0.75rem; color: var(--text-muted);"> · {{ $att['mime'] }} · {{ number_format($att['byte_size'] / 1024, 1) }} KB ciphertext</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="/pro/medical/patients/{{ $patient->id }}/entries/{{ $entry['model']->id }}/attachments"
                          method="POST"
                          enctype="multipart/form-data"
                          style="margin-top: 0.85rem; padding-top: 0.75rem; border-top: 1px solid var(--border-light);">
                        @csrf
                        <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.35rem;">Add encrypted file (JPEG, PNG, WebP, PDF · max 10 MB)</label>
                        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap; align-items: center;">
                            <input type="file" name="attachment" accept=".jpg,.jpeg,.png,.webp,.pdf,image/jpeg,image/png,image/webp,application/pdf" required
                                   style="font-size: 0.85rem;">
                            <button type="submit" style="background: var(--primary-navy); color: white; border: none; padding: 0.45rem 0.85rem; border-radius: var(--radius-md); font-weight: 700; cursor: pointer; font-size: 0.8rem;">
                                Encrypt &amp; attach
                            </button>
                        </div>
                    </form>
                </div>
            @endforeach
        </div>
    @endif
@endsection
