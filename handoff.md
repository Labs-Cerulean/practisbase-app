# PractisBase: Project Handoff & Development Brief

## Project Overview
PractisBase is a custom-built, highly secure fiscal reporting and invoicing ledger designed specifically for self-employed professionals in Malta. It strictly adheres to Maltese tax laws, providing real-time calculations for Progressive Income Tax, Part-Time TA22, Social Security (SSC), and VAT (Articles 10 & 11). Note: The system is a tool, not a certified accounting software, and requires strict legal disclaimers regarding tax liability.

Operated by **Cerulean Labs Ltd** as a self-serve, multi-tenant SaaS. Users manage their own subscriptions via online card payments (Stripe — currently bypassed in onboarding for testing).

---

## End State (North Star)

When PractisBase is "complete", a Maltese self-employed professional can:

1. **Register** with enforceable T&Cs (liability disclaimer), complete fiscal onboarding, and land on a tier-aware dashboard.
2. **Operate a compliant ledger** — Clients, RFPs, Invoices, Credit Notes, Payments — with IDOR-proof tenant isolation, no future-dating, and locked fiscal years.
3. **See trustworthy tax math** — Live Fiscal Report with auditable breakdowns (TA22, SSC, VAT, PT settlement). **Free includes the fiscal summary**; paid tiers unlock Expenses, branding, exports, Pro modules.
4. **Self-serve their plan** — Upgrade/downgrade Free → Standard → Pro (Medical / Architect / Engineer) from Settings; Free hard-capped at **5 lifetime clients** (deletes do not free a slot).
5. **Use tier features without leaking entitlements** — Middleware + controller checks gate Expenses, Document Storage, VAT Export, TA22 generation, Pro industry modules (not the core fiscal summary).
6. **Export professional PDFs** — Branded invoices/RFPs/credit notes/receipts (Standard+ custom logo).
7. **Run industry workflows safely** — Pro Medical with PII delinked from clinical journals (GDPR); Pro Architect DMS + BCA-aligned docs + stamper; Pro Engineer certifications with photo/expiry logs.
8. **Pay Cerulean Labs** — Real Stripe billing replaces the current DEV bypass.

Everything below is sequenced **backwards from that end state**: foundations first (security, tiers, legal), then core product surface, then monetized features, then Pro verticals, then documents and billing polish.

---

## The SaaS Architecture & Monetization

### Product decisions (locked)
* **Pre-launch:** No real users in production yet. Schema and infra may be corrected freely; still build correctly so we do not re-learn tenancy later.
* **Free fiscal access:** Free tier **may use the Live Fiscal Report / summary**. Do not middleware-gate `/reports` behind Standard.
* **Free client quota:** Cap is **5 lifetime clients**. Soft-deleting or hard-deleting a client **does not** restore a slot. Upgrade to Standard+ unlocks unlimited.

### 1. Free Tier (€0/mo)
* **Limits:** **5 lifetime Clients** (enforced in controller + surfaced in UI as e.g. `3 / 5 used`). Deletion does not decrement usage.
* **Capabilities:** Basic Invoices & Ledger (RFPs, official invoices, payments received), Summary Dashboard, **Live Fiscal Report / summary**, Standard Support.
* **Out of scope for Free:** Expenses, Document Storage, custom branding, VAT Export, Automated TA22 generation, Pro modules. *(Nav may soft-hide these; Phase 1 hardens with middleware.)*

### 2. Standard Tier (€15.99/mo)
* **Limits:** Unlimited Clients.
* **Capabilities:** Everything in Free, plus Custom Branding & Logo on documents, Expense Tracking & Receipts, Document & File Uploads, Automated TA22 Form generation, Accountant VAT Export.

