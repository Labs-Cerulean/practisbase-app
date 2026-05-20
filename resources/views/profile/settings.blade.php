@extends('layouts.app')

@section('page_title', 'Account Settings')

@section('content')
    <div style="max-width: 650px; margin: 0 auto;">
        
        <h1 style="font-size: 1.75rem; color: var(--primary-navy); margin-bottom: 1.5rem;">Account Settings</h1>

        @if(session('success'))
            <div style="background: #d1fae5; border: 1px solid #10b981; color: #047857; padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1.5rem; font-weight: 500;">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div style="background: #fef2f2; border: 1px solid #f87171; color: #b91c1c; padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1.5rem; font-size: 0.85rem;">
                <ul style="margin: 0; padding-left: 1.5rem;">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="/settings/profile" method="POST">
            @csrf
            @method('PUT')

            <div style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 2rem; box-shadow: var(--shadow-sm); margin-bottom: 2rem;">
                <h3 style="color: var(--primary-navy); margin-top: 0; margin-bottom: 1.5rem; border-bottom: 1px solid var(--border-light); padding-bottom: 0.5rem;">Personal Details</h3>
                
                <div class="form-group" style="margin-bottom: 1.25rem;">
                    <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; font-size: 0.9rem;">Full Name / Practice Name</label>
                    <input type="text" name="name" value="{{ $user->name }}" required style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                </div>

                <div class="form-group" style="margin-bottom: 1.25rem;">
                    <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; font-size: 0.9rem;">Email Address</label>
                    <input type="email" name="email" value="{{ $user->email }}" required style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.25rem;">
                    <div>
                        <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; font-size: 0.9rem;">Profession</label>
                        <input type="text" name="profession" id="profInput" value="{{ $user->profession }}" list="professionSuggestions" required onchange="handleProfChange()" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                        <datalist id="professionSuggestions">
                            @if(isset($customProfessions))
                                @foreach($customProfessions as $prof)
                                    <option value="{{ $prof }}">
                                @endforeach
                            @endif
                        </datalist>
                    </div>
                    <div>
                        <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; font-size: 0.9rem;">Warrant Number (Optional)</label>
                        <input type="text" name="warrant_number" value="{{ $user->warrant_number }}" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                    </div>
                </div>
            </div>

            <div style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 2rem; box-shadow: var(--shadow-sm); margin-bottom: 2rem;">
                <h3 style="color: var(--primary-navy); margin-top: 0; margin-bottom: 1.5rem; border-bottom: 1px solid var(--border-light); padding-bottom: 0.5rem;">Fiscal & Compliance Setup</h3>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.25rem;">
                    <div>
                        <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; font-size: 0.9rem;">Employment Type</label>
                        <select name="employment_type" id="empType" onchange="handleEmpChange()" required style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); background: white;">
                            <option value="full_time" {{ $user->employment_type === 'full_time' ? 'selected' : '' }}>Full-Time Self-Employed</option>
                            <option value="part_time" {{ $user->employment_type === 'part_time' ? 'selected' : '' }}>Part-Time Self-Employed</option>
                        </select>
                    </div>
                    
                    <div id="dobSettingsGroup" style="display: {{ $user->employment_type === 'full_time' ? 'block' : 'none' }};">
                        <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; font-size: 0.9rem;">Date of Birth (SSC Caps)</label>
                        <input type="date" name="date_of_birth" id="dobSettingsInput" value="{{ $user->date_of_birth ? $user->date_of_birth->format('Y-m-d') : '' }}" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.25rem;">
                    <div>
                        <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; font-size: 0.9rem;">VAT Registration Status</label>
                        <select name="vat_status" id="vatSettingsStatus" onchange="handleVatChange()" required style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); background: white;">
                            <option value="article_11" {{ $user->vat_status === 'article_11' ? 'selected' : '' }}>Article 11 (Exempt - Under €35k)</option>
                            <option value="article_10" {{ $user->vat_status === 'article_10' ? 'selected' : '' }}>Article 10 (Standard - Over €35k)</option>
                            <option value="exempt" {{ $user->vat_status === 'exempt' ? 'selected' : '' }}>VAT Exempt (Fifth Schedule)</option>
                        </select>
                    </div>
                    
                    <div id="vatSettingsGroup" style="display: {{ in_array($user->vat_status, ['article_10', 'article_11']) ? 'block' : 'none' }};">
                        <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; font-size: 0.9rem;">VAT Number</label>
                        <input type="text" name="vat_number" id="vatSettingsInput" value="{{ $user->vat_number }}" placeholder="MT..." style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                    </div>
                </div>

                <div id="medicalVatAlert" style="display: {{ $user->profession === 'Medical Professional' ? 'block' : 'none' }}; margin-top: 1rem; padding: 1rem; background: #fffbeb; border: 1px solid #fef3c7; border-left: 4px solid #f59e0b; border-radius: var(--radius-md); color: #92400e; font-size: 0.85rem; line-height: 1.5;">
                    <strong>⚠️ Note on Medical Exemptions (Fifth Schedule):</strong><br>
                    Under Maltese VAT Law, the medical exemption applies <strong>strictly to therapeutic care</strong> provided by professionals warranted under the Health Care Professions Act. Non-therapeutic services (e.g., purely cosmetic procedures, corporate consultancy, medico-legal reports) may be subject to standard 18% VAT. If you provide taxable services, you must register under Article 10 or 11.
                </div>

                <button type="submit" style="margin-top: 1.5rem; width: 100%; background: var(--primary-cerulean); color: white; border: none; padding: 1rem; border-radius: var(--radius-md); font-weight: 700; font-size: 1.05rem; cursor: pointer; box-shadow: 0 4px 12px rgba(2, 132, 199, 0.3);">
                    Save All Profile Changes
                </button>
            </div>
        </form>

        <div style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 2rem; box-shadow: var(--shadow-sm); margin-bottom: 2rem;">
            <h3 style="color: var(--primary-navy); margin-top: 0; margin-bottom: 1.5rem; border-bottom: 1px solid var(--border-light); padding-bottom: 0.5rem;">Security</h3>
            
            <form action="/settings/password" method="POST">
                @csrf
                @method('PUT')

                <div class="form-group" style="margin-bottom: 1.25rem;">
                    <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; font-size: 0.9rem;">Current Password</label>
                    <input type="password" name="current_password" required style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.25rem;">
                    <div>
                        <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; font-size: 0.9rem;">New Password</label>
                        <input type="password" name="password" required style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                    </div>
                    <div>
                        <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; font-size: 0.9rem;">Confirm New Password</label>
                        <input type="password" name="password_confirmation" required style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                    </div>
                </div>

                <button type="submit" style="background: var(--primary-navy); color: white; border: none; padding: 0.75rem 1.5rem; border-radius: var(--radius-md); font-weight: 600; cursor: pointer;">
                    Update Password
                </button>
            </form>
        </div>

    </div>

    <script>
        // Handle Employment toggle logic
        function handleEmpChange() {
            const empType = document.getElementById('empType').value;
            const dobGroup = document.getElementById('dobSettingsGroup');
            const dobInput = document.getElementById('dobSettingsInput');

            if (empType === 'full_time') {
                dobGroup.style.display = 'block';
                dobInput.required = true;
            } else {
                dobGroup.style.display = 'none';
                dobInput.required = false;
            }
        }

        // Handle VAT toggle logic
        function handleVatChange() {
            const vatStatus = document.getElementById('vatSettingsStatus');
            const vatGroup = document.getElementById('vatSettingsGroup');
            const vatInput = document.getElementById('vatSettingsInput');

            if (vatStatus.value === 'article_10' || vatStatus.value === 'article_11') {
                vatGroup.style.display = 'block';
                vatInput.required = true;
            } else {
                vatGroup.style.display = 'none';
                vatInput.required = false;
            }
        }

        // Handle Profession change logic (Show Strict Medical Warning but DO NOT lock the dropdown)
        function handleProfChange() {
            const prof = document.getElementById('profInput').value;
            const alert = document.getElementById('medicalVatAlert');
            
            if (prof === 'Medical Professional') {
                alert.style.display = 'block';
            } else {
                alert.style.display = 'none';
            }
        }

        // Run once on load to ensure initial state is correct
        document.addEventListener("DOMContentLoaded", function() {
            handleProfChange();
        });
    </script>
@endsection