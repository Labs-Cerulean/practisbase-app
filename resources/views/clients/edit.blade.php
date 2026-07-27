@extends('layouts.app')

@section('page_title', 'Edit client')

@section('content')
    <div style="max-width: 600px; margin: 0 auto; background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 2rem; box-shadow: var(--shadow-sm);">
        
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
            <h2 style="color: var(--primary-navy); margin: 0;">Edit Client</h2>
            <a href="/clients/{{ $client->id }}" style="color: var(--text-muted); text-decoration: none; font-size: 0.9rem; font-weight: 500;">Cancel</a>
        </div>

        <form action="/clients/{{ $client->id }}" method="POST" id="clientForm">
            @csrf
            @method('PUT') 

            <div style="display: flex; gap: 1rem; margin-bottom: 2rem; background: #f8fafc; padding: 0.5rem; border-radius: var(--radius-md); border: 1px solid var(--border-light);">
                <label style="flex: 1; text-align: center; padding: 0.75rem; border-radius: 6px; cursor: pointer; font-weight: 600; transition: 0.2s;" id="lbl_individual" class="type-toggle {{ $client->type === 'individual' ? 'active-toggle' : '' }}">
                    <input type="radio" name="type" value="individual" {{ $client->type === 'individual' ? 'checked' : '' }} onchange="toggleForm('individual')" style="display: none;">
                    👤 Individual
                </label>
                <label style="flex: 1; text-align: center; padding: 0.75rem; border-radius: 6px; cursor: pointer; font-weight: 600; transition: 0.2s;" id="lbl_company" class="type-toggle {{ $client->type === 'company' ? 'active-toggle' : '' }}">
                    <input type="radio" name="type" value="company" {{ $client->type === 'company' ? 'checked' : '' }} onchange="toggleForm('company')" style="display: none;">
                    🏢 Company
                </label>
            </div>

            <div class="form-group" style="margin-bottom: 1.25rem;">
                <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; font-size: 0.9rem;" id="nameLabel">Full Name</label>
                <input type="text" name="name" value="{{ $client->name }}" required style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.25rem;">
                <div>
                    <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; font-size: 0.9rem;">Phone (Mobile)</label>
                    <input type="tel" name="phone" value="{{ $client->phone }}" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                </div>
                <div>
                    <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; font-size: 0.9rem;">Email</label>
                    <input type="email" name="email" value="{{ $client->email }}" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                </div>
            </div>

            <div class="form-group" style="margin-bottom: 2rem;">
                <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; font-size: 0.9rem;">Billing Address</label>
                <textarea name="billing_address" rows="2" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); font-family: inherit; resize: vertical;">{{ $client->billing_address }}</textarea>
            </div>

            <hr style="border: none; border-top: 1px solid var(--border-light); margin-bottom: 2rem;">

            <div id="companyFields" style="display: none;">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.25rem;">
                    <div>
                        <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; font-size: 0.9rem;">VAT Number</label>
                        <input type="text" name="vat_number" value="{{ $client->profile_data['vat_number'] ?? '' }}" placeholder="MT..." style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                    </div>
                    <div>
                        <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; font-size: 0.9rem;">Reg Number</label>
                        <input type="text" name="registration_number" value="{{ $client->profile_data['registration_number'] ?? '' }}" placeholder="C-..." style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                    </div>
                </div>
                <div class="form-group" style="margin-bottom: 1.25rem;">
                    <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; font-size: 0.9rem;">Primary Contact Person</label>
                    <input type="text" name="contact_person" value="{{ $client->profile_data['contact_person'] ?? '' }}" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                </div>
            </div>

            <div id="individualFields">
                <div class="form-group" style="margin-bottom: 1.25rem;">
                    <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; font-size: 0.9rem;">ID Card Number</label>
                    <input type="text" name="id_card_number" value="{{ $client->profile_data['id_card_number'] ?? '' }}" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                </div>
            </div>

            <div style="position: sticky; bottom: 1rem; margin-top: 2rem;">
                <button type="submit" style="width: 100%; padding: 1rem; background: var(--primary-cerulean); color: white; border: none; border-radius: var(--radius-md); font-weight: 700; font-size: 1.05rem; cursor: pointer; box-shadow: 0 4px 12px rgba(2, 132, 199, 0.3);">
                    Update Client Profile
                </button>
            </div>
        </form>
    </div>

    <style>
        .active-toggle { background: white; box-shadow: var(--shadow-sm); border: 1px solid var(--border-light); color: var(--primary-cerulean); }
        .type-toggle:not(.active-toggle) { color: var(--text-muted); }
    </style>
    <script>
        function toggleForm(type) {
            // Update Toggle Visuals
            document.getElementById('lbl_individual').classList.remove('active-toggle');
            document.getElementById('lbl_company').classList.remove('active-toggle');
            document.getElementById('lbl_' + type).classList.add('active-toggle');

            // Swap Label
            document.getElementById('nameLabel').innerText = type === 'company' ? 'Company Name' : 'Full Name';

            // Swap Fields
            if (type === 'company') {
                document.getElementById('companyFields').style.display = 'block';
                document.getElementById('individualFields').style.display = 'none';
            } else {
                document.getElementById('companyFields').style.display = 'none';
                document.getElementById('individualFields').style.display = 'block';
            }
        }

        // Run immediately on page load to configure the initial layout
        window.onload = function() {
            toggleForm('{{ $client->type }}');
        };
    </script>
@endsection