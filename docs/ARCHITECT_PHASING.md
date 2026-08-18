# Architect desk — product phasing

Natural perit workflow: **site → PA cases → BCA mobilisation → neighbours / CR → clearances → works → close**.  
Sellable front door: **projects + PA status + map**. Paperwork (CR / MS / BCA templates) stays the depth behind the pin.

Soft-launch freeze still applies: extend Architect Client → Project → PA + DMS + BCA + CR/MS; do not regress them. Schema changes = manual PostgreSQL under `database/manual/`.

---

## Architect mental model (keep sacred)

1. I have a **project** and a **site**
2. I have **PA / PC / DN** cases on it — I follow them
3. When the Authority **decides / endorses**, I mobilise for **BCA**
4. **Neighbours and condition reports** first, then MS / insurance / guarantees
5. **Clearances**, then works, then close

Every screen answers one of those. Dashboard = attention + map, not a feature zoo.

---

## Phase 1 — Portfolio command centre *(sellable front door)*

**Feel:** “Open PractisBase → see my jobs.”

| Ship | Detail |
|---|---|
| Project hub | Client, site, phase, team (perit / structural where needed) |
| Case types | PA / PC / DN (and related as needed) on the project |
| Case lifecycle | Tracking → Pending/Awaiting Decision → Recommended → Decided → Endorsed → Fee Payment / Under Appeal / Refused / Revoked / Withdrawn |
| eApps deep link | Build official Case Details URL from `caseType` + **padded** case number + `caseYear` |
| Map | Pin every project (lat/long); drop pin → reverse-geocode street; portfolio map |
| Filters | Locality, client, project status, PA status, case type — **same filters on list and map** |
| Architect dashboard | Rebuild around projects, open PAs, status attention, map |
| PA MapServer | **Deep link only** (“Open in PA MapServer” at this site / search). No API assumed |

**Exit:** Perit demos Malta/Gozo portfolio + live cases in under a minute.

### eApps leading zeros *(critical)*

Wrong padding loads a page with **zero documents** (silent failure).

| Bad | Good |
|---|---|
| `PA/0525/22` | `PA/00525/22` |

Rules when building the eApps URL (and when normalising stored case numbers for links):

- Parse display forms like `PA/00525/22`, `PA 00525/22`, `pa/525/22`.
- Case **number** segment must be zero-padded to the Authority width (**5 digits** for standard PA-style numbers unless a typed exception is documented later).
- Never strip leading zeros from the link builder; prefer pad-on-link so `525` and `0525` both become `00525`.
- UI: show the canonical padded form; warn if the stored number cannot be normalised.
- Smoke-test: open link → documents present, not an empty case shell.

### PA MapServer reality

- `https://pamapserver.pa.org.mt/` is the Authority GIS. **No public API assumed** for dropping our pins onto their map.
- **Do not promise** “show PractisBase sites as pins on MapServer.” Without an API / partner embed, that is not under our control.
- What we ship instead:
  1. **Our** map with perit project pins + filters (source of truth for the practice).
  2. Per-project **Open in MapServer** (and Open in eApps) so official layers stay live at the Authority.
  3. Later: only if PA/MSDI expose allowed WMS/tiles, optional basemap/overlays on *our* map — still not writing pins into MapServer.

---

## Phase 1.5 — Project document library polish

Reuse existing DMS (`architect_documents` + `architect_document_revisions`).

| Ship | Detail |
|---|---|
| Clearer types | Plans, surveys, structural, PA docs, photos, other PDF |
| Project library UX | Obvious “files for this job” with revision history |
| In-app PDF reader | Open plans/surveys without forcing download |
| Links | Attach docs to PA case and/or neighbour pack when relevant |

**Exit:** Feels like a real office filing cabinet, versioned.

---

## Phase 2 — Neighbour campaign + impact radius

**Feel:** “This project’s third parties, step by step.”

| Ship | Detail |
|---|---|
| Neighbour register | Address, owner/occupier, phone, email, relation (abutting / overlying / underlying / excavation affected) |
| Tracker | Identified → Contacted → Appointment booked → Survey done → Report drafted → Sent → Accepted / Objected → Filed for BCA |
| Desk cues | Missing email, overdue appointment, objections, pack completeness |
| Attach CR | Reuse existing condition-report builder + photos/PDF on the neighbour row |
| Impact radius tool | On **zoomed project map**: buffer from pin / outer shell (default e.g. 20 m; optional depth→radius bands). Working aid only — label *confirm on site / title*; feeds “add neighbour” |

