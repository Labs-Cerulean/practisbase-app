# PractisBase: Project Handoff & Development Brief

## Project Overview
PractisBase is a custom-built, highly secure fiscal reporting and invoicing ledger designed specifically for self-employed professionals in Malta. It strictly adheres to Maltese tax laws, providing real-time calculations for Progressive Income Tax, Part-Time TA22, Social Security (SSC), and VAT (Articles 10 & 11). Note: The system is a tool, not a certified accounting software, and requires strict legal disclaimers regarding tax liability.

Operated by **Cerulean Labs Ltd** as a self-serve, multi-tenant SaaS. Users manage their own subscriptions via online card payments (Stripe — currently bypassed in onboarding for testing).

---

## End State (North Star)

When PractisBase is "complete", a Maltese self-employed professional can:

1. **Register** with enforceable T&Cs (liability disclaimer), complete fiscal onboarding, and land on a tier-aware dashboard.
2. **Operate a compliant ledger** — Clients, RFPs, Invoices, Credit Notes, Payments — with IDOR-proof tenant isolation, no future-dating, and locked fiscal years.
3. **See trustworthy tax math** — Live Fiscal Report with auditable breakdowns (TA22, SSC, VAT, PT settlement) on **Standard+**. Free stays on Dashboard + ledger only (5 lifetime clients is too incomplete a book for official tax math).
4. **Self-serve their plan** — Upgrade/downgrade Free → Standard → Pro (Medical / Architect / Engineer) from Settings; Free hard-capped at **5 lifetime clients** (deletes do not free a slot).
5. **Use tier features without leaking entitlements** — Middleware + controller checks gate Live Fiscal Report, Expenses, Document Storage, Accountant Download, TA22 generation, Pro industry modules.
6. **Export professional PDFs** — Branded invoices/RFPs/credit notes/receipts (Standard+ custom logo).
7. **Run industry workflows safely** — Pro Medical with PII delinked from clinical journals, **practitioner-held recovery code** encryption (Labs cannot decrypt; lost key = unrecoverable; backup requires code + signed acknowledgment), including **encrypted journal attachments** (photos/files); Pro Architect DMS + BCA-aligned docs + stamper; Pro Engineer projects; **Certificates & Declarations shared across all Pro packages** (doctors, architects, engineers).
8. **Pay Cerulean Labs** — Real Stripe billing replaces the current DEV bypass.
9. **Deliver signed paid documents (late)** — Create, sign/stamp, and sell one-time download links (prescriptions first; stamped drawings / certs later) with patient/client payment → single download → fiscal ledger entry.

Everything below is sequenced **backwards from that end state**: foundations first (security, tiers, legal), then core product surface, then monetized features, then Pro verticals, then documents and billing polish, then paid document commerce.

---

## The SaaS Architecture & Monetization

### Product decisions (locked)
* **Pre-launch:** No real users in production yet. Schema and infra may be corrected freely; still build correctly so we do not re-learn tenancy later.
* **Free fiscal access:** Free tier does **not** get Live Fiscal Report. Gate `/reports` behind Standard+ (incomplete 5-client books can produce misleading liability pictures).
* **Free client quota:** Cap is **5 lifetime clients**. Soft-deleting or hard-deleting a client **does not** restore a slot. Upgrade to Standard+ unlocks unlimited + fiscal report.
* **Tier transitions:** All upgrade / downgrade / Pro-package switches are first-class Phase 0 policy (see **Tier Transition Matrix** below). Encode in shared helpers before Settings plan-switching UI. **Never delete tenant data on downgrade** — gate features; retain rows.
* **Downgrade with excess clients:** Keep **all** existing clients visible and usable in directory/ledger; only block **new** creates when Free and `clients_created_count >= 5`.
* **Pro package switch:** Prior package data is **retained locked** (no wipe, no casual access); new package unlocks empty/fresh tools.
* **Pro selection is profession-gated:**
  * `pro-med` ↔ profession `Medical Professional` only
  * `pro-arch` ↔ profession `Architect / Perit` only
  * `pro-eng` ↔ profession `Engineer` only
  * Other professions: Free or Standard only. Onboarding + Settings DEV switch must enforce this (reject mismatched tier).

### Medical / GDPR data across tier changes (priority)

**Honest status today:** Not adequately catered. Clinical fields (DOB, gender; controller also accepts blood type/allergies) sit on billing `clients.profile_data`. Tier changes currently only flip `users.tier` — they do **nothing** special for sensitive data. That is unacceptable for launch and is why Phase 0 containment + locked retention rules exist.

**Target model (Phase 0 containment + Phase 4 schema):**

| Data class | Where it lives | Sensitivity | Tier change behaviour |
|---|---|---|---|
| Billing Client (name, email, phone, address, VAT, ID card) | `clients` + billing-only `profile_data` | PII (commercial) | Always available on every tier; subject to Free create cap rules |
| Clinical / health data (allergies, blood type, journals, Rx, diagnoses) | **Never** on `clients` after Phase 0. Phase 4: separate clinical store | Special category (GDPR Art. 9) | Accessible **only** while `tier === pro-med` **and** profession is Medical Professional |
| Patient identity store (Phase 4) | Separate from clinical payload; opaque link to billing client optional | PII | Same lock as clinical when not on `pro-med`; delinked so a journal row is not one JOIN away from email/ID card |

**Rules to encode (Phase 0 policy now; Phase 4 storage later):**

1. **Phase 0 stop-the-bleed:** Remove clinical UI/writes from Clients; scrub any clinical keys from `profile_data` via manual SQL. After this, **tier changes cannot “leak” health data via Client** because it is not stored there.
2. **No auto-migration of Clients → Patients** on upgrade to `pro-med`. Upgrading does not turn a billing client into a clinical patient. User explicitly creates patient/journal records in Pro Medical (Phase 4).
3. **Downgrade `pro-med` → standard/free (or switch to `pro-arch`/`pro-eng`):**
   * Clinical + patient stores become **retained locked**: no list/read/write/export via normal app routes; middleware 403/upgrade gate.
   * Billing clients/invoices remain fully usable.
   * Data is **not** deleted (GDPR erasure is a separate user/support right, not a side effect of billing downgrade).
4. **Re-upgrade to `pro-med`:** Same owner regains access to their locked medical records (tier + profession checks both pass).
5. **Profession gate + tier gate are AND conditions** for medical routes: wrong profession or wrong tier → deny. Prevents “Tutor on pro-med” and “Doctor on Free” from opening journals.
6. **Audit expectation (Phase 4+):** Access to special-category data should be logged (who/when); lock/unlock on tier change is an entitlement event, not a delete event.
7. **Backup / export of medical data:** Never deliver plaintext patient/clinical data without the practitioner’s recovery code (see **Practitioner-held encryption key** below). Downgrade does not email a silent ZIP.
8. **Cerulean Labs must not be able to read patient clinical payloads at rest** once live encryption ships — support cannot “reset” or recover medical content without the practitioner’s code.

### Practitioner-held encryption key (go-live medical requirement)

