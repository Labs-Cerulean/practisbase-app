@extends('layouts.app')

@section('page_title', 'New company document')

@section('content')
    <div style="max-width: 800px; margin: 0 auto; background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 2rem; box-shadow: var(--shadow-sm);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
            <h1 style="font-size: 1.3rem; color: var(--primary-navy); margin: 0;">New company document</h1>
            <a href="/company/invoices" style="color: var(--text-muted); font-weight: 600; font-size: 0.85rem; text-decoration: none;">Cancel</a>
        </div>

        @if(! $profile->hasVatNumber())
            <div style="margin-bottom: 1.25rem; padding: 0.85rem 1rem; background: #fffbeb; border: 1px solid #fef3c7; border-left: 4px solid #f59e0b; border-radius: var(--radius-md); color: #92400e; font-size: 0.9rem; line-height: 1.45;">
                Company VAT number not set yet. RFPs are fine.
                <a href="/company/profile" style="color: #92400e; font-weight: 700;">Add VAT number</a>
                before tax invoices or charging 18%.
            </div>
        @endif

        @if($errors->any())
            <div style="margin-bottom: 1.25rem; padding: 0.85rem 1rem; background: #fef2f2; border: 1px solid #fecaca; border-radius: var(--radius-md); color: #991b1b; font-size: 0.9rem;">
                {{ $errors->first() }}
            </div>
        @endif

        <form action="/company/invoices" method="POST" id="companyInvoiceForm">
            @csrf

            <div style="display: flex; gap: 0.5rem; margin-bottom: 1.5rem; background: #f8fafc; padding: 0.4rem; border-radius: var(--radius-md); border: 1px solid var(--border-light);">
                <label style="flex: 1; text-align: center; padding: 0.7rem; border-radius: 6px; cursor: pointer; font-weight: 600; background: white; border: 1px solid var(--border-light); color: var(--primary-cerulean);" id="lbl_invoice">
                    <input type="radio" name="type" value="invoice" checked onchange="syncType()" style="display: none;"> Tax invoice
                </label>
                <label style="flex: 1; text-align: center; padding: 0.7rem; border-radius: 6px; cursor: pointer; font-weight: 600; color: var(--text-muted);" id="lbl_rfp">
                    <input type="radio" name="type" value="rfp" onchange="syncType()" style="display: none;"> RFP
                </label>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem; margin-bottom: 1.5rem;">
                <div>
                    <label style="display: block; font-weight: 600; margin-bottom: 0.4rem; font-size: 0.9rem;">Billed to</label>
                    <select name="company_client_id" required style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); background: white;">
                        <option value="">Select…</option>
                        @foreach($clients as $client)
                            <option value="{{ $client->id }}" @selected(old('company_client_id') == $client->id)>{{ $client->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label style="display: block; font-weight: 600; margin-bottom: 0.4rem; font-size: 0.9rem;">Issue date</label>
                    <input type="date" name="issue_date" value="{{ old('issue_date', date('Y-m-d')) }}" max="{{ date('Y-m-d') }}" min="{{ $profile->first_period_start->format('Y-m-d') }}" required style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                </div>
                <div>
                    <label style="display: block; font-weight: 600; margin-bottom: 0.4rem; font-size: 0.9rem;">Due date</label>
                    <input type="date" name="due_date" value="{{ old('due_date', date('Y-m-d', strtotime('+30 days'))) }}" required style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                </div>
            </div>

            <h3 style="color: var(--primary-navy); font-size: 1.05rem; margin-bottom: 0.75rem;">Line items (ex-VAT)</h3>
            <div id="itemsContainer">
                <div class="item-row" style="display: grid; grid-template-columns: 3fr 1fr 1fr auto; gap: 0.75rem; margin-bottom: 0.75rem;">
                    <input type="text" name="item_desc[]" placeholder="Description" required style="padding: 0.7rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                    <input type="number" name="item_qty[]" value="1" step="0.01" min="0.01" required oninput="calc()" style="padding: 0.7rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                    <input type="number" name="item_price[]" placeholder="€" step="0.01" min="0" required oninput="calc()" style="padding: 0.7rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                    <button type="button" onclick="this.closest('.item-row').remove(); calc();" style="padding: 0.7rem; background: #fee2e2; color: #ef4444; border: none; border-radius: var(--radius-md); cursor: pointer; font-weight: bold;">X</button>
                </div>
            </div>
            <button type="button" onclick="addRow()" style="background: transparent; color: var(--primary-cerulean); border: 2px dashed var(--primary-cerulean); padding: 0.65rem; width: 100%; border-radius: var(--radius-md); font-weight: 600; cursor: pointer; margin-bottom: 1.5rem;">+ Line</button>

            <div style="background: #f8fafc; padding: 1.25rem; border-radius: var(--radius-md); border: 1px solid var(--border-light); margin-bottom: 1.25rem;">
                <label style="display: flex; align-items: center; gap: 0.5rem; font-weight: 600; cursor: pointer; margin-bottom: 0.75rem;">
                    <input type="checkbox" name="apply_vat" id="vatToggle" value="1" checked onchange="calc()" style="width: 1.15rem; height: 1.15rem;">
                    Charge 18% VAT (Article 10)
                </label>
                <textarea name="notes" rows="2" placeholder="Notes…" style="width: 100%; padding: 0.7rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); margin-bottom: 0.75rem;">{{ old('notes') }}</textarea>
                <div style="display: flex; justify-content: flex-end; gap: 1.5rem; font-size: 0.95rem;">
                    <div>Subtotal <strong id="subEl">€0.00</strong></div>
                    <div>VAT <strong id="vatEl">€0.00</strong></div>
                    <div>Total <strong id="totEl">€0.00</strong></div>
                </div>
            </div>

            <button type="submit" style="background: var(--primary-cerulean); color: white; border: none; padding: 0.75rem 1.35rem; border-radius: var(--radius-md); font-weight: 700; cursor: pointer;">Create document</button>
        </form>
    </div>

    <script>
        function syncType() {
            var isInv = document.querySelector('input[name="type"]:checked').value === 'invoice';
            document.getElementById('lbl_invoice').style.background = isInv ? 'white' : 'transparent';
            document.getElementById('lbl_invoice').style.border = isInv ? '1px solid var(--border-light)' : 'none';
            document.getElementById('lbl_invoice').style.color = isInv ? 'var(--primary-cerulean)' : 'var(--text-muted)';
            document.getElementById('lbl_rfp').style.background = !isInv ? 'white' : 'transparent';
            document.getElementById('lbl_rfp').style.border = !isInv ? '1px solid var(--border-light)' : 'none';
            document.getElementById('lbl_rfp').style.color = !isInv ? 'var(--primary-cerulean)' : 'var(--text-muted)';
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
        syncType();
        calc();
    </script>
@endsection
