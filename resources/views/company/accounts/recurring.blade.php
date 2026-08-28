@extends('layouts.app')

@section('page_title', 'Monthly billing')

@section('content')
    @php
        $showCreate = $openCreate || $errors->any();
    @endphp

    <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.25rem;">
        <div>
            <h1 style="font-size: 1.4rem; color: var(--primary-navy); margin: 0 0 0.25rem;">Monthly billing</h1>
            <p style="margin: 0; color: var(--text-muted); font-size: 0.9rem; max-width: 34rem; line-height: 1.45;">
                Estate Hub schedules · issued proformas listed below · tax invoices after payment.
            </p>
        </div>
        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
            <a href="/company/invoices" style="background: white; color: var(--primary-navy); border: 1px solid var(--border-light); padding: 0.55rem 1rem; border-radius: var(--radius-md); font-weight: 600; font-size: 0.85rem; text-decoration: none;">All invoices &amp; RFPs</a>
            <form method="POST" action="/company/recurring/generate">
                @csrf
                <button type="submit" style="background: var(--primary-cerulean); color: white; border: none; padding: 0.55rem 1rem; border-radius: var(--radius-md); font-weight: 600; font-size: 0.85rem; cursor: pointer;">
                    {{ $dueCatchUpCount > 0 ? 'Catch up due months ('.$dueCatchUpCount.')' : 'Generate due proformas' }}
                </button>
            </form>
            <button type="button" id="btnNewSchedule" onclick="toggleCreateForm(true)" style="background: var(--primary-navy); color: white; border: none; padding: 0.55rem 1rem; border-radius: var(--radius-md); font-weight: 600; font-size: 0.85rem; cursor: pointer; {{ $showCreate ? 'display:none;' : '' }}">+ New schedule</button>
        </div>
    </div>

    @if(session('success'))
        <div style="background: #ecfdf5; border: 1px solid #a7f3d0; color: #065f46; border-radius: var(--radius-lg); padding: 0.85rem 1.1rem; margin-bottom: 1rem; font-size: 0.9rem;">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div style="background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; border-radius: var(--radius-lg); padding: 0.85rem 1.1rem; margin-bottom: 1rem; font-size: 0.9rem;">
            <ul style="margin: 0; padding-left: 1.1rem;">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if($dueCatchUpCount > 0)
        <div style="background: #fffbeb; border: 1px solid #fde68a; border-radius: var(--radius-lg); padding: 0.85rem 1.1rem; margin-bottom: 1rem; font-size: 0.88rem; color: #92400e; line-height: 1.45;">
            {{ $dueCatchUpCount }} schedule(s) have months waiting. Press <strong>Catch up due months</strong> to issue July / August (and any other gaps) in one go.
        </div>
    @endif

    @if($clients->isEmpty())
        <div style="background: #fffbeb; border: 1px solid #fde68a; border-radius: var(--radius-lg); padding: 1.1rem 1.25rem; margin-bottom: 1.25rem; color: #92400e; font-size: 0.9rem; line-height: 1.45;">
            Add a company client first.
            <a href="/company/clients/create" style="color: #92400e; font-weight: 700;">Create client</a>
        </div>
    @endif

    <div id="createPanel" style="{{ $showCreate ? '' : 'display:none;' }} background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.25rem 1.4rem; box-shadow: var(--shadow-sm); margin-bottom: 1.5rem;">
        <div style="display: flex; justify-content: space-between; align-items: center; gap: 1rem; margin-bottom: 0.85rem;">
            <div>
                <div style="font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.04em;">New Estate Hub schedule</div>
                <p style="margin: 0.25rem 0 0; font-size: 0.82rem; color: var(--text-muted);">Client → hubs → rates → start date → confirm</p>
            </div>
            <button type="button" onclick="toggleCreateForm(false)" style="background: white; color: var(--text-muted); border: 1px solid var(--border-light); padding: 0.4rem 0.75rem; border-radius: var(--radius-md); font-weight: 600; font-size: 0.8rem; cursor: pointer;">Close</button>
        </div>

        <form method="POST" action="/company/recurring" id="estate-hub-form">
            @csrf

            <div style="margin-bottom: 1.1rem;">
                <label style="display: block; font-size: 0.75rem; font-weight: 600; color: var(--text-muted); margin-bottom: 0.25rem;">Client</label>
                <select name="company_client_id" required style="width: 100%; max-width: 28rem; padding: 0.55rem 0.65rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); background: white;">
                    <option value="">Select…</option>
                    @foreach($clients as $client)
                        <option value="{{ $client->id }}" @selected(old('company_client_id') == $client->id)>
                            {{ $client->name }}@if($client->email) · {{ $client->email }}@endif
                        </option>
                    @endforeach
                </select>
            </div>

            <div style="font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.04em; margin-bottom: 0.5rem;">Service sections</div>
            <div style="display: grid; gap: 0.65rem; margin-bottom: 0.85rem;">
                <label style="display: flex; align-items: flex-start; gap: 0.65rem; padding: 0.75rem 0.9rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); background: #f8fafc;">
                    <input type="checkbox" name="section_os" value="1" checked disabled style="width: 1.1rem; height: 1.1rem; margin-top: 0.15rem;">
                    <input type="hidden" name="section_os" value="1">
                    <div style="flex: 1;">
                        <div style="font-weight: 700; color: var(--primary-navy);">Estate hub: OS</div>
                        <div style="margin-top: 0.5rem;">
                            <label style="font-size: 0.72rem; font-weight: 600; color: var(--text-muted);">Agreed rate € / month ex-VAT</label>
                            <input type="number" name="agreed_rate_os" id="rate_os" step="0.01" min="0" required value="{{ old('agreed_rate_os', '0.00') }}" oninput="syncPackage()" style="display: block; width: 100%; max-width: 10rem; margin-top: 0.25rem; padding: 0.45rem 0.55rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                        </div>
                    </div>
                </label>
                <label style="display: flex; align-items: flex-start; gap: 0.65rem; padding: 0.75rem 0.9rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                    <input type="checkbox" name="section_plant" id="section_plant" value="1" @checked(old('section_plant')) onchange="syncPackage()" style="width: 1.1rem; height: 1.1rem; margin-top: 0.15rem;">
                    <div style="flex: 1;">
                        <div style="font-weight: 700; color: var(--primary-navy);">Plant hub</div>
                        <div style="margin-top: 0.5rem;" id="plant_rate_wrap">
                            <label style="font-size: 0.72rem; font-weight: 600; color: var(--text-muted);">Agreed rate € / month ex-VAT</label>
                            <input type="number" name="agreed_rate_plant" id="rate_plant" step="0.01" min="0" value="{{ old('agreed_rate_plant') }}" oninput="syncPackage()" style="display: block; width: 100%; max-width: 10rem; margin-top: 0.25rem; padding: 0.45rem 0.55rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                        </div>
                    </div>
                </label>
                <label style="display: flex; align-items: flex-start; gap: 0.65rem; padding: 0.75rem 0.9rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                    <input type="checkbox" name="section_sales" id="section_sales" value="1" @checked(old('section_sales')) onchange="syncPackage()" style="width: 1.1rem; height: 1.1rem; margin-top: 0.15rem;">
                    <div style="flex: 1;">
                        <div style="font-weight: 700; color: var(--primary-navy);">Sales hub</div>
                        <div style="margin-top: 0.5rem;" id="sales_rate_wrap">
                            <label style="font-size: 0.72rem; font-weight: 600; color: var(--text-muted);">Agreed rate € / month ex-VAT</label>
                            <input type="number" name="agreed_rate_sales" id="rate_sales" step="0.01" min="0" value="{{ old('agreed_rate_sales') }}" oninput="syncPackage()" style="display: block; width: 100%; max-width: 10rem; margin-top: 0.25rem; padding: 0.45rem 0.55rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                        </div>
                    </div>
                </label>
            </div>

            <div id="packagePreview" style="margin-bottom: 1.1rem; padding: 0.75rem 0.9rem; background: #eff6ff; border: 1px solid #bfdbfe; border-radius: var(--radius-md); color: #1e3a8a; font-size: 0.9rem;">
                <strong id="packageLabel">Estate hub: OS Only</strong>
                · <span id="packageTotal">€0.00</span> ex-VAT / month
                <span id="packageVat" style="color: #64748b; font-size: 0.8rem;"></span>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 0.75rem; margin-bottom: 1rem;">
                <div>
                    <label style="display: block; font-size: 0.75rem; font-weight: 600; color: var(--text-muted); margin-bottom: 0.25rem;">Start date</label>
                    <input type="date" name="start_date" value="{{ old('start_date', date('Y-m-d')) }}" required style="width: 100%; padding: 0.5rem 0.65rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                    <div style="font-size: 0.7rem; color: var(--text-muted); margin-top: 0.25rem;">Past starts auto-issue missing months on save.</div>
                </div>
                <div>
                    <label style="display: block; font-size: 0.75rem; font-weight: 600; color: var(--text-muted); margin-bottom: 0.25rem;">Billing day (1–28)</label>
                    <input type="number" name="day_of_month" min="1" max="28" value="{{ old('day_of_month', 1) }}" required style="width: 100%; padding: 0.5rem 0.65rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                </div>
                <div>
                    <label style="display: block; font-size: 0.75rem; font-weight: 600; color: var(--text-muted); margin-bottom: 0.25rem;">Due days</label>
                    <input type="number" name="due_days" min="0" max="90" value="{{ old('due_days', 14) }}" required style="width: 100%; padding: 0.5rem 0.65rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                </div>
            </div>

            <div style="margin-bottom: 1rem;">
                <label style="display: block; font-size: 0.75rem; font-weight: 600; color: var(--text-muted); margin-bottom: 0.25rem;">Notes (optional)</label>
                <textarea name="notes" rows="2" maxlength="2000" style="width: 100%; padding: 0.5rem 0.65rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); resize: vertical;">{{ old('notes') }}</textarea>
            </div>

            <div style="display: grid; gap: 0.45rem; margin-bottom: 1rem; padding: 0.85rem 1rem; background: #f8fafc; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                <label style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.88rem; font-weight: 600; color: var(--primary-navy); cursor: pointer;">
                    <input type="checkbox" name="auto_email" value="1" @checked(old('auto_email')) style="width: 1.05rem; height: 1.05rem;">
                    Auto-email proforma when generated
                </label>
                <label style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.88rem; font-weight: 600; color: var(--primary-navy); cursor: pointer;">
                    <input type="checkbox" name="auto_reminders" value="1" @checked(old('auto_reminders')) style="width: 1.05rem; height: 1.05rem;">
                    Include in batch payment reminders
                </label>
                <label style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.88rem; font-weight: 600; color: var(--primary-navy); cursor: pointer;">
                    <input type="checkbox" name="reminder_include_statement" value="1" @checked(old('reminder_include_statement', true)) style="width: 1.05rem; height: 1.05rem;">
                    Attach open-balance statement to reminders
                </label>
            </div>

            <label style="display: flex; align-items: flex-start; gap: 0.55rem; margin-bottom: 1rem; font-size: 0.9rem; color: var(--primary-navy); cursor: pointer;">
                <input type="checkbox" name="confirmed" value="1" required style="width: 1.1rem; height: 1.1rem; margin-top: 0.15rem;">
                <span>Confirm client, package, rates, and start date. Proformas until paid, then tax invoices.</span>
            </label>

            <button type="submit" @disabled($clients->isEmpty()) style="background: var(--primary-navy); color: white; border: none; padding: 0.6rem 1.2rem; border-radius: var(--radius-md); font-weight: 700; font-size: 0.9rem; cursor: pointer;">Confirm schedule</button>
        </form>
    </div>


    <div style="display: grid; gap: 0.9rem; margin-bottom: 1.5rem;">
        @forelse($schedules as $schedule)
            @php
                $sections = $schedule->package_sections ?? [];
                $subtotal = $schedule->monthlySubtotal();
                $issued = $issuedBySchedule[$schedule->id] ?? collect();
                $behind = $schedule->is_active && $schedule->next_issue_on->lte(now()->startOfDay());
            @endphp
            <div id="schedule-{{ $schedule->id }}" style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.1rem 1.25rem; box-shadow: var(--shadow-sm);">
                <div style="display: flex; justify-content: space-between; gap: 1rem; flex-wrap: wrap; margin-bottom: 0.75rem;">
                    <div>
                        <div style="font-weight: 700; color: var(--primary-navy); font-size: 1.05rem;">{{ $schedule->title }}</div>
                        <div style="font-size: 0.82rem; color: var(--text-muted); margin-top: 0.25rem; line-height: 1.45;">
                            <a href="/company/clients/{{ $schedule->company_client_id }}" style="color: var(--primary-cerulean); font-weight: 600; text-decoration: none;">{{ $schedule->client->name ?? 'Client' }}</a>
                            · start {{ optional($schedule->start_date)->format('d M Y') ?? '—' }}
                            · billing day {{ $schedule->day_of_month }}
                            · next {{ $schedule->next_issue_on->format('d M Y') }}
                            · {{ $schedule->is_active ? 'active' : 'paused' }}
                            @if($behind)
                                <span style="color: #b45309; font-weight: 700;"> · catch-up needed</span>
                            @endif
                        </div>
                    </div>
                    <div style="text-align: right;">
                        <div style="font-weight: 700; color: var(--primary-navy);">€{{ number_format($subtotal, 2) }} <span style="font-size: 0.75rem; font-weight: 500; color: var(--text-muted);">ex-VAT / mo</span></div>
                        <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.2rem;">
                            @if(in_array('os', $sections, true)) OS €{{ number_format((float) $schedule->agreed_rate_os, 2) }}@endif
                            @if(in_array('plant', $sections, true)) · Plant €{{ number_format((float) $schedule->agreed_rate_plant, 2) }}@endif
                            @if(in_array('sales', $sections, true)) · Sales €{{ number_format((float) $schedule->agreed_rate_sales, 2) }}@endif
                        </div>
                    </div>
                </div>

                <div style="margin-bottom: 0.85rem;">
                    <div style="display: flex; justify-content: space-between; align-items: baseline; gap: 0.5rem; margin-bottom: 0.45rem;">
                        <div style="font-size: 0.72rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.03em;">Issued documents ({{ $issued->count() }})</div>
                        <a href="/company/invoices" style="font-size: 0.75rem; font-weight: 600; color: var(--primary-cerulean); text-decoration: none;">Open invoices list</a>
                    </div>
                    @if($issued->isEmpty())
                        <div style="font-size: 0.85rem; color: var(--text-muted); padding: 0.65rem 0.75rem; background: #f8fafc; border: 1px dashed var(--border-light); border-radius: var(--radius-md);">
                            No proformas yet. Use <strong>Catch up due months</strong> if the start date is in the past.
                        </div>
                    @else
                        <div style="overflow-x: auto; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                            <table style="width: 100%; border-collapse: collapse; font-size: 0.85rem;">
                                <thead>
                                    <tr style="background: #f8fafc; text-align: left; color: var(--text-muted); font-size: 0.7rem; text-transform: uppercase;">
                                        <th style="padding: 0.45rem 0.65rem; font-weight: 700;">Date</th>
                                        <th style="padding: 0.45rem 0.65rem; font-weight: 700;">Number</th>
                                        <th style="padding: 0.45rem 0.65rem; font-weight: 700;">Type</th>
                                        <th style="padding: 0.45rem 0.65rem; font-weight: 700;">Status</th>
                                        <th style="padding: 0.45rem 0.65rem; font-weight: 700; text-align: right;">Total</th>
                                        <th style="padding: 0.45rem 0.65rem; font-weight: 700; text-align: right;">Balance</th>
                                        <th style="padding: 0.45rem 0.65rem; font-weight: 700;"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($issued as $doc)
                                        @php
                                            $bal = $doc->balance();
                                            $typeLabel = match ($doc->type) {
                                                'rfp' => $doc->status === 'converted' ? 'Proforma → invoiced' : 'Proforma',
                                                'invoice' => 'Tax invoice',
                                                default => strtoupper($doc->type),
                                            };
                                        @endphp
                                        <tr style="border-top: 1px solid var(--border-light);">
                                            <td style="padding: 0.5rem 0.65rem; white-space: nowrap;">{{ $doc->issue_date->format('d M Y') }}</td>
                                            <td style="padding: 0.5rem 0.65rem; font-weight: 600; color: var(--primary-navy);">{{ $doc->document_number }}</td>
                                            <td style="padding: 0.5rem 0.65rem;">{{ $typeLabel }}</td>
                                            <td style="padding: 0.5rem 0.65rem;">{{ $doc->status }}</td>
                                            <td style="padding: 0.5rem 0.65rem; text-align: right; font-variant-numeric: tabular-nums;">€{{ number_format((float) $doc->total, 2) }}</td>
                                            <td style="padding: 0.5rem 0.65rem; text-align: right; font-variant-numeric: tabular-nums; color: {{ $bal > 0.009 ? '#b45309' : '#059669' }};">€{{ number_format($bal, 2) }}</td>
                                            <td style="padding: 0.5rem 0.65rem; text-align: right; white-space: nowrap;">
                                                <a href="/company/invoices/{{ $doc->id }}/pdf" style="font-size: 0.78rem; font-weight: 600; color: var(--primary-cerulean); text-decoration: none;">PDF</a>
                                                <a href="/company/invoices" style="font-size: 0.78rem; font-weight: 600; color: var(--text-muted); text-decoration: none; margin-left: 0.45rem;">Pay / convert</a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>

                <details style="margin-bottom: 0.75rem;">
                    <summary style="cursor: pointer; font-size: 0.8rem; font-weight: 600; color: var(--text-muted); list-style: none;">SLA · email settings · actions</summary>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 0.75rem; margin-top: 0.65rem;">
                        <div style="padding: 0.75rem 0.85rem; background: #f8fafc; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                            <div style="font-size: 0.72rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.03em; margin-bottom: 0.45rem;">Signed SLA</div>
                            @if($schedule->hasSla())
                                <div style="font-size: 0.85rem; color: #166534; margin-bottom: 0.45rem;">On file: {{ $schedule->sla_original_name ?: 'SLA document' }}</div>
                                <a href="/company/recurring/{{ $schedule->id }}/sla" style="font-size: 0.8rem; font-weight: 600; color: var(--primary-cerulean); text-decoration: none;">Download</a>
                            @else
                                <div style="font-size: 0.85rem; color: #92400e; margin-bottom: 0.45rem;">Not uploaded yet</div>
                            @endif
                            <form method="POST" action="/company/recurring/{{ $schedule->id }}/sla" enctype="multipart/form-data" style="margin-top: 0.5rem; display: flex; flex-wrap: wrap; gap: 0.35rem; align-items: center;">
                                @csrf
                                <input type="file" name="sla" accept=".pdf,.jpg,.jpeg,.png" required style="font-size: 0.75rem; max-width: 100%;">
                                <button type="submit" style="font-size: 0.78rem; font-weight: 600; background: white; color: var(--primary-navy); border: 1px solid var(--border-light); border-radius: var(--radius-md); padding: 0.35rem 0.65rem; cursor: pointer;">{{ $schedule->hasSla() ? 'Replace' : 'Upload' }}</button>
                            </form>
                        </div>
                        <div style="padding: 0.75rem 0.85rem; background: #f8fafc; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                            <div style="font-size: 0.72rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.03em; margin-bottom: 0.45rem;">Email &amp; reminders</div>
                            <form method="POST" action="/company/recurring/{{ $schedule->id }}/settings" style="display: grid; gap: 0.35rem;">
                                @csrf
                                <label style="font-size: 0.8rem; display: flex; gap: 0.4rem; align-items: center; cursor: pointer;">
                                    <input type="checkbox" name="auto_email" value="1" @checked($schedule->auto_email)> Auto-email on generate
                                </label>
                                <label style="font-size: 0.8rem; display: flex; gap: 0.4rem; align-items: center; cursor: pointer;">
                                    <input type="checkbox" name="auto_reminders" value="1" @checked($schedule->auto_reminders)> Batch reminders
                                </label>
                                <label style="font-size: 0.8rem; display: flex; gap: 0.4rem; align-items: center; cursor: pointer;">
                                    <input type="checkbox" name="reminder_include_statement" value="1" @checked($schedule->reminder_include_statement)> Include statement PDF
                                </label>
                                <button type="submit" style="justify-self: start; margin-top: 0.25rem; font-size: 0.78rem; font-weight: 600; background: white; color: var(--primary-navy); border: 1px solid var(--border-light); border-radius: var(--radius-md); padding: 0.35rem 0.65rem; cursor: pointer;">Save</button>
                            </form>
                        </div>
                    </div>
                    <div style="display: flex; flex-wrap: wrap; gap: 0.45rem; align-items: center; margin-top: 0.65rem;">
                        <a href="/company/recurring/{{ $schedule->id }}/statement" style="font-size: 0.8rem; font-weight: 600; color: var(--primary-cerulean); text-decoration: none; padding: 0.4rem 0.7rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">Statement PDF</a>
                        <form method="POST" action="/company/recurring/{{ $schedule->id }}/remind" style="display: inline;">
                            @csrf
                            <input type="hidden" name="include_statement" value="{{ $schedule->reminder_include_statement ? '1' : '0' }}">
                            <button type="submit" style="font-size: 0.8rem; font-weight: 600; background: white; color: var(--primary-navy); border: 1px solid var(--border-light); border-radius: var(--radius-md); padding: 0.4rem 0.7rem; cursor: pointer;">Send reminder</button>
                        </form>
                        <form method="POST" action="/company/recurring/reminders" style="display: inline;">
                            @csrf
                            <button type="submit" style="font-size: 0.8rem; font-weight: 600; background: white; color: var(--primary-navy); border: 1px solid var(--border-light); border-radius: var(--radius-md); padding: 0.4rem 0.7rem; cursor: pointer;">Send all auto-reminders</button>
                        </form>
                        <form method="POST" action="/company/recurring/{{ $schedule->id }}/toggle" style="margin-left: auto;">
                            @csrf
                            <button type="submit" style="font-size: 0.8rem; font-weight: 600; background: white; color: var(--primary-navy); border: 1px solid var(--border-light); border-radius: var(--radius-md); padding: 0.4rem 0.7rem; cursor: pointer;">
                                {{ $schedule->is_active ? 'Pause' : 'Activate' }}
                            </button>
                        </form>
                    </div>
                </details>
            </div>
        @empty
            <div style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 2rem; text-align: center; color: var(--text-muted);">
                No schedules yet.
                <button type="button" onclick="toggleCreateForm(true)" style="display: inline-block; margin-top: 0.75rem; background: var(--primary-navy); color: white; border: none; padding: 0.55rem 1rem; border-radius: var(--radius-md); font-weight: 600; font-size: 0.85rem; cursor: pointer;">+ New schedule</button>
            </div>
        @endforelse
    </div>

    <script>
        function toggleCreateForm(show) {
            var panel = document.getElementById('createPanel');
            var btn = document.getElementById('btnNewSchedule');
            if (!panel) return;
            panel.style.display = show ? 'block' : 'none';
            if (btn) btn.style.display = show ? 'none' : 'inline-block';
            if (show) {
                panel.scrollIntoView({ behavior: 'smooth', block: 'start' });
                syncPackage();
            }
        }
        function syncPackage() {
            var plantEl = document.getElementById('section_plant');
            var salesEl = document.getElementById('section_sales');
            if (!plantEl) return;
            var plant = plantEl.checked;
            var sales = salesEl.checked;
            var os = parseFloat(document.getElementById('rate_os').value) || 0;
            var plantRate = parseFloat(document.getElementById('rate_plant').value) || 0;
            var salesRate = parseFloat(document.getElementById('rate_sales').value) || 0;
            document.getElementById('plant_rate_wrap').style.opacity = plant ? '1' : '0.45';
            document.getElementById('sales_rate_wrap').style.opacity = sales ? '1' : '0.45';
            document.getElementById('rate_plant').required = plant;
            document.getElementById('rate_sales').required = sales;
            var label = 'Estate hub: OS Only';
            if (plant && sales) label = 'Estate hub: OS + Plant hub + Sales hub';
            else if (plant) label = 'Estate hub: OS + Plant hub';
            else if (sales) label = 'Estate hub: OS + Sales hub';
            var total = os + (plant ? plantRate : 0) + (sales ? salesRate : 0);
            document.getElementById('packageLabel').textContent = label;
            document.getElementById('packageTotal').textContent = '€' + total.toFixed(2);
            document.getElementById('packageVat').textContent = ' · +18% VAT after payment (€' + (total * 1.18).toFixed(2) + ' est.)';
        }
        syncPackage();
    </script>
@endsection
