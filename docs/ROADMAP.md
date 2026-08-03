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

**Implementation hygiene for every Eng/Arch slice:** new tables + new routes + new Blade views; scope every query with `user_id`; reuse existing stamp/PDF patterns; no drive-by refactors of fiscal or Medical controllers.

---

## Package maturity (honest)

Engineer is the thinnest Practice package today. Soft launch cannot pretend otherwise.

| Surface | Medical | Architect | Engineer |
|---|---|---|---|
| Project / case workspace | Patients + journals | Client → Project → PA (deep) | **Projects list + create only** (no show/edit, no docs under project) |
| Field / clinical capture | Journals, attachments, Rx, referrals | DMS, BCA template fill | **None beyond generic cert photo** |
| Stamp / warrant PDFs | Stampables under patient | Stamper + certs | Shared Certificates only |
| Mobile-first site workflow | N/A (clinic) | Not yet (neighbor reports planned) | **Not started** |
| Team seats | Out of soft launch | Out of soft launch | Out of soft launch |
| BOQ / specs | N/A | Post soft launch | Post soft launch |

### Engineer — what actually exists today
- `engineer_projects` table + create/list UI (`/pro/engineer/projects`)
- Discipline / phase labels (including EMS/BMS as **labels only** — no EMS/BMS workflows)
- Shared `/pro/certificates` register (title, subject, kind, dates, optional photo, stamp & issue, PDF)
- Nav + dashboard shortcuts

### Engineer — what does **not** exist yet (soft-launch gap)
- Project detail page, edit, archive
- Project journals / notes timeline
- Document / drawing upload + version control under a project
- Specialised report types (fire, noise, ventilation, lighting)
- Project-linked field certification logs (equipment examples, expiry reminders, mobile photo → project)
- Engineer stamp pack tied to a project (beyond generic certificate rows)
- Technical exports that are more than a single certificate PDF
- Team delegation into Eng projects

Pricing copy today oversells (“technical document exports”, “project workspace”). Soft launch must either **ship the missing Eng depth** or **narrow public Eng claims** until it does. Prefer shipping depth.

---

## North star

| Principle | Meaning |
|---|---|
| Master owns money | Billing, ledgers, tax settings, final invoices, and warrant sign-off stay on the sole-trader master account. |
| Juniors get work, not books | Delegated seats see assigned projects/files only — never firm financials or tax balances. |
| Profession depth sells Practice/Pro | Medical / Architect / Engineer tools justify the ladder; finance is the trust base. |
| **Engineer catch-up before soft launch** | Practice Eng must feel like a real technical desk, not a project name + generic certificate. |
| Library before AI | Ship a native BOQ/spec database first; add optional AI top-ups later with fair-use caps. |
| Internal Ltd ≠ product | Cerulean Labs company books stay hard-gated operator tooling, not a sold Ltd SKU. |

---

## Vision map (five pillars — post soft launch continues)

```text
┌─────────────────────────────────────────────────────────────────┐
│ 1. Team & Delegation (Arch / Eng)           POST soft launch    │
├─────────────────────────────────────────────────────────────────┤
│ 2. Core Financial Ledger & Tax Engine       LIVE — freeze       │
├─────────────────────────────────────────────────────────────────┤
│ 3. Accountant Access Hook                   ZIP live; portal    │
│                                             POST soft launch    │
├─────────────────────────────────────────────────────────────────┤
│ 4. Specialised Profession Tooling                               │
│    Medical ⚕️ largely soft-launch ready                          │
│    Architect 📐 soft-launch ready (+ optional neighbor stretch) │
│    Engineer ⚙️ LARGEST SOFT-LAUNCH BUILD                         │
├─────────────────────────────────────────────────────────────────┤
│ 5. BOQ & Specification Suite                POST soft launch    │
│    5.1 Core library → 5.2 AI top-up                             │
└─────────────────────────────────────────────────────────────────┘
```

---

## Soft-launch definition

**Soft launch = closed beta / limited invite** where each sold Practice path is honestly usable:

| Path | Soft-launch ready when… |
|---|---|
| **Standard / Pro financial** | Already met — ledger, expenses, Tax & VAT, ZIP (keep green) |
| **Practice / Pro Medical** | Already largely met — vault unlock reliable enough for beta; stampables/Rx work |
| **Practice / Pro Architect** | Already largely met — PA file + DMS/BCA fill + stamper/certs; neighbor reports optional stretch |
| **Practice / Pro Engineer** | **Must reach SL-E exit criteria below** before Eng is invited at scale |

Out of soft launch (explicitly later): team seats, accountant read-only portal, BOQ library, AI top-up, Medical clinical seats, selling Ltd books.

---

## Soft-launch implementation phasing

Work is ordered to **protect freezes**, close the **Engineer gap first**, then optional Arch polish, then beta gate. Each slice is shippable on its own PR without touching fiscal math.