**Intent (product language may say “hash code”):** Patient/clinical data at rest is **encrypted** with a key derived from a **recovery code** shown once to the doctor. This is **not** one-way hashing of the records (hashing would make normal app use impossible). Cerulean Labs / PractisBase **does not store the plaintext recovery code** and cannot decrypt patient payloads without it.

**Lifecycle:**

1. **First activation of Pro Medical (or first patient record):** System generates a high-entropy recovery code; display once with copy/download-safe presentation; require explicit confirmation that the doctor has saved it offline.
2. **Legal acknowledgment (blocking):** Doctor must sign/accept: *“I understand that if I lose this recovery code, my patient data will be permanently unrecoverable by me or by Cerulean Labs. PractisBase cannot reset this key.”* Persist acceptance timestamp, IP, and code challenge (same pattern as T&Cs: `accepted_at`, `accepted_ip`, optionally read-duration). Store only a **verifier** (e.g. salted hash of the recovery code) if needed to validate future entry — never the code itself in recoverable form.
3. **At rest:** Clinical/patient special-category fields encrypted. App session unlock: doctor enters recovery code (or unlocks via session key derived after code entry) to use journals in-product.
4. **Backup / data download:** Export flow **must** prompt for the recovery code. Only after successful verification does the download contain **decrypted** patient data. Wrong/missing code → no plaintext export (encrypted blob only is unacceptable as a “backup” UX — fail closed).
5. **Lost key:** No server-side recovery of the old code. Doctor may open a **new vault** with a **new** recovery code; the **previous vault ciphertext stays on file** (still encrypted) so if the old code is later found, that vault can be unlocked again. Labs cannot unlock either vault. **Weekly medical backup is mandatory** (acknowledgment + overdue reminders). **New-vault setup must guide upload/restore of the latest backup** into the new vault (re-encrypted under the new key) so the worst-case loss window is **about one week** (time since last successful backup).
6. **Tier downgrade / retain locked:** Ciphertext remains; without recovery code + `pro-med` entitlement, no decrypt in app. Re-upgrade still requires the **same** recovery code for that vault to unlock historical data.
7. **Billing Clients / invoices / fiscal data:** Not covered by this vault (commercial ledger remains usable with normal auth). Only patient/clinical stores use the practitioner key.

**Phase placement:**

| Phase | Work |
|---|---|
| **0** | Document requirement; do not invent Client-side clinical storage that fights this model |
| **4** | Implement vault schema, encryption, one-time code reveal, signed acknowledgment, session unlock, export-with-code |
| **6** | Launch legal review of acknowledgment wording; ensure T&Cs + medical addendum align; no go-live of Pro Medical without this |

**Phase 0 vs Phase 4 split:**

| Phase | GDPR deliverable |
|---|---|
| **0** | Eliminate clinical-on-Client; encode TierPolicy locks for future medical routes; profession-gated Pro selection; document retain-locked + practitioner-key encryption requirements |
| **4** | Delinked patient PII vs clinical tables; encrypt clinical vault with practitioner-held recovery code; lock on leave `pro-med`; export only with code; signed unrecoverability acknowledgment |

If clinical keys still exist in DB before Phase 0 scrub ships, treat them as **incident inventory**: scrub SQL is mandatory in Phase 0, not optional.

### Tier Transition Matrix (Phase 0 — design + encode)

Canonical tiers: `free` | `standard` | `pro-med` | `pro-arch` | `pro-eng`.  
**Standard+** = everything except `free`. **Pro** = any `pro-*`.

#### Invariants (all permutations)
1. **`clients_created_count` never resets** and never decrements (not on delete, not on downgrade, not on upgrade).
2. **No data destruction on plan change.** Invoices, clients, payments, tax payments, expenses (when built), Pro module rows — retained.
3. **Entitlements are evaluated at request time** from `users.tier` (later: Stripe subscription status). Middleware/helpers are the source of truth; Blade nav is cosmetic.
4. **Profession is independent of tier.** Profession stays support-only to change. Fifth Schedule VAT exempt follows **profession === Medical Professional**, not `tier === pro-med`. A tutor on `pro-med` (if allowed) does not become VAT-exempt; a doctor on `free` remains VAT-exempt.
5. **Billing Client ≠ Patient.** After Phase 0 medical containment, Clients stay commercial counterparties on every tier. Pro Medical journals (Phase 4) are separate records; upgrading Free→`pro-med` does not reinterpret existing clients as clinical patients.

#### Capability matrix

| Capability | free | standard | pro-med | pro-arch | pro-eng |
|---|---|---|---|---|---|
| Dashboard + Clients + Ledger | yes | yes | yes | yes | yes |
| Create client | if lifetime &lt; 5 | unlimited | unlimited | unlimited | unlimited |
| Live Fiscal Report `/reports` | no | yes | yes | yes | yes |
| Expenses / docs / branding / TA22 / Accountant Download | no | yes | yes | yes | yes |
| Patient Journals / Rx / referrals | no | no | yes | no | no |
| Architect DMS / stamper / phases | no | no | no | yes | no |
| Engineering projects | no | no | no | no | yes |
| Certificates & declarations (shared Pro) | no | no | yes | yes | yes |
| EMS/BMS templates | no | no | no | no | later |

#### Upgrade paths (examples)

| From → To | Clients | Fiscal report | Pro modules | Notes |
|---|---|---|---|---|
| free → standard | Unlock unlimited creates; lifetime counter kept | Unlock `/reports` | — | Existing ≤5 clients remain; can add more |
| free → pro-med (only if profession Medical Professional) | Same as → standard | Unlock | Unlock Medical package only | Clients stay billing-only; journals start empty (Phase 4). Mismatched profession → reject. |
| standard → pro-* | Still unlimited | Still on | Unlock that package | |
| pro-X → pro-Y (switch) | Still unlimited | Still on | **Lose X UI/API; gain Y.** X data retained but locked (read-only export later; no delete) | Switching Medical→Arch must not wipe journals |

#### Downgrade paths (examples)

| From → To | Clients | Fiscal report | Prior paid data |
|---|---|---|---|
| standard/pro → free | **Keep all existing clients** (even if &gt;5 or lifetime &gt;5). **Block new creates** if `clients_created_count >= 5`. Deletes still do not free slots. | **Revoke** `/reports` (upgrade CTA) | Expenses/docs/Pro rows retained; routes return upgrade gate. Ledger/invoices for existing clients remain fully usable (core Free capability). |
| pro-* → standard | Unlimited creates remain | Remains | Pro package UI/API revoked; Pro data retained/locked |
| pro-X → free | Same Free downgrade rules | Revoke | Standard + Pro feature data retained/locked |

#### Edge cases to encode in Phase 0 helpers (not leave implicit)

