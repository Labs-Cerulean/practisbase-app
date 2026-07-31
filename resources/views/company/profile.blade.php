@extends('layouts.app')

@section('page_title', 'Company profile')

@section('content')
    <div style="max-width: 720px; margin: 0 auto;">
        <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 1.25rem;">
            <div>
                <h1 style="font-size: 1.4rem; color: var(--primary-navy); margin: 0 0 0.25rem;">Company profile</h1>
                <p style="margin: 0; color: var(--text-muted); font-size: 0.9rem;">{{ $periodLabel }}</p>
            </div>
            <a href="/company" style="color: var(--primary-cerulean); font-weight: 600; font-size: 0.85rem; text-decoration: none;">← Desk</a>
        </div>

        <div style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.5rem; box-shadow: var(--shadow-sm); margin-bottom: 1.25rem;">
            <div style="font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 0.75rem;">Registry (fixed)</div>
            <div style="display: grid; gap: 0.65rem; font-size: 0.95rem; color: var(--primary-navy);">
                <div><strong>{{ $profile->legal_name }}</strong></div>
                <div>{{ $profile->registration_number }}</div>
                <div style="color: var(--text-muted); line-height: 1.4;">{{ $profile->registered_office }}</div>
                <div>Share capital: €{{ number_format((float) $profile->share_capital_eur, 2) }}
                    @if($profile->shareCapitalReceived())
                        · received {{ $profile->share_capital_received_at->format('d M Y') }}
                    @else
                        · pending at BOV
                    @endif
                </div>
                <div>VAT status: Article 10 (18%)</div>
            </div>
        </div>

        <div style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.5rem; box-shadow: var(--shadow-sm); margin-bottom: 1.25rem;">
            <div style="font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 0.75rem;">Company logo</div>
            <p style="margin: 0 0 1rem; font-size: 0.85rem; color: var(--text-muted); line-height: 1.45;">
                Shown on tax invoices, RFPs, and credit note PDFs. Also used on future company reports.
            </p>
            @if($profile->logoDataUri())
                <div style="margin-bottom: 1rem; padding: 1rem; background: #f8fafc; border: 1px solid var(--border-light); border-radius: var(--radius-md); display: inline-block;">
                    <img src="{{ $profile->logoDataUri() }}" alt="Company logo" style="max-height: 80px; max-width: 220px; display: block;">
                </div>
            @else
                <div style="margin-bottom: 1rem; font-size: 0.85rem; color: var(--text-muted);">No logo uploaded yet.</div>
            @endif

            <form method="POST" action="/company/profile/logo" enctype="multipart/form-data" style="display: flex; flex-wrap: wrap; gap: 0.75rem; align-items: end;">
                @csrf
                <div style="flex: 1; min-width: 220px;">
                    <label style="display: block; font-weight: 600; margin-bottom: 0.4rem; font-size: 0.9rem;">Upload image</label>
                    <input type="file" name="logo" accept=".jpg,.jpeg,.png,.webp,image/*" style="width: 100%; padding: 0.4rem 0;">
                    @error('logo')<div style="color: #b91c1c; font-size: 0.8rem; margin-top: 0.35rem;">{{ $message }}</div>@enderror
                    <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.3rem;">JPG, PNG or WebP · max 2 MB</div>
                </div>
                <button type="submit" style="background: var(--primary-cerulean); color: white; border: none; padding: 0.65rem 1.1rem; border-radius: var(--radius-md); font-weight: 700; cursor: pointer;">Save logo</button>
            </form>

            @if($profile->logo_path)
                <form method="POST" action="/company/profile/logo" style="margin-top: 0.85rem;" onsubmit="return confirm('Remove the company logo from PDFs?');">
                    @csrf
                    <input type="hidden" name="remove_logo" value="1">
                    <button type="submit" style="background: white; color: #b91c1c; border: 1px solid #fecaca; padding: 0.5rem 0.9rem; border-radius: var(--radius-md); font-weight: 600; font-size: 0.85rem; cursor: pointer;">Remove logo</button>
                </form>
            @endif
        </div>

        <form method="POST" action="/company/profile" style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.5rem; box-shadow: var(--shadow-sm);">
            @csrf
            @method('PUT')

            <div style="margin-bottom: 1.25rem;">
                <label style="display: block; font-weight: 600; margin-bottom: 0.4rem; font-size: 0.9rem;">VAT number</label>
                <input type="text" name="vat_number" value="{{ old('vat_number', $profile->vat_number) }}" placeholder="MT…" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                @error('vat_number')<div style="color: #b91c1c; font-size: 0.8rem; margin-top: 0.35rem;">{{ $message }}</div>@enderror
            </div>

            <div style="margin-bottom: 1.25rem;">
                <label style="display: block; font-weight: 600; margin-bottom: 0.4rem; font-size: 0.9rem;">VAT filing frequency</label>
                <select name="vat_filing_frequency" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); background: white;">
                    <option value="quarterly" @selected(old('vat_filing_frequency', $profile->vat_filing_frequency) === 'quarterly')>Quarterly</option>
                    <option value="monthly" @selected(old('vat_filing_frequency', $profile->vat_filing_frequency) === 'monthly')>Monthly</option>
                </select>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.25rem;">
                <div>
                    <label style="display: block; font-weight: 600; margin-bottom: 0.4rem; font-size: 0.9rem;">Bank</label>
                    <input type="text" name="bank_name" value="{{ old('bank_name', $profile->bank_name) }}" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                </div>
                <div>
                    <label style="display: block; font-weight: 600; margin-bottom: 0.4rem; font-size: 0.9rem;">IBAN</label>
                    <input type="text" name="bank_iban" value="{{ old('bank_iban', $profile->bank_iban) }}" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                </div>
            </div>

            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; font-weight: 600; margin-bottom: 0.4rem; font-size: 0.9rem;">Payment instructions (on PDFs)</label>
                <textarea name="payment_instructions" rows="4" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); resize: vertical;">{{ old('payment_instructions', $profile->payment_instructions) }}</textarea>
            </div>

            <button type="submit" style="background: var(--primary-cerulean); color: white; border: none; padding: 0.7rem 1.25rem; border-radius: var(--radius-md); font-weight: 700; cursor: pointer;">Save profile</button>
        </form>
    </div>
@endsection
