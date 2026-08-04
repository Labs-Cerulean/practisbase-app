# PractisBase Product Roadmap

Internal product direction for growing PractisBase from a Maltese sole-trader fiscal + profession desk into a **team-capable practice system** with BOQ/specs depth — without diluting master sole-trader control.

Public prices stay list **ex-VAT + 18% VAT**. Fiscal math stays Maltese-strict (RFP = €0 official weight until invoice; tax bracket year fallback; TA22 spillover; SSC caps; Art 10/11; year lock; no future dating). Schema changes ship as **manual PostgreSQL** under `database/manual/` (no Laravel migrations unless explicitly requested).

---

## Soft-launch rule: do not break what works

Everything below is additive. Soft-launch work **must not** regress:

| Freeze zone | Why |
|---|---|
| Document ledger (clients, RFP → invoice, credit notes, payments, PDFs) | Trust base; daily use |
| Expense engine + receipt attachments | Tax pack integrity |
| Tax & VAT engine (brackets, TA22, SSC, Art 10/11, PT, year lock) | Legal correctness |
| Accountant ZIP export | Existing accountant handoff |
| Medical vault crypto + recovery code + stampables/prescriptions | Patient safety / GDPR |
| Architect Client → Project → PA + DMS + BCA template fill + stamper | Already usable Practice Arch path |
| Shared certificate stamp/issue/PDF flow | Works for Arch/Eng today — extend, don’t rewrite |
| Tier gates (`TierPolicy`, `pro:*` middleware) | Entitlement model is stable |
| Cerulean company books (internal flag) | Operator-only; ignore for soft launch |

**Implementation hygiene:** new tables + new routes + new Blade views; scope every query with `user_id`; reuse existing stamp/PDF patterns; no drive-by refactors of fiscal or Medical controllers.

---

## Package maturity (honest)

Engineer is the thinnest Practice package today. Soft launch cannot pretend otherwise.

| Surface | Medical | Architect | Engineer |
|---|---|---|---|
| Project / case workspace | Patients + journals | Client → Project → PA (deep) | **Projects list + create only** |
| Field / clinical capture | Journals, attachments, Rx, referrals | DMS, BCA template fill | Field certs + specialised reports (generic builders) |
| Stamp / warrant PDFs | Stampables under patient | Stamper + certs | Shared Certificates only |
| Mobile-first site workflow | N/A (clinic) | Not yet (neighbor reports planned) | **Not started** |
| BOQ / Spec library | N/A | **Pre soft launch (shared Arch/Eng)** | **Pre soft launch (shared Arch/Eng)** |
| Team seats | Out of soft launch | Out of soft launch | Out of soft launch |

### Engineer — what actually exists today
- Client → Project → PA (optional number) + project show/edit/archive
- Drawings & documents with revisions (`engineer_documents`)
- Generic field certificate builder (equipment / scaffold / PA starters) — stamp → PDF
- Generic specialised report builder (fire / noise / ventilation / lighting starters) — stamp → PDF
- Shared `/pro/certificates` register (simple stamp & issue)
- Nav + dashboard shortcuts

### Engineer — soft-launch gap (code)
- Mobile field polish (E5)
- BOQ / Spec library (shared Arch/Eng, SL-B)

### Shared Arch/Eng — soft-launch gap (library)
- Master BOQ + Twin Block specs (seeded from your legacy docs — **pre launch**)
- Builder UI + clone project

Pricing copy today oversells Eng. Soft launch must ship Eng depth **and** a usable BOQ/spec library (or narrow claims). Prefer shipping both.

---

## North star

| Principle | Meaning |
|---|---|
| Master owns money | Billing, ledgers, tax, final invoices, warrant sign-off stay on master. |
| Juniors get work, not books | Seats never see firm financials (post soft launch). |
| **Engineer catch-up + BOQ before soft launch** | Eng desk must be real; Arch/Eng need a native library on day 1 of beta. |
| Library before AI | Seed + Twin Block ship pre launch; Gemini top-up stays post launch. |
| Internal Ltd ≠ product | Cerulean company books stay operator-only. |

---

## Vision map

