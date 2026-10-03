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
                        <label style="display: block; font-weight: 600; margin-bottom: 0.4rem; font-size: 0.9rem;">Supplier VAT (€)</label>
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
                        <option value="director" @selected(old('funded_by', 'director') === 'director')>I paid personally (company owes me a refund)</option>
                        <option value="company" @selected(old('funded_by') === 'company')>Company bank / card (already paid)</option>
                        <option value="payable" @selected(old('funded_by') === 'payable')>Not paid yet (supplier is waiting)</option>
                    </select>
                    <div id="fundedGuide" style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.3rem;">Director-funded costs stay as a loan until you mark them refunded.</div>
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
            if (funded) funded.addEventListener('change', syncFunded);
            syncSupplier();
            syncFunded();

            @if($canReverseCharge ?? false)
            var rate = {{ \App\Models\CompanyExpense::REVERSE_CHARGE_RATE }};
            var amountEl = document.getElementById('expenseAmount');
            var vatEl = document.getElementById('expenseVatAmount');
            var rcEl = document.getElementById('isReverseCharge');
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
                    var rcVat = Math.round(Math.max(0, net) * rate * 100) / 100;
                    if (rc) {
                        vatEl.value = rcVat.toFixed(2);
                        vatEl.readOnly = true;
                        vatEl.style.background = '#f1f5f9';
                        hint.textContent = 'Supplier VAT stays €0 — self-assessed 18% is stored for the VAT return.';
                        preview.style.display = 'block';
                        figure.textContent = euro(rcVat);
                    } else {
                        vatEl.readOnly = false;
                        vatEl.style.background = '';
                        hint.textContent = 'Maltese supplier invoice VAT, if any. Leave €0 for US SaaS with no VAT.';
                        preview.style.display = 'none';
                    }
                }
                rcEl.addEventListener('change', syncRc);
                amountEl.addEventListener('input', syncRc);
                syncRc();
            }
            @endif
        })();
    </script>
@endsection