Office flow first — no phone field tool yet.

**Exit:** Perit runs a neighbour list to BCA-ready without a spreadsheet.

---

## Phase 2.5 — Practice reference library

Practice-wide knowledge shelf (not per-project): DC15, local plans, Use Classes (LN 74/2014), Health & Sanitary (LN 277/2016), etc.

| Ship | Detail |
|---|---|
| Curated uploads | Admin/perit uploads official PDFs + tags |
| In-app reader | Read beside project/map without leaving the job |
| Official deep links | Where a stable PA/gov URL exists |

**Do not** scrape Authority sites for “always live” copies — brittle and ToS-risky. Curated file + link stays honest.

**Exit:** Policy docs open next to the project in one click.

---

## Phase 3 — BCA mobilisation checklist

**Feel:** “After Endorsed, the system walks me through mobilisation.”

Parallel first:

- Archaeologist (if required)
- Geological investigation / GIR (e.g. excavation depth rule as a prompt, not invented law)
- Neighbour / CR programme (Phase 2)

Then sequence:

- Method statements (reuse DMS/EMS/CMS builders)
- Construction site insurance (S.L. 623.11 / LN 38 of 2024) — register, not blank-only
- Bank guarantees: footpath / site management (623.08); third-party (623.06 Reg 6); excavation complexes (623.06 Reg 7 if applicable)
- Responsibility forms (detailed + summary)
- Clearances: demolition → excavation → construction
- Temporary water / electricity
- Soft gate toward commencement (CNF after written clearance — developer SOP framing)

Soft gates: warn if out of order; pack completeness %. Explicit product rules where useful: no meters / no change of applicant as reminders, not silent magic.

**Exit:** Endorsed project has a single mobilisation spine using existing CR/MS/templates plus new insurance/guarantee/clearance statuses.

---

## Phase 4 — Field + automation

| Ship | Detail |
|---|---|
| Phone CR tool | Open neighbour on site → camera / annotate → GPS-time → PDF → advance tracker → optional email |
| Auto email | Survey invite, appointment confirm, send report, MS / works notices (registered-letter style where required) |
| eApps status pull | When case is open on eApps, push live status into the project; ignore unknown cases; **once Endorsed, Decided must not overwrite** |

**Exit:** Site + inbox use the same neighbour/PA flow.

---

## Phase 5 — Close-out

CNF / works under clearance, guarantee release, project complete — still project-centric and quiet.

---

## UX rules (all phases)

- One primary object per screen (project, case, neighbour, checklist step).
- Status language matches PA/BCA speak architects already use.
- Prefer “what’s next?” over empty forms.
- Reuse CR / MS / BCA templates; don’t rebuild a second product.
- Map and list always share the same filters.

---

## Suggested build / PR order

| Order | Slice | Risk |
|---|---|---|
| 1 | Project lat/long + map + filters + Arch dashboard | Med (new map stack) |
| 2 | PA/PC/DN model + lifecycle + **padded eApps URL** + MapServer deep link | Low–med |
| 3 | DMS library polish + in-app PDF reader | Low |
| 4 | Neighbour register + tracker + CR attach | Med |
| 5 | Impact radius on zoomed map | Low–med |
| 6 | Reference library + reader | Low |
| 7 | BCA mobilisation checklist (insurance / guarantees / clearances) | Med |
| 8 | Phone CR + emails | Med–high |
| 9 | eApps status pull (Endorsed protection) | Med (external dependency) |
| 10 | Close-out polish | Low |

BOQ/Spec (roadmap SL-B) stays a parallel soft-launch track; it does not block Phase 1 map/PA selling.

---

## Explicit non-goals (this programme)

- Cloning or pinning into PA MapServer without an official API/partner channel
- Scraping eApps/MapServer HTML as a substitute for APIs
- Inventing legal radius tables as binding determinations
- Rewriting fiscal / Medical while building Arch portfolio
- Team seats / junior delegation (post soft launch per main roadmap)

---

## Success signals

| Signal | Indicates |
|---|---|
| Dashboard is projects + PAs + map | Sellable Arch front door |
| eApps link opens the **correct** case with documents | Zero-padding rule held |
| Map filters by locality / client / status | Portfolio ops, not a pretty picture |
| Neighbour pack readiness without Excel | Phase 2 landed |
| Endorsed → mobilisation checklist in one place | Phase 3 landed |
| Fiscal / Med / existing Arch CR-MS still green | Freeze held |