```text
┌─────────────────────────────────────────────────────────────────┐
│ 1. Team & Delegation                         POST soft launch   │
├─────────────────────────────────────────────────────────────────┤
│ 2. Core Financial Ledger & Tax Engine        LIVE — freeze      │
├─────────────────────────────────────────────────────────────────┤
│ 3. Accountant Access Hook                    ZIP live; portal   │
│                                              POST soft launch   │
├─────────────────────────────────────────────────────────────────┤
│ 4. Specialised Profession Tooling                               │
│    Medical ⚕️ soft-launch ready (+ unlock polish)                │
│    Architect 📐 soft-launch ready (+ optional neighbor)         │
│    Engineer ⚙️ CRITICAL PATH (shell → docs → certs → reports)   │
├─────────────────────────────────────────────────────────────────┤
│ 5. BOQ & Specification Suite                                    │
│    5.1 Core library + Twin Block + builder   PRE soft launch    │
│    5.2 AI top-up (Gemini)                    POST soft launch   │
└─────────────────────────────────────────────────────────────────┘
```

---

## Soft-launch definition

**Soft launch = closed beta / limited invite** where each sold Practice path is honestly usable:

| Path | Soft-launch ready when… |
|---|---|
| **Standard / Pro financial** | Already met — keep green |
| **Practice / Pro Medical** | Vault unlock reliable; stampables/Rx work |
| **Practice / Pro Architect** | PA/DMS/BCA/stamper green **+** can build a BOQ from library and export matching Spec |
| **Practice / Pro Engineer** | SL-E exit criteria **+** same BOQ/Spec library usable on Eng projects |

Out of soft launch: team seats, accountant read-only portal, **AI** BOQ features, Medical clinical seats, selling Ltd books.

---

## Soft-launch implementation phasing

```text
SL-0  Freeze & honesty gate
  │
SL-E  Engineer technical desk          ← CRITICAL (code)
  │     E1 Project shell
  │     E2 Documents & drawings VC
  │     E3 Field certification suite
  │     E4 Specialised report templates
  │     E5 Mobile field polish
  │
SL-B  BOQ & Spec core library          ← PRE LAUNCH (parallel with SL-E once E1 exists)
  │     B0 NotebookLM / seed ingest (dev-side, offline)
  │     B1 Schema + seed load into PostgreSQL
  │     B2 Twin Block linking + project BOQ builder UI
  │     B3 Spec export + clone project
  │
SL-A  Architect optional stretch       [neighbor reports — not blocking]
SL-M  Medical WebAuthn polish          [parallel]
  │
SL-G  Soft-launch gate
  │
─── Soft launch ─────────────────────────────────────────────────
  │
P2    Team & delegation
P3    Accountant read-only portal
P5    AI top-up (Gemini)               ← was “5.2”; stays post launch
```

**Parallelism:** Start **B0 ingest immediately** (you + NotebookLM offline) while E1–E5 code lands. B1–B3 need E1 project shell (or Arch PA/project) as the attach point — Eng project show is enough.

Do **not** block soft launch on AI. Do **block** soft launch Arch/Eng invites if the core library is empty.

---

### SL-0 — Freeze & honesty gate

| Task | Detail |
|---|---|
| Document freezes | Freeze table above is the contract |
| Eng + BOQ honesty | Claims must match E2/E4 + B2/B3 before invite |
| No fiscal/Medical refactors | Soft-launch feature PRs stay additive |

---

### SL-E — Engineer technical desk (critical path)

Build **around** existing `engineer_projects` and shared `certificates`. Do not replace them.

#### E1 — Project shell
Show page, edit, soft archive, dashboard into show, empty states.

#### E2 — Documents & drawings VC
`engineer_documents` + versions; project Documents tab; optional “Publish pack” ZIP.

#### E3 — Field certification suite
Nullable `engineer_project_id` on `certificates`; Eng kinds; seed titles from your examples; expiry on project; keep stamp engine.

#### E4 — Specialised reports
Fire / noise / ventilation / lighting — draft → stampable PDF; `engineer_reports` + JSON payload.

#### E5 — Mobile field polish
Camera-first cert/report forms; project Field strip.

**SL-E exit:** Project shell + docs/versions + project-linked certs + four report types draft→PDF + mobile-usable forms. Fiscal/Medical/Arch freezes untouched.

---

### SL-B — BOQ & Spec core library (pre soft launch)

**Goal:** Day-1 library for Arch and Eng with **no end-user AI**. Twin Block: every BOQ line ↔ spec clause.

#### B0 — Dev-side ingest (you start now)
See **Content ingest** below. Output lands in `database/seed/boq/` and `database/seed/templates/` as JSON — never as live Gemini calls.

#### B1 — Schema + seed
Manual SQL: `boq_categories`, `boq_master_items`, `specification_blocks` (+ link key). Loader: JSON → PostgreSQL (one-shot script or SQL generation). Global library (not per-user); users clone lines into project BOQs.