| Scenario | Required behaviour |
|---|---|
| Free user creates 5 clients, upgrades to `pro-med`, creates 10 more, downgrades to Free | Lifetime count = 15 → **cannot create** any new client. All 15 clients + their invoices remain accessible in directory/ledger. No `/reports`. No journals UI. |
| Free user at 3/5 upgrades to standard then back to Free without new creates | Lifetime still 3 → can create 2 more. |
| User on `pro-med` with journals (Phase 4) switches to `pro-arch` | Journals inaccessible; **ciphertext retained**; unlock later only with same recovery code + return to `pro-med`. Arch tools empty/fresh. |
| User on `pro-med` downgrades to Free | Journals locked (encrypted at rest); billing clients untouched; `/reports` locked. |
| Medical backup download without recovery code | **Denied** — no decrypted export. |
| Medical profession on Free | VAT exempt via profession; still no `/reports`; still 5-client lifetime cap. |
| Non-medical profession selects `pro-med` at onboarding | **Rejected** — Pro packages are profession-gated. Show error / disable mismatched plan cards. |
| Medical profession selects `pro-arch` | **Rejected** — same gate. |
| Downgrade `pro-med` while journals exist (Phase 4) | Journals **retained locked**; billing clients unaffected; `/reports` follows Standard+ vs Free rules. |
| Downgrade while fiscal year closed | Year-lock still wins for mutations; tier gate is separate. |
| Stripe grace / failed payment (Phase 6) | Entitlement drops per webhook policy using same downgrade rules — **still no data wipe**; medical stores lock if leaving `pro-med`. |

#### Phase 0 deliverable for transitions (code, not just docs)
* Centralise entitlements in one place, e.g. `App\Support\TierPolicy` or `User` methods:
  * `isPaid()`, `isPro()`, `proPackage()`, `canAccessReports()`, `canAddClient()`, `lifetimeClientCount()`, `canAccessStandardTools()`, `canAccessProPackage('med'|'arch'|'eng')`, `allowedTiersForProfession()`, `assertTierAllowedForProfession($tier)`
  * `assertCanAddClient()` used by `ClientController@store`
* Onboarding `savePlan` + future Settings plan switch: reject Pro tier that does not match profession.
* Any future Settings DEV plan switch / Stripe webhook **only changes `users.tier`** (after profession gate) and relies on these helpers — no per-controller special cases.
* Middleware `tier:standard` means “Standard+” (includes all `pro-*`). Middleware `tier:pro-med` means exact package.
* Sidebar Soft gates call the same helpers.
* Do **not** implement full Settings subscription UX in Phase 0 if deferred — but **do** implement the policy object + profession gate on plan submit.