```text
SL-0  Freeze & honesty gate          [docs / pricing claims]
  │
SL-E  Engineer technical desk        ← CRITICAL PATH TO SOFT LAUNCH
  │     E1 Project shell
  │     E2 Documents & drawings VC
  │     E3 Field certification suite (equipment examples)
  │     E4 Specialised report templates
  │     E5 Mobile photo capture polish
  │
SL-A  Architect optional stretch     [neighbor reports — nice, not blocking Eng]
  │
SL-M  Medical beta polish            [WebAuthn reliability — parallel, small]
  │
SL-G  Soft-launch gate               [invite checklist, no Stripe required]
  │
─── Soft launch ─────────────────────────────────────────────────
  │
P2    Team & delegation
P3    Accountant read-only portal
P4    BOQ & Spec core library
P5    AI top-up
```

---

### SL-0 — Freeze & honesty gate

| Task | Detail |
|---|---|
| Document freezes | This file’s freeze table is the contract |
| Eng pricing honesty | Until E1–E4 land, avoid claiming “technical document exports” beyond certificate PDFs — or ship E2/E4 and keep the claim |
| No fiscal/Medical refactors | Soft-launch PRs touch `Pro/Engineer/*`, Eng views, Eng SQL, shared cert **extensions** only when linking to projects |

**Exit:** Team agrees Eng is the critical path; freeze list respected.

---

### SL-E — Engineer technical desk (critical path)

Build **around** existing `engineer_projects` and shared `certificates`. Do not replace them.

#### E1 — Project shell (foundation)

| Deliverable | Notes |
|---|---|
| Project show page | Overview: discipline, phase, status, notes, recent activity |
| Edit + soft archive | Keep list/create; add update; status `active` / `archived` |
| Dashboard cards | Point into show, not only list |
| Empty states | Clear “what to do next” (add drawing, log cert, start report) |

**Do not touch:** fiscal routes, Medical, Architect controllers.

**Exit:** Engineer can open a project and update it without creating a new project every time.

#### E2 — Documents & drawings version control

| Deliverable | Notes |
|---|---|
| `engineer_documents` (manual SQL) | `user_id`, `engineer_project_id`, title, category (drawing / calc / report / other), current version |
| Version rows | Upload PDF/image; version number; note; uploaded_at; never delete stamped history lightly |
| Project Documents tab | Upload, list versions, download |
| Optional publish snapshot | “Publish pack” ZIP of current versions for a project (this is the real “technical export”) |

**Reuse:** `TenantStorage` path pattern (`tenants/{id}/engineer/...`).

**Exit:** At least one drawing with v1→v2 under a project; master downloads current set.

#### E3 — Field certification suite (equipment examples)

Extend shared certificates — **do not fork a second stamp engine**.

| Deliverable | Notes |
|---|---|
| Link cert → project | Nullable `engineer_project_id` on `certificates` (manual SQL) |
| Engineer-oriented kinds | e.g. equipment / installation / commissioning / inspection (additive to `Certificate::KINDS`) |
| Seed examples | Catalog/blanks from your real equipment certification examples (dev seed JSON → optional starter rows or template titles only) |
| Expiry surface | Project show lists certs; highlight expired / expiring ≤30 days |
| Photo on site | Keep existing photo upload; ensure mobile camera capture works on create/edit |
| Stamp & issue | Existing flow; after stamp, still immutable |

**Exit:** Engineer logs equipment cert under a project, stamps it, PDF downloads, expiry visible on project.

#### E4 — Specialised report templates

Templated fields first (Blade forms + PDF), not a free-form CMS.

| Report type | Soft-launch minimum |
|---|---|
| Fire | Project-linked draft → stampable PDF |
| Noise | Same pattern |
| Ventilation | Same pattern |
| Lighting | Same pattern |

Suggested tables: `engineer_reports` (`type`, `status` draft/stamped, `payload` JSON, `engineer_project_id`, stamp fields mirroring certificates).

**Reuse:** warrant/stamp/signature from user profile; `IssueCode` pattern if appropriate.

**Exit:** One of each report type can be drafted on a project and stamped to PDF. Remaining report richness can deepen post soft launch.

#### E5 — Mobile photo / field polish

| Deliverable | Notes |
|---|---|
| Mobile-friendly cert + report forms | Large targets, camera `capture` attributes, minimal typing |
| Project “Field” strip | Quick links: + Cert photo, + Report, open drawings |

**Exit:** Usable on phone for a site visit without desktop.

**SL-E package exit (Engineer soft-launch ready):** Project shell + documents/versions + project-linked certs with expiry + at least fire/noise/ventilation/lighting draft→PDF + mobile-usable field forms. Shared stamp/PDF and fiscal paths unchanged.

---

### SL-A — Architect optional stretch (not blocking Eng)