### 3. Pro Tiers (€49.99/mo)
All Pro tiers include everything in Standard, plus one industry package:
* **Pro Medical (`pro-med`):** Secure Patient Journals, Digital Prescriptions, Referral Letters. *GDPR: PII must be delinked from medical details in the database.*
* **Pro Architect (`pro-arch`):** Architect DMS, Document Stamper, Project Phase Tracking. *BCA-aligned Method Statements / declarations.*
* **Pro Engineer (`pro-eng`):** EMS / BMS Templates, Certification Generator, Technical Specs Export. *Certification logs with photo upload + expiry management.*

**Canonical tier values in code today:** `free`, `standard`, `pro-med`, `pro-arch`, `pro-eng` (stored on `users.tier`).

---

## TODAY'S STATUS: What Is Already Complete

The core fiscal engine is functioning:

* **Math Engine:** `ReportController` calculates multi-tiered Maltese tax logic (TA22 spillover, SSC caps, VAT thresholds, PT settlement).
* **Database foundation:** PostgreSQL schemas for `users`, `clients`, `invoices`, `payments`, `tax_rates` (JSON), plus in-use tables `tax_payments` and `fiscal_years` (no Eloquent model for fiscal years; no in-repo migrations for those two — manage via manual SQL per `.cursorrules`).
* **Live Fiscal UI:** `reports/index.blade.php` — CSS variables, warnings, clickable modal breakdowns, Article 11 progress.
* **Government Ledger:** Provisional Tax / VAT payments with Vanilla JS smart guides updating Final June Settlement.
* **Strict constraints already in places:** Future-dating blocked in key UIs, tax-rate year fallback, fiscal year lock checks in reports mutations.
* **Auth & onboarding:** Custom session auth (not Breeze); register with T&Cs scroll/checkbox/IP/duration; profession → financial → plan selection (Stripe currently bypassed).
* **Settings (partial):** Fiscal profile + password at `/settings`; **no subscription management panel yet**.
* **Clients / Ledger / Dashboard:** Functional CRUD surfaces exist; Convert-to-Invoice and credit/refund flows exist in `InvoiceController`.
* **Soft tier UI:** Sidebar shows Standard/Pro nav stubs based on `tier`; routes are not middleware-gated.

### Known gaps discovered in codebase audit (feed Phase 1)
| Gap | Severity | Notes |
|-----|----------|-------|
| No `app/Http/Middleware` / empty `bootstrap/app.php` aliases | High | No tier, onboarding, or T&Cs gate |
| Free 5-client lifetime quota not enforced | High | `ClientController@store` has no count check; no lifetime counter / soft-delete policy yet |
| `InvoiceController@store` `client_id` => `exists:clients,id` without `user_id` | Critical | Cross-tenant client attach IDOR |
| `TaxPayment` model `$guarded = []` | Medium | Mass-assignment risk |
| Settings has no subscription panel | Medium | Tier only set at onboarding |
| Sidebar hides Live Fiscal Report from Free | Product bug | Free should see `/reports`; currently Standard+ only in nav |
| Onboarding incomplete users can hit `/dashboard` | Medium | Login skips onboarding check |
| Medical fields stored on `clients.profile_data` | Future (P4) | Conflicts with GDPR delink end-state |
| Stripe / Cashier absent | Future (billing) | DEV bypass banner on plans page |

---

## Roadmap to Completion (End in Mind)

Phases are ordered so later work never fights earlier architecture. Each phase ends with a shippable, testable slice.

### Phase 0 — Baseline Hardening (do first inside / with Phase 1)
*Close security holes before building product surface on top of them.*

1. Fix `client_id` validation to require ownership: `exists:clients,id,user_id,{Auth::id()}`.
2. Audit every `find`/`Route::bind` path for `user_id` scoping (Clients, Invoices, Payments, TaxPayments).
3. Tighten `TaxPayment` fillable/guarded; never `$request->all()` into creates.
4. Provide clean ANSI PostgreSQL (no `--` comments, no `::json` casts) for any missing `tax_payments` / `fiscal_years` columns if prod is incomplete — **no Laravel migrations unless explicitly requested**.

