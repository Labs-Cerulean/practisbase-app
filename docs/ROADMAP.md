# PractisBase Product Roadmap

Internal product direction for growing PractisBase from a Maltese sole-trader fiscal + profession desk into a **team-capable practice system** with BOQ/specs depth — without diluting master sole-trader control.

Public prices stay list **ex-VAT + 18% VAT**. Fiscal math stays Maltese-strict (RFP = €0 official weight until invoice; tax bracket year fallback; TA22 spillover; SSC caps; Art 10/11; year lock; no future dating). Schema changes ship as **manual PostgreSQL** under `database/manual/` (no Laravel migrations unless explicitly requested).

---

## North star

| Principle | Meaning |
|---|---|
| Master owns money | Billing, ledgers, tax settings, final invoices, and warrant sign-off stay on the sole-trader master account. |
| Juniors get work, not books | Delegated seats see assigned projects/files only — never firm financials or tax balances. |
| Profession depth sells Practice/Pro | Medical / Architect / Engineer tools justify the ladder; finance is the trust base. |
| Library before AI | Ship a native BOQ/spec database first; add optional AI top-ups later with fair-use caps. |
| Internal Ltd ≠ product | Cerulean Labs company books stay hard-gated operator tooling, not a sold Ltd SKU. |

---

## Vision map (your five pillars)

```text
┌─────────────────────────────────────────────────────────────────┐
│ 1. Team & Delegation (Arch / Eng packages)                      │
│    Master sole-trader · scoped seats · no finance access        │
├─────────────────────────────────────────────────────────────────┤
│ 2. Core Financial Ledger & Tax Engine          STATUS: LIVE     │
│    Clients · RFP→Invoice · Expenses · Tax/VAT · Year lock       │
├─────────────────────────────────────────────────────────────────┤
│ 3. Accountant Access Hook                                       │
│    ZIP (live) → tokenised read-only inspection portal           │
├─────────────────────────────────────────────────────────────────┤
│ 4. Specialised Profession Tooling                               │
│    Medical ⚕️ · Architect 📐 · Engineer ⚙️                         │
├─────────────────────────────────────────────────────────────────┤
│ 5. BOQ & Specification Suite                                    │
│    5.1 Core library (NotebookLM seed, Twin Block, no AI)        │
│    5.2 Post-launch AI top-up (Gemini + usage caps)              │
└─────────────────────────────────────────────────────────────────┘
```

---

## Current status (as of this roadmap)

### Pillar 2 — Built and operational (trust base)
- Document ledger: clients, RFPs (non-fiscal until converted), tax invoices, credit notes, payments, PDFs
- Expense engine: categories, receipt attachments, deductible toggles, capital/WFH helpers
- Live Tax & VAT: progressive income tax (Single / Married / Parent), TA22 + spillover, SSC weekly/pro-rata caps, Art 10 (Output − Input), Art 11 (€35k breach alerts)
- Provisional tax logging; June settlement = annual liability − PT payments
- Governance: fiscal year close/lock, regime/tax setup, bracket year fallback, no future dating
- Accountant ZIP export (locked year summaries + invoice/expense packs + receipts)

### Pillar 4 — Profession foundations (partial)
| Package | Live today | Gap this roadmap fills |
|---|---|---|
| **Medical** | Encrypted vault, patients, journals + attachments, prescriptions, referrals, stampables, WebAuthn unlock path | Vault reliability polish; clinical role seats later (out of near-term Arch/Eng seat work) |
| **Architect** | Client → Project → PA hierarchy, DMS, BCA template fill, stamper, certificates, licence contacts | Neighbor Condition Reports (mobile-first); guided BCA DMS/EMS/CMS packs; team assignment into PA files |
| **Engineer** | Projects, shared certificates (EMS/BMS thin) | Journals, drawings VC, specialised reports (fire / noise / ventilation / lighting), field cert suite + equipment examples |

### Pillar 1 / 3 / 5 — Not built yet
- Team seats / scoped roles / project assignment
- Accountant read-only portal (ZIP remains the offline path)
- Neighbor condition reports
- Full BCA method-statement productisation beyond template blanks
- Engineer drawings VC / specialised report suite depth
- BOQ & Spec library + Twin Block builder + clone
- AI BOQ top-up layer

### Internal only (not a sold SKU)
- Cerulean Labs Ltd company desk (`company_books_enabled`) — Art 10 invoices/expenses/profile/logo

---

## Phase map (build order)

```text
Phase 0  Foundation hardening              [largely done — keep green]
    │
Phase 1  Architect/Engineer field depth    ← neighbor reports, BCA suite, eng certs
    │
Phase 2  Team & delegation seats           ← juniors on mobile, no finance access
    │
Phase 3  Accountant collaboration          ← read-only portal on top of ZIP
    │
Phase 4  BOQ & Spec core library           ← NotebookLM-seeded, no end-user AI
    │
Phase 5  AI top-up (optional)              ← Gemini draft/parse + usage caps
```

