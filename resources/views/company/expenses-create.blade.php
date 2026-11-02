@extends('layouts.app')

@section('page_title', 'Log company expense')

@section('content')
    @php
        $read = $read ?? [];
        $matchedIds = $read['matched_supplier_ids'] ?? [];
        $matchedId = $matchedIds[0] ?? null;
        if (old('supplier_mode')) {
            $supplierMode = old('supplier_mode');
        } elseif ($matchedId) {
            $supplierMode = 'existing';
        } elseif ($suppliers->isEmpty() || !empty($read['supplier_name'])) {
            $supplierMode = 'new';
        } else {
            $supplierMode = 'existing';
        }
        $amountValue = old('amount', isset($read['net']) && $read['net'] !== null ? number_format((float) $read['net'], 2, '.', '') : '');
        $vatValue = old('vat_amount', isset($read['vat']) && $read['vat'] !== null ? number_format((float) $read['vat'], 2, '.', '') : '0');
        $rcOn = $errors->any() ? (bool) old('is_reverse_charge') : (bool) ($read['reverse_charge'] ?? false);
        $computerGrossValue = old('computer_invoice_total');
        $computerTreatment = old('computer_treatment');
        if ($computerGrossValue === null && ! $errors->any()) {
            $seedTotal = $read['total'] ?? $read['net'] ?? null;
            $computerGrossValue = $seedTotal !== null ? number_format((float) $seedTotal, 2, '.', '') : '';
        }
        if ($computerTreatment === null) {
            $seedNet = isset($read['net']) ? (float) $read['net'] : 0.0;
            $seedVat = isset($read['vat']) ? (float) $read['vat'] : 0.0;
            if ($rcOn || $seedVat <= 0.009) {
                $computerTreatment = 'zero';
            } elseif ($seedNet > 0 && abs(($seedVat / $seedNet) - 0.19) < 0.015) {
                $computerTreatment = 'de19';
            } elseif ($seedNet > 0 && abs(($seedVat / $seedNet) - 0.18) < 0.015) {
                $computerTreatment = 'mt18';
            } else {
                $computerTreatment = 'irrecoverable';
            }
        }
        if ($computerGrossValue === null) {
            $computerGrossValue = '';
        }
    @endphp

    <div style="max-width: 720px; margin: 0 auto;">
        <div style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.75rem; box-shadow: var(--shadow-sm); margin-bottom: 1rem;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem;">
                <h1 style="font-size: 1.3rem; color: var(--primary-navy); margin: 0;">Log company expense</h1>
                <a href="/company/expenses" style="color: var(--text-muted); font-weight: 600; font-size: 0.85rem; text-decoration: none;">Cancel</a>
            </div>
            <p style="font-size: 0.85rem; color: var(--text-muted); line-height: 1.45; margin: 0 0 1rem;">
                Upload the supplier PDF first. PractisBase reads the supplier and the invoice, then you check the figures and save. Nothing is posted until you confirm.
            </p>

            @if(session('success'))
                <div style="margin-bottom: 1rem; padding: 0.85rem 1rem; background: #ecfdf5; border: 1px solid #a7f3d0; border-radius: var(--radius-md); color: #065f46; font-size: 0.9rem;">{{ session('success') }}</div>
            @endif
            @if($errors->any())
                <div style="margin-bottom: 1rem; padding: 0.85rem 1rem; background: #fef2f2; border: 1px solid #fecaca; border-radius: var(--radius-md); color: #991b1b; font-size: 0.9rem;">{{ $errors->first() }}</div>
            @endif

            <form method="POST" action="/company/expenses/intake" enctype="multipart/form-data">
                @csrf
                <label style="display: block; font-weight: 600; margin-bottom: 0.4rem; font-size: 0.9rem;">Supplier invoice PDF</label>
                <div style="display: flex; flex-wrap: wrap; gap: 0.6rem; align-items: center;">
                    <input type="file" name="invoice" accept="application/pdf,.pdf" required style="flex: 1; min-width: 12rem; padding: 0.4rem 0;">
                    <button type="submit" style="background: var(--primary-navy); color: white; border: none; padding: 0.65rem 1rem; border-radius: var(--radius-md); font-weight: 700; cursor: pointer;">Read invoice</button>
                </div>
                <div id="intakeGuide" style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.35rem;">Google, Railway, and other digital invoices. A photo of a paper bill can be attached below instead.</div>
            </form>
        </div>

        <div style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.75rem; box-shadow: var(--shadow-sm);">
            @if($draft)
                <div style="margin-bottom: 1rem; padding: 0.85rem 1rem; background: #f8fafc; border: 1px solid var(--border-light); border-radius: var(--radius-md); font-size: 0.85rem; color: var(--primary-navy); line-height: 1.45;">
                    <strong>Attached:</strong> {{ $draft['original_name'] ?? 'invoice.pdf' }}
                    @foreach($read['notes'] ?? [] as $note)
                        <div style="margin-top: 0.35rem; color: var(--text-muted);">{{ $note }}</div>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="/company/expenses" enctype="multipart/form-data" id="companyExpenseForm">
                @csrf
                @if($draft)
                    <input type="hidden" name="draft_token" value="{{ $draft['token'] }}">
                @endif

                <fieldset style="border: 1px solid var(--border-light); border-radius: var(--radius-md); padding: 0.9rem 1rem 0.4rem; margin: 0 0 1rem;">
                    <legend style="font-weight: 700; color: var(--primary-navy); font-size: 0.9rem; padding: 0 0.35rem;">Supplier</legend>
                    <div style="display: flex; gap: 1rem; flex-wrap: wrap; margin-bottom: 0.75rem;">
                        <label style="display: flex; align-items: center; gap: 0.4rem; font-size: 0.9rem; cursor: pointer;">
                            <input type="radio" name="supplier_mode" value="existing" id="supplierModeExisting" @checked($supplierMode === 'existing') @disabled($suppliers->isEmpty())>
                            Link existing
                        </label>
                        <label style="display: flex; align-items: center; gap: 0.4rem; font-size: 0.9rem; cursor: pointer;">
                            <input type="radio" name="supplier_mode" value="new" id="supplierModeNew" @checked($supplierMode === 'new')>
                            Create supplier
                        </label>
                    </div>
                    <div id="supplierExistingBox" style="margin-bottom: 0.75rem;">
                        <label style="display: block; font-weight: 600; margin-bottom: 0.4rem; font-size: 0.85rem;">Saved supplier</label>
                        <select name="company_supplier_id" id="companySupplierId" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); background: white;">
                            <option value="">Choose supplier</option>
                            @foreach($suppliers as $supplier)
                                <option value="{{ $supplier->id }}" @selected((string) old('company_supplier_id', $matchedId) === (string) $supplier->id)>
                                    {{ $supplier->name }}@if($supplier->vat_number) · {{ $supplier->vat_number }}@endif
                                </option>
                            @endforeach
                        </select>
                        @if($suppliers->isEmpty())
                            <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.3rem;">No suppliers yet. Create one from this invoice.</div>
                        @endif
                    </div>
                    <div id="supplierNewBox" style="margin-bottom: 0.75rem;">
                        <div id="supplierNewGuide" style="font-size: 0.8rem; color: var(--text-muted); line-height: 1.4; margin-bottom: 0.65rem;">
                            A new supplier is saved with this expense. The next invoice from the same name links here instead of starting a second record.
                        </div>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(12rem, 1fr)); gap: 0.75rem;">
                            <div>
                                <label style="display: block; font-weight: 600; margin-bottom: 0.4rem; font-size: 0.85rem;">Name</label>
                                <input type="text" name="supplier_name" id="supplierName" value="{{ old('supplier_name', $read['supplier_name'] ?? '') }}" placeholder="Google Ireland Limited" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                            </div>
                            <div>
                                <label style="display: block; font-weight: 600; margin-bottom: 0.4rem; font-size: 0.85rem;">VAT number</label>
                                <input type="text" name="supplier_vat" value="{{ old('supplier_vat', $read['vat_number'] ?? '') }}" placeholder="IE6388047V" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                            </div>
                            <div>
                                <label style="display: block; font-weight: 600; margin-bottom: 0.4rem; font-size: 0.85rem;">Country</label>
                                <input type="text" name="supplier_country" value="{{ old('supplier_country', $read['country'] ?? '') }}" placeholder="Ireland" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                            </div>
                            <div>
                                <label style="display: block; font-weight: 600; margin-bottom: 0.4rem; font-size: 0.85rem;">Email</label>
                                <input type="email" name="supplier_email" value="{{ old('supplier_email', $read['email'] ?? '') }}" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                            </div>
                        </div>
                        <div style="margin-top: 0.75rem;">
                            <label style="display: block; font-weight: 600; margin-bottom: 0.4rem; font-size: 0.85rem;">Address</label>
                            <input type="text" name="supplier_address" value="{{ old('supplier_address', $read['address'] ?? '') }}" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                        </div>
                    </div>
                </fieldset>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(12rem, 1fr)); gap: 1rem; margin-bottom: 1rem;">
                    <div>
                        <label style="display: block; font-weight: 600; margin-bottom: 0.4rem; font-size: 0.9rem;">Invoice date</label>
                        <input type="date" name="expense_date" value="{{ old('expense_date', $read['invoice_date'] ?? date('Y-m-d')) }}" max="{{ date('Y-m-d') }}" min="2026-01-01" required style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                    </div>
                    <div>
                        <label style="display: block; font-weight: 600; margin-bottom: 0.4rem; font-size: 0.9rem;">Supplier invoice number</label>
                        <input type="text" name="supplier_invoice_number" value="{{ old('supplier_invoice_number', $read['invoice_number'] ?? '') }}" placeholder="482193" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                    </div>
                </div>
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; font-weight: 600; margin-bottom: 0.4rem; font-size: 0.9rem;">Category</label>
                    <select name="category" id="expenseCategory" required style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); background: white;">
                        @foreach($categories as $key => $label)
                            <option value="{{ $key }}" @selected(old('category', $read['category'] ?? 'software') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <div id="computerGuide" style="display: none; margin-top: 0.65rem; padding: 0.85rem 1rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); background: #f8fafc;">
                        <div style="font-size: 0.8rem; color: var(--primary-navy); line-height: 1.45;">
                            The 25% is wear and tear, separate from VAT. The ex-VAT cost goes on the balance sheet, then a quarter of it is written off each year for four years. The first slice uses the invoice date. The next three use 1 January. VAT you can reclaim is not written off.
                        </div>
                        <div style="display: flex; flex-wrap: wrap; gap: 0.5rem; align-items: end; margin-top: 0.75rem;">
                            <div style="flex: 1; min-width: 9rem;">
                                <label for="computerGross" style="display: block; font-weight: 600; margin-bottom: 0.3rem; font-size: 0.8rem;">Invoice total on the PDF</label>
                                <input type="number" name="computer_invoice_total" id="computerGross" step="0.01" min="0" value="{{ $computerGrossValue }}" placeholder="1553.57" style="width: 100%; padding: 0.55rem 0.65rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                            </div>
                            <div style="flex: 1.4; min-width: 14rem;">
                                <label for="computerTreatment" style="display: block; font-weight: 600; margin-bottom: 0.3rem; font-size: 0.8rem;">How VAT was charged</label>
                                <select name="computer_treatment" id="computerTreatment" style="width: 100%; padding: 0.55rem 0.65rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); background: white;">
                                    <option value="zero" @selected($computerTreatment === 'zero')>0% — supplier charged no VAT (reverse charge)</option>
                                    <option value="de19" @selected($computerTreatment === 'de19')>19% German VAT is inside the total</option>
                                    <option value="mt18" @selected($computerTreatment === 'mt18')>18% Maltese VAT is inside the total</option>
                                    <option value="irrecoverable" @selected($computerTreatment === 'irrecoverable')>VAT was charged and cannot be reclaimed</option>
                                </select>
                            </div>
                        </div>
                        <div id="computerSchedule" style="font-size: 0.78rem; color: var(--text-muted); line-height: 1.45; margin-top: 0.55rem;"></div>
                    </div>
                </div>
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; font-weight: 600; margin-bottom: 0.4rem; font-size: 0.9rem;">Description</label>
                    <input type="text" name="description" value="{{ old('description', $read['description'] ?? '') }}" required placeholder="e.g. Google Workspace Jul 2026" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                </div>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(12rem, 1fr)); gap: 1rem; margin-bottom: 1rem;">
                    <div>
                        <label style="display: block; font-weight: 600; margin-bottom: 0.4rem; font-size: 0.9rem;">Amount ex-VAT (€)</label>
                        <input type="number" name="amount" id="expenseAmount" step="0.01" min="0.01" value="{{ $amountValue }}" required style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                    </div>
                    <div>
                        <label id="expenseVatLabel" style="display: block; font-weight: 600; margin-bottom: 0.4rem; font-size: 0.9rem;">Supplier VAT (€)</label>
                        <input type="number" name="vat_amount" id="expenseVatAmount" step="0.01" min="0" value="{{ $vatValue }}" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                        <div id="vatHint" style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.3rem;">Maltese supplier invoice VAT, if any. Leave €0 for US SaaS with no VAT.</div>
                    </div>
                </div>

                @if($canReverseCharge ?? false)
                    <div style="margin-bottom: 1rem; padding: 0.85rem 1rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); background: #f8fafc;">
                        <label style="display: flex; align-items: flex-start; gap: 0.65rem; cursor: pointer;">
                            <input type="checkbox" name="is_reverse_charge" id="isReverseCharge" value="1" @checked($rcOn) style="margin-top: 0.2rem;">
                            <span>
                                <span style="font-weight: 700; color: var(--primary-navy); font-size: 0.9rem;">Reverse charge (EU / B2B)</span>
                                <span id="rcGuide" style="display: block; font-size: 0.8rem; color: var(--text-muted); line-height: 1.45; margin-top: 0.25rem;">
                                    Tick when the invoice has no Maltese VAT but you must self-assess 18% on the VAT return (e.g. Amazon Business, EU SaaS). Posts matching output + input VAT — net €0, boxes filled.
                                </span>
                            </span>
                        </label>
                        <div id="rcPreview" style="display: none; margin-top: 0.65rem; font-size: 0.82rem; color: var(--primary-navy); line-height: 1.4;">
                            Self-assessed VAT (18%): <strong id="rcVatFigure">€0.00</strong>
                            · cash paid stays at the ex-VAT amount.
                        </div>
                    </div>
                @endif

                <div style="margin-bottom: 1rem;">
                    <label style="display: block; font-weight: 600; margin-bottom: 0.4rem; font-size: 0.9rem;">Who paid?</label>
                    <select name="funded_by" id="fundedBy" required style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); background: white;">
                        <option value="company" @selected(old('funded_by', 'company') === 'company')>Company bank / card (already paid)</option>
                        <option value="director" @selected(old('funded_by', 'company') === 'director')>I paid personally (company owes me a refund)</option>
                        <option value="payable" @selected(old('funded_by', 'company') === 'payable')>Not paid yet (supplier is waiting)</option>
                    </select>
                    <div id="fundedGuide" style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.3rem;">Already paid from the company bank or card. Saving credits the bank on that invoice date.</div>
                </div>
                <div style="margin-bottom: 1.5rem;">
                    <label style="display: block; font-weight: 600; margin-bottom: 0.4rem; font-size: 0.9rem;">{{ $draft ? 'Replace receipt (optional)' : 'Receipt (photo or PDF)' }}</label>
                    <input type="file" name="receipt" accept=".jpg,.jpeg,.png,.pdf,image/*" capture="environment" style="width: 100%; padding: 0.5rem 0;">
                    @if($draft)
                        <div style="font-size: 0.75rem; color: var(--text-muted);">Leave this empty to keep the PDF you just read.</div>
                    @endif
                </div>
                <button type="submit" style="background: var(--primary-cerulean); color: white; border: none; padding: 0.7rem 1.25rem; border-radius: var(--radius-md); font-weight: 700; cursor: pointer;">Save expense</button>
            </form>
        </div>
    </div>

    <script>
        (function () {
            var modeExisting = document.getElementById('supplierModeExisting');
            var modeNew = document.getElementById('supplierModeNew');
            var boxExisting = document.getElementById('supplierExistingBox');
            var boxNew = document.getElementById('supplierNewBox');
            var supplierSelect = document.getElementById('companySupplierId');
            var supplierName = document.getElementById('supplierName');
            var newGuide = document.getElementById('supplierNewGuide');
            var funded = document.getElementById('fundedBy');
            var fundedGuide = document.getElementById('fundedGuide');

            function syncSupplier() {
                var isNew = modeNew && modeNew.checked;
                if (boxExisting) boxExisting.style.display = isNew ? 'none' : 'block';
                if (boxNew) boxNew.style.display = isNew ? 'block' : 'none';
                if (supplierSelect) supplierSelect.required = !isNew;
                if (supplierName) supplierName.required = !!isNew;
                if (newGuide && supplierName && supplierName.value.trim() !== '') {
                    newGuide.textContent = 'Saving creates “' + supplierName.value.trim() + '”. A later invoice with the same name links to this supplier.';
                }
            }

            function syncFunded() {
                if (!funded || !fundedGuide) return;
                if (funded.value === 'company') {
                    fundedGuide.textContent = 'Already paid from the company bank or card. Saving credits the bank on that invoice date.';
                } else if (funded.value === 'payable') {
                    fundedGuide.textContent = 'The supplier issued the invoice and is waiting. The cost is recorded now. The bank moves only when you record the payment and attach proof.';
                } else {
                    fundedGuide.textContent = 'Director-funded costs stay as a loan until you mark them refunded.';
                }
            }

            if (modeExisting) modeExisting.addEventListener('change', syncSupplier);
            if (modeNew) modeNew.addEventListener('change', syncSupplier);
            if (supplierName) supplierName.addEventListener('input', syncSupplier);
            var categoryEl = document.getElementById('expenseCategory');
            var computerGuide = document.getElementById('computerGuide');
            var computerGross = document.getElementById('computerGross');
            var computerTreatment = document.getElementById('computerTreatment');
            var computerSchedule = document.getElementById('computerSchedule');
            var amountElEarly = document.getElementById('expenseAmount');
            var vatElEarly = document.getElementById('expenseVatAmount');
            var reverseChargeRate = {{ \App\Models\CompanyExpense::REVERSE_CHARGE_RATE }};
            var applyingComputer = false;

            function money(n) {
                return (Math.round(n * 100) / 100).toFixed(2);
            }

            function syncComputer() {
                var isComputer = categoryEl && categoryEl.value === 'computer';
                if (computerGuide) computerGuide.style.display = isComputer ? 'block' : 'none';
                if (!isComputer || !computerSchedule || !amountElEarly) return;
                var cost = parseFloat(amountElEarly.value) || 0;
                var gross = parseFloat(computerGross && computerGross.value) || 0;
                var treatment = computerTreatment ? computerTreatment.value : 'zero';
                if (cost <= 0 && gross <= 0) {
                    computerSchedule.textContent = 'Type the invoice total from the PDF. The cost, the VAT, and the 25% write-off follow that total.';
                    return;
                }
                var base = cost > 0 ? cost : gross;
                var annual = Math.round(base / 4 * 100) / 100;
                var last = Math.round((base - annual * 3) * 100) / 100;
                var writeOff = 'The cost €' + money(base) + ' is written off at €' + money(annual) + ' in the invoice year and on the next two 1 January dates, then €' + money(last) + ' in the fourth year.';
                var link = '';
                if (gross > 0 && treatment === 'zero') {
                    var rcVat = Math.round(gross * reverseChargeRate * 100) / 100;
                    link = 'Invoice total €' + money(gross) + ' is the cost. The supplier charged €0 VAT. Malta self-assesses €' + money(rcVat) + ' at 18%. That VAT is not added to the invoice total and is not written off. ';
                } else if (gross > 0 && (treatment === 'de19' || treatment === 'mt18')) {
                    var included = treatment === 'de19' ? 0.19 : 0.18;
                    var net = Math.round(gross / (1 + included) * 100) / 100;
                    var vatPart = Math.round((gross - net) * 100) / 100;
                    var where = treatment === 'de19' ? 'German' : 'Maltese';
                    link = 'Invoice total €' + money(gross) + ' = cost €' + money(net) + ' + ' + where + ' VAT €' + money(vatPart) + '. The VAT can be reclaimed, so it is not written off. ';
                } else if (gross > 0) {
                    link = 'Invoice total €' + money(gross) + ' is the full cost, because the VAT cannot be reclaimed. ';
                }
                computerSchedule.textContent = link + writeOff;
            }

            function applyComputerTreatment() {
                if (applyingComputer || !computerGross || !amountElEarly || !vatElEarly) return;
                var gross = parseFloat(computerGross.value);
                if (!(gross > 0)) {
                    syncComputer();
                    return;
                }
                applyingComputer = true;
                var treatment = computerTreatment ? computerTreatment.value : 'zero';
                var rcBox = document.getElementById('isReverseCharge');
                if (treatment === 'de19' || treatment === 'mt18') {
                    var includedRate = treatment === 'de19' ? 0.19 : 0.18;
                    var netAmount = Math.round(gross / (1 + includedRate) * 100) / 100;
                    if (rcBox) rcBox.checked = false;
                    amountElEarly.value = money(netAmount);
                    vatElEarly.value = money(gross - netAmount);
                } else if (treatment === 'zero') {
                    amountElEarly.value = money(gross);
                    if (rcBox) rcBox.checked = true;
                    else vatElEarly.value = '0.00';
                } else {
                    if (rcBox) rcBox.checked = false;
                    amountElEarly.value = money(gross);
                    vatElEarly.value = '0.00';
                }
                if (rcBox) rcBox.dispatchEvent(new Event('change'));
                amountElEarly.dispatchEvent(new Event('input'));
                applyingComputer = false;
                syncComputer();
            }

            if (categoryEl) categoryEl.addEventListener('change', function () {
                if (categoryEl.value === 'computer' && computerGross && computerGross.value === '' && amountElEarly && parseFloat(amountElEarly.value) > 0) {
                    var rcBox = document.getElementById('isReverseCharge');
                    var currentVat = parseFloat(vatElEarly && vatElEarly.value) || 0;
                    var rcOn = rcBox && rcBox.checked;
                    computerGross.value = money(parseFloat(amountElEarly.value) + (rcOn ? 0 : currentVat));
                    if (computerTreatment) {
                        if (rcOn || currentVat <= 0.009) computerTreatment.value = 'zero';
                        else if (Math.abs(currentVat / parseFloat(amountElEarly.value) - 0.19) < 0.015) computerTreatment.value = 'de19';
                        else if (Math.abs(currentVat / parseFloat(amountElEarly.value) - 0.18) < 0.015) computerTreatment.value = 'mt18';
                        else computerTreatment.value = 'irrecoverable';
                    }
                }
                if (categoryEl.value === 'computer' && computerGross && parseFloat(computerGross.value) > 0) {
                    applyComputerTreatment();
                } else {
                    syncComputer();
                }
            });
            if (amountElEarly) amountElEarly.addEventListener('input', syncComputer);
            if (computerGross) computerGross.addEventListener('input', applyComputerTreatment);
            if (computerTreatment) computerTreatment.addEventListener('change', applyComputerTreatment);
            if (funded) funded.addEventListener('change', syncFunded);
            syncSupplier();
            syncFunded();

            @if($canReverseCharge ?? false)
            var amountEl = document.getElementById('expenseAmount');
            var vatEl = document.getElementById('expenseVatAmount');
            var rcEl = document.getElementById('isReverseCharge');
            var vatLabel = document.getElementById('expenseVatLabel');
            var hint = document.getElementById('vatHint');
            var preview = document.getElementById('rcPreview');
            var figure = document.getElementById('rcVatFigure');
            if (amountEl && vatEl && rcEl) {
                function euro(n) {
                    return '€' + n.toFixed(2);
                }
                function syncRc() {
                    var rc = rcEl.checked;
                    var net = parseFloat(amountEl.value) || 0;
                    var rcVat = Math.round(Math.max(0, net) * reverseChargeRate * 100) / 100;
                    if (rc) {
                        vatEl.value = rcVat.toFixed(2);
                        vatEl.readOnly = true;
                        vatEl.style.background = '#f1f5f9';
                        if (vatLabel) vatLabel.textContent = 'Self-assessed VAT (€)';
                        hint.textContent = 'The supplier charged €0. This 18% is for the Malta VAT return only. It is not added to the invoice total and not paid to the supplier.';
                        preview.style.display = 'block';
                        figure.textContent = euro(rcVat);
                    } else {
                        vatEl.readOnly = false;
                        vatEl.style.background = '';
                        if (vatLabel) vatLabel.textContent = 'Supplier VAT (€)';
                        hint.textContent = 'Maltese supplier invoice VAT, if any. Leave €0 for US SaaS with no VAT.';
                        preview.style.display = 'none';
                    }
                }
                rcEl.addEventListener('change', function () {
                    if (applyingComputer) {
                        syncRc();
                        return;
                    }
                    if (categoryEl && categoryEl.value === 'computer' && computerTreatment && computerGross && parseFloat(computerGross.value) > 0) {
                        if (rcEl.checked) computerTreatment.value = 'zero';
                        else if (computerTreatment.value === 'zero') computerTreatment.value = 'irrecoverable';
                        applyComputerTreatment();
                        return;
                    }
                    syncRc();
                    syncComputer();
                });
                amountEl.addEventListener('input', function () {
                    if (!applyingComputer) syncRc();
                });
                syncRc();
            }
            @endif

            if (categoryEl && categoryEl.value === 'computer' && computerGross && parseFloat(computerGross.value) > 0) {
                applyComputerTreatment();
            } else {
                syncComputer();
            }
        })();
    </script>
@endsection