#### Locked decisions (confirmed)
1. Downgrade to Free with &gt;5 existing clients → **keep all visible**.
2. Pro package switch → **retain locked** (no export-then-delete by default).
3. Pro tier selection → **profession-gated**.
4. **Go-live patient/clinical data:** Encrypted at rest; **practitioner holds the only recovery code**; backup/download decrypt requires that code; doctor must sign that **lost code = permanently unrecoverable** (Cerulean Labs cannot reset).
5. **Pre-start List A — accepted:** Doctor = controller / Labs = processor (support never reads/resets recovery code); password reset ≠ vault unlock; single-user v1; vault = clinical only; not sole clinical EPR; no clinical emails.
6. **Hosting:** Prefer EU/EEA for production. Current host plan: **Railway (basic/Hobby paid tier)**. EU is achievable: Railway documents **EU West Metal (Amsterdam)**; Hobby users may need the **Metal Volumes** feature flag (`railway.com/account/feature-flags`) then set Postgres/app region under Settings → Regions. **Verify region before Pro Medical go-live** (patient data). Ledger-only development can proceed on whatever region is live today. Not a Phase 0 coding blocker.
7. **Pre-start List B — deferred:** Tax retention vs account deletion, GDPR erasure vs lost-key, DPA wording — **not decided**; resolve with legal before Pro Medical / account-deletion go-live (Phase 6). Do not block Phase 0.
8. **C1 — Lost recovery code:** Doctor may create a **new vault with a new key**. The **old vault ciphertext remains stored** (still encrypted) in case the old key is later found. **New-vault wizard guides restore from latest weekly backup** (upload backup → import into new vault under new key). Target: **max clinical data loss ≈ one week** if backups were kept. Mandate weekly backup + overdue nag; acknowledgment covers key-loss risk and backup duty. Labs cannot recover without a code/backup the doctor holds.
9. **C2 — Vault unlock (revised — decision #24):** Login password and vault recovery code stay **separate**. On a **trusted device**, unlock with **platform WebAuthn** (phone biometrics, Touch ID, Windows Hello / platform PIN) after first unlock with the recovery code. New devices, revoked devices, and full recovery still require the recovery code. Store the code as a **Secure Note** (not as the PractisBase website password). Vault forms use `type="text"` + ignore attributes so browsers/password managers do **not** offer to save/update the site login password.
10. **C3 — Arch/Eng files:** Normal account auth only in v1 (no practitioner-held encryption).
11. **C4 — Accountant pack (Standard + all Pro):** No accountant login seat in v1. Instead, Standard+ users get an **Accountant Download** — a purpose-built export that presents the **full ledger for their accountant** (full details: clients/counterparties as needed for books, invoices, RFPs distinction, credit notes, payments, expenses when built, VAT breakdown, tax payments / PT where relevant). Doctor downloads and sends the file themselves. Free tier: not included.
12. **C5 — Client delete:** **Soft-archive** (hide; keep rows for invoice history). Lifetime `clients_created_count` still never decrements.
13. **VAT number at signup/onboarding:** **Optional.** Collect VAT **status** (Art 10 / 11 / exempt) during fiscal onboarding so ledger math works; do **not** require an MT VAT number to finish signup. Many starters are not VAT-registered yet. Ask for the number in Settings anytime; **hard-require only when it is legally needed to issue** — i.e. Article 10 users creating an invoice or applying 18% VAT (document must show the supplier VAT ID). Article 11 / exempt / medical: number remains optional (show on PDFs if present).
14. **Forgot password:** Login must offer a forgot-password flow (email reset link). Password reset **never** unlocks the medical vault (already locked in List A). Build in Phase 2 with core auth UX.
15. **Certificates & declarations:** Shared register at `/pro/certificates` remains for **Arch/Eng** (create/edit/stamp there for now; separate structure later). **Medical** authors stampables only under the patient; the Medical nav item opens the `/pro/medical/stampables` ledger instead (decision #23). Document Stamper remains Arch-primary for PDF stamp sheets.
16. **Encrypted journal attachments (photos / files):** Doctors may attach images and documents to clinical journal entries. Attachments are **special-category data** and follow the same vault rules as journal text — never plaintext at rest, never Labs-decryptable, never served without vault unlock. Architecture locked below; ship in **Phase 5** (after Phase 4 vault foundations).
17. **Signed Document Commerce (Phase 7 — last roadmap phase):** Practitioners create, sign, and stamp professional documents on PractisBase, issue a **one-time-use patient/client payment link**, collect payment, then release a **single download**. First vertical: **medical prescriptions**. Same rails later for architects (stamped drawings / declarations) and engineers (certificates). Depends on Phase 5 PDFs + Phase 6 Stripe. Do **not** start before launch billing is real.
18. **Closed beta before Stripe:** Invite beta testers **without** live card billing. Keep the Settings DEV / admin plan switch (or grant tiers manually) so testers exercise Free / Standard / Pro. **Do not** implement Stripe + coupons/vouchers just to give free beta access — that adds payment, webhook, and support surface area before product feedback lands. Stripe (Phase 6) ships when beta feedback has stabilized and you are ready to charge. Promo codes / complimentary periods are fine **inside** Phase 6 once Checkout exists, not as a reason to pull billing forward.
19. **Arch / Eng Pro depth is expert-guided:** Scaffolding (projects, stamper shell, shared certificates) may exist, but **niche Architect and Engineer workflows must not be invented by the agent**. Nicholas (domain expert) will specify exact document types, phases, BCA/EMS/BMS behaviours, and field schemas before those modules are deepened. Prefer thin shells + placeholders over guessed industry logic.
20. **Patient ↔ Client optional link (create once):** Doctors must not retype the same person for journals and invoices. `patients.billing_client_id` is an **opaque optional FK** to a billing Client owned by the same user. Prefill patient display name from the Client when linking. Clinical fields (DOB, notes, journals, Rx) stay encrypted in the vault only — never copied onto `clients.profile_data`. Invoices/RFPs stay on the Client. One Client may link to at most one Patient (and vice versa for the active link). Unlinked patients (clinical-only) and unlinked clients (billing-only) remain valid. **Patient-first path:** from the patient record, create a billing Client (name prefilled; email/phone/address collected) and auto-link — still no clinical fields on the Client. Search/filter on the patient directory runs **after vault unlock** on decrypted session data (names are not plaintext in Postgres).
21. **Stamp & issue lock (all stampable professional documents):** Prescriptions, referrals, and medical certificates (clinical entries) plus shared Pro certificates/declarations are **editable while draft**. Pressing **Stamp & issue** sets `issued_at` / `stamped_at` and makes the record immutable (no edit, no new attachments). Official PDF download is available only after issue for clinical stampables. Journal notes are never stamped and remain editable. Same immutability rule applies to future Arch/Eng stampable stored documents.
22. **Unique issue codes + printable PDF templates:** Every stampable document gets a unique `issue_code` (e.g. `RX-…`, `RF-…`, `MC-…`, `CT-…`) and issue timestamp at Stamp & issue. Code + date print on the official PDF authenticity mark so reprints/reuse can be flagged via practitioner lookup (`/pro/medical/issue-codes/lookup`, `/pro/certificates/lookup`). Type-specific Blade PDF templates live under `resources/views/pro/medical/pdf/` (prescription / referral / certificate) and `resources/views/pro/certificates/pdf.blade.php`. Manual SQL: `database/manual/phase5_issue_codes_postgresql.sql`. Custom letterhead layouts may replace these templates when the domain expert supplies them — keep the authenticity mark.
23. **Medical stampables unified under patient; Certificates menu = ledger:** Doctors create/edit/stamp prescriptions, referrals, and medical certificates **only** from the patient clinical entry flow. The Medical nav item **Certificates & Declarations** points to `/pro/medical/stampables` — a quick-find register of **all** clinical stampables with search/filter (type, draft/issued, patient, issue code) plus authenticity lookup. `/pro/certificates` create/edit remains for **Arch/Eng only** (separate structure later). Medical hits to `/pro/certificates` redirect to the stampables ledger; legacy medical rows in `certificates` appear read-only on the ledger. Clinical certificate payloads include kind / subject / expiry (encrypted); create/edit UI and PDF templates are type-specific so stampables do not look like journal notes.
24. **Trusted-device vault unlock (WebAuthn):** Do **not** unify the PractisBase login password with the medical vault recovery code. After unlocking once with the recovery code, the doctor may register this browser/OS as a trusted device via **platform authenticator** (Face ID, fingerprint, Touch ID, Windows Hello). Server stores the WebAuthn public key + a DEK wrapped with a random wrap key; the wrap key is returned once and kept in **IndexedDB** on that browser only. Later sessions: WebAuthn assertion + local wrap key → unwrap DEK into session. Revoking a device deletes the server row; clearing browser storage also forces recovery-code unlock. Aligns with zero-knowledge / password-manager trusted-device patterns, not org-held EHR keys. Manual SQL: `database/manual/phase5_vault_trusted_devices_postgresql.sql`.

### Encrypted journal attachments — how we tackle it (decision #16)

**Problem:** A clinical photo or PDF in R2 without encryption would break the practitioner-held vault promise (Labs or a bucket leak could read health images).

**Approach (server-side encrypt with vault DEK — same trust model as journal ciphertext):**

1. **Upload only while vault unlocked.** Controller refuses attachment writes if session DEK is absent.
2. **Encrypt file bytes with the vault DEK** (libsodium secretbox / same helper family as clinical fields). Prefer chunked encryption for larger files; store nonce + ciphertext only.
3. **Store ciphertext on private R2** via `TenantStorage` under a medical path, e.g. `medical/{user_id}/vault_{vault_id}/attachments/{uuid}.bin`. Bucket stays private; no public object URLs.
4. **DB metadata row** (scoped `user_id` + opaque patient/entry ids): encrypted original filename + mime (or encrypt as one JSON blob), byte size, ciphertext storage key, content hash of ciphertext, created_at. Never store plaintext filename in searchable cleartext if it can contain clinical clues (prefer encrypted).
5. **Download / view:** Auth + vault unlock → decrypt in memory / stream → response. No long-lived plaintext cache on disk. Optional in-session thumbnail only (do not persist plaintext thumbs).
6. **Allowlist + limits:** e.g. `image/jpeg`, `image/png`, `image/webp`, `application/pdf`; hard max size (e.g. 10–15 MB). Reject executables and exotic types.
7. **Weekly backup / new vault restore:** Attachment ciphertext must be included in the medical backup pack (still encrypted under the vault key, or re-wrapped for the export format). New-vault restore re-encrypts under the new DEK like journal rows.
8. **Tier lock:** On leave `pro-med`, attachment ciphertext retained locked with the vault — same as journals. No plaintext export on downgrade.
9. **Not the same as Standard Document Storage:** Expense receipts / logos / Arch DMS use normal account auth (decision C3). Only **Pro Medical journal attachments** use the vault DEK.

**Explicit non-goals for v1:** Client-side-only encryption in the browser; end-to-end patient-facing photo upload portals; OCR of attachment contents into cleartext search.

### Signed Document Commerce — Phase 7 shape (decision #17)

**Happy path (prescriptions):**

1. Doctor (vault unlocked) creates a prescription from a patient/journal context.
2. System generates a branded PDF; doctor **signs and stamps** (reuse Document Stamper patterns / warrant overlay).
3. Doctor sets a fee (or €0 free issue) and creates a **delivery link** — high-entropy one-time token, expiry, optional max views = 1 after payment.
4. Patient opens link (no PractisBase account required): sees practitioner name, document type, amount, pays via **Stripe Checkout** (Connect or destination charge so **the doctor is paid**, not Labs).
5. On `checkout.session.completed`: mark token **paid**; allow **exactly one** PDF download (or short download window then burn). Unpaid / expired / already-used → dead link.
6. **Fiscal integrity:** Successful patient payment must land in the doctor’s PractisBase ledger (invoice + payment, or equivalent income document) so VAT/income tax math stays honest. RFP rules still apply if they issue a pro-forma first — official fiscal weight only on invoice.
7. **Clinical privacy:** Public link page must **not** expose diagnoses, full journal, or patient identifiers beyond what the Rx PDF itself needs. Prefer opaque token; avoid putting patient name in the URL.
8. **Storage:** Paid PDF ciphertext or access-controlled private object; never a permanent public S3/R2 URL. After burn, further GETs 410/404.
9. **Extensibility:** Same `document_deliveries` rails for Arch stamped packs and Eng certificates — document type enum, profession-gated templates, shared payment + one-time download machinery.

**Dependencies:** Phase 4 vault + stamper foundations; Phase 5 Rx/PDF generation; Phase 6 real Stripe + webhooks; legal review (telemedicine / Rx issuance rules in Malta — product is a tool, not a substitute for professional duty).

### ROADMAP STATUS: LOCKED — Phase 0–5 shipped; closed beta before Stripe (#18); revisions #13–24 applied
Product decisions above are frozen for build. Further changes require an explicit revision. Next: beta hardening / Arch–Eng only with expert briefs; **Phase 6 Stripe last among monetization steps** (before Phase 7 document commerce).

### 1. Free Tier (€0/mo)
* **Limits:** **5 lifetime Clients** (enforced in controller + surfaced in UI as e.g. `3 / 5 used`). Deletion does not decrement usage.
* **Capabilities:** Basic Invoices & Ledger (RFPs, official invoices, payments received), Summary Dashboard, Standard Support.
* **Out of scope for Free:** Live Fiscal Report, Expenses, Document Storage, custom branding, Accountant Download, Automated TA22 generation, Pro modules. *(Nav soft-hides; Phase 1 hardens with middleware.)*

### 2. Standard Tier (€15.99/mo)
* **Limits:** Unlimited Clients.
* **Capabilities:** Everything in Free, plus **Live Fiscal Report**, Custom Branding & Logo on documents, Expense Tracking & Receipts, Document & File Uploads, Automated TA22 Form generation, **Accountant Download** (full ledger pack for the user’s accountant).

### 3. Pro Tiers (€49.99/mo)
All Pro tiers include everything in Standard, plus one industry package **and** shared **Certificates & Declarations**:
* **Pro Medical (`pro-med`):** Secure Patient Journals (incl. encrypted attachments), Digital Prescriptions, Referral Letters, medical certificates / fitness declarations. *GDPR: PII must be delinked from medical details in the database. Paid one-time Rx delivery = Phase 7.*
* **Pro Architect (`pro-arch`):** Architect DMS, Document Stamper, Project Phase Tracking, professional declarations. *BCA-aligned Method Statements / declarations.*
* **Pro Engineer (`pro-eng`):** Engineering projects, EMS / BMS Templates (later), certificate logs with photo + expiry. *Technical specs export later.*

**Canonical tier values in code today:** `free`, `standard`, `pro-med`, `pro-arch`, `pro-eng` (stored on `users.tier`).

---

## TODAY'S STATUS: What Is Already Complete

The core fiscal engine is functioning:

* **Math Engine:** `ReportController` calculates multi-tiered Maltese tax logic (TA22 spillover, SSC caps, VAT thresholds, PT settlement).
* **Database foundation:** PostgreSQL schemas for `users`, `clients`, `invoices`, `payments`, `tax_rates` (JSON), plus in-use tables `tax_payments` and `fiscal_years` (no Eloquent model for fiscal years; no in-repo migrations for those two — manage via manual SQL per `.cursorrules`).
* **Live Fiscal UI:** `reports/index.blade.php` — CSS variables, warnings, clickable modal breakdowns, Article 11 progress.
* **Government Ledger:** Provisional Tax / VAT payments with Vanilla JS smart guides updating Final June Settlement.
* **Strict constraints already in places:** Future-dating blocked in key UIs, tax-rate year fallback, fiscal year lock checks in reports mutations.
* **Auth & onboarding:** Custom session auth (not Breeze); register with T&Cs scroll/checkbox/IP/duration; profession → financial → plan selection (Stripe currently bypassed). VAT **status** required on financial step; VAT **number** optional until Art 10 invoice / apply-VAT. Forgot password: Phase 2.
* **Settings (partial):** Fiscal profile + password at `/settings`; **no subscription management panel yet**.
* **Clients / Ledger / Dashboard:** Functional CRUD surfaces exist; Convert-to-Invoice and credit/refund flows exist in `InvoiceController`.
* **Soft tier UI:** Sidebar shows Standard/Pro nav stubs based on `tier`; routes are not middleware-gated.

### Known gaps discovered in codebase audit
| Gap | Severity | Phase |
|-----|----------|-------|
| Clinical fields on billing `clients.profile_data` | Critical | **0** containment |
| `client_id` exists without `user_id` on invoice create | Critical | **0** |
| `target_invoice_id` exists without `user_id` (transfer) | High | **0** (validation consistency; query already scopes) |
| Ledger/payment mutations ignore closed `fiscal_years` | High | **0** (reports only today) |
| Document/payment dates: UI `max=today` but server often omits `before_or_equal:today` | High | **0** |
| Settings: profession shown readonly but POST can change it | High | **0** |
| `TaxPayment` `$guarded = []` | Medium | **0** |
| Document numbers derived from `latest id + 1` | Medium | **0** (pre-launch renumber strategy) |
| Dead layout branch `tier === 'pro'` | Low | **0** cleanup |
| No central TierPolicy for upgrade/downgrade permutations | Critical | **0** encode matrix |
| Free 5-client lifetime quota not enforced | High | **1** (uses Phase 0 policy) |
| `/reports` open to Free (should be Standard+) | High | **1** middleware using Phase 0 policy |
| No tier / onboarding middleware | High | **1** |
| Settings has no subscription panel | Medium | **1** |
| Full Pro Medical delinked schema | Critical later | **4** |
| Stripe / Cashier absent | Launch | **6** |

---

## Roadmap to Completion (End in Mind)

Phases are ordered so later work never fights earlier architecture. Each phase ends with a shippable, testable slice.

### Why medical data was scary — and where the fix lives

**What is wrong today (not theoretical):** For `profession === 'Medical Professional'`, Client create/edit shows a **Clinical Profile** block (DOB, gender). `ClientController` writes those into the same `clients.profile_data` JSON used for billing extras (VAT number, ID card, etc.), and also accepts `blood_type` / `allergies` even when the form does not show them. `clients/show` dumps the whole JSON. That mixes **billing identity** with **health-adjacent data** on one row — the opposite of the GDPR delink rule in `.cursorrules` / Pro Medical.

**Why you almost did not see a fix:** The original brief parked "delink PII from medical details" inside **Phase 4 (Pro Medical)**. That is the right place for the *full* patient/journal schema, but it is the **wrong** place to keep collecting clinical fields on Clients. With no real users, we can and must **contain** this in Phase 0 so no bad shape hardens before launch.

| When | What | Intent |
|------|------|--------|
| **Phase 0 (now)** | Stop the bleed — remove clinical UI/controller writes; scrub JSON; billing-only `profile_data` | No new mixed data |
| **Phase 4** | Build proper Pro Medical tables (opaque patient ref ≠ clinical journal payload) | Real GDPR architecture |

### Phase 0 — Integrity & Containment (FIRST)
*Outcome: Wrong data shapes and fiscal/security holes are fixed while there are no real users. No SaaS monetization UI yet — that is Phase 1.*

**Rule of thumb for Phase 0:** If leaving it broken means later features inherit a bad invariant (IDOR, clinical-on-client, open fiscal years, future-dated books), it belongs here.

#### 0A. Tenant IDOR & mass assignment
1. `InvoiceController@store`: `client_id` => `exists:clients,id,user_id,{Auth::id()}`.
2. Same ownership pattern on every `exists:…,id` foreign key (e.g. `target_invoice_id` on payment transfer).
3. Pass audit of Client / Invoice / Payment / TaxPayment paths for `user_id` scoping.
4. `TaxPayment`: replace `$guarded = []` with explicit `$fillable`.

#### 0B. Medical containment (mandatory)
1. Remove Clinical Profile from `clients/create` and `clients/edit`.
2. Stop writing `dob`, `gender`, `blood_type`, `allergies` (any clinical keys) in `ClientController`.
3. `profile_data` = billing/identity only (`vat_number`, `registration_number`, `contact_person`, `id_card_number`).
4. Whitelist those keys on `clients/show` (no raw dump).
5. Manual PostgreSQL scrub of clinical keys from existing `profile_data`.
6. Do **not** build Patient Journals here — Phase 4.

#### 0C. Fiscal closure & no future dating (`.cursorrules`)
1. Shared helper e.g. `assertFiscalYearOpen($userId, $year)` used by Reports **and** ledger mutations (create/convert/credit/pay/refund/delete payment).
2. Server validation `before_or_equal:today` on `issue_date`, `payment_date`, `refund_date` (UI `max` is not enough).
3. `due_date` may remain in the future (payment terms); do not future-date issue/payment/refund.

#### 0D. Profile integrity
1. Settings: do **not** accept `profession` changes from POST (match the “contact support” UI). Base VAT medical rules on the stored profession, not a tampered request field.

#### 0E. Document numbering (pre-launch cheap fix)
1. Replace `latestDoc->id + 1` with a per-user, per-type, per-year sequence (count/max of existing numbers or a counter column) so deletes/concurrency cannot collide or skip oddly.

#### 0F. Small cleanups
1. Remove dead sidebar branch `tier === 'pro'` (canonical values are `pro-med` / `pro-arch` / `pro-eng`).
2. Manual ANSI SQL notes for `tax_payments` / `fiscal_years` if incomplete — **no migrations unless requested**.

#### 0G. Tier transition policy (encode now)
1. Implement `TierPolicy` / `User` entitlement helpers per **Tier Transition Matrix** + **Medical / GDPR** section (`canAddClient`, `canAccessReports`, `canAccessProPackage`, `assertTierAllowedForProfession`, etc.).
2. Add manual SQL for `users.clients_created_count` (integer not null default 0).
3. Enforce profession-gated Pro selection on `AuthController@savePlan` (and any DEV tier switch).
4. Wire sidebar Soft gates to the same helpers (no duplicated `in_array(tier, …)` sprawl).
5. Medical containment (0B) is a GDPR prerequisite so tier changes cannot expose health fields on Clients.
6. Acceptance scenarios:
   * free(5 creates) → pro-med → +N clients → free: create blocked; all clients visible; `/reports` denied.
   * free(3) → standard → free: 2 creates remain.
   * Tutor cannot select `pro-med`; Medical Professional cannot select `pro-arch`.
   * pro-med → pro-arch: med routes denied; arch allowed (stubs OK); no data delete (retain locked).
   * standard → free: `/reports` denied; ledger OK.

#### 0H. Companion UI only (no redesign)
*UI that must change for Phase 0 backend fixes to be honest — not dashboard polish or Settings subscription.*

1. **Medical:** Remove Clinical Profile blocks from Client create/edit; Client show whitelists billing keys only (no `@foreach` dump of `profile_data`).
2. **Profession:** Settings profession field `disabled` (not merely `readonly` — readonly still POSTs) + helper text; server ignores profession (0D).
3. **Dates:** Confirm `max="{{ date('Y-m-d') }}"` on every issue/payment/refund date input that 0C validates (create + actions partial already mostly correct).
4. **Nav integrity:**
   * Keep Live Fiscal Report under Standard+ nav (not Free). Fix the malformed bare `<a>` into a proper `<li><a class="nav-link">` inside Standard Tools. Drive visibility via `canAccessReports()` / paid helpers.
   * Delete the dead `tier === 'pro'` duplicate Pro Tools block.
   * Prefer **hide** stub `#` links for unbuilt features until they exist (optional in Phase 0; full tier wiring in Phase 1).
5. **Closed fiscal year (0C):** Where ledger actions would mutate a locked year, disable buttons / show a short lock banner (mirror reports closed-year UX) — no silent server 400s only.

#### 0I. Phase 0 acceptance criteria
* [ ] Cross-tenant `client_id` rejected on invoice create.
* [ ] No clinical UI or persistence on Clients; JSON scrubbed; show page billing-only.
* [ ] Closed-year ledger mutation blocked; UI disables/explains when locked.
* [ ] Future `issue_date` / `payment_date` / `refund_date` rejected server-side; matching `max=today` on inputs.
* [ ] Profession cannot be changed via Settings (UI + server).
* [ ] `TaxPayment` not unguarded.
* [ ] Document numbers stable under delete/recreate.
* [ ] Dead `tier === 'pro'` branch gone; reports nav link structurally fixed (still Standard+-gated).
* [ ] `TierPolicy` (or equivalent) covers Free↔Standard↔each Pro upgrade/downgrade/switch rules; `clients_created_count` column present; downgrade never wipes data.

**Explicitly NOT Phase 0 UI:** Settings subscription card polish, full `N / 5` marketing banners, dashboard redesign, building Expense/Pro screens, visual redesign / new CSS frameworks. (Counter + `canAddClient` are Phase 0; pretty Settings CTA is Phase 1.)

### Phase 1: Subscriptions, T&Cs & Multi-Tenant Security
*Outcome: Free lifetime 5-client cap; tier middleware for paid extras; Settings plan card; onboarding/T&Cs cannot be skipped. Tenant IDOR already closed in Phase 0.*

#### 1A. Lifetime Free client quota (UI + enforcement on top of Phase 0 policy)
* Enforce creates via `canAddClient()` / `assertCanAddClient()` in `ClientController@store` (and block create UI when at cap).
* Settings + create page show `N / 5` lifetime usage and explain deletes/downgrades do not restore slots.
* Relies on Phase 0 `clients_created_count` + Tier Transition Matrix — do not reimplement rules in the controller.

#### 1B. Tier feature middleware
* `EnsureUserTier` aliased as `tier` in `bootstrap/app.php`, backed by the same Phase 0 `TierPolicy`.
* **Gate `/reports` behind Standard+.** Free → upgrade redirect/flash.
* Prepare gates for Expenses / Document Storage / Accountant Download / TA22 / Pro packages.
* Settings DEV plan switch only updates `users.tier`; run through transition acceptance scenarios.

#### 1C. Settings — subscription & profile
* Subscription card: tier badge, `N / 5` lifetime usage copy (deletes do not restore), upgrade CTAs that call out unlimited clients **and** Live Fiscal Report (DEV plan switch OK until Stripe).
* Keep fiscal/password sections intact (no UI regression). Profession remains immutable here (Phase 0).

#### 1D. Legal T&Cs & onboarding gates
* `EnsureTermsAccepted` + `EnsureOnboardingComplete` so incomplete users cannot hit app routes.
* Leave room for future `terms_version` (manual SQL later).

#### 1E. Phase 1 acceptance criteria
* [ ] Free after 5 creates: further creates rejected even if clients were deleted.
* [ ] Settings shows lifetime `N / 5` for Free.
* [ ] Free cannot open `/reports` (nav hidden + middleware upgrade path).
* [ ] Standard+ can open `/reports`.
* [ ] Incomplete onboarding cannot use `/dashboard` / `/ledger` / `/clients`.
* [ ] Paid-only routes blocked for Free with upgrade path.
* [ ] CSRF + `user_id` scoping preserved; no Tailwind/React.

### Phase 2: Main Dashboard, Core Ledger UI & Auth Completeness
*Outcome: Daily operating surface is polished and feeds the math engine correctly; login/auth gaps closed.*

* Enrich `/dashboard` KPIs (current-year focus, unpaid/overdue, quick actions) without turning Free into a full fiscal cockpit.
* Finalize ledger UX: create/edit/list Clients, RFPs, Invoices; keep Convert-to-Invoice / credit / payment flows solid.
* Ensure RFP cash never enters official tax liability paths (already in math; guard UI copy and filters).
* Client archive/delete UX that respects lifetime quota messaging (slot not restored).
* **Forgot password on login:** “Forgot password?” link on `/login` → email reset form → signed token reset page → new password. Use Laravel notification/mail (Railway SMTP or equivalent). Rate-limit requests. Success copy must not reveal whether the email exists. **Never** treat password reset as medical vault unlock.
* **VAT number deferred capture (revision #13):** Keep optional on onboarding; nudge in Settings; block Art 10 invoice / apply-VAT without a number (redirect/flash to Settings).
* *(Year-lock and future-dating already enforced in Phase 0 — do not regress.)*

### Phase 3: Standard Tier Features
*Outcome: €15.99 value prop is real and gated (also included in all Pro tiers).*

* Expense Ledger replacing static `estimated_expenses` for paid users (column remains fallback for Free / incomplete data).
* Receipt/file uploads (tenant-scoped storage paths).
* **Accountant Download (Standard + all Pro):** Purpose-built export the practitioner downloads and sends to their accountant — **full ledger detail**, not a thin summary. Include at minimum: document register (invoices / RFPs / credit notes with clear fiscal vs non-fiscal distinction), line/payment history, client/counterparty billing identity needed for books, VAT analysis (Art 10/11 as applicable), expenses/receipts when live, and logged tax/PT/SSC/VAT payments where stored. Formats: CSV and/or Excel (PDF pack optional later). Gate with `canAccessStandardTools()` / tier middleware. Free: upgrade CTA only.
* Automated TA22 form generation logic + download.
* Custom branding/logo fields on user profile → consumed by PDF phase.

### Phase 4: Pro Tier Foundations & Stamping
*Outcome: Industry packages scaffolded on a GDPR-safe data model (Phase 0 already ensured Clients stay billing-only).*

* Global Document Stamper (signatures/warrants on PDFs) — shared by Arch (primary) and reusable.
* **Pro Medical GDPR schema (manual SQL) — the real fix, not the containment:**
  * Billing `clients` remain commercial counterparties only (Phase 0 invariant).
  * Separate patient/PII store and clinical/journal store linked by opaque IDs (Art. 9 data not co-located with billing email/ID card).
  * All medical routes require `canAccessProPackage('med')` (tier **and** profession).
  * On leave `pro-med` (downgrade or package switch): **retain locked** — no app access, no auto-delete.
  * On return to `pro-med`: unlock same owner’s records **only with their recovery code**.
  * Never stuff health fields back onto `clients.profile_data`.
  * Digital Prescriptions / Referral Letters use clinical store + controlled identity join only (authoring in Phase 4–5; **paid patient delivery links = Phase 7**).
  * Journal **file/photo attachments** are designed in decision #16; implement encrypt-at-rest upload in Phase 5 (not plaintext R2).
* **Practitioner-held recovery code (mandatory before any real patient data in prod):**
  * Generate one-time recovery code at Pro Medical vault setup; doctor confirms save.
  * Persist signed acknowledgment: lost key ⇒ Cerulean cannot recover; doctor must keep weekly backups (`accepted_at`, `accepted_ip`, etc.).
  * Encrypt patient/clinical payloads at rest; store code verifier only, never recoverable plaintext code on server.
  * In-app use requires unlock with code **every login/session**.
  * **Backup/download:** always prompt for recovery code; release **decrypted** export only on success; fail closed otherwise. Surface **weekly backup mandate** + overdue nag.
  * **Lost code → new vault:** wizard creates new key/vault, **guides upload of latest backup**, restores into new vault (re-encrypt under new key). Aim for **≤ ~1 week** clinical loss if weekly backups were kept. Retain old vault ciphertext if old key resurfaces.
  * No support “reset key” path.
* Scaffold routes/UI shells: Patient Journals, Architect DMS + phases, Engineer projects, **shared Certificates & Declarations** (all Pro packages — doctors, architects, and engineers all issue these).
* Domain rules: BCA Method Statements (Arch); certification photo + expiry on shared certificate register; EMS/BMS template content with domain expert before locking schemas.
* **Do not invent deep Arch/Eng behaviour** (decision #19) — wait for Nicholas’s niche briefs before expanding beyond shells.

### Phase 5: Document Generation, PDF Export & Vault Attachments
*Outcome: Every official document is downloadable and brandable; medical journals can hold encrypted photos/files.*

* PDF engine already partially present (DomPDF in ledger) — harden templates for Official Invoice, RFP, Credit Note, Payment Receipt.
* Standard+ injects logo/branding; Free uses PractisBase-safe defaults + disclaimer footer.
* Medical PDFs that include clinical content (Rx, referrals) only generate after vault unlock; never cache plaintext clinical PDFs in world-readable storage.
* **Encrypted journal attachments (decision #16):** upload/encrypt/store/download per architecture above; include in weekly medical backup; MIME allowlist + size caps; IDOR via `user_id` + vault session.
* Prescription **authoring + signed PDF** (doctor-side) lives here; **paid one-time patient links** wait for Phase 7.

### Phase 6: Billing & Launch Polish (after closed beta — end-game ops)
*Outcome: Real money, real legal posture, production confidence. Runs **after** beta testers have used the product on granted tiers (decision #18).*

* Replace onboarding/Settings DEV bypass with Stripe Checkout / Customer Portal (Cashier or thin custom).
* Optional: Stripe promo codes / complimentary months for ex-beta users — only once Checkout is live.
* Webhooks update `users.tier`; failed payment → grace → downgrade path that re-applies 5-client rule without deleting data; medical vault stays encrypted/locked.
* Terms versioning + re-acceptance modal when legal text changes.
* **Medical addendum:** Launch-ready wording for recovery-code unrecoverability acknowledgment; align master T&Cs with practitioner-held key model.
* **Go-live gate:** Pro Medical must not accept real patient data in production until encryption + code reveal + signed acknowledgment + export-with-code + weekly backup UX are shipped and legally reviewed; **confirm Railway EU/EEA region** (or move host) before patient data; DPA/processor terms; support runbook “never request recovery code.”
* Disclaimers: Fiscal Report/PDFs ≠ certified accountant advice; Medical ≠ sole clinical system of record / continuity-of-care disclaimer.
* **List B legal** (tax retention vs delete account; erasure vs lost-key; DPA): resolve before account-deletion features and Pro Medical launch — still open.
* Referral codes (`referral_code` / `referred_by_id` already on User) wired if part of launch.
* **Stripe Connect / destination charges design spike** (for Phase 7 doctor payouts) — document approach; no need to ship patient payment links yet.

### Phase 7: Signed Document Commerce (LAST — post-launch enrichment)
*Outcome: One-time paid delivery of signed/stamped professional documents; prescriptions first, other professions next.*

* **Depends on:** Phase 5 signed PDFs + Phase 6 live Stripe (+ Connect decision).
* Doctor creates Rx → signs/stamps → sets fee → shares one-time link.
* Patient pays → receives single download; token burned; unpaid/expired/used links fail closed.
* Auto-post income into practitioner ledger (invoice + payment) so fiscal reports stay accurate.
* Reuse rails for Architect / Engineer document types when templates are ready.
* Legal/compliance review for prescription issuance and tele-delivery in Malta before marketing the feature.
* **Out of scope until this phase:** Patient accounts, multi-download subscriptions, marketplace discovery.

---

## Suggested Build Order

```
Phase 0A IDOR + TaxPayment fillable
    → Phase 0B medical containment + SQL scrub + Client show whitelist
    → Phase 0C year-lock helper + before_or_equal:today + lock UI on ledger
    → Phase 0D/0H profession disabled in Settings
    → Phase 0E document numbering fix
    → Phase 0G TierPolicy + clients_created_count SQL + sidebar uses helpers
    → Phase 0F/0H nav markup fix (reports stay Standard+); remove tier===pro
Phase 1 enforce canAddClient in store + N/5 UI
    → tier + onboarding middleware (reports Standard+)
    → Settings Subscription / DEV plan switch (tier column only)
    → route group wiring
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
* **Start implementing Phase 0 first** (integrity/containment), then Phase 1 (SaaS gates), per "Suggested Build Order".
* **Free:** **no** Live Fiscal Report; **5 lifetime clients** (counter, never decremented on delete). Upgrade to Standard+ for `/reports` + unlimited clients.
* **Transitions:** Phase 0 encodes matrix — keep all clients visible on Free downgrade; Pro switch retain locked; Pro selection profession-gated; medical access = tier AND profession; no data wipe on plan change.
* **GDPR:** Clinical-on-Client unsafe today — Phase 0 scrub mandatory. Phase 4: delinked stores + practitioner-held recovery code (encrypt at rest; backup needs code; lost code = unrecoverable; Labs cannot reset). Phase 6: legal go-live gate before real patient data.
* **Pre-start decisions locked:** A yes (Railway EU = verify at go-live); B deferred; C1 new vault + restore-from-weekly-backup guide + keep old ciphertext; **C2 trusted-device WebAuthn unlock + recovery code for new/recovery (decision #24)**; C3 Arch/Eng normal auth; **C4 Accountant Download for Standard+ (full ledger pack, doctor sends)**; C5 soft-archive.
* **Password reset never unlocks medical vault.** Forgot-password shipped in Phase 2 (decision #14).
* **VAT number optional at onboarding** (decision #13); required only for Art 10 invoice / apply-VAT.
* **Certificates & declarations shared across all Pro** (decision #15).
* **Encrypted journal attachments (decision #16):** Phase 5 — ciphertext on private R2 under vault DEK; not the same as Standard document storage. Manual SQL: `database/manual/phase5_postgresql.sql`.
* **Signed Document Commerce (decision #17 / Phase 7):** Last phase — create/sign/stamp → one-time pay link → single download → ledger income. Rx first; Arch/Eng later.
* **Closed beta before Stripe (decision #18):** Grant tiers via Settings plan switch; no Checkout/vouchers until post-beta Phase 6.
* **Arch/Eng depth (decision #19):** Expert-guided only — do not invent niche workflows.
* **Patient ↔ Client optional link (decision #20):** Opaque `billing_client_id`; prefill name; clinical stays vault-only; directory search after unlock.
* **Stamp & issue lock (decision #21):** Draft editable; Stamp & issue locks Rx/referral/clinical certificate + shared cert register. Manual SQL: `database/manual/phase5_stamp_lock_postgresql.sql`.
* **Issue codes + PDF templates (decision #22):** Unique code + issue date on every stamped PDF; type-specific templates; practitioner lookup to flag reuse. Manual SQL: `database/manual/phase5_issue_codes_postgresql.sql`.
* **Medical stampables ledger (decision #23):** Create under patient; Certificates & Declarations menu = `/pro/medical/stampables` search/filter register. Arch/Eng keep `/pro/certificates` for now.
* **Trusted-device WebAuthn unlock (decision #24):** Platform biometrics / device unlock after first recovery-code unlock; wrap key in IndexedDB; login password stays separate. Manual SQL: `database/manual/phase5_vault_trusted_devices_postgresql.sql`.
* **Phase 0–5 merged to `13.x`.** Closed-beta hardening: weekly medical backup export + overdue nag + beta chrome. New-vault restore wizard still follow-up.
* **Manual SQL:** run `database/manual/phase4_postgresql.sql` (and incremental phase4_* files if needed) then `database/manual/phase5_postgresql.sql` for clinical attachments.
* **Blob storage:** Receipts/logos/cert photos use `TenantStorage` → `TENANT_DISK=r2` in production. Medical attachments use `medical/{user}/vault_{id}/attachments/*.bin` with **extra vault encryption**.
* **Medical vault:** Recovery code shown once; verifier only in DB; session DEK after unlock; **trusted-device WebAuthn** for subsequent unlocks on registered browsers (decision #24); recovery code still required for new devices / revoke / full recovery; **weekly backup ZIP** at `/pro/medical/vault/backup` (re-prompts recovery code; sets `last_backup_at`). Pre-production / closed-beta banners until Phase 6 legal go-live.