Phases 1–2 can partially overlap once PA/project assignment exists, but **do not ship team seats before** project-scoped permissions and a clear master-vs-seat auth model.

Medical vault/clinical polish runs **in parallel** and is not blocked by Arch/Eng team work. Medical team seats wait until a separate clinical-role design exists.

---

## Phase 0 — Foundation hardening (complete / keep green)

**Goal:** Trust base for everything above. Matches pillar **2**.

| Area | Status | Keep doing |
|---|---|---|
| Document ledger | Done | Strict RFP vs invoice fiscal weight |
| Expenses | Done | Receipt integrity + deductible rules |
| Tax & VAT engine | Done | Bracket fallback, Art 11 alerts, year lock |
| Settings / regimes | Done | Safe defaults; no mid-year silent wipe |
| Free → Standard → Practice → Pro ladder | Done | Ex-VAT + VAT transparency on pricing |
| IDOR / CSRF / mass-assignment hygiene | Ongoing | Always `user_id` scope; no `$request->all()` |

**Exit criteria:** Fiscal regressions blocked; profession packages remain additive, not replacements for the ledger.

---

## Phase 1 — Architect & Engineer field depth (pre-team)

**Goal:** Make Practice/Pro packages indispensable on site and in the studio **before** inviting juniors. Deepens pillar **4B / 4C**.

### 1A — Architect: PA + BCA + Neighbor Condition Reports (priority)

| Deliverable | Notes |
|---|---|
| PA-centric project file | Already started (Client → Project → PA); deepen status, dates, parties |
| BCA compliance suite | Productise **DMS / EMS / CMS** from template blanks into guided, stampable packs aligned to Building & Construction Authority requirements |
| **Neighbor Condition Reports (core)** | Multiple reports per PA; mobile-first capture while inspecting surrounding properties |
| Photo & defect logging | Instant camera capture → project file; defect categories + structural notes |
| Stamper / certificates | Warrant + stamp + signature on reviewed packs |

**Mobile rule:** Neighbor Condition Report create/edit must be usable one-handed on phone (large tap targets, camera-first; offline-tolerant later if needed).

**Suggested tables (manual SQL later):** `neighbor_condition_reports`, `neighbor_condition_photos`, `neighbor_defects` — always scoped to master `user_id` + `pa_project_id`.

### 1B — Engineer: journals, certs, specialised reports

| Deliverable | Notes |
|---|---|
| Project journal + document / drawing versions | Versioned uploads; publishable report snapshots |
| Specialised report types | Fire, noise, ventilation, lighting (templated fields first) |
| Certification suite | Field cert logs, mobile photo proof, expiry reminders, stamp/warrant/signature |
| Example equipment certs | Seed from your real examples (catalog + blank forms) |

**Exit criteria:** A sole arch/eng can run a live site inspection and PA/project file from phone without juniors; Practice tier value is obvious.

### 1C — Medical (parallel track, not Arch/Eng-gated)

| Deliverable | Notes |
|---|---|
| Vault reliability | Fingerprint / WebAuthn unlock polish; recovery-code path remains sacred |
| GDPR posture | PII strictly decoupled from clinical journals (already the design intent — keep enforcing) |
| Prescriptions / referrals | Warrant, stamp, signature + retained logs |
| Clinical seats | Deferred until Arch/Eng seat model is proven |

---

## Phase 2 — Team & Delegation (Architect & Engineer packages)

**Goal:** Master sole trader keeps the firm; juniors get scoped project work. Delivers pillar **1**.

### Account model
- **Sole-trader master account:** total control over billing, financial ledgers, tax settings, master sign-offs, seat invites, role matrix
- **Team seat:** invited email, accepts invite, restricted role (e.g. Junior Architect / Site Engineer / Draftsperson / Site Inspector)

### Permissions (non-negotiable)

| Seat **can** | Seat **cannot** |
|---|---|
| Open assigned projects / PAs only | Firm-wide client financials / tax balances |
| Create/edit assigned docs & condition reports | Tax & VAT, expenses, provisional tax |
| Mobile site inspection + photo upload | Issue tax invoices / credit notes / RFPs |
| Compile BOQ drafts (once Phase 4 exists) | Change tax settings / close fiscal years |
| Stream work into master’s PA/project file | View other seats’ unassigned work (unless shared) |
| Submit packs for master review | Final warrant stamp / official certificate issue (master only, unless explicitly granted later) |

### Technical building blocks
- `team_memberships` — master `user_id`, seat `user_id`, role, status, invited_at, accepted_at
- `project_assignments` / `pa_assignments` — scope of work
- Permission middleware distinct from `tier` / `pro` package checks
- Audit log — who created/edited site reports (warrant accountability)
- Master **Review & stamp** queue for seat-submitted packs

**Exit criteria:** Junior on mobile logs a Neighbor Condition Report into the master’s PA file; master stamps; seat never sees Tax & VAT.

---

## Phase 3 — Accountant Access Hook

**Goal:** Seamless collaboration with external accountants **without** data mutation rights. Delivers pillar **3**.