### Phase 1: Subscriptions, T&Cs & Multi-Tenant Security  ← **CURRENT FOCUS**
*Outcome: A Free user cannot exceed 5 clients; a user cannot touch another tenant's rows; paid features are server-gated; Settings shows plan + legal stance; incomplete onboarding cannot skip into the app.*

#### 1A. Tenant isolation & lifetime Free client quota
* Enforce **5 lifetime clients** for Free in `ClientController@store` (and block create UI when at cap).
* **Deletes do not free a slot.** Preferred infra (pre-launch, schema malleable):
  1. Add `users.clients_created_count` (integer, default 0), increment only on successful create; **never** decrement on delete; OR
  2. Soft-delete clients (`deleted_at`) and count `withTrashed()` toward the Free cap.
  *Recommendation: (1) monotonic counter — simple, survives hard deletes, easy to show `used / 5` in Settings. Provide manual PostgreSQL for the column.*
* Prefer shared `User` helpers: `lifetimeClientCount()`, `canAddClient()`, `isPaid()`, `hasMinTier('standard')`, `proPackage()`.
* Keep per-query `where('user_id', Auth::id())` as the law (no relying on UI alone).

#### 1B. Tier feature middleware
* Add middleware e.g. `EnsureUserTier` aliased in `bootstrap/app.php` as `tier`.
* Usage: `->middleware(['auth', 'tier:standard'])` or `tier:pro-med,pro-arch,pro-eng`.
* **Do not gate `/reports` for Free** — Free keeps the fiscal summary. Gate Expenses / Document Storage / VAT Export / TA22 / Pro routes when those land.
* Fix sidebar so Free users see Live Fiscal Report; keep Standard/Pro tool stubs soft-hidden until built.
* Free hitting a paid route gets upgrade redirect/flash, not a silent 403 only.

#### 1C. Settings — subscription & profile
* Extend `/settings` with a **Subscription** card: current tier badge, lifetime usage (e.g. `3 / 5 clients used` — clarify deletes do not restore), upgrade CTAs (Stripe later; DEV plan switch OK for now).
* Keep existing fiscal/password sections intact (no UI regression).

#### 1D. Legal T&Cs acceptance flow
* Registration flow already records `terms_accepted_at`, `accepted_ip`, `read_duration_seconds`.
* Add middleware `EnsureTermsAccepted` (and optional `EnsureOnboardingComplete`) so users without terms / incomplete profession→financial→plan cannot reach app routes.
* Leave room for future `terms_version` column (manual SQL when needed) without blocking Phase 1.

#### 1E. Phase 1 acceptance criteria
* [ ] Free user after 5 creates: POST `/clients` rejected even if some clients were deleted; create page disabled/warned.
* [ ] Settings shows lifetime usage `N / 5` for Free (not merely active row count).
* [ ] Free can open `/reports` (nav link visible).
* [ ] Cross-user `client_id` on invoice create fails validation.
* [ ] Incomplete onboarding cannot use `/dashboard` / `/ledger` / `/clients`.
* [ ] Paid-only routes (when present) blocked for Free with upgrade path.
* [ ] All mutations remain CSRF + `user_id` scoped; no new Tailwind/React.

### Phase 2: Main Dashboard & Core Ledger UI
*Outcome: Daily operating surface is polished and feeds the math engine correctly.*

* Enrich `/dashboard` KPIs (current-year focus, unpaid/overdue, quick actions) without turning Free into a full fiscal cockpit.
* Finalize ledger UX: create/edit/list Clients, RFPs, Invoices; keep Convert-to-Invoice / credit / payment flows solid.
* Ensure RFP cash never enters official tax liability paths (already in math; guard UI copy and filters).
* Year-lock: block ledger mutations for closed `fiscal_years` consistently (not only on reports).

### Phase 3: Standard Tier Features
*Outcome: €15.99 value prop is real and gated.*

* Expense Ledger replacing static `estimated_expenses` for paid users (column remains fallback for Free / incomplete data).
* Receipt/file uploads (tenant-scoped storage paths).
* Accountant VAT Export (CSV/Excel).
* Automated TA22 form generation logic + download.
* Custom branding/logo fields on user profile → consumed by PDF phase.