| Priority | Item | Soft launch? |
|---|---|---|
| P0 | Keep PA / DMS / BCA fill / stamper green | Required (already live) |
| P1 | Neighbor Condition Reports (mobile) | Stretch — ship if Eng is ahead of schedule |
| P2 | Guided DMS/EMS/CMS packs beyond blanks | Prefer post soft launch unless quick |

Do **not** delay Eng soft launch for neighbor reports.

---

### SL-M — Medical beta polish (parallel)

| Item | Note |
|---|---|
| WebAuthn / fingerprint unlock reliability | Fix without changing vault crypto scheme |
| Recovery code path | Keep sacred; no “reset without code” |
| No clinical team seats | Post soft launch |

Medical is soft-launch eligible once unlock is trustworthy for invited doctors.

---

### SL-G — Soft-launch gate (invite checklist)

Before inviting external Practice Eng users:

- [ ] SL-E exit criteria met on staging
- [ ] Fiscal regression smoke: create RFP → convert invoice → expense → Tax & VAT glance → year lock still blocks
- [ ] Medical smoke: unlock vault → open patient → issue stampable (if Med invites in same wave)
- [ ] Architect smoke: open PA → fill one BCA template → stamp/cert still works
- [ ] Engineer smoke: create project → upload drawing v1/v2 → log equipment cert → stamp → one specialised report PDF
- [ ] IDOR spot-check: Eng project/doc/cert IDs scoped to `Auth::id()`
- [ ] Pricing / onboarding Eng bullets match shipped reality
- [ ] Stripe still optional (closed beta) — existing plan

**Soft launch wave suggestion:** Standard/Pro financial + Med + Arch first (already strong), Eng in same wave **only after** SL-E. Or Eng one invite wave later — never Eng-first while shell-only.

---

## Post soft launch (unchanged destination)

### P2 — Team & Delegation (Arch & Eng)
Master invites juniors; project/PA assignments; seats never see Tax & VAT; master review & stamp queue. Natural first seat workflow: Eng field cert photos / Arch neighbor reports.

### P3 — Accountant read-only portal
Tokenised inspection of locked years on top of existing ZIP.

### P4 — BOQ & Spec core library
NotebookLM → `recipe_book.json` → `boq_categories` / `boq_master_items` / `specification_blocks`; Twin Block linking; Blade+JS builder; clone project. No end-user AI.

### P5 — AI top-up
Gemini 1.5 Flash scope→draft and PDF/scan→BOQ; `ai_usage_logs` + fair-use caps.

---

## Suggested PR sequencing (soft launch)

| Order | PR theme | Touches | Risk to freezes |
|---|---|---|---|
| 1 | Eng E1 project show/edit/archive | Eng only | Low |
| 2 | Eng E2 documents + versions (+ optional project ZIP) | Eng SQL + storage | Low |
| 3 | Eng E3 cert↔project + Eng kinds + expiry UI | certificates column + Eng views | Low–med (shared cert — additive only) |
| 4 | Eng E4 report types (start with one, then remaining three) | Eng only | Low |
| 5 | Eng E5 mobile field polish | Eng views/JS | Low |
| 6 | Pricing/onboarding Eng copy sync | marketing Blade | Low |
| 7 | Optional Arch neighbor reports | Arch only | Low |
| 8 | Medical WebAuthn polish | Medical unlock only | Med-scoped |
| 9 | Soft-launch checklist + beta notes | docs | None |

Fiscal, Tax & VAT, expenses, accountant ZIP, Architect DMS/BCA, Medical vault crypto: **no soft-launch feature PRs** unless a critical bug blocks beta.

---

## Explicit non-goals before soft launch

- Team seats / scoped roles
- Accountant read-only portal
- BOQ library or any Gemini/AI dependency
- Rewriting shared certificate stamp engine
- Refactoring FiscalReportEngine / tax brackets “while we’re here”
- Selling Ltd company books
- Medical clinical team seats

---

## Success signals (soft launch)

| Signal | Indicates |
|---|---|
| Eng projects have documents + certs attached | Package is no longer a shell |
| Specialised report PDFs issued in beta | Eng Practice worth €24.99/€34.99 |
| Fiscal smoke still green after Eng PRs | Freeze held |
| Arch/Med invitees keep using existing tools | No regression |
| Pricing bullets match screenshots | Honesty |

---

## Longer-term success signals (post soft launch)

| Signal | Indicates |
|---|---|
| Seat invites accepted | Delegation matches real studios |
| Master stamp queue non-empty | Juniors produce; masters sign |
| Accountant portal without ZIP | Collaboration without mutation risk |
| BOQ built without AI | Core library enough |
| AI drafts accepted then edited | Top-up saves time |

---

## Document ownership

This roadmap is the product compass through soft launch (Engineer catch-up first) and beyond (seats → accountant portal → BOQ → AI). Fiscal engine changes stay under Maltese domain rules in `.cursorrules`. Update this file when a soft-launch slice ships or scope is consciously cut.
