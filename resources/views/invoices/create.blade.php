@extends('layouts.app')

@section('page_title', 'New document')

@section('content')
    <style>
        .active-toggle { background: white; box-shadow: var(--shadow-sm); border: 1px solid var(--border-light); color: var(--primary-cerulean); }
        .type-toggle:not(.active-toggle) { color: var(--text-muted); }
    </style>

    <div style="max-width: 800px; margin: 0 auto; background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 2rem; box-shadow: var(--shadow-sm);">
        
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
            <h2 style="color: var(--primary-navy); margin: 0;">New Document</h2>
            <a href="/ledger" style="color: var(--text-muted); text-decoration: none; font-weight: 600; font-size: 0.9rem;">Cancel</a>
        </div>

        @if($user->missingVatNumberForArticle10Documents())
            <div style="margin-bottom: 1.5rem; padding: 0.85rem 1rem; background: #fffbeb; border: 1px solid #fef3c7; border-left: 4px solid #f59e0b; border-radius: var(--radius-md); color: #92400e; font-size: 0.9rem; line-height: 1.45;">
                You are on Article 10 without a VAT number.
                <a href="/settings" style="color: #92400e; font-weight: 700;">Add it in Settings</a>
                before creating a tax invoice or charging 18% VAT. RFPs can still be saved without it.
            </div>
        @endif

        @if($errors->has('vat_number'))
            <div style="margin-bottom: 1.5rem; padding: 0.85rem 1rem; background: #fef2f2; border: 1px solid #fecaca; border-left: 4px solid #ef4444; border-radius: var(--radius-md); color: #991b1b; font-size: 0.9rem; line-height: 1.45;">
                {{ $errors->first('vat_number') }}
                <a href="/settings" style="color: #991b1b; font-weight: 700;">Open Settings</a>
            </div>
        @endif

        <form action="/ledger" method="POST" id="invoiceForm">
            @csrf

            <div style="display: flex; gap: 1rem; margin-bottom: 2rem; background: #f8fafc; padding: 0.5rem; border-radius: var(--radius-md); border: 1px solid var(--border-light);">
                <label style="flex: 1; text-align: center; padding: 0.75rem; border-radius: 6px; cursor: pointer; font-weight: 600; transition: 0.2s;" id="lbl_invoice" class="type-toggle active-toggle">
                    <input type="radio" name="type" value="invoice" checked onchange="toggleDocType('invoice')" style="display: none;">
                    📄 Tax Invoice
                </label>
                <label style="flex: 1; text-align: center; padding: 0.75rem; border-radius: 6px; cursor: pointer; font-weight: 600; transition: 0.2s;" id="lbl_rfp" class="type-toggle">
                    <input type="radio" name="type" value="rfp" onchange="toggleDocType('rfp')" style="display: none;">
                    📨 Request for Payment
                </label>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
                <div>
                    <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; font-size: 0.9rem;">Billed To</label>
                    <select name="client_id" required style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); background: white;">
                        <option value="">Select a Client...</option>
                        @foreach($clients as $client)
                            <option value="{{ $client->id }}">{{ $client->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; font-size: 0.9rem;">Issue Date</label>
                    <input type="date" name="issue_date" value="{{ date('Y-m-d') }}" max="{{ date('Y-m-d') }}" required style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                </div>
                <div>
                    <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; font-size: 0.9rem;">Due Date</label>
                    <input type="date" name="due_date" value="{{ date('Y-m-d', strtotime('+30 days')) }}" required style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                </div>
            </div>

            <hr style="border: none; border-top: 1px solid var(--border-light); margin-bottom: 2rem;">

            <h3 style="color: var(--primary-navy); font-size: 1.1rem; margin-bottom: 1rem;">Line Items</h3>
            
            <div id="itemsContainer">
                <div class="item-row" style="display: grid; grid-template-columns: 3fr 1fr 1fr auto; gap: 1rem; align-items: start; margin-bottom: 1rem; padding-bottom: 1rem; border-bottom: 1px dashed var(--border-light);">
                    <div>
                        <input type="text" name="item_desc[]" placeholder="Description of service..." required style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                    </div>
                    <div>
                        <input type="number" name="item_qty[]" placeholder="Qty" value="1" step="0.01" min="0.01" required oninput="calculateTotals()" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                    </div>
                    <div>
                        <input type="number" name="item_price[]" placeholder="Price (€)" step="0.01" min="0" required oninput="calculateTotals()" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                    </div>
                    <div>
                        <button type="button" onclick="removeRow(this)" style="padding: 0.75rem; background: #fee2e2; color: #ef4444; border: none; border-radius: var(--radius-md); cursor: pointer; font-weight: bold; width: 100%;">X</button>
                    </div>
                </div>
            </div>

            <button type="button" onclick="addRow()" style="background: transparent; color: var(--primary-cerulean); border: 2px dashed var(--primary-cerulean); padding: 0.75rem; width: 100%; border-radius: var(--radius-md); font-weight: 600; cursor: pointer; margin-bottom: 2rem;">
                + Add Another Item
            </button>

            <div style="background: #f8fafc; padding: 1.5rem; border-radius: var(--radius-md); border: 1px solid var(--border-light); display: flex; flex-wrap: wrap; justify-content: space-between; align-items: flex-start; gap: 2rem;">
                
                <div style="flex: 1; min-width: 250px;">
                    <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; font-size: 0.9rem;">Document Notes (Optional)</label>
                    <textarea name="notes" rows="3" placeholder="Thank you for your business..." style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); resize: vertical;"></textarea>
                    
                    @if($user->vat_status === 'article_10')
                        <label style="display: flex; align-items: center; gap: 0.5rem; margin-top: 1rem; font-weight: 600; cursor: pointer;" id="vatLabelContainer">
                            <input type="checkbox" name="apply_vat" id="vatToggle" value="1" onchange="calculateTotals()" style="width: 1.2rem; height: 1.2rem;">
                            Add 18% Standard VAT
                        </label>
                    @else
                        <div style="margin-top: 1rem; padding: 0.75rem; background: #f1f5f9; border-left: 3px solid #94a3b8; font-size: 0.85rem; color: #475569; border-radius: 0 4px 4px 0;">
                            <strong>VAT Exempt:</strong> As an {{ $user->vat_status === 'article_11' ? 'Article 11' : 'Exempt' }} profile, you do not charge VAT.
                            <input type="hidden" id="vatToggle" value="0"> 
                        </div>
                    @endif
                </div>

                <div style="min-width: 200px; text-align: right;">
                    <div style="color: var(--text-muted); margin-bottom: 0.5rem;">Subtotal: <span id="displaySubtotal" style="font-weight: 600; color: var(--text-main);">€0.00</span></div>
                    <div style="color: var(--text-muted); margin-bottom: 1rem;">VAT: <span id="displayVat" style="font-weight: 600; color: var(--text-main);">€0.00</span></div>
                    
                    <div style="font-size: 1.5rem; color: var(--primary-navy); font-weight: 700; margin-bottom: 1.5rem; border-top: 1px solid var(--border-light); padding-top: 1rem;">
                        Total: <span id="displayTotal">€0.00</span>
                    </div>

                    <button type="submit" style="width: 100%; padding: 1rem; background: var(--primary-cerulean); color: white; border: none; border-radius: var(--radius-md); font-weight: 700; font-size: 1.05rem; cursor: pointer; box-shadow: 0 4px 12px rgba(2, 132, 199, 0.3);">
                        Save Document
                    </button>
                </div>

            </div>
        </form>
    </div>

    <script>
        // 1. Create the banner element as soon as the page loads
        document.addEventListener('DOMContentLoaded', function() {
            const pageTitle = document.querySelector('h2');
            if (!pageTitle) return;

            const headerContainer = pageTitle.parentElement;
            const banner = document.createElement('div');
            banner.id = 'typeBanner';
            banner.style.cssText = "display: none; padding: 1rem; border-radius: 8px; margin-top: 1.5rem; font-size: 0.95rem; transition: all 0.2s ease-in-out; box-shadow: var(--shadow-sm);";
            
            // Inject the banner right below the title header
            headerContainer.insertAdjacentElement('afterend', banner);
            
            // Run the UI update immediately to set the default blue state
            const checkedType = document.querySelector('input[name="type"]:checked');
            if (checkedType) {
                updateUX(checkedType.value);
            }
        });

        // 2. Trigger the UX update whenever the user clicks the toggle
        function toggleDocType(type) {
            document.getElementById('lbl_invoice').classList.remove('active-toggle');
            document.getElementById('lbl_rfp').classList.remove('active-toggle');
            document.getElementById('lbl_' + type).classList.add('active-toggle');
            
            updateUX(type); // Fire the color change!
        }

        // 3. The function that violently shifts the colors
        function updateUX(type) {
            const pageTitle = document.querySelector('h2'); 
            const submitBtn = document.querySelector('button[type="submit"]');
            const banner = document.getElementById('typeBanner');

            if (type === 'rfp') {
                // --- RFP MODE (PURPLE) ---
                if (pageTitle) pageTitle.innerHTML = 'New <span style="color: #4f46e5;">Request for Payment</span>';
                
                if (banner) {
                    banner.style.display = 'block';
                    banner.style.backgroundColor = '#eef2ff';
                    banner.style.color = '#3730a3';
                    banner.style.borderLeft = '4px solid #4f46e5';
                    banner.innerHTML = '<strong>💡 Pro-Forma Mode Active:</strong> This is an unofficial holding document. It will not impact your fiscal Tax or VAT reports until you convert it into a Tax Invoice.';
                }

                if (submitBtn) {
                    submitBtn.style.backgroundColor = '#4f46e5';
                    submitBtn.innerText = 'Generate Master RFP';
                }
            } else {
                // --- TAX INVOICE MODE (BLUE) ---
                if (pageTitle) pageTitle.innerHTML = 'New <span style="color: #0369a1;">Tax Invoice</span>';
                
                if (banner) {
                    banner.style.display = 'block';
                    banner.style.backgroundColor = '#f0f9ff';
                    banner.style.color = '#075985';
                    banner.style.borderLeft = '4px solid #0284c7';
                    banner.innerHTML = '<strong>📄 Official Fiscal Mode:</strong> Generating this document will permanently record it in your ledger and count towards your VAT/Tax thresholds.';
                }

                if (submitBtn) {
                    submitBtn.style.backgroundColor = 'var(--primary-cerulean, #0284c7)';
                    submitBtn.innerText = 'Generate Tax Invoice';
                }
            }
        }

        // Existing Math Functions
        function addRow() {
            const container = document.getElementById('itemsContainer');
            const rowHtml = `
                <div class="item-row" style="display: grid; grid-template-columns: 3fr 1fr 1fr auto; gap: 1rem; align-items: start; margin-bottom: 1rem; padding-bottom: 1rem; border-bottom: 1px dashed var(--border-light);">
                    <div>
                        <input type="text" name="item_desc[]" placeholder="Description of service..." required style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                    </div>
                    <div>
                        <input type="number" name="item_qty[]" placeholder="Qty" value="1" step="0.01" min="0.01" required oninput="calculateTotals()" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                    </div>
                    <div>
                        <input type="number" name="item_price[]" placeholder="Price (€)" step="0.01" min="0" required oninput="calculateTotals()" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                    </div>
                    <div>
                        <button type="button" onclick="removeRow(this)" style="padding: 0.75rem; background: #fee2e2; color: #ef4444; border: none; border-radius: var(--radius-md); cursor: pointer; font-weight: bold; width: 100%;">X</button>
                    </div>
                </div>
            `;
            container.insertAdjacentHTML('beforeend', rowHtml);
        }

        function removeRow(btn) {
            const rows = document.querySelectorAll('.item-row');
            if(rows.length > 1) {
                btn.closest('.item-row').remove();
                calculateTotals();
            } else {
                alert('You must have at least one line item.');
            }
        }

        function calculateTotals() {
            let subtotal = 0;
            const rows = document.querySelectorAll('.item-row');
            
            rows.forEach(row => {
                const qty = parseFloat(row.querySelector('input[name="item_qty[]"]').value) || 0;
                const price = parseFloat(row.querySelector('input[name="item_price[]"]').value) || 0;
                subtotal += (qty * price);
            });

            const vatElement = document.getElementById('vatToggle');
            const applyVat = vatElement && vatElement.type === 'checkbox' ? vatElement.checked : false;
            const vat = applyVat ? (subtotal * 0.18) : 0;
            const total = subtotal + vat;

            document.getElementById('displaySubtotal').innerText = '€' + subtotal.toFixed(2);
            document.getElementById('displayVat').innerText = '€' + vat.toFixed(2);
            document.getElementById('displayTotal').innerText = '€' + total.toFixed(2);
        }
    </script>
@endsection