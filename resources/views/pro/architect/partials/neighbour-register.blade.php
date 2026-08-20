{{-- Neighbour register + tracker (Phase 2). Expects: $project, $neighbourRelations, $neighbourStatuses, $neighbourDesk --}}
<section id="neighbours" style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.2rem; box-shadow: var(--shadow-sm);">
    <div style="display: flex; justify-content: space-between; align-items: baseline; gap: 0.75rem; flex-wrap: wrap; margin-bottom: 0.35rem;">
        <h2 style="margin: 0; font-size: 1.05rem; color: var(--primary-navy);">Neighbours</h2>
        <span style="font-size: 0.78rem; color: var(--text-muted);">
            {{ $neighbourDesk['pack_ready'] }} of {{ $neighbourDesk['total'] }} BCA-ready
        </span>
    </div>
    <p style="margin: 0 0 0.85rem; font-size: 0.82rem; color: var(--text-muted); line-height: 1.45;">
        Third-party register for this site — track contact through survey, report, and BCA filing. Attach a Seventh Schedule condition report on each row.
    </p>

    @if(!empty($neighbourDesk['cues']) && $neighbourDesk['total'] > 0)
        <div style="display: flex; flex-wrap: wrap; gap: 0.4rem; margin-bottom: 0.85rem;">
            @foreach($neighbourDesk['cues'] as $cue)
                <span style="font-size: 0.72rem; font-weight: 650; color: {{ str_contains($cue, 'objection') || str_contains($cue, 'overdue') || str_contains($cue, 'missing') ? '#9a3412' : '#3f6212' }}; background: {{ str_contains($cue, 'objection') || str_contains($cue, 'overdue') || str_contains($cue, 'missing') ? '#fff7ed' : '#f7fee7' }}; border: 1px solid {{ str_contains($cue, 'objection') || str_contains($cue, 'overdue') || str_contains($cue, 'missing') ? '#fed7aa' : '#d9f99d' }}; border-radius: var(--radius-md); padding: 0.28rem 0.55rem;">{{ $cue }}</span>
            @endforeach
        </div>
    @endif

    @if($project->neighbours->isEmpty())
        <p style="margin: 0 0 1rem; color: var(--text-muted); font-size: 0.9rem; line-height: 1.45;">
            No neighbours yet. Add abutting / overlying / underlying / excavation-affected properties here instead of a spreadsheet.
        </p>
    @else
        <div style="display: grid; gap: 0.75rem; margin-bottom: 1rem;">
            @foreach($project->neighbours as $neighbour)
                @php
                    $rowWarn = $neighbour->missingEmail() || $neighbour->appointmentOverdue() || $neighbour->isObjected();
                @endphp
                <div style="border: 1px solid {{ $rowWarn ? '#fed7aa' : 'var(--border-light)' }}; border-radius: var(--radius-md); padding: 0.85rem 0.9rem; background: {{ $neighbour->isObjected() ? '#fff7ed' : 'white' }};">
                    <form method="POST" action="/pro/architect/projects/{{ $project->id }}/neighbours/{{ $neighbour->id }}" style="display: grid; gap: 0.55rem;">
                        @csrf
                        @method('PUT')
                        <div style="display: flex; justify-content: space-between; gap: 0.5rem; flex-wrap: wrap;">
                            <div style="min-width: 0; flex: 1;">
                                <div style="font-size: 0.7rem; font-weight: 700; color: #3f6212; text-transform: uppercase; letter-spacing: 0.02em;">{{ $neighbour->relationLabel() }}</div>
                                <div style="font-weight: 700; color: var(--primary-navy);">{{ $neighbour->owner_occupier_name ?: 'Owner / occupier TBD' }}</div>
                                <div style="font-size: 0.82rem; color: var(--text-muted);">{{ $neighbour->addressLine() }}</div>
                            </div>
                            <div style="display: flex; gap: 0.45rem; align-items: flex-start; flex-wrap: wrap;">
                                @if($neighbour->missingEmail())
                                    <span style="font-size: 0.68rem; font-weight: 700; color: #9a3412;">No email</span>
                                @endif
                                @if($neighbour->appointmentOverdue())
                                    <span style="font-size: 0.68rem; font-weight: 700; color: #9a3412;">Overdue</span>
                                @endif
                                @if($neighbour->conditionReport)
                                    <a href="/pro/architect/condition-reports/{{ $neighbour->conditionReport->id }}" style="font-size: 0.78rem; font-weight: 650; color: #3f6212; text-decoration: none;">
                                        CR {{ $neighbour->conditionReport->isStamped() ? $neighbour->conditionReport->issue_code : 'draft' }}
                                    </a>
                                @else
                                    <a href="/pro/architect/condition-reports/create?project_id={{ $project->id }}&amp;neighbour_id={{ $neighbour->id }}&amp;starter=seventh_schedule" style="font-size: 0.78rem; font-weight: 650; color: #3f6212; text-decoration: none;">+ Build CR</a>
                                @endif
                            </div>
                        </div>

                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 0.45rem;">
                            <div>
                                <label style="display: block; font-size: 0.68rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.2rem;">Address *</label>
                                <input type="text" name="address" value="{{ old('address', $neighbour->address) }}" required style="width: 100%; padding: 0.45rem 0.55rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); font-size: 0.85rem;">
                            </div>
                            <div>
                                <label style="display: block; font-size: 0.68rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.2rem;">Owner / occupier</label>
                                <input type="text" name="owner_occupier_name" value="{{ old('owner_occupier_name', $neighbour->owner_occupier_name) }}" style="width: 100%; padding: 0.45rem 0.55rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); font-size: 0.85rem;">
                            </div>
                            <div>
                                <label style="display: block; font-size: 0.68rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.2rem;">Relation</label>
                                <select name="relation" required style="width: 100%; padding: 0.45rem 0.55rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); font-size: 0.85rem;">
                                    @foreach($neighbourRelations as $key => $label)
                                        <option value="{{ $key }}" @selected(old('relation', $neighbour->relation) === $key)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label style="display: block; font-size: 0.68rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.2rem;">Status</label>
                                <select name="status" required style="width: 100%; padding: 0.45rem 0.55rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); font-size: 0.85rem;">
                                    @foreach($neighbourStatuses as $key => $label)
                                        <option value="{{ $key }}" @selected(old('status', $neighbour->status) === $key)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label style="display: block; font-size: 0.68rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.2rem;">Phone</label>
                                <input type="text" name="phone" value="{{ old('phone', $neighbour->phone) }}" style="width: 100%; padding: 0.45rem 0.55rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); font-size: 0.85rem;">
                            </div>
                            <div>
                                <label style="display: block; font-size: 0.68rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.2rem;">Email</label>
                                <input type="email" name="email" value="{{ old('email', $neighbour->email) }}" style="width: 100%; padding: 0.45rem 0.55rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); font-size: 0.85rem;">
                            </div>
                            <div>
                                <label style="display: block; font-size: 0.68rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.2rem;">Appointment</label>
                                <input type="date" name="appointment_on" value="{{ old('appointment_on', optional($neighbour->appointment_on)->format('Y-m-d')) }}" style="width: 100%; padding: 0.45rem 0.55rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); font-size: 0.85rem;">
                            </div>
                        </div>
                        <div>
                            <label style="display: block; font-size: 0.68rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.2rem;">Notes</label>
                            <input type="text" name="notes" value="{{ old('notes', $neighbour->notes) }}" style="width: 100%; padding: 0.45rem 0.55rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); font-size: 0.85rem;">
                        </div>
                        <div style="display: flex; justify-content: space-between; gap: 0.5rem; flex-wrap: wrap; align-items: center;">
                            <button type="submit" style="background: var(--primary-navy); color: white; border: none; border-radius: var(--radius-md); padding: 0.4rem 0.85rem; font-weight: 650; font-size: 0.82rem; cursor: pointer;">Save</button>
                            <button type="submit" form="neighbour-delete-{{ $neighbour->id }}" style="background: none; border: none; color: #b91c1c; font-weight: 700; cursor: pointer; font-size: 0.8rem;" onclick="return confirm('Remove this neighbour from the register?');">Remove</button>
                        </div>
                    </form>

                    <form id="neighbour-delete-{{ $neighbour->id }}" method="POST" action="/pro/architect/projects/{{ $project->id }}/neighbours/{{ $neighbour->id }}" style="display: none;">
                        @csrf
                        @method('DELETE')
                    </form>

                    @if($project->conditionReports->isNotEmpty() && ! $neighbour->architect_condition_report_id)
                        <form method="POST" action="/pro/architect/projects/{{ $project->id }}/neighbours/{{ $neighbour->id }}/link-report" style="display: flex; gap: 0.45rem; flex-wrap: wrap; align-items: end; margin-top: 0.55rem; padding-top: 0.55rem; border-top: 1px solid #e2e8f0;">
                            @csrf
                            <div style="flex: 1; min-width: 160px;">
                                <label style="display: block; font-size: 0.68rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.2rem;">Link existing CR</label>
                                <select name="architect_condition_report_id" required style="width: 100%; padding: 0.45rem 0.55rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); font-size: 0.85rem;">
                                    <option value="">Choose report…</option>
                                    @foreach($project->conditionReports as $cr)
                                        <option value="{{ $cr->id }}">{{ $cr->title }} · {{ $cr->isStamped() ? $cr->issue_code : 'Draft' }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <button type="submit" style="background: white; border: 1px solid var(--border-light); color: var(--primary-navy); border-radius: var(--radius-md); padding: 0.45rem 0.75rem; font-weight: 650; font-size: 0.82rem; cursor: pointer;">Link</button>
                        </form>
                    @endif
                </div>
            @endforeach
        </div>
    @endif

    <form method="POST" action="/pro/architect/projects/{{ $project->id }}/neighbours" style="display: grid; gap: 0.55rem; border-top: 1px solid #e2e8f0; padding-top: 0.85rem;">
        @csrf
        <div style="font-size: 0.78rem; font-weight: 700; color: var(--primary-navy);">Add neighbour</div>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 0.45rem;">
            <div style="grid-column: 1 / -1;">
                <label style="display: block; font-size: 0.68rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.2rem;">Address *</label>
                <input type="text" name="address" value="{{ old('address') }}" required placeholder="Third-party property address" style="width: 100%; padding: 0.5rem 0.6rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); font-size: 0.88rem;">
            </div>
            <div>
                <label style="display: block; font-size: 0.68rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.2rem;">Owner / occupier</label>
                <input type="text" name="owner_occupier_name" value="{{ old('owner_occupier_name') }}" style="width: 100%; padding: 0.5rem 0.6rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); font-size: 0.88rem;">
            </div>
            <div>
                <label style="display: block; font-size: 0.68rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.2rem;">Relation *</label>
                <select name="relation" required style="width: 100%; padding: 0.5rem 0.6rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); font-size: 0.88rem;">
                    @foreach($neighbourRelations as $key => $label)
                        <option value="{{ $key }}" @selected(old('relation', 'abutting') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label style="display: block; font-size: 0.68rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.2rem;">Status *</label>
                <select name="status" required style="width: 100%; padding: 0.5rem 0.6rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); font-size: 0.88rem;">
                    @foreach($neighbourStatuses as $key => $label)
                        <option value="{{ $key }}" @selected(old('status', 'identified') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label style="display: block; font-size: 0.68rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.2rem;">Phone</label>
                <input type="text" name="phone" value="{{ old('phone') }}" style="width: 100%; padding: 0.5rem 0.6rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); font-size: 0.88rem;">
            </div>
            <div>
                <label style="display: block; font-size: 0.68rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.2rem;">Email</label>
                <input type="email" name="email" value="{{ old('email') }}" style="width: 100%; padding: 0.5rem 0.6rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); font-size: 0.88rem;">
            </div>
            <div>
                <label style="display: block; font-size: 0.68rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.2rem;">Appointment</label>
                <input type="date" name="appointment_on" value="{{ old('appointment_on') }}" style="width: 100%; padding: 0.5rem 0.6rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); font-size: 0.88rem;">
            </div>
        </div>
        <button type="submit" style="justify-self: start; background: #3f6212; color: white; border: none; border-radius: var(--radius-md); padding: 0.5rem 1rem; font-weight: 650; font-size: 0.88rem; cursor: pointer;">Add to register</button>
    </form>
</section>