#### B2 — Builder UI
Spreadsheet-style Blade + Vanilla JS on Arch project/PA and Eng project: pick from library, qty × rate, live totals. Twin Block stitches matching spec blocks as lines are added.

#### B3 — Spec export + clone
Export Technical Spec PDF/DOCX-equivalent from linked blocks; one-click clone of a past project’s BOQ/spec set.

**Trade categories (seed coverage target for soft launch):**
- **Engineering:** Electrical, Infrastructure, Firefighting, Drainage, Fire Detection, ELV, HVAC  
- **Architecture:** Demolition & Clearance, Excavation, Concrete & Structural, Masonry, Waterproofing, Finishes, External Works  

Soft launch does **not** need every line from every legacy job — it needs a **broad, clean** master set per trade (hundreds of solid items beats thousands of messy duplicates).

**SL-B exit:** User builds a BOQ from the library on an Eng or Arch project and exports a consistent Spec with **zero** AI calls.

---

### SL-A / SL-M / SL-G

- **SL-A:** Keep Arch green; neighbor reports optional stretch.  
- **SL-M:** WebAuthn reliability only; recovery code sacred.  
- **SL-G checklist:** SL-E + SL-B met; fiscal / Med / Arch smokes green; Eng smoke includes BOQ add + Spec export; pricing matches reality; Stripe still optional.

---

## Content ingest — NotebookLM vs directly here

You have tonnes of BOQs, specs, certificates, and reports. Use a **hybrid**:

| Material | Best tool | Why |
|---|---|---|
| Messy legacy **BOQs** (PDF/Word/scans, many jobs) | **NotebookLM first** | Best at reading bulk PDFs and extracting repeated line patterns into structured drafts |
| Messy legacy **specs** paired with those BOQs | **NotebookLM first** | Needs clause extraction + linking hints to BOQ codes |
| Already-clean Excel/CSV BOQs | **Direct here** | Skip LM; convert to our JSON schema and commit |
| **Certificate blanks** / equipment cert examples | **Direct here** (light NotebookLM OK) | Few field names matter more than prose; faster to define template JSON by hand from 5–10 exemplars |
| **Report examples** (fire/noise/vent/lighting) | **NotebookLM for field inventory**, then **direct** to lock template schema | LM lists recurring headings; we freeze a stable form schema in repo |
| Final curated master library | **Direct in repo** | Only reviewed JSON is seeded to PostgreSQL |

**Do not** upload client-identifying project data into NotebookLM notebooks you will keep long-term without scrubbing. Prefer redacted packs: strip client names, addresses, prices if sensitive, PA numbers if needed — keep **descriptions, units, typical rates bands, spec clause text**.

**Do not** paste NotebookLM chat prose into the database. Only accept **valid JSON** matching the schemas in `docs/seed/`.

### Recommended workflow

```text
1. Scrub / batch sources by trade (Electrical, HVAC, Masonry, …)
2. NotebookLM notebook per trade (or per Arch vs Eng)
3. Run the fixed extraction prompt → download / copy JSON
4. Validate JSON (schema); dedupe codes; fix Twin Block links
5. Commit to database/seed/boq/<trade>.json (and templates/)
6. Dev loader inserts into PostgreSQL before soft launch
```

Cursor/agents here are best for **schema validation, dedupe, SQL load, and builder UI** — not for reading hundreds of PDFs in one go. NotebookLM (or a local Gemini batch) wins at bulk reading; we win at turning clean JSON into product.

---

## What NotebookLM should output

Full schemas and a copy-paste prompt live in [`docs/seed/NOTEBOOKLM_OUTPUT.md`](seed/NOTEBOOKLM_OUTPUT.md). Summary:

### A) BOQ + Spec “recipe book” (one JSON file per trade)

```json
{
  "schema_version": 1,
  "trade": "electrical",
  "package": "engineering",
  "source_batch": "notebooklm-electrical-2026-08",
  "categories": [
    {
      "code": "EL-LV",
      "name": "LV Distribution",
      "items": [
        {
          "item_code": "EL-LV-0010",
          "description": "Supply and install 12-way TP&N distribution board",
          "unit": "Nr",
          "typical_rate_eur": null,
          "rate_notes": "optional; omit client rates if scrubbing",
          "keywords": ["DB", "distribution board", "TPN"],
          "spec_block_code": "SPEC-EL-LV-DB-01"
        }
      ]
    }
  ],
  "specification_blocks": [
    {
      "block_code": "SPEC-EL-LV-DB-01",
      "title": "LV Distribution Boards",
      "body": "Full clause text…",
      "related_item_codes": ["EL-LV-0010"]
    }
  ]
}
```