| Deliverable | Notes |
|---|---|
| Accountant ZIP (exists) | One-click batch export: locked FY summaries, itemised invoices (CSV/PDF), categorised expenses, receipt assets — keep as default offline handoff |
| Read-only inspection portal | Secure, tokenised interface; locked end-of-year reports + VAT breakdowns for June Settlement / VAT returns |
| Assignment | Master invites accountant email; revoke anytime |
| Guardrails | No create/update/delete; no escalation into finance write; tokens expire / rotate |

**Exit criteria:** Accountant reviews June settlement / VAT pack online without a login that can edit the ledger.

---

## Phase 4 — BOQ & Specification Suite (core launch, no end-user AI)

**Goal:** Fast, robust native library + builder for Architect and Engineer (and their seats). Delivers pillar **5.1**.

### 4.1 Dev-side seeding — NotebookLM pipeline
- Offline (dev-side only): ingest hundreds of legacy BOQs/specs → clean standardised `recipe_book.json` files
- Seed PostgreSQL before launch: `boq_categories`, `boq_master_items`, `specification_blocks`
- Day-1 library for all Arch/Eng users **and** their team members
- **No end-user AI dependencies or API costs** at this phase

### 4.2 Trade categories
- **Engineering:** Electrical, Infrastructure, Firefighting, Drainage, Fire Detection, ELV, HVAC
- **Architecture:** Demolition & Clearance, Excavation, Concrete & Structural, Masonry, Waterproofing, Finishes, External Works

### 4.3 Product engine
- **Twin Block auto-linking:** every BOQ line item ↔ corresponding technical specification clause; building a BOQ stitches the complete matching Technical Spec document
- **Dynamic builder:** spreadsheet-style Blade + Vanilla JS — live qty × rate totals
- **Clone project:** one-click clone of past job BOQ/spec set

**Exit criteria:** User builds a BOQ from the library and exports a consistent Spec document with **zero** AI calls.

---

## Phase 5 — AI Top-Up Layer (post-launch, optional)

**Goal:** Speed, not dependency. Core library remains first-class if AI is off or capped. Delivers pillar **5.2**.

| Feature | Mechanism |
|---|---|
| Project Scope Auto-Assembler | Natural language scope → Gemini 1.5 Flash selects master DB items → ~80% draft BOQ |
| PDF / Scan → Structured BOQ | Scanned tenders/PDFs → async Gemini Vision → project BOQ tables |
| Cost control | `ai_usage_logs`, background queue jobs, invisible fair-use caps |

**Exit criteria:** AI is additive; fair-use enforced; library-only path remains fully usable.

---

## Suggested build order (dependency-safe)

1. **Neighbor Condition Reports + mobile capture** (Arch) — highest practice differentiator
2. **BCA method-statement guided packs** (Arch) — DMS / EMS / CMS
3. **Engineer cert suite + specialised report types** (Eng) — seed from your equipment examples
4. **Team seats + project/PA assignment + permission matrix**
5. **Seat mobile workflows** — condition reports / cert photos stream to master; master stamp queue
6. **Accountant read-only portal** — on top of existing ZIP
7. **BOQ library seed + Twin Block builder + clone** — NotebookLM → `recipe_book.json` → PostgreSQL
8. **AI top-up** behind `ai_usage_logs` + caps

Medical continues in parallel (vault unlock reliability, clinical polish). Medical team seats stay out of scope until clinical role design is separate.

---

## Tier / package implications (rough)

| Capability | Likely home |
|---|---|
| Ledger + tax + accountant ZIP | Standard and above |
| Profession tools (Medical / Arch / Eng foundations) | Practice (profession) or Full Pro |
| Team seats | Practice/Pro Arch & Eng — seat count TBD |
| Neighbor reports / BCA / eng cert depth | Practice/Pro Arch & Eng |
| BOQ & Spec core library | Practice/Pro Arch & Eng (+ seats) |
| AI top-up | Optional add-on or Pro-only fair-use |

Final seat pricing and seat caps are a commercial decision — lock after Phase 1 field tools prove weekly use.

---

## Explicit non-goals (near term)

- Selling a general Ltd-company accounting product (Cerulean desk stays internal)
- Giving seats invoice/tax access “just for convenience”
- End-user AI as a launch blocker for BOQ
- Replacing Maltese fiscal rules with simplified approximations
- Medical clinical team seats before Arch/Eng seat model is proven

---

## Success signals

| Signal | Indicates |
|---|---|
| Practice Arch/Eng retention | Field tools (condition reports, certs) used weekly |
| Seat invites accepted | Delegation model matches real studios |
| Master stamp queue non-empty | Juniors produce; masters sign |
| Accountant portal opens without ZIP | Collaboration without mutation risk |
| BOQ built without AI | Core library is enough |
| AI drafts accepted then edited | Top-up saves time, doesn’t invent nonsense |

---

## Document ownership

This roadmap is the product compass for Arch/Eng growth, team delegation, accountant collaboration, and BOQ. Fiscal engine changes stay under Maltese domain rules in `.cursorrules`. Update this file when a phase ships or scope is consciously cut.