### Phase 4: Pro Tier Foundations & Stamping
*Outcome: Industry packages scaffolded on a GDPR-safe data model.*

* Global Document Stamper (signatures/warrants on PDFs) — shared by Arch (primary) and reusable.
* **Medical GDPR schema (manual SQL):** separate PII entity from journal/clinical records; no clinical payload on `clients.profile_data` long-term; migrate carefully.
* Scaffold routes/UI shells: Patient Journals, Architect DMS + phases, Engineer Certification Generator.
* Domain rules: BCA Method Statements (Arch); certification photo + expiry (Eng). Clarify EMS/BMS template content with domain expert before locking schemas.

### Phase 5: Document Generation & PDF Export
*Outcome: Every official document is downloadable and brandable.*

* PDF engine (DomPDF or Browsershot — choose when implementing; prefer server-simple DomPDF unless pixel-perfect print needs Browsershot).
* Controllers for Official Invoice, RFP, Credit Note, Payment Receipt (receipt view already exists).
* Standard+ injects logo/branding; Free uses PractisBase-safe defaults + disclaimer footer.

### Phase 6: Billing & Launch Polish (end-game ops)
*Outcome: Real money, real legal posture, production confidence.*

* Replace onboarding/Settings DEV bypass with Stripe Checkout / Customer Portal (Cashier or thin custom).
* Webhooks update `users.tier`; failed payment → grace → downgrade path that re-applies 5-client rule without deleting data.
* Terms versioning + re-acceptance modal when legal text changes.
* Disclaimers on Fiscal Report and PDFs: tool ≠ certified accountant advice.
* Referral codes (`referral_code` / `referred_by_id` already on User) wired if part of launch.

---

## Suggested Build Order Within Phase 1

```
Phase 0 IDOR fixes
    → users.clients_created_count (manual SQL) + User helpers
    → EnsureUserTier + EnsureOnboardingComplete middleware
    → Free lifetime 5-client cap (controller + create UI; no decrement on delete)
    → Unhide /reports for Free in sidebar; prepare tier aliases for future paid routes
    → Settings Subscription card (lifetime usage copy)
    → T&Cs / onboarding route groups in web.php
```

Do **not** introduce Stripe in Phase 1; keep DEV plan switching behind a clear testing affordance in Settings so Pro UI can be exercised.

---

## AI Assistant Instructions (Cursor)

1. Always reference `.cursorrules` before generating backend logic — especially IDOR, mass assignment, manual PostgreSQL, Blade/Vanilla JS only.
2. Build with this End State in mind: leave room for subscription limits, tier middleware, and Pro verticals in schema/controllers.
3. Medical features: explicitly separate PII from journal data (GDPR).
4. No Tailwind/Bootstrap/React/Vue. Inline CSS + CSS variables + Vanilla JS.
5. Prioritize mathematical accuracy and data integrity over UI flair.
6. If an acronym or Pro-domain term (EMS, BMS, BCA, etc.) is unclear, ask the developer (domain expert) before inventing schema.
7. Prefer raw PostgreSQL snippets for schema changes; never migrations/seeders unless explicitly requested.
8. Always issue work as a revision on a new pull request.
9. Do not truncate complex Blade/controller deliveries; do not regress granular tax UI (TA22/spillover rows).

---

## Continuity Notes for the Next Agent Session

* **Branch base:** `13.x`
* **Stack in repo:** Laravel 13 / PHP 8.3+ (handoff historically said 11.x — follow the repo)
* **Pre-launch:** No real users — infra/schema fixes are in scope; still prefer correct tenancy patterns.
* **Start implementing Phase 0+1** from "Suggested Build Order" above unless the developer revises priorities.
* **Free:** fiscal summary allowed; **5 lifetime clients** (counter, never decremented on delete).
* **Do not** assume Stripe exists; plan UI may continue to set `users.tier` directly until Phase 6.
