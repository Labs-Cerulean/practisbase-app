@extends('layouts.app')

@section('page_title', 'New proforma (RFP)')

@section('content')
    <div style="max-width: 800px; margin: 0 auto; background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 2rem; box-shadow: var(--shadow-sm);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
            <h1 style="font-size: 1.3rem; color: var(--primary-navy); margin: 0;">New proforma (RFP)</h1>
            <a href="/company/invoices" style="color: var(--text-muted); font-weight: 600; font-size: 0.85rem; text-decoration: none;">Cancel</a>
        </div>

        <div style="margin-bottom: 1.25rem; padding: 0.85rem 1rem; background: #eff6ff; border: 1px solid #bfdbfe; border-left: 4px solid var(--primary-cerulean); border-radius: var(--radius-md); color: #1e3a8a; font-size: 0.9rem; line-height: 1.45;">
            Cerulean billing is <strong>proforma until paid</strong>. This RFP has €0 fiscal weight and does not commit output VAT.
            When the client pays in full, it auto-converts to a tax invoice (or convert manually earlier if needed).
        </div>

        @if(! $profile->hasVatNumber())
            <div style="margin-bottom: 1.25rem; padding: 0.85rem 1rem; background: #fffbeb; border: 1px solid #fef3c7; border-left: 4px solid #f59e0b; border-radius: var(--radius-md); color: #92400e; font-size: 0.9rem; line-height: 1.45;">
                Company VAT number not set yet. Proformas are fine.
                <a href="/company/profile" style="color: #92400e; font-weight: 700;">Add VAT number</a>
                before conversion can post 18% output VAT.
            </div>
        @endif

        @if($errors->any())
            <div style="margin-bottom: 1.25rem; padding: 0.85rem 1rem; background: #fef2f2; border: 1px solid #fecaca; border-radius: var(--radius-md); color: #991b1b; font-size: 0.9rem;">
                {{ $errors->first() }}
            </div>
        @endif

        <form action="/company/invoices" method="POST" id="companyInvoiceForm">
            @csrf
            <input type="hidden" name="type" value="rfp">

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: 1rem; margin-bottom: 0.75rem;">
                <div>
                    <label style="display: block; font-weight: 600; margin-bottom: 0.4rem; font-size: 0.9rem;">Billed to</label>
                    <select name="company_client_id" id="clientSelect" required onchange="checkClient()" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); background: white;">
                        <option value="">Select…</option>
                        @foreach($clients as $client)
                            <option value="{{ $client->id }}" @selected(old('company_client_id') == $client->id)>{{ $client->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label style="display: block; font-weight: 600; margin-bottom: 0.4rem; font-size: 0.9rem;">Issue date</label>
                    <input type="date" name="issue_date" id="issueDate" value="{{ old('issue_date', date('Y-m-d')) }}" max="{{ date('Y-m-d') }}" min="{{ $profile->first_period_start->format('Y-m-d') }}" required onchange="if (!document.getElementById('supplyTouched').value) { document.getElementById('supplyDate').value = this.value; }" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                </div>
                <div>
                    <label style="display: block; font-weight: 600; margin-bottom: 0.4rem; font-size: 0.9rem;">Supply date</label>
                    <input type="hidden" id="supplyTouched" value="{{ old('supply_date') ? '1' : '' }}">
                    <input type="date" name="supply_date" id="supplyDate" value="{{ old('supply_date', old('issue_date', date('Y-m-d'))) }}" max="{{ date('Y-m-d') }}" min="{{ $profile->first_period_start->format('Y-m-d') }}" required onchange="document.getElementById('supplyTouched').value='1'" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                    <div style="font-size: 0.72rem; color: var(--text-muted); margin-top: 0.3rem;">Used on the tax invoice at conversion.</div>
                </div>
                <div>
                    <label style="display: block; font-weight: 600; margin-bottom: 0.4rem; font-size: 0.9rem;">Due date</label>
                    <input type="date" name="due_date" value="{{ old('due_date', date('Y-m-d', strtotime('+30 days'))) }}" required style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                </div>
            </div>

            <div id="clientWarning" style="display: none; margin-bottom: 1.25rem; padding: 0.75rem 1rem; background: #fff7ed; border: 1px solid #fed7aa; border-radius: var(--radius-md); color: #9a3412; font-size: 0.85rem; line-height: 1.45;"></div>

            <h3 style="color: var(--primary-navy); font-size: 1.05rem; margin-bottom: 0.75rem;">Line items (ex-VAT, EUR)</h3>
            <div id="itemsContainer">
                <div class="item-row" style="display: grid; grid-template-columns: 3fr 1fr 1fr auto; gap: 0.75rem; margin-bottom: 0.75rem;">
                    <input type="text" name="item_desc[]" placeholder="Description of service" required style="padding: 0.7rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                    <input type="number" name="item_qty[]" value="1" step="0.01" min="0.01" required oninput="calc()" style="padding: 0.7rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                    <input type="number" name="item_price[]" placeholder="€" step="0.01" min="0" required oninput="calc()" style="padding: 0.7rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                    <button type="button" onclick="this.closest('.item-row').remove(); calc();" style="padding: 0.7rem; background: #fee2e2; color: #ef4444; border: none; border-radius: var(--radius-md); cursor: pointer; font-weight: bold;">X</button>
                </div>
            </div>
            <button type="button" onclick="addRow()" style="background: transparent; color: var(--primary-cerulean); border: 2px dashed var(--primary-cerulean); padding: 0.65rem; width: 100%; border-radius: var(--radius-md); font-weight: 600; cursor: pointer; margin-bottom: 1.5rem;">+ Line</button>

            <div style="background: #f8fafc; padding: 1.25rem; border-radius: var(--radius-md); border: 1px solid var(--border-light); margin-bottom: 1.25rem;">
                <label style="display: flex; align-items: center; gap: 0.5rem; font-weight: 600; cursor: pointer; margin-bottom: 0.35rem;">
                    <input type="checkbox" name="apply_vat" id="vatToggle" value="1" checked onchange="calc()" style="width: 1.15rem; height: 1.15rem;">
                    Show 18% VAT estimate on this proforma
                </label>
                <div style="font-size: 0.75rem; color: var(--text-muted); margin-bottom: 0.75rem; line-height: 1.4;">Estimate only — VAT posts to the ledger when the RFP converts to a tax invoice.</div>
                <textarea name="notes" rows="2" placeholder="Notes…" style="width: 100%; padding: 0.7rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); margin-bottom: 0.75rem;">{{ old('notes') }}</textarea>
                <div style="display: flex; justify-content: flex-end; gap: 1.5rem; font-size: 0.95rem; flex-wrap: wrap;">
                    <div>Taxable amount <strong id="subEl">€0.00</strong></div>
                    <div>VAT 18% <strong id="vatEl">€0.00</strong></div>
                    <div>Total EUR <strong id="totEl">€0.00</strong></div>
                </div>
            </div>

            <button type="submit" style="background: var(--primary-cerulean); color: white; border: none; padding: 0.75rem 1.35rem; border-radius: var(--radius-md); font-weight: 700; cursor: pointer;">Create proforma</button>
        </form>
    </div>

    <script>
        var clientMeta = @json($clientMeta);

        function checkClient() {
            var warn = document.getElementById('clientWarning');
            var id = document.getElementById('clientSelect').value;
            if (!id || !clientMeta[id]) {
                warn.style.display = 'none';
                return;
            }
            var meta = clientMeta[id];
            var missing = [];
            if (!meta.has_address) missing.push('billing address');
            if (!meta.has_vat) missing.push('VAT number');
            if (missing.length) {
                warn.style.display = 'block';
                warn.innerHTML = 'This client is missing ' + missing.join(' and ') + '. Fine for a proforma; required before converting to a tax invoice on payment.';
            } else {
                warn.style.display = 'none';
            }
        }
        function addRow() {
            var row = document.querySelector('.item-row').cloneNode(true);
            row.querySelectorAll('input').forEach(function (el) {
                if (el.name === 'item_qty[]') el.value = '1';
                else el.value = '';
            });
            document.getElementById('itemsContainer').appendChild(row);
        }
        function calc() {
            var descs = document.querySelectorAll('input[name="item_desc[]"]');
            var qtys = document.querySelectorAll('input[name="item_qty[]"]');
            var prices = document.querySelectorAll('input[name="item_price[]"]');
            var sub = 0;
            for (var i = 0; i < descs.length; i++) {
                sub += (parseFloat(qtys[i].value) || 0) * (parseFloat(prices[i].value) || 0);
            }
            var vatOn = document.getElementById('vatToggle').checked;
            var vat = vatOn ? sub * 0.18 : 0;
            document.getElementById('subEl').textContent = '€' + sub.toFixed(2);
            document.getElementById('vatEl').textContent = '€' + vat.toFixed(2);
            document.getElementById('totEl').textContent = '€' + (sub + vat).toFixed(2);
        }
        calc();
        checkClient();
    </script>
@endsection
