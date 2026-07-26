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

        <div style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 2rem; box-shadow: var(--shadow-sm); margin-bottom: 2rem;">
            <h3 style="color: var(--primary-navy); margin-top: 0; margin-bottom: 0.5rem; border-bottom: 1px solid var(--border-light); padding-bottom: 0.5rem;">Subscription</h3>
            <p style="color: var(--text-muted); font-size: 0.85rem; margin-top: 0.75rem; margin-bottom: 1.25rem;">
                Current plan drives client limits and Live Fiscal Report access. Deletes do <strong>not</strong> free Free-tier client slots.
            </p>

            <div style="display: flex; flex-wrap: wrap; gap: 0.75rem; align-items: center; margin-bottom: 1.25rem;">
                <span style="display: inline-block; background: rgba(2, 132, 199, 0.1); color: var(--primary-cerulean); font-weight: 700; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px; padding: 0.35rem 0.75rem; border-radius: 20px; border: 1px solid rgba(2, 132, 199, 0.25);">
                    {{ ucwords(str_replace('-', ' ', $user->tier ?: 'free')) }}
                </span>
                <span style="font-size: 0.9rem; color: var(--text-main); font-weight: 600;">
                    {{ $user->clientUsageLabel() }}
                </span>
            </div>

            @unless($user->isPaid())
                <div style="background: #fffbeb; border: 1px solid #fde68a; border-left: 4px solid #f59e0b; border-radius: var(--radius-md); padding: 0.85rem 1rem; margin-bottom: 1.25rem; color: #92400e; font-size: 0.85rem; line-height: 1.45;">
                    Free includes Dashboard + ledger only (max {{ $user->freeClientCap() }} lifetime clients). Upgrade to Standard or Pro for <strong>unlimited clients</strong> and the <strong>Live Fiscal Report</strong>.
                </div>
            @endunless

            <div style="background: #fef3c7; color: #b45309; text-align: left; padding: 0.75rem 1rem; border-radius: var(--radius-md); font-size: 0.8rem; font-weight: 600; margin-bottom: 1rem; border: 1px solid #fde68a;">
                DEV MODE: Plan changes below update your account instantly. Stripe billing is not connected yet.
            </div>

            <form action="/settings/plan" method="POST">
                @csrf
                @method('PUT')
                <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; font-size: 0.9rem;">Change plan</label>
                <div style="display: flex; gap: 0.75rem; flex-wrap: wrap; align-items: center;">
                    <select name="tier" required style="flex: 1; min-width: 180px; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); background: white;">
                        @foreach(($allowedTiers ?? ['free', 'standard']) as $tierOption)
                            <option value="{{ $tierOption }}" {{ ($user->tier ?: 'free') === $tierOption ? 'selected' : '' }}>
                                {{ ucwords(str_replace('-', ' ', $tierOption)) }}
                            </option>
                        @endforeach
                    </select>
                    <button type="submit" style="padding: 0.75rem 1.25rem; background: var(--primary-cerulean); color: white; border: none; border-radius: var(--radius-md); font-weight: 700; cursor: pointer;">
                        Update Plan
                    </button>
                </div>
                <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.5rem;">
                    Pro packages are limited to your registered profession. Contact support to change profession.
                </div>
            </form>
        </div>

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
                        <input type="text" id="profInput" value="{{ $user->profession }}" disabled style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); background: #f1f5f9; color: #64748b; cursor: not-allowed; font-weight: 500;">
                        <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.3rem;">Contact support to change your registered profession.</div>
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
                        <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; font-size: 0.9rem;">VAT Number <span style="font-weight: 500; color: var(--text-muted);">(optional until needed)</span></label>
                        <input type="text" name="vat_number" id="vatSettingsInput" value="{{ $user->vat_number }}" placeholder="MT..." style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                        <p style="font-size: 0.75rem; color: var(--text-muted); margin: 0.4rem 0 0; line-height: 1.4;">Required only when issuing an Article 10 invoice or charging 18% VAT.</p>
                    </div>
                </div>

                @if($user->missingVatNumberForArticle10Documents())
                    <div style="margin-bottom: 1.25rem; padding: 0.85rem 1rem; background: #fffbeb; border: 1px solid #fef3c7; border-left: 4px solid #f59e0b; border-radius: var(--radius-md); color: #92400e; font-size: 0.85rem; line-height: 1.45;">
                        You are on Article 10 without a VAT number. Add it here before creating an official invoice or applying 18% VAT.
                    </div>
                @endif

                <div id="medicalVatAlert" style="display: {{ $user->profession === 'Medical Professional' ? 'block' : 'none' }}; margin-top: 1rem; padding: 1rem; background: #fffbeb; border: 1px solid #fef3c7; border-left: 4px solid #f59e0b; border-radius: var(--radius-md); color: #92400e; font-size: 0.85rem; line-height: 1.5;">
                    <strong>⚠️ Note on Medical Exemptions (Fifth Schedule):</strong><br>
                    Under Maltese VAT Law, the medical exemption applies <strong>strictly to therapeutic care</strong> provided by professionals warranted under the Health Care Professions Act. Non-therapeutic services (e.g., purely cosmetic procedures, corporate consultancy, medico-legal reports) may be subject to standard 18% VAT. If you provide taxable services, you must register under Article 10 or 11.
                </div>

                <div style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 2rem; box-shadow: var(--shadow-sm); margin-bottom: 2rem;">
                <h3 style="color: var(--primary-navy); margin-top: 0; margin-bottom: 1.5rem; border-bottom: 1px solid var(--border-light); padding-bottom: 0.5rem;">Fiscal Configuration</h3>
                <p style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 1.5rem;">These details are required to accurately generate your Live Fiscal Report. <br><em>Note: This information is strictly for calculation purposes and does not appear on your invoices.</em></p>
                
                <div style="display: grid; grid-template-columns: 1fr; gap: 1.5rem;">
                    
                    <div>
                        <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; font-size: 0.9rem;">Tax Computation Status</label>
                        <select name="tax_computation" required style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); font-family: inherit; background: white;">
                            <option value="" disabled {{ !$user->tax_computation ? 'selected' : '' }}>Select your status</option>
                            <option value="single" {{ $user->tax_computation === 'single' ? 'selected' : '' }}>Single</option>
                            <option value="married" {{ $user->tax_computation === 'married' ? 'selected' : '' }}>Married</option>
                            <option value="parent" {{ $user->tax_computation === 'parent' ? 'selected' : '' }}>Parent</option>
                        </select>
                    </div>

                    <div>
                        <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; font-size: 0.9rem;">Primary Employment Salary (Gross)</label>
                        <p style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0; margin-bottom: 0.5rem;">If this practice is your secondary income, enter your main job's annual salary. Otherwise, enter 0.</p>
                        <div style="display: flex; align-items: center; background: #f8fafc; border: 1px solid var(--border-light); border-radius: var(--radius-md); padding: 0 0.75rem;">
                            <span style="color: var(--text-muted); font-weight: 600;">€</span>
                            <input type="number" name="primary_salary" step="0.01" min="0" value="{{ $user->primary_salary ?? '0.00' }}" required style="width: 100%; padding: 0.75rem; border: none; background: transparent; font-family: inherit;">
                        </div>
                    </div>

                    <div style="background: #f8fafc; border: 1px solid var(--border-light); border-radius: var(--radius-md); padding: 1rem;">
                        <label style="display: flex; align-items: center; gap: 0.5rem; font-weight: 600; cursor: pointer;">
                            <input type="checkbox" name="max_ssc_paid" value="1" {{ $user->max_ssc_paid ? 'checked' : '' }} style="width: 1.2rem; height: 1.2rem;">
                            I already pay the maximum Social Security (SSC) at my primary job.
                        </label>
                        <p style="font-size: 0.8rem; color: var(--text-muted); margin-left: 1.7rem; margin-top: 0.25rem;">Checking this box legally exempts your part-time self-employed income from further SSC contributions.</p>
                    </div>

                    <div>
                        <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; font-size: 0.9rem;">Estimated Annual Allowable Expenses</label>
                        <p style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0; margin-bottom: 0.5rem;">Fallback used when the Expense Ledger for a year is empty (or on Free). Standard+ ledger totals override this when &gt; €0.</p>
                        <div style="display: flex; align-items: center; background: #f8fafc; border: 1px solid var(--border-light); border-radius: var(--radius-md); padding: 0 0.75rem;">
                            <span style="color: var(--text-muted); font-weight: 600;">€</span>
                            <input type="number" name="estimated_expenses" step="0.01" min="0" value="{{ $user->estimated_expenses ?? '0.00' }}" required style="width: 100%; padding: 0.75rem; border: none; background: transparent; font-family: inherit;">
                        </div>
                    </div>

                </div>
            </div>

                <h3 style="color: var(--primary-navy); margin-top: 2rem; margin-bottom: 1.5rem; border-bottom: 1px solid var(--border-light); padding-bottom: 0.5rem;">Accepted Payment Methods</h3>
                <p style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 1.5rem;">Select how you want clients to pay you. These will be automatically formatted onto your invoices. <strong>(Select at least one)</strong></p>

                @php $pm = $user->payment_methods ?? []; @endphp

                <div style="background: #f8fafc; border: 1px solid var(--border-light); border-radius: var(--radius-md); padding: 1rem; margin-bottom: 1rem;">
                    <label style="display: flex; align-items: center; gap: 0.5rem; font-weight: 600; cursor: pointer;">
                        <input type="checkbox" name="pm_bov" value="1" id="toggleBov" onchange="toggleSection('bovSection')" {{ isset($pm['bov_mobile']) ? 'checked' : '' }} style="width: 1.2rem; height: 1.2rem;">
                        BOV Mobile Pay
                    </label>
                    <div id="bovSection" style="display: {{ isset($pm['bov_mobile']) ? 'block' : 'none' }}; margin-top: 1rem;">
                        <input type="text" name="pm_bov_number" placeholder="Mobile Number (e.g., +356 9999 9999)" value="{{ $pm['bov_mobile'] ?? '' }}" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                    </div>
                </div>

                <div style="background: #f8fafc; border: 1px solid var(--border-light); border-radius: var(--radius-md); padding: 1rem; margin-bottom: 1rem;">
                    <label style="display: flex; align-items: center; gap: 0.5rem; font-weight: 600; cursor: pointer;">
                        <input type="checkbox" name="pm_revolut" value="1" id="toggleRevolut" onchange="toggleSection('revolutSection')" {{ isset($pm['revolut']) ? 'checked' : '' }} style="width: 1.2rem; height: 1.2rem;">
                        Revolut
                    </label>
                    <div id="revolutSection" style="display: {{ isset($pm['revolut']) ? 'block' : 'none' }}; margin-top: 1rem;">
                        <input type="text" name="pm_revolut_number" placeholder="Mobile Number or Revolut Tag (@username)" value="{{ $pm['revolut'] ?? '' }}" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                    </div>
                </div>

                <div style="background: #f8fafc; border: 1px solid var(--border-light); border-radius: var(--radius-md); padding: 1rem; margin-bottom: 1rem;">
                    <label style="display: flex; align-items: center; gap: 0.5rem; font-weight: 600; cursor: pointer;">
                        <input type="checkbox" name="pm_bank" value="1" id="toggleBank" onchange="toggleSection('bankSection')" {{ !empty($pm['banks']) ? 'checked' : '' }} style="width: 1.2rem; height: 1.2rem;">
                        Bank Transfers (IBAN)
                    </label>
                    <div id="bankSection" style="display: {{ !empty($pm['banks']) ? 'block' : 'none' }}; margin-top: 1rem;">
                        <div id="bankRowsContainer">
                            @if(!empty($pm['banks']))
                                @foreach($pm['banks'] as $bank)
                                    <div class="bank-row" style="display: grid; grid-template-columns: 1fr 2fr auto; gap: 0.5rem; margin-bottom: 0.5rem;">
                                        <input type="text" name="bank_names[]" value="{{ $bank['bank'] }}" placeholder="Bank (e.g., BOV)" required style="padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                                        <input type="text" name="ibans[]" value="{{ $bank['iban'] }}" placeholder="IBAN (e.g., MT12 BOVM...)" required style="padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
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

                <div style="background: #f8fafc; border: 1px solid var(--border-light); border-radius: var(--radius-md); padding: 1rem; margin-bottom: 2rem;">
                    <label style="display: flex; align-items: center; gap: 0.5rem; font-weight: 600; cursor: pointer;">
                        <input type="checkbox" name="pm_cheque" value="1" id="toggleCheque" onchange="toggleSection('chequeSection')" {{ isset($pm['cheque']) ? 'checked' : '' }} style="width: 1.2rem; height: 1.2rem;">
                        Physical Cheques
                    </label>
                    <div id="chequeSection" style="display: {{ isset($pm['cheque']) ? 'block' : 'none' }}; margin-top: 1rem;">
                        <input type="text" name="pm_cheque_name" placeholder="Payable To (e.g., Dr. Jane Doe)" value="{{ $pm['cheque']['name'] ?? '' }}" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); margin-bottom: 0.5rem;">
                        <input type="text" name="pm_cheque_address" placeholder="Postal Address for Cheques" value="{{ $pm['cheque']['address'] ?? '' }}" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                    </div>
                </div>

                <button type="submit" style="margin-top: 1.5rem; width: 100%; background: var(--primary-cerulean); color: white; border: none; padding: 1rem; border-radius: var(--radius-md); font-weight: 700; font-size: 1.05rem; cursor: pointer; box-shadow: 0 4px 12px rgba(2, 132, 199, 0.3);">
                    Save All Profile Changes
                </button>
            </div>
        </form>

        <div style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 2rem; box-shadow: var(--shadow-sm); margin-bottom: 2rem;">
            <h3 style="color: var(--primary-navy); margin-top: 0; margin-bottom: 0.75rem; border-bottom: 1px solid var(--border-light); padding-bottom: 0.5rem;">Document Branding</h3>
            @if($user->canAccessStandardTools())
                <p style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 1.25rem;">Upload a logo for invoice / RFP PDFs (Standard+).</p>
                @if($user->logoDataUri())
                    <div style="margin-bottom: 1rem;">
                        <img src="{{ $user->logoDataUri() }}" alt="Current logo" style="max-height: 64px; max-width: 200px; border: 1px solid var(--border-light); border-radius: 6px; padding: 0.35rem; background: #f8fafc;">
                    </div>
                @endif
                <form action="/settings/branding" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    <div style="margin-bottom: 1rem;">
                        <input type="file" name="logo" accept=".jpg,.jpeg,.png,.webp">
                        <p style="font-size: 0.75rem; color: var(--text-muted); margin: 0.35rem 0 0;">JPG, PNG or WebP · max 2MB · private object storage (Cloudflare R2 in production).</p>
                    </div>
                    @if($user->logo_path)
                        <label style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.85rem; margin-bottom: 1rem; cursor: pointer;">
                            <input type="checkbox" name="remove_logo" value="1">
                            Remove current logo
                        </label>
                    @endif
                    <button type="submit" style="background: var(--primary-navy); color: white; border: none; padding: 0.75rem 1.5rem; border-radius: var(--radius-md); font-weight: 600; cursor: pointer;">Save Branding</button>
                </form>
            @else
                <p style="color: var(--text-muted); font-size: 0.9rem; margin: 0;">Custom logo branding is included with Standard and Pro. Upgrade in the Subscription card above.</p>
            @endif
        </div>

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
                vatInput.required = false;
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
@endsection