**Hard rules for NotebookLM output:**
- Valid JSON only (no markdown fences in the file you save)
- Stable `item_code` / `block_code` (unique within trade)
- Every BOQ item has a `spec_block_code`; every block lists `related_item_codes` (Twin Block)
- Units normalised: `Nr`, `m`, `m2`, `m3`, `kg`, `Sum`, `Item`, etc.
- No client names, site addresses, or job references inside descriptions

### B) Certificate template examples (Eng equipment)

```json
{
  "schema_version": 1,
  "kind": "equipment",
  "title_template": "Electrical installation certificate — {{equipment_type}}",
  "fields": [
    {"key": "equipment_type", "label": "Equipment type", "type": "text", "required": true},
    {"key": "serial_number", "label": "Serial no.", "type": "text", "required": false},
    {"key": "location", "label": "Location", "type": "text", "required": true},
    {"key": "test_result", "label": "Result", "type": "select", "options": ["Pass", "Fail", "Conditional"], "required": true},
    {"key": "next_due_on", "label": "Next due", "type": "date", "required": false}
  ],
  "default_notes": optional string,
  "example_filled": { "equipment_type": "…", "serial_number": "…", "location": "…", "test_result": "Pass" }
}
```

### C) Report template examples (fire / noise / ventilation / lighting)

```json
{
  "schema_version": 1,
  "report_type": "fire",
  "title": "Fire safety report",
  "sections": [
    {
      "key": "scope",
      "label": "Scope of inspection",
      "fields": [
        {"key": "premises_use", "label": "Premises use", "type": "text", "required": true},
        {"key": "standards_referenced", "label": "Standards", "type": "textarea", "required": false}
      ]
    }
  ],
  "example_narrative_snippets": ["optional short anonymised phrases for placeholder help text only"]
}
```

Ask NotebookLM for **field inventories + example structures**, not finished legal reports. We freeze the form schema in code; your exemplars teach which fields matter.

---

## Post soft launch

### P2 — Team & Delegation
Master invites juniors; project/PA assignments; seats never see Tax & VAT; master stamp queue.

### P3 — Accountant read-only portal
Tokenised locked-year inspection on top of ZIP.

### P5 — AI top-up (optional)
Gemini 1.5 Flash: scope → draft BOQ; PDF/scan → structured BOQ; `ai_usage_logs` + fair-use caps. Core library remains usable with AI off.

---

## Suggested PR sequencing (pre soft launch)

| Order | PR theme | Touches | Risk |
|---|---|---|---|
| 1 | Eng E1 project show/edit/archive | Eng only | Low |
| 2 | Eng E2 documents + versions | Eng SQL + storage | Low |
| 3 | Eng E3 cert↔project + kinds + expiry | certificates additive | Low–med |
| 4 | Eng E4 report types | Eng only | Low |
| 5 | Eng E5 mobile polish | Eng views/JS | Low |
| 6 | BOQ B1 schema + seed loader | SQL + seed JSON | Low |
| 7 | BOQ B2 builder + Twin Block | Arch/Eng project views | Low–med |
| 8 | BOQ B3 Spec export + clone | Arch/Eng | Low |
| 9 | Pricing/onboarding copy sync | marketing Blade | Low |
| 10 | Optional Arch neighbor / Med WebAuthn | scoped | Low |
| 11 | Soft-launch checklist | docs | None |

B0 (NotebookLM) runs **outside** git until curated JSON is ready to commit (steps 6–8). Fiscal freezes: no feature PRs unless a beta-blocking bug.

---

## Explicit non-goals before soft launch

- Team seats / scoped roles  
- Accountant read-only portal  
- **End-user** Gemini/AI BOQ features (dev-side NotebookLM ingest is allowed)  
- Rewriting shared certificate stamp engine  
- Refactoring FiscalReportEngine “while we’re here”  
- Selling Ltd company books  
- Medical clinical team seats  

---

## Success signals (soft launch)

| Signal | Indicates |
|---|---|
| Eng projects have docs + certs + a report PDF | Eng package is real |
| BOQ built from library + Spec exported | Twin Block library is enough without AI |
| Fiscal / Med / Arch smokes still green | Freeze held |
| Pricing bullets match screens | Honesty |

---

## Document ownership

Compass through soft launch (Eng catch-up + BOQ core library) and beyond (seats → accountant portal → AI). Fiscal rules stay in `.cursorrules`. Update when a slice ships or scope is cut.
