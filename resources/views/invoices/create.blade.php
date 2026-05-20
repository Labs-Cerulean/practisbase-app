@extends('layouts.app')

@section('page_title', 'Create Invoice')

@section('content')
    <div style="max-width: 800px; margin: 0 auto; background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 2rem; box-shadow: var(--shadow-sm);">
        
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
            <h2 style="color: var(--primary-navy); margin: 0;">New Invoice</h2>
            <a href="/ledger" style="color: var(--text-muted); text-decoration: none; font-weight: 600; font-size: 0.9rem;">Cancel</a>
        </div>

        <form action="/ledger" method="POST" id="invoiceForm">
            @csrf

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
                    <input type="date" name="issue_date" value="{{ date('Y-m-d') }}" required style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
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
                    <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; font-size: 0.9rem;">Invoice Notes (Optional)</label>
                    <textarea name="notes" rows="3" placeholder="Thank you for your business..." style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); resize: vertical;"></textarea>
                    
                    <label style="display: flex; align-items: center; gap: 0.5rem; margin-top: 1rem; font-weight: 600; cursor: pointer;">
                        <input type="checkbox" name="apply_vat" id="vatToggle" value="1" onchange="calculateTotals()" style="width: 1.2rem; height: 1.2rem;">
                        Add 18% VAT
                    </label>
                </div>

                <div style="min-width: 200px; text-align: right;">
                    <div style="color: var(--text-muted); margin-bottom: 0.5rem;">Subtotal: <span id="displaySubtotal" style="font-weight: 600; color: var(--text-main);">€0.00</span></div>
                    <div style="color: var(--text-muted); margin-bottom: 1rem;">VAT (18%): <span id="displayVat" style="font-weight: 600; color: var(--text-main);">€0.00</span></div>
                    
                    <div style="font-size: 1.5rem; color: var(--primary-navy); font-weight: 700; margin-bottom: 1.5rem; border-top: 1px solid var(--border-light); padding-top: 1rem;">
                        Total: <span id="displayTotal">€0.00</span>
                    </div>

                    <button type="submit" style="width: 100%; padding: 1rem; background: var(--primary-cerulean); color: white; border: none; border-radius: var(--radius-md); font-weight: 700; font-size: 1.05rem; cursor: pointer; box-shadow: 0 4px 12px rgba(2, 132, 199, 0.3);">
                        Save Invoice
                    </button>
                </div>

            </div>
        </form>
    </div>

    <script>
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

            const applyVat = document.getElementById('vatToggle').checked;
            const vat = applyVat ? (subtotal * 0.18) : 0;
            const total = subtotal + vat;

            // Update UI
            document.getElementById('displaySubtotal').innerText = '€' + subtotal.toFixed(2);
            document.getElementById('displayVat').innerText = '€' + vat.toFixed(2);
            document.getElementById('displayTotal').innerText = '€' + total.toFixed(2);
        }
    </script>
@endsection