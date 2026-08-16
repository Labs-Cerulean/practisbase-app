@extends('layouts.app')

@section('page_title', 'Settings')

@section('content')
    <div style="max-width: 650px; margin: 0 auto;">

        <h1 style="font-size: 1.5rem; color: var(--primary-navy); margin-bottom: 0.75rem;">Settings</h1>

        <div class="settings-tabs" role="tablist" aria-label="Settings sections" style="display: flex; flex-wrap: wrap; gap: 0.15rem; margin-bottom: 1.25rem; border-bottom: 1px solid var(--border-light);">
            @php
                $settingsTabs = [
                    'plan' => 'Plan',
                    'practice' => 'Practice',
                    'tax' => 'Tax',
                    'payments' => 'Payments',
                    'branding' => 'Branding',
                    'security' => 'Security',
                    'backup' => 'Backup',
                    'app' => 'App',
                ];
            @endphp
            @foreach($settingsTabs as $tabId => $tabLabel)
                <button type="button" class="settings-tab-btn" role="tab" data-tab="{{ $tabId }}" aria-controls="tab-{{ $tabId }}" style="padding: 0.65rem 0.85rem; background: none; border: none; border-bottom: 2px solid transparent; margin-bottom: -1px; font-weight: 700; font-size: 0.8rem; color: var(--text-muted); cursor: pointer;">{{ $tabLabel }}</button>
            @endforeach
        </div>

        @if(session('success'))
            <div style="background: #d1fae5; border: 1px solid #10b981; color: #047857; padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1.5rem; font-weight: 500;">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div style="background: #fef2f2; border: 1px solid #f87171; color: #b91c1c; padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1.5rem; font-weight: 500;">
                {{ session('error') }}
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

        {{-- Plan --}}
        <div id="tab-plan" class="settings-tab-panel" role="tabpanel" style="display: none;">
            <div id="plan" style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 2rem; box-shadow: var(--shadow-sm);">
                <h3 style="color: var(--primary-navy); margin-top: 0; margin-bottom: 0.5rem; border-bottom: 1px solid var(--border-light); padding-bottom: 0.5rem;">Plan</h3>
                <p style="color: var(--text-muted); font-size: 0.85rem; margin-top: 0.75rem; margin-bottom: 1.25rem;">
                    Accounts (Tax &amp; VAT) and practice tools are separate. Practice keeps Free invoicing ({{ $user->freeClientCap() }} lifetime clients).
                </p>

                <div style="display: flex; flex-wrap: wrap; gap: 0.75rem; align-items: center; margin-bottom: 1.25rem;">
                    <span style="display: inline-block; background: rgba(2, 132, 199, 0.1); color: var(--primary-cerulean); font-weight: 700; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px; padding: 0.35rem 0.75rem; border-radius: 20px; border: 1px solid rgba(2, 132, 199, 0.25);">
                        {{ \App\Support\TierPolicy::label($user->tier ?: 'free') }}
                    </span>
                    <span style="font-size: 0.9rem; color: var(--text-main); font-weight: 600;">
                        {{ $user->clientUsageLabel() }}
                    </span>
                </div>

                @unless($user->hasStandardFinancial())
                    <div style="background: #fffbeb; border: 1px solid #fde68a; border-left: 4px solid #f59e0b; border-radius: var(--radius-md); padding: 0.85rem 1rem; margin-bottom: 1.25rem; color: #92400e; font-size: 0.85rem; line-height: 1.45;">
                        @if($user->isPracticeOnly())
                            Practice includes Free invoicing only (max {{ $user->freeClientCap() }} lifetime clients). Upgrade to <strong>Full Pro</strong> for unlimited clients and <strong>Tax &amp; VAT</strong>.
                        @else
                            Free includes Overview + invoices only (max {{ $user->freeClientCap() }} lifetime clients). Upgrade to Standard for accounts, Practice for profession tools, or Full Pro for both.
                        @endif
                    </div>
                @endunless

                <div style="background: #eff6ff; color: #1e3a8a; text-align: left; padding: 0.75rem 1rem; border-radius: var(--radius-md); font-size: 0.8rem; font-weight: 600; margin-bottom: 1rem; border: 1px solid #bfdbfe;">
                    Change plan below. No card charge during Founding access.
                </div>

                <form action="/settings/plan" method="POST" id="plan-change-form">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="confirm_downgrade" id="confirm_downgrade_field" value="">
                    <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; font-size: 0.9rem;">Change plan</label>
                    <div style="display: flex; gap: 0.75rem; flex-wrap: wrap; align-items: center;">
                        <select name="tier" id="plan-tier-select" required style="flex: 1; min-width: 180px; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); background: white;">
                            @foreach(($allowedTiers ?? ['free', 'standard']) as $tierOption)
                                <option value="{{ $tierOption }}" {{ ($user->tier ?: 'free') === $tierOption ? 'selected' : '' }}>
                                    {{ \App\Support\TierPolicy::label($tierOption) }}
                                    @if(\App\Support\TierPolicy::priceLabel($tierOption) !== '')
                                        ({{ \App\Support\TierPolicy::priceLabel($tierOption) }}/mo)
                                    @endif
                                </option>
                            @endforeach
                        </select>
                        <button type="submit" id="plan-submit-btn" style="padding: 0.75rem 1.25rem; background: var(--primary-cerulean); color: white; border: none; border-radius: var(--radius-md); font-weight: 700; cursor: pointer;">
                            Update plan
                        </button>
                    </div>
                    <div id="plan-change-preview" style="display: none; margin-top: 0.85rem; padding: 0.85rem 1rem; border-radius: var(--radius-md); font-size: 0.85rem; line-height: 1.45;"></div>
                    <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.5rem;">
                        Practice and Pro packages are limited to your registered profession.
                    </div>
                </form>
            </div>
        </div>

        <div id="downgrade-modal" class="pb-modal" hidden>
            <div class="pb-modal-backdrop" data-close-downgrade></div>
            <div class="pb-modal-panel" role="dialog" aria-modal="true" aria-labelledby="downgrade-modal-title">
                <h2 id="downgrade-modal-title" style="margin: 0 0 0.75rem; color: var(--primary-navy); font-size: 1.15rem;">Confirm downgrade</h2>
                <p style="margin: 0 0 0.85rem; color: var(--text-muted); font-size: 0.9rem; line-height: 1.45;">
                    Are you sure? Access changes apply immediately. Existing data is kept, but locked or hidden tools will not be available on the lower plan.
                </p>
                <ul id="downgrade-modal-list" style="margin: 0 0 1rem; padding-left: 1.2rem; color: #991b1b; font-size: 0.85rem; line-height: 1.45;"></ul>
                <label style="display: flex; gap: 0.55rem; align-items: flex-start; margin-bottom: 0.85rem; font-size: 0.85rem; line-height: 1.4; cursor: pointer;">
                    <input type="checkbox" id="downgrade-understand" style="margin-top: 0.15rem;">
                    <span>I understand I may lose access to Pro/Standard tools and that medical vault data stays locked without re-upgrade + recovery code.</span>
                </label>
                <label style="display: block; font-weight: 600; margin-bottom: 0.35rem; font-size: 0.85rem;">Type DOWNGRADE to confirm</label>
                <input type="text" id="downgrade-typed" name="confirm_downgrade_typed" form="plan-change-form" autocomplete="off" placeholder="DOWNGRADE" style="width: 100%; padding: 0.7rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); margin-bottom: 1rem; font-family: ui-monospace, monospace;">
                <div style="display: flex; gap: 0.5rem; flex-wrap: wrap; justify-content: flex-end;">
                    <button type="button" data-close-downgrade style="padding: 0.65rem 1rem; background: white; border: 1px solid var(--border-light); border-radius: var(--radius-md); font-weight: 600; cursor: pointer;">Cancel</button>
                    <button type="button" id="downgrade-confirm-btn" style="padding: 0.65rem 1rem; background: #b91c1c; color: white; border: none; border-radius: var(--radius-md); font-weight: 700; cursor: pointer;">Downgrade plan</button>
                </div>
            </div>
        </div>

        {{-- Practice --}}
        <div id="tab-practice" class="settings-tab-panel" role="tabpanel" style="display: none;">
            <form action="/settings/profile" method="POST">
                @csrf
                @method('PUT')
                <input type="hidden" name="settings_section" value="practice">

                <div id="practice" style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 2rem; box-shadow: var(--shadow-sm);">
                    <h3 style="color: var(--primary-navy); margin-top: 0; margin-bottom: 1.5rem; border-bottom: 1px solid var(--border-light); padding-bottom: 0.5rem;">Practice</h3>

                    <div class="form-group" style="margin-bottom: 1.25rem;">
                        <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; font-size: 0.9rem;">Full Name / Practice Name <span style="color: #b91c1c;">*</span></label>
                        <input type="text" name="name" value="{{ old('name', $user->name) }}" required style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                    </div>

                    <div class="form-group" style="margin-bottom: 1.25rem;">
                        <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; font-size: 0.9rem;">Postnominals</label>
                        <input type="text" name="postnominals" value="{{ old('postnominals', $user->postnominals) }}" placeholder="e.g. MD, MRCS, B.Sc." maxlength="255" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                    </div>

                    <div class="form-group" style="margin-bottom: 1.25rem;">
                        <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; font-size: 0.9rem;">Email Address <span style="color: #b91c1c;">*</span></label>
                        <input type="email" name="email" value="{{ old('email', $user->email) }}" required style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.25rem;">
                        <div>
                            <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; font-size: 0.9rem;">Profession</label>
                            <input type="text" id="profInput" value="{{ $user->profession }}" disabled style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); background: #f1f5f9; color: #64748b; cursor: not-allowed; font-weight: 500;">
                            <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.3rem;">Contact support to change.</div>
                        </div>
                        <div>
                            <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; font-size: 0.9rem;">Warrant / Council</label>
                            <select name="warrant_type" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); background: white;">
                                <option value="">Prefer not to say</option>
                                @php $warrantType = old('warrant_type', $user->warrant_type); @endphp
                                @if($user->profession === 'Medical Professional')
                                    <option value="Medical Council Malta" {{ $warrantType === 'Medical Council Malta' ? 'selected' : '' }}>Medical Council Malta</option>
                                @elseif($user->profession === 'Architect / Perit')
                                    <option value="Kamra tal-Periti" {{ $warrantType === 'Kamra tal-Periti' ? 'selected' : '' }}>Kamra tal-Periti</option>
                                @elseif($user->profession === 'Engineer')
                                    <option value="Engineering Board" {{ $warrantType === 'Engineering Board' ? 'selected' : '' }}>Engineering Board</option>
                                @else
                                    <option value="Medical Council Malta" {{ $warrantType === 'Medical Council Malta' ? 'selected' : '' }}>Medical Council Malta</option>
                                    <option value="Kamra tal-Periti" {{ $warrantType === 'Kamra tal-Periti' ? 'selected' : '' }}>Kamra tal-Periti</option>
                                    <option value="Engineering Board" {{ $warrantType === 'Engineering Board' ? 'selected' : '' }}>Engineering Board</option>
                                @endif
                                @if($warrantType && ! in_array($warrantType, ['Medical Council Malta', 'Kamra tal-Periti', 'Engineering Board', ''], true))
                                    <option value="{{ $warrantType }}" selected>{{ $warrantType }} (international)</option>
                                @endif
                            </select>
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom: 1.25rem;">
                        <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; font-size: 0.9rem;">Medical Reg / Warrant Number</label>
                        <input type="text" name="warrant_number" value="{{ old('warrant_number', $user->warrant_number) }}" placeholder="e.g. 3264" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                        <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.3rem;">Used on clinical PDFs and new Document Stamper stamps.</div>
                    </div>

                    @if($user->canAccessProPackage('med'))
                        <div class="form-group" style="margin-bottom: 1.25rem;">
                            <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; font-size: 0.9rem;">Default clinical note template</label>
                            <select name="clinical_note_template" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); background: white;">
                                @foreach(\App\Support\ClinicalNoteTemplates::optionsForUser($user) as $key => $label)
                                    <option value="{{ $key }}" {{ old('clinical_note_template', $user->clinical_note_template ?? 'general') === $key ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                            <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.3rem;">
                                Used for patient notes.
                                <a href="/pro/medical/templates" style="color: var(--primary-cerulean); font-weight: 600;">Manage templates</a>
                            </div>
                        </div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.25rem;">
                            <div>
                                <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; font-size: 0.9rem;">Clinic phone</label>
                                <input type="text" name="clinic_phone" value="{{ old('clinic_phone', $user->clinic_phone) }}" placeholder="e.g. +356 21XX XXXX" maxlength="64" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                            </div>
                            <div>
                                <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; font-size: 0.9rem;">Clinic address</label>
                                <input type="text" name="clinic_address" value="{{ old('clinic_address', $user->clinic_address) }}" placeholder="Clinic / consulting rooms" maxlength="500" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                            </div>
                        </div>
                        <div style="font-size: 0.75rem; color: var(--text-muted); margin: -0.5rem 0 1.25rem;">Shown on prescription / referral / certificate letterheads.</div>
                    @endif

                    <button type="submit" style="width: 100%; background: var(--primary-cerulean); color: white; border: none; padding: 1rem; border-radius: var(--radius-md); font-weight: 700; font-size: 1.05rem; cursor: pointer; box-shadow: 0 4px 12px rgba(2, 132, 199, 0.3);">
                        Save practice
                    </button>
                </div>
            </form>
        </div>

        {{-- Tax --}}
        <div id="tab-tax" class="settings-tab-panel" role="tabpanel" style="display: none;">
            <form action="/settings/profile" method="POST">
                @csrf
                @method('PUT')
                <input type="hidden" name="settings_section" value="tax">

                <div id="tax-setup" style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 2rem; box-shadow: var(--shadow-sm);">
                    <h3 style="color: var(--primary-navy); margin-top: 0; margin-bottom: 0.35rem; border-bottom: 1px solid var(--border-light); padding-bottom: 0.5rem;">Tax</h3>
                    <p style="color: var(--text-muted); font-size: 0.85rem; margin: 0 0 1.5rem; line-height: 1.45;">Mapped to Maltese sole-trader rules for your Tax &amp; VAT report.</p>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.25rem;">
                        <div>
                            <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; font-size: 0.9rem;">How do you work? <span style="color: #b91c1c;">*</span></label>
                            <select name="employment_type" id="empType" onchange="handleEmpChange()" required style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); background: white;">
                                <option value="full_time" {{ old('employment_type', $user->employment_type) === 'full_time' ? 'selected' : '' }}>This practice is my main work (full-time self-employed)</option>
                                <option value="part_time" {{ old('employment_type', $user->employment_type) === 'part_time' ? 'selected' : '' }}>I also have a main job (part-time self-employed)</option>
                            </select>
                            <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.35rem;">Part-time uses TA22 where it applies.</div>
                        </div>

                        @php
                            $empTypeVal = old('employment_type', $user->employment_type);
                            $showDob = $empTypeVal === 'full_time' || ($dobLocked ?? false);
                        @endphp
                        <div id="dobSettingsGroup" style="display: {{ $showDob ? 'block' : 'none' }};">
                            <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; font-size: 0.9rem;">Date of Birth (SSC) <span style="color: #b91c1c;">*</span></label>
                            <input type="date" name="date_of_birth" id="dobSettingsInput" value="{{ old('date_of_birth', $user->date_of_birth ? $user->date_of_birth->format('Y-m-d') : '') }}"
                                   {{ ($dobLocked ?? false) ? 'disabled' : '' }}
                                   max="{{ now()->subYears(18)->format('Y-m-d') }}"
                                   style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); {{ ($dobLocked ?? false) ? 'background:#f1f5f9;color:#64748b;' : '' }}">
                            @if($dobLocked ?? false)
                                <div style="font-size: 0.75rem; color: #92400e; margin-top: 0.35rem;">Locked after a fiscal year was closed.</div>
                            @else
                                <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.3rem;">Used for Class 2 SSC age bands. Must be 18+.</div>
                            @endif
                        </div>
                    </div>

                    @if($hasClosedFiscalYears ?? false)
                        <div style="margin-bottom: 1.25rem; padding: 0.85rem 1rem; background: #eff6ff; border-left: 4px solid #2563eb; border-radius: var(--radius-md); color: #1e3a8a; font-size: 0.85rem; line-height: 1.45;">
                            Closed years stay frozen. Changes only affect open years.
                        </div>
                    @endif

                    <div id="regimeEffectiveWrap" style="display: none; margin-bottom: 1.25rem; padding: 1rem; background: #fffbeb; border: 1px solid #fde68a; border-radius: var(--radius-md);">
                        <label style="display: block; font-weight: 700; margin-bottom: 0.35rem; font-size: 0.9rem; color: #92400e;">Apply this tax setup from</label>
                        <p style="margin: 0 0 0.65rem; font-size: 0.8rem; color: #78350f; line-height: 1.45;">
                            Documents before this date keep the previous setup. Defaults to today.
                        </p>
                        <input type="date" name="regime_effective_from" id="regimeEffectiveFrom" value="{{ old('regime_effective_from', date('Y-m-d')) }}" max="{{ date('Y-m-d') }}" style="width: 100%; max-width: 16rem; padding: 0.75rem; border: 1px solid #f59e0b; border-radius: var(--radius-md); background: white;">
                        @error('regime_effective_from')
                            <div style="color: #991b1b; font-size: 0.8rem; margin-top: 0.4rem;">{{ $message }}</div>
                        @enderror
                    </div>

                    @if(!empty($regimeSegments) && count($regimeSegments) > 0)
                        <div style="margin-bottom: 1.25rem; padding: 0.85rem 1rem; background: #f8fafc; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                            <div style="font-size: 0.8rem; font-weight: 700; color: var(--primary-navy); margin-bottom: 0.45rem;">Tax setup history</div>
                            <ul style="margin: 0; padding-left: 1.1rem; font-size: 0.8rem; color: var(--text-muted); line-height: 1.5;">
                                @foreach($regimeSegments as $seg)
                                    <li>
                                        From {{ \Illuminate\Support\Carbon::parse($seg['effective_from'])->format('d M Y') }}:
                                        {{ $seg['employment_type'] === 'part_time' ? 'Part-time' : 'Full-time' }},
                                        {{ str_replace('_', ' ', $seg['vat_status']) }},
                                        salary €{{ number_format($seg['primary_salary'], 2) }}
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.25rem;">
                        <div>
                            <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; font-size: 0.9rem;">Do you charge VAT? <span style="color: #b91c1c;">*</span></label>
                            <select name="vat_status" id="vatSettingsStatus" onchange="handleVatChange()" required style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); background: white;">
                                <option value="article_11" {{ old('vat_status', $user->vat_status) === 'article_11' ? 'selected' : '' }}>No VAT yet — under €35k (Article 11)</option>
                                <option value="article_10" {{ old('vat_status', $user->vat_status) === 'article_10' ? 'selected' : '' }}>Yes — I charge 18% VAT (Article 10)</option>
                                <option value="exempt" {{ old('vat_status', $user->vat_status) === 'exempt' ? 'selected' : '' }}>Exempt work (e.g. therapeutic medical / Fifth Schedule)</option>
                            </select>
                        </div>

                        <div id="vatSettingsGroup" style="display: {{ in_array(old('vat_status', $user->vat_status), ['article_10', 'article_11']) ? 'block' : 'none' }};">
                            <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; font-size: 0.9rem;">VAT Number <span style="font-weight: 500; color: var(--text-muted);">(optional until needed)</span></label>
                            <input type="text" name="vat_number" id="vatSettingsInput" value="{{ old('vat_number', $user->vat_number) }}" placeholder="MT..." style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                        </div>
                    </div>

                    @if($user->missingVatNumberForArticle10Documents())
                        <div style="margin-bottom: 1.25rem; padding: 0.85rem 1rem; background: #fffbeb; border: 1px solid #fef3c7; border-left: 4px solid #f59e0b; border-radius: var(--radius-md); color: #92400e; font-size: 0.85rem; line-height: 1.45;">
                            Add a VAT number before creating an Article 10 invoice.
                        </div>
                    @endif

                    <div id="medicalVatAlert" style="display: {{ $user->profession === 'Medical Professional' ? 'block' : 'none' }}; margin-bottom: 1.25rem; font-size: 0.8rem; color: var(--text-muted); line-height: 1.45;">
                        Therapeutic care is usually Fifth Schedule exempt. Non-therapeutic work may need Article 10/11.
                    </div>

                    <h3 id="tax-details" style="color: var(--primary-navy); margin-top: 0.5rem; margin-bottom: 0.5rem; border-bottom: 1px solid var(--border-light); padding-bottom: 0.5rem;">Tax details</h3>
                    <p style="color: var(--text-muted); font-size: 0.85rem; margin-bottom: 1.25rem;">For your Tax &amp; VAT report only — never printed on invoices.</p>

                    <div style="display: grid; grid-template-columns: 1fr; gap: 1.5rem; margin-bottom: 1.5rem;">
                        <div>
                            <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; font-size: 0.9rem;">Tax Computation Status</label>
                            <select name="tax_computation" id="taxComputation" required style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); font-family: inherit; background: white;">
                                <option value="" disabled {{ !old('tax_computation', $user->tax_computation) ? 'selected' : '' }}>Select your status</option>
                                <option value="single" {{ old('tax_computation', $user->tax_computation) === 'single' ? 'selected' : '' }}>Single</option>
                                <option value="married" {{ old('tax_computation', $user->tax_computation) === 'married' ? 'selected' : '' }}>Married</option>
                                <option value="parent" {{ old('tax_computation', $user->tax_computation) === 'parent' ? 'selected' : '' }}>Parent</option>
                            </select>
                        </div>

                        <div>
                            <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; font-size: 0.9rem;">Primary Employment Salary (Gross)</label>
                            <p style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0; margin-bottom: 0.5rem;">If this is secondary income, enter your main job’s annual salary. Otherwise 0.</p>
                            <div style="display: flex; align-items: center; background: #f8fafc; border: 1px solid var(--border-light); border-radius: var(--radius-md); padding: 0 0.75rem;">
                                <span style="color: var(--text-muted); font-weight: 600;">€</span>
                                <input type="number" name="primary_salary" id="primarySalary" step="0.01" min="0" value="{{ old('primary_salary', $user->primary_salary ?? '0.00') }}" required style="width: 100%; padding: 0.75rem; border: none; background: transparent; font-family: inherit;">
                            </div>
                        </div>

                        <div style="background: #f8fafc; border: 1px solid var(--border-light); border-radius: var(--radius-md); padding: 1rem;">
                            <label style="display: flex; align-items: center; gap: 0.5rem; font-weight: 600; cursor: pointer;">
                                <input type="checkbox" name="max_ssc_paid" id="maxSscPaid" value="1" {{ (old('settings_section') === 'tax' ? (bool) old('max_ssc_paid') : (bool) $user->max_ssc_paid) ? 'checked' : '' }} style="width: 1.2rem; height: 1.2rem;">
                                I already pay max SSC at my primary job.
                            </label>
                            <p style="font-size: 0.8rem; color: var(--text-muted); margin-left: 1.7rem; margin-top: 0.25rem;">Exempts part-time self-employed income from further SSC.</p>
                        </div>

                        <div>
                            <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; font-size: 0.9rem;">Estimated Annual Allowable Expenses</label>
                            <p style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0; margin-bottom: 0.5rem;">Saved for <strong>{{ date('Y') }}</strong>. Ledger totals override when &gt; €0.</p>
                            <div style="display: flex; align-items: center; background: #f8fafc; border: 1px solid var(--border-light); border-radius: var(--radius-md); padding: 0 0.75rem;">
                                <span style="color: var(--text-muted); font-weight: 600;">€</span>
                                <input type="number" name="estimated_expenses" step="0.01" min="0" value="{{ old('estimated_expenses', ($user->estimated_expenses_by_year[(string) date('Y')] ?? null) !== null ? $user->estimated_expenses_by_year[(string) date('Y')] : ($user->estimated_expenses ?? '0.00')) }}" required style="width: 100%; padding: 0.75rem; border: none; background: transparent; font-family: inherit;">
                            </div>
                        </div>

                        <div style="background: #eff6ff; border: 1px solid #bfdbfe; border-radius: var(--radius-md); padding: 1rem;">
                            <div style="font-weight: 700; color: #1e3a8a; margin-bottom: 0.35rem; font-size: 0.9rem;">Business-use helpers</div>
                            <p style="margin: 0 0 0.75rem; font-size: 0.8rem; color: #1e40af; line-height: 1.45;">
                                Car/fuel <strong>{{ $user->car_business_use_percent !== null ? number_format((float) $user->car_business_use_percent, 0).'%' : 'not set' }}</strong>,
                                home office <strong>{{ $user->home_office_percent !== null ? number_format((float) $user->home_office_percent, 0).'%' : 'not set' }}</strong>.
                            </p>
                            <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                                <button type="button" data-open-car-helper style="padding: 0.5rem 0.85rem; background: white; border: 1px solid #93c5fd; border-radius: var(--radius-md); font-weight: 600; font-size: 0.8rem; cursor: pointer; color: #1d4ed8;">Car / fuel helper</button>
                                <button type="button" data-open-wfh-helper style="padding: 0.5rem 0.85rem; background: white; border: 1px solid #93c5fd; border-radius: var(--radius-md); font-weight: 600; font-size: 0.8rem; cursor: pointer; color: #1d4ed8;">Home office helper</button>
                                <a href="/expenses" style="padding: 0.5rem 0.85rem; font-weight: 600; font-size: 0.8rem; color: #1d4ed8;">Open expenses →</a>
                            </div>
                        </div>
                    </div>

                    <button type="submit" style="width: 100%; background: var(--primary-cerulean); color: white; border: none; padding: 1rem; border-radius: var(--radius-md); font-weight: 700; font-size: 1.05rem; cursor: pointer; box-shadow: 0 4px 12px rgba(2, 132, 199, 0.3);">
                        Save tax setup
                    </button>
                </div>
            </form>
        </div>

        {{-- Payments --}}
        <div id="tab-payments" class="settings-tab-panel" role="tabpanel" style="display: none;">
            <form action="/settings/profile" method="POST">
                @csrf
                @method('PUT')
                <input type="hidden" name="settings_section" value="payments">

                <div id="payments" style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 2rem; box-shadow: var(--shadow-sm);">
                    <h3 style="color: var(--primary-navy); margin-top: 0; margin-bottom: 0.5rem; border-bottom: 1px solid var(--border-light); padding-bottom: 0.5rem;">Payments</h3>
                    <p style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 1.5rem;">Shown on invoices when set.</p>

                    @php $pm = $user->payment_methods ?? []; @endphp

                    <div style="background: #f8fafc; border: 1px solid var(--border-light); border-radius: var(--radius-md); padding: 1rem; margin-bottom: 1rem;">
                        <label style="display: flex; align-items: center; gap: 0.5rem; font-weight: 600; cursor: pointer;">
                            <input type="checkbox" name="pm_bov" value="1" id="toggleBov" onchange="toggleSection('bovSection')" {{ (old('settings_section') === 'payments' ? (bool) old('pm_bov') : isset($pm['bov_mobile'])) ? 'checked' : '' }} style="width: 1.2rem; height: 1.2rem;">
                            BOV Mobile Pay
                        </label>
                        <div id="bovSection" style="display: {{ (old('settings_section') === 'payments' ? (bool) old('pm_bov') : isset($pm['bov_mobile'])) ? 'block' : 'none' }}; margin-top: 1rem;">
                            <input type="text" name="pm_bov_number" placeholder="Mobile Number (e.g., +356 9999 9999)" value="{{ old('pm_bov_number', $pm['bov_mobile'] ?? '') }}" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                        </div>
                    </div>

                    <div style="background: #f8fafc; border: 1px solid var(--border-light); border-radius: var(--radius-md); padding: 1rem; margin-bottom: 1rem;">
                        <label style="display: flex; align-items: center; gap: 0.5rem; font-weight: 600; cursor: pointer;">
                            <input type="checkbox" name="pm_revolut" value="1" id="toggleRevolut" onchange="toggleSection('revolutSection')" {{ (old('settings_section') === 'payments' ? (bool) old('pm_revolut') : isset($pm['revolut'])) ? 'checked' : '' }} style="width: 1.2rem; height: 1.2rem;">
                            Revolut
                        </label>
                        <div id="revolutSection" style="display: {{ (old('settings_section') === 'payments' ? (bool) old('pm_revolut') : isset($pm['revolut'])) ? 'block' : 'none' }}; margin-top: 1rem;">
                            <input type="text" name="pm_revolut_number" placeholder="Mobile Number or Revolut Tag (@username)" value="{{ old('pm_revolut_number', $pm['revolut'] ?? '') }}" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                        </div>
                    </div>

                    <div style="background: #f8fafc; border: 1px solid var(--border-light); border-radius: var(--radius-md); padding: 1rem; margin-bottom: 1rem;">
                        <label style="display: flex; align-items: center; gap: 0.5rem; font-weight: 600; cursor: pointer;">
                            <input type="checkbox" name="pm_bank" value="1" id="toggleBank" onchange="toggleSection('bankSection')" {{ (old('settings_section') === 'payments' ? (bool) old('pm_bank') : !empty($pm['banks'])) ? 'checked' : '' }} style="width: 1.2rem; height: 1.2rem;">
                            Bank Transfers (IBAN)
                        </label>
                        <div id="bankSection" style="display: {{ (old('settings_section') === 'payments' ? (bool) old('pm_bank') : !empty($pm['banks'])) ? 'block' : 'none' }}; margin-top: 1rem;">
                            <div id="bankRowsContainer">
                                @php
                                    if (old('settings_section') === 'payments' && old('bank_names') !== null) {
                                        $banks = [];
                                        foreach ((array) old('bank_names') as $i => $bankName) {
                                            $banks[] = ['bank' => $bankName, 'iban' => old('ibans.'.$i, '')];
                                        }
                                    } else {
                                        $banks = $pm['banks'] ?? [];
                                    }
                                @endphp
                                @if(!empty($banks))
                                    @foreach($banks as $bank)
                                        <div class="bank-row" style="display: grid; grid-template-columns: 1fr 2fr auto; gap: 0.5rem; margin-bottom: 0.5rem;">
                                            <input type="text" name="bank_names[]" value="{{ $bank['bank'] ?? '' }}" placeholder="Bank (e.g., BOV)" required style="padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                                            <input type="text" name="ibans[]" value="{{ $bank['iban'] ?? '' }}" placeholder="IBAN (e.g., MT12 BOVM...)" required style="padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                                            <button type="button" onclick="this.closest('.bank-row').remove()" style="padding: 0.75rem; background: #fee2e2; color: #ef4444; border: none; border-radius: var(--radius-md); cursor: pointer; font-weight: bold;">X</button>
                                        </div>
                                    @endforeach
                                @else
                                    <div class="bank-row" style="display: grid; grid-template-columns: 1fr 2fr auto; gap: 0.5rem; margin-bottom: 0.5rem;">
                                        <input type="text" name="bank_names[]" placeholder="Bank (e.g., BOV)" style="padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                                        <input type="text" name="ibans[]" placeholder="IBAN (e.g., MT12 BOVM...)" style="padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                                        <button type="button" onclick="this.closest('.bank-row').remove()" style="padding: 0.75rem; background: #fee2e2; color: #ef4444; border: none; border-radius: var(--radius-md); cursor: pointer; font-weight: bold;">X</button>
                                    </div>
                                @endif
                            </div>
                            <button type="button" onclick="addBankRow()" style="margin-top: 0.5rem; background: transparent; color: var(--primary-cerulean); border: 1px dashed var(--primary-cerulean); padding: 0.5rem; border-radius: var(--radius-md); font-size: 0.85rem; cursor: pointer;">+ Add Another Bank</button>
                        </div>
                    </div>

                    <div style="background: #f8fafc; border: 1px solid var(--border-light); border-radius: var(--radius-md); padding: 1rem; margin-bottom: 1.5rem;">
                        <label style="display: flex; align-items: center; gap: 0.5rem; font-weight: 600; cursor: pointer;">
                            <input type="checkbox" name="pm_cheque" value="1" id="toggleCheque" onchange="toggleSection('chequeSection')" {{ (old('settings_section') === 'payments' ? (bool) old('pm_cheque') : isset($pm['cheque'])) ? 'checked' : '' }} style="width: 1.2rem; height: 1.2rem;">
                            Physical Cheques
                        </label>
                        <div id="chequeSection" style="display: {{ (old('settings_section') === 'payments' ? (bool) old('pm_cheque') : isset($pm['cheque'])) ? 'block' : 'none' }}; margin-top: 1rem;">
                            <input type="text" name="pm_cheque_name" placeholder="Payable To (e.g., Dr. Jane Doe)" value="{{ old('pm_cheque_name', $pm['cheque']['name'] ?? '') }}" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); margin-bottom: 0.5rem;">
                            <input type="text" name="pm_cheque_address" placeholder="Postal Address for Cheques" value="{{ old('pm_cheque_address', $pm['cheque']['address'] ?? '') }}" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                        </div>
                    </div>

                    <button type="submit" style="width: 100%; background: var(--primary-cerulean); color: white; border: none; padding: 1rem; border-radius: var(--radius-md); font-weight: 700; font-size: 1.05rem; cursor: pointer; box-shadow: 0 4px 12px rgba(2, 132, 199, 0.3);">
                        Save payments
                    </button>
                </div>
            </form>
        </div>

        {{-- Branding --}}
        <div id="tab-branding" class="settings-tab-panel" role="tabpanel" style="display: none;">
            <div id="branding" style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 2rem; box-shadow: var(--shadow-sm);">
                <h3 style="color: var(--primary-navy); margin-top: 0; margin-bottom: 0.75rem; border-bottom: 1px solid var(--border-light); padding-bottom: 0.5rem;">Branding</h3>
                @if($user->canAccessStandardTools() || $user->canAccessProPackage('med'))
                    <form action="/settings/branding" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        @if($user->canAccessStandardTools())
                            <p style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 1rem;">Logo for invoices / RFPs and clinical letterheads.</p>
                            @if($user->logoDataUri())
                                <div style="margin-bottom: 1rem;">
                                    <img src="{{ $user->logoDataUri() }}" alt="Current logo" style="max-height: 64px; max-width: 200px; border: 1px solid var(--border-light); border-radius: 6px; padding: 0.35rem; background: #f8fafc;">
                                </div>
                            @endif
                            <div style="margin-bottom: 1rem;">
                                <input type="file" name="logo" accept=".jpg,.jpeg,.png,.webp">
                                <p style="font-size: 0.75rem; color: var(--text-muted); margin: 0.35rem 0 0;">JPG, PNG or WebP · max 2MB.</p>
                            </div>
                            @if($user->logo_path)
                                <label style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.85rem; margin-bottom: 1.25rem; cursor: pointer;">
                                    <input type="checkbox" name="remove_logo" value="1">
                                    Remove current logo
                                </label>
                            @endif
                        @endif

                        @if($user->canAccessProPackage('med'))
                            <div style="{{ $user->canAccessStandardTools() ? 'border-top: 1px solid var(--border-light); padding-top: 1.25rem; margin-top: 0.5rem;' : '' }}">
                                <p style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 0.75rem;">
                                    <strong style="color: var(--primary-navy);">Clinical stamp / signature</strong> — printed on prescription, referral, and certificate PDFs when you issue them. Separate from Stamp &amp; issue (that only locks the document).
                                </p>
                                @if($user->clinicalStampDataUri())
                                    <div style="margin-bottom: 1rem;">
                                        <img src="{{ $user->clinicalStampDataUri() }}" alt="Clinical stamp" style="max-height: 96px; max-width: 240px; border: 1px solid var(--border-light); border-radius: 6px; padding: 0.35rem; background: #f8fafc;">
                                    </div>
                                @else
                                    <p style="font-size: 0.85rem; color: #92400e; background: #fffbeb; border: 1px solid #fde68a; border-radius: var(--radius-md); padding: 0.65rem 0.85rem; margin-bottom: 1rem;">
                                        No stamp uploaded yet — issued PDFs will not show a stamp image until you add one here.
                                    </p>
                                @endif
                                <div style="margin-bottom: 1rem;">
                                    <input type="file" name="clinical_stamp" accept=".jpg,.jpeg,.png,.webp">
                                    <p style="font-size: 0.75rem; color: var(--text-muted); margin: 0.35rem 0 0;">JPG, PNG or WebP · max 2MB.</p>
                                </div>
                                @if($user->clinical_stamp_path)
                                    <label style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.85rem; margin-bottom: 1rem; cursor: pointer;">
                                        <input type="checkbox" name="remove_clinical_stamp" value="1">
                                        Remove clinical stamp
                                    </label>
                                @endif
                            </div>
                        @endif

                        <button type="submit" style="background: var(--primary-navy); color: white; border: none; padding: 0.75rem 1.5rem; border-radius: var(--radius-md); font-weight: 600; cursor: pointer;">Save Branding</button>
                    </form>
                @else
                    <p style="color: var(--text-muted); font-size: 0.9rem; margin: 0;">Custom logo branding is included with Standard and Pro. Upgrade in Plan.</p>
                @endif
            </div>
        </div>

        {{-- Security --}}
        <div id="tab-security" class="settings-tab-panel" role="tabpanel" style="display: none;">
            <div id="security" style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 2rem; box-shadow: var(--shadow-sm); margin-bottom: 1.5rem;">
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

            @if($showMedicalVaultDevices ?? false)
                <div id="trusted-devices" style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 2rem; box-shadow: var(--shadow-sm);">
                    <h3 style="color: var(--primary-navy); margin-top: 0; margin-bottom: 0.5rem; border-bottom: 1px solid var(--border-light); padding-bottom: 0.5rem;">Trusted devices</h3>
                    <p style="color: var(--text-muted); font-size: 0.85rem; margin: 0.75rem 0 1rem; line-height: 1.45;">
                        Enable quick unlock per browser after the vault is unlocked.
                    </p>
                    @unless($medicalVaultUnlocked ?? false)
                        <div style="margin-bottom: 1rem; padding: 0.75rem 1rem; background: #fffbeb; border-left: 4px solid #f59e0b; border-radius: var(--radius-md); color: #92400e; font-size: 0.85rem;">
                            Unlock from <a href="/pro/medical/patients" style="color: #92400e; font-weight: 700;">Patients</a> to enable on this browser. You can still revoke below.
                        </div>
                    @endunless
                    <div id="settings-trusted-devices-list" style="font-size: 0.9rem; color: var(--text-muted);">
                        @php $initialDevices = $medicalVaultDevices ?? []; @endphp
                        @if(count($initialDevices) === 0)
                            <span style="color: var(--text-muted);">No trusted devices yet.@if($medicalVaultUnlocked ?? false) Use the button below on this browser.@endif</span>
                        @else
                            <div style="display: grid; gap: 0.55rem;">
                                @foreach($initialDevices as $d)
                                    <div style="display: flex; justify-content: space-between; align-items: center; gap: 0.75rem; flex-wrap: wrap; padding: 0.65rem 0.75rem; background: #f8fafc; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                                        <div>
                                            <div style="font-weight: 700; color: var(--primary-navy);">{{ $d['device_label'] ?: 'Trusted device' }}</div>
                                            <div style="font-size: 0.75rem; color: var(--text-muted);">
                                                Last used:
                                                @if(!empty($d['last_used_at']))
                                                    {{ \Illuminate\Support\Carbon::parse($d['last_used_at'])->timezone(config('app.timezone'))->format('d M Y, H:i') }}
                                                @else
                                                    never
                                                @endif
                                            </div>
                                        </div>
                                        <button type="button" data-revoke-id="{{ $d['id'] }}" data-credential-id="{{ $d['credential_id'] }}" style="padding: 0.4rem 0.75rem; background: white; border: 1px solid #fecaca; color: #991b1b; border-radius: var(--radius-md); font-weight: 700; cursor: pointer; font-size: 0.8rem;">Revoke</button>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                    @if($medicalVaultUnlocked ?? false)
                        <button type="button" id="settings-trust-enable" style="display: none; margin-top: 1rem; padding: 0.55rem 1rem; background: white; color: #1d4ed8; border: 1px solid #93c5fd; border-radius: var(--radius-md); font-weight: 700; cursor: pointer; font-size: 0.85rem;">
                            Enable quick unlock on this browser
                        </button>
                        <div id="settings-trust-status" style="display: none; margin-top: 0.65rem; font-size: 0.85rem;"></div>
                    @endif
                </div>
            @endif
        </div>

        {{-- Backup --}}
        <div id="tab-backup" class="settings-tab-panel" role="tabpanel" style="display: none;">
            <div id="data-backup" style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 2rem; box-shadow: var(--shadow-sm);">
                <h3 style="color: var(--primary-navy); margin-top: 0; margin-bottom: 0.5rem; border-bottom: 1px solid var(--border-light); padding-bottom: 0.5rem;">Backup</h3>
                <p style="color: var(--text-muted); font-size: 0.85rem; line-height: 1.45; margin: 0.75rem 0 1rem;">
                    Weekly ZIP of your clients, invoices, payments, and expenses. Clinical vault journals use the medical backup.
                </p>
                <div style="font-size: 0.9rem; color: var(--text-muted); margin-bottom: 1rem;">
                    Last backup:
                    <strong style="color: var(--primary-navy);">
                        {{ $user->last_data_backup_at ? $user->last_data_backup_at->format('d M Y H:i') : 'Never' }}
                    </strong>
                    @if($user->isDataBackupOverdue())
                        <span style="color: var(--primary-cerulean); font-weight: 600; margin-left: 0.35rem;">· Reminder this week</span>
                    @endif
                </div>
                <div style="display: flex; gap: 0.75rem; flex-wrap: wrap; align-items: center;">
                    <a href="/exports/backup" style="background: var(--primary-cerulean); color: white; padding: 0.7rem 1.15rem; border-radius: var(--radius-md); font-weight: 700; font-size: 0.9rem; text-decoration: none;">Open backup</a>
                    @if($user->canAccessProPackage('med'))
                        <a href="/pro/medical/vault/backup" style="color: var(--primary-navy); font-weight: 600; font-size: 0.85rem; text-decoration: none; border-bottom: 1px dotted var(--primary-navy);">Medical vault backup</a>
                    @endif
                </div>
            </div>
        </div>

        {{-- App --}}
        <div id="tab-app" class="settings-tab-panel" role="tabpanel" style="display: none;">
            <div id="install-app" style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 2rem; box-shadow: var(--shadow-sm);">
                <h3 style="color: var(--primary-navy); margin-top: 0; margin-bottom: 0.5rem; border-bottom: 1px solid var(--border-light); padding-bottom: 0.5rem;">App</h3>
                <p style="color: var(--text-muted); font-size: 0.85rem; line-height: 1.45; margin: 0.75rem 0 1rem;">
                    Open PractisBase in one tap — no app store needed.
                </p>
                <button type="button" data-open-install-app style="background: white; color: var(--primary-navy); border: 1px solid var(--border-light); padding: 0.7rem 1.15rem; border-radius: var(--radius-md); font-weight: 600; font-size: 0.9rem; cursor: pointer;">
                    Download app
                </button>
            </div>
        </div>

    </div>

    @include('expenses.partials.business-use-helpers', [
        'user' => $user,
        'redirectTo' => '/settings#tax',
    ])

    <script>
        (function () {
            var valid = { plan: true, practice: true, tax: true, payments: true, branding: true, security: true, backup: true, app: true };
            var aliases = {
                'tax-setup': 'tax',
                'tax-details': 'tax',
                'data-backup': 'backup',
                'install-app': 'app',
                'trusted-devices': 'security'
            };

            function resolveTab(raw) {
                var h = (raw || '').replace(/^#/, '');
                if (aliases[h]) return aliases[h];
                if (valid[h]) return h;
                return 'plan';
            }

            function activate(tab) {
                tab = resolveTab(tab);
                if (!valid[tab]) tab = 'plan';
                document.querySelectorAll('.settings-tab-panel').forEach(function (el) {
                    el.style.display = el.id === 'tab-' + tab ? 'block' : 'none';
                });
                document.querySelectorAll('.settings-tab-btn').forEach(function (btn) {
                    var on = btn.getAttribute('data-tab') === tab;
                    btn.style.color = on ? 'var(--primary-navy)' : 'var(--text-muted)';
                    btn.style.borderBottomColor = on ? 'var(--primary-cerulean)' : 'transparent';
                    btn.setAttribute('aria-selected', on ? 'true' : 'false');
                });
                if (history.replaceState) {
                    history.replaceState(null, '', '#' + tab);
                } else {
                    location.hash = tab;
                }
            }

            document.querySelectorAll('.settings-tab-btn').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    activate(btn.getAttribute('data-tab'));
                });
            });

            window.addEventListener('hashchange', function () {
                activate(location.hash);
            });

            var initial = resolveTab(location.hash);
            @if(old('settings_section'))
                initial = @json(old('settings_section'));
            @elseif($errors->has('regime_effective_from') || $errors->has('employment_type') || $errors->has('vat_status') || $errors->has('tax_computation') || $errors->has('primary_salary') || $errors->has('estimated_expenses') || $errors->has('date_of_birth'))
                initial = 'tax';
            @elseif($errors->has('name') || $errors->has('email') || $errors->has('warrant_number') || $errors->has('postnominals'))
                initial = 'practice';
            @elseif($errors->has('pm_bov_number') || $errors->has('pm_revolut_number') || $errors->has('pm_cheque_name') || $errors->has('bank_names') || $errors->has('ibans'))
                initial = 'payments';
            @elseif($errors->has('current_password') || $errors->has('password'))
                initial = 'security';
            @elseif($errors->has('logo') || $errors->has('clinical_stamp') || $errors->has('branding'))
                initial = 'branding';
            @endif
            activate(initial);
            window.__settingsActivateTab = activate;
        })();

        (function () {
            var currentTier = @json($currentTier ?? \App\Support\TierPolicy::normalize($user->tier));
            var downgradeMap = @json(collect($allowedTiers ?? [])->mapWithKeys(fn ($t) => [$t => \App\Support\TierPolicy::isDowngrade($currentTier ?? $user->tier, $t)])->all());
            var consequences = @json($planConsequences ?? []);
            var form = document.getElementById('plan-change-form');
            var select = document.getElementById('plan-tier-select');
            var preview = document.getElementById('plan-change-preview');
            var confirmField = document.getElementById('confirm_downgrade_field');
            var modal = document.getElementById('downgrade-modal');
            var modalList = document.getElementById('downgrade-modal-list');
            var understand = document.getElementById('downgrade-understand');
            var typed = document.getElementById('downgrade-typed');
            var confirmBtn = document.getElementById('downgrade-confirm-btn');
            if (!form || !select || !preview) return;

            function isDowngrade(to) {
                return !!downgradeMap[to];
            }

            function renderPreview() {
                var to = select.value;
                var notes = consequences[to] || [];
                if (!notes.length || to === currentTier) {
                    preview.style.display = 'none';
                    preview.innerHTML = '';
                    return;
                }
                var downgrade = isDowngrade(to);
                preview.style.display = 'block';
                preview.style.background = downgrade ? '#fef2f2' : '#ecfdf5';
                preview.style.border = downgrade ? '1px solid #fecaca' : '1px solid #a7f3d0';
                preview.style.color = downgrade ? '#991b1b' : '#065f46';
                preview.innerHTML = '<strong style="display:block;margin-bottom:0.35rem;">' + (downgrade ? 'Downgrade warning' : 'Plan change') + '</strong><ul style="margin:0;padding-left:1.1rem;">' +
                    notes.map(function (n) { return '<li style="margin-bottom:0.25rem;">' + n + '</li>'; }).join('') + '</ul>';
            }

            function openModal(notes) {
                modalList.innerHTML = notes.map(function (n) { return '<li style="margin-bottom:0.35rem;">' + n + '</li>'; }).join('');
                understand.checked = false;
                typed.value = '';
                confirmField.value = '';
                modal.hidden = false;
                document.body.style.overflow = 'hidden';
            }

            function closeModal() {
                modal.hidden = true;
                document.body.style.overflow = '';
                confirmField.value = '';
            }

            select.addEventListener('change', renderPreview);
            renderPreview();

            form.addEventListener('submit', function (e) {
                var to = select.value;
                if (to === currentTier) return;
                if (!isDowngrade(to)) {
                    confirmField.value = '';
                    confirmField.disabled = true;
                    typed.value = '';
                    typed.disabled = true;
                    return;
                }
                confirmField.disabled = false;
                typed.disabled = false;
                if (confirmField.value === '1' && understand.checked && typed.value.trim().toUpperCase() === 'DOWNGRADE') {
                    return;
                }
                e.preventDefault();
                openModal(consequences[to] || []);
            });

            confirmBtn.addEventListener('click', function () {
                if (!understand.checked) {
                    alert('Tick the confirmation checkbox first.');
                    return;
                }
                if (typed.value.trim().toUpperCase() !== 'DOWNGRADE') {
                    alert('Type DOWNGRADE to confirm.');
                    return;
                }
                confirmField.disabled = false;
                typed.disabled = false;
                confirmField.value = '1';
                modal.hidden = true;
                document.body.style.overflow = '';
                form.submit();
            });

            modal.querySelectorAll('[data-close-downgrade]').forEach(function (el) {
                el.addEventListener('click', closeModal);
            });
        })();

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
            syncRegimeEffective();
        }

        function handleVatChange() {
            const vatStatus = document.getElementById('vatSettingsStatus');
            const vatGroup = document.getElementById('vatSettingsGroup');
            const vatInput = document.getElementById('vatSettingsInput');

            if (vatStatus.value === 'article_10' || vatStatus.value === 'article_11') {
                vatGroup.style.display = 'block';
                vatInput.required = false;
            } else {
                vatGroup.style.display = 'none';
                vatInput.required = false;
            }
            syncRegimeEffective();
        }

        @php
            $regimePrimarySalary = number_format((float) ($user->primary_salary ?? 0), 2, '.', '');
        @endphp
        var regimeBaseline = {
            employment_type: @json($user->employment_type),
            vat_status: @json($user->vat_status),
            tax_computation: @json($user->tax_computation ?: 'single'),
            primary_salary: @json($regimePrimarySalary),
            max_ssc_paid: @json((bool) $user->max_ssc_paid)
        };

        function syncRegimeEffective() {
            var wrap = document.getElementById('regimeEffectiveWrap');
            var input = document.getElementById('regimeEffectiveFrom');
            if (!wrap || !input) return;
            var emp = document.getElementById('empType');
            var vat = document.getElementById('vatSettingsStatus');
            var tax = document.getElementById('taxComputation');
            var sal = document.getElementById('primarySalary');
            var ssc = document.getElementById('maxSscPaid');
            var changed = false;
            if (emp && emp.value !== regimeBaseline.employment_type) changed = true;
            if (vat && vat.value !== regimeBaseline.vat_status) changed = true;
            if (tax && tax.value !== regimeBaseline.tax_computation) changed = true;
            if (sal && Number(sal.value || 0).toFixed(2) !== Number(regimeBaseline.primary_salary || 0).toFixed(2)) changed = true;
            if (ssc && !!ssc.checked !== !!regimeBaseline.max_ssc_paid) changed = true;
            wrap.style.display = changed ? 'block' : 'none';
            input.required = changed;
        }

        function handleProfChange() {
            const profEl = document.getElementById('profInput');
            const alert = document.getElementById('medicalVatAlert');
            if (!profEl || !alert) return;

            if (profEl.value === 'Medical Professional') {
                alert.style.display = 'block';
            } else {
                alert.style.display = 'none';
            }
        }

        document.addEventListener("DOMContentLoaded", function() {
            handleProfChange();
            syncRegimeEffective();
            ['empType', 'vatSettingsStatus', 'taxComputation', 'primarySalary', 'maxSscPaid'].forEach(function (id) {
                var el = document.getElementById(id);
                if (!el) return;
                el.addEventListener('change', syncRegimeEffective);
                el.addEventListener('input', syncRegimeEffective);
            });
            @if($errors->has('regime_effective_from'))
                var wrapErr = document.getElementById('regimeEffectiveWrap');
                if (wrapErr) {
                    wrapErr.style.display = 'block';
                    var inputErr = document.getElementById('regimeEffectiveFrom');
                    if (inputErr) inputErr.required = true;
                }
            @endif
        });

        function toggleSection(sectionId) {
            const section = document.getElementById(sectionId);
            section.style.display = section.style.display === 'none' ? 'block' : 'none';
        }

        function addBankRow() {
            const container = document.getElementById('bankRowsContainer');
            const rowHtml = `
                <div class="bank-row" style="display: grid; grid-template-columns: 1fr 2fr auto; gap: 0.5rem; margin-bottom: 0.5rem;">
                    <input type="text" name="bank_names[]" placeholder="Bank (e.g., BOV)" required style="padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                    <input type="text" name="ibans[]" placeholder="IBAN (e.g., MT12 BOVM...)" required style="padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                    <button type="button" onclick="this.closest('.bank-row').remove()" style="padding: 0.75rem; background: #fee2e2; color: #ef4444; border: none; border-radius: var(--radius-md); cursor: pointer; font-weight: bold;">X</button>
                </div>
            `;
            container.insertAdjacentHTML('beforeend', rowHtml);
        }
    </script>

    @if($showMedicalVaultDevices ?? false)
        @include('pro.medical._vault-device-js')
        <script>
            (function () {
                if (!window.PractisVaultDevice) return;
                var listEl = document.getElementById('settings-trusted-devices-list');
                var enableBtn = document.getElementById('settings-trust-enable');
                var statusEl = document.getElementById('settings-trust-status');
                var vaultUnlocked = @json((bool) ($medicalVaultUnlocked ?? false));

                function formatWhen(iso) {
                    if (!iso) return 'never';
                    try {
                        var d = new Date(iso);
                        return isNaN(d.getTime()) ? 'unknown' : d.toLocaleString();
                    } catch (e) { return 'unknown'; }
                }

                function escapeHtml(s) {
                    return String(s == null ? '' : s)
                        .replace(/&/g, '&amp;')
                        .replace(/</g, '&lt;')
                        .replace(/>/g, '&gt;')
                        .replace(/"/g, '&quot;');
                }

                function bindRevokeButtons() {
                    if (!listEl) return;
                    listEl.querySelectorAll('[data-revoke-id]').forEach(function (btn) {
                        if (btn.getAttribute('data-bound') === '1') return;
                        btn.setAttribute('data-bound', '1');
                        btn.addEventListener('click', function () {
                            if (!confirm('Revoke quick unlock for this device?')) return;
                            btn.disabled = true;
                            PractisVaultDevice.revokeDevice(btn.getAttribute('data-revoke-id'), btn.getAttribute('data-credential-id')).then(function () {
                                return PractisVaultDevice.listDevices().then(renderDevices);
                            }).catch(function (e) {
                                alert(e.message || 'Could not revoke device.');
                                btn.disabled = false;
                            });
                        });
                    });
                }

                function renderDevices(devices) {
                    if (!listEl) return;
                    if (!devices.length) {
                        listEl.innerHTML = '<span style="color: var(--text-muted);">No trusted devices yet.' +
                            (vaultUnlocked ? ' Use the button below on this browser after unlocking the vault.' : '') +
                            '</span>';
                        return;
                    }
                    var html = '<div style="display: grid; gap: 0.55rem;">';
                    devices.forEach(function (d) {
                        html += '<div style="display: flex; justify-content: space-between; align-items: center; gap: 0.75rem; flex-wrap: wrap; padding: 0.65rem 0.75rem; background: #f8fafc; border: 1px solid var(--border-light); border-radius: var(--radius-md);">';
                        html += '<div><div style="font-weight: 700; color: var(--primary-navy);">' + escapeHtml(d.device_label || 'Trusted device') + '</div>';
                        html += '<div style="font-size: 0.75rem; color: var(--text-muted);">Last used: ' + escapeHtml(formatWhen(d.last_used_at)) + '</div></div>';
                        html += '<button type="button" data-revoke-id="' + escapeHtml(d.id) + '" data-credential-id="' + escapeHtml(d.credential_id || '') + '" style="padding: 0.4rem 0.75rem; background: white; border: 1px solid #fecaca; color: #991b1b; border-radius: var(--radius-md); font-weight: 700; cursor: pointer; font-size: 0.8rem;">Revoke</button>';
                        html += '</div>';
                    });
                    html += '</div>';
                    listEl.innerHTML = html;
                    bindRevokeButtons();
                }

                bindRevokeButtons();
                PractisVaultDevice.listDevices().then(renderDevices).catch(function () {});

                if (enableBtn && vaultUnlocked) {
                    PractisVaultDevice.platformAvailable().then(function (ok) {
                        if (!ok) return;
                        return PractisVaultDevice.hasLocalWrapKey().then(function (hasKey) {
                            enableBtn.style.display = hasKey ? 'none' : 'inline-block';
                        });
                    });
                    enableBtn.addEventListener('click', function () {
                        if (statusEl) statusEl.style.display = 'none';
                        enableBtn.disabled = true;
                        enableBtn.textContent = 'Waiting…';
                        PractisVaultDevice.registerDevice().then(function (result) {
                            if (statusEl) {
                                statusEl.style.display = 'block';
                                statusEl.style.color = '#065f46';
                                statusEl.textContent = (result && result.message) ? result.message : 'Quick unlock enabled.';
                            }
                            enableBtn.style.display = 'none';
                            return PractisVaultDevice.listDevices().then(renderDevices);
                        }).catch(function (e) {
                            if (statusEl) {
                                statusEl.style.display = 'block';
                                statusEl.style.color = '#991b1b';
                                statusEl.textContent = e.message || 'Could not enable quick unlock.';
                            }
                            enableBtn.disabled = false;
                            enableBtn.textContent = 'Enable quick unlock on this browser';
                        });
                    });
                }
            })();
        </script>
    @endif
@endsection
