# Gemini prompt — PractisBase Privacy Policy & Master Service Agreement

Copy everything below the line into Gemini. Ask for two separate Markdown documents. Have a Maltese commercial / data-protection lawyer review before publish.

---

You are a senior commercial counsel drafting customer-facing legal documents for a Maltese SaaS product. Write in clear, professional English suitable for self-employed professionals in Malta. Prefer short paragraphs and numbered clauses. Avoid marketing fluff. Flag any clause that needs a lawyer’s local review with `[LAWYER REVIEW]`.

## Product facts (treat as authoritative)

- **Product name:** PractisBase
- **Operator / controller for platform data:** Cerulean Labs Limited (Malta). Use placeholders `[COMPANY FULL LEGAL NAME]`, `[REGISTERED ADDRESS]`, `[COMPANY REGISTRATION NUMBER]`, `[VAT NUMBER]`, `[PRIVACY CONTACT EMAIL]`, `[SUPPORT EMAIL]` where exact registry details are unknown.
- **Governing law / venue:** Laws of Malta; courts of Malta. GDPR + Data Protection Act (Cap. 586 of the Laws of Malta) apply.
- **Customers:** Natural persons who are **self-employed sole traders** in Malta (doctors / medical professionals, architects / periti, engineers, tutors, and similar). **Not** for Ltd companies, public companies, corporate partnerships as separate legal persons, VAT groups, or employers running payroll/FSS for staff.
- **Offering:** Subscription SaaS for practice admin: clients, invoicing (including RFPs / pro-forma with €0 fiscal weight until converted to invoice), expenses, Maltese fiscal helpers (income tax brackets, TA22 part-time scheme, SSC, Article 10 VAT 18%, Article 11 exempt €35k threshold warnings, provisional tax), profession tools (medical clinical vault, architect/engineer projects, certificates, reports).
- **Closed beta then paid plans** (Free / Standard / Pro packages). Billing may be offline during beta; terms must still cover paid subscription, suspension, and termination.
- **No professional advice:** PractisBase does **not** provide medical, architectural, engineering, tax, accounting, or legal advice. Outputs are tools/helpers; the user remains fully responsible for professional judgments, filings, certifications, prescriptions, and calculations submitted to authorities or clients.
- **Tax / fiscal disclaimers (must appear in MSA):** Fiscal figures are estimates/helpers based on user data and configured rates; not a substitute for a warrant / CPA / tax practitioner; rates and law change; user must verify before filing with CFR / Jobsplus / VAT Department / other authorities.
- **Data roles (critical):**
  - For **client / patient / project content** the User uploads: User is **Data Controller**; Cerulean Labs / PractisBase is **Data Processor**.
  - For **account, billing, usage, support, and community feedback** data: Cerulean Labs is **Data Controller**.
- **Medical vault:** Optional encrypted clinical records. Client-side / vault cryptography; recovery codes; biometric unlock on trusted devices. Operator cannot routinely read plaintext clinical content. Lost recovery code may mean irreversible loss of vault data — disclose clearly; no “we can reset your clinical data” promise.
- **Subprocessors / hosting:** Assume EU/EEA hosting is preferred; use placeholder `[HOSTING / SUBPROCESSORS LIST URL OR TABLE]` for cloud host, email, error monitoring, payment processor (e.g. Stripe when live).
- **Retention:** Account data while account active + reasonable period after closure; clinical/client data until user deletes or account closed per processor instructions; backups for a defined short window; legal retention where required.
- **International transfers:** Only with appropriate GDPR safeguards if any non-EEA subprocessors exist.
- **Security:** Appropriate technical and organisational measures; breach notification duties under GDPR; user must protect credentials, recovery codes, and devices.
- **Children:** Not directed at under-16s / under-18s as end customers.
- **Community feedback:** In-app suggestions/bugs with two-way replies; may be used to improve the product; do not put sensitive patient data in feedback.
- **IP:** PractisBase retains IP in software, templates (except user content), branding. User retains IP in their uploaded content; limited licence to host/process to provide the service.
- **Acceptable use:** No unlawful content; no attempt to break encryption/security; no scraping; no resale of access; no using the product for Ltd/company books as if it were corporate accounting software.
- **Liability:** Cap liability to fees paid in prior 12 months (or a modest fixed cap during free beta). Exclude indirect / consequential loss to the extent permitted by Maltese law. Do **not** exclude liability for death/personal injury caused by negligence, fraud, or other non-excludable liability.
- **Indemnity:** User indemnifies provider for claims arising from user’s professional acts, unlawful uploads, and misuse of clinical/client data.
- **Suspension / termination:** Non-payment, breach, abuse, legal risk; user may export data where the product allows before closure (state practical limits for encrypted vault).
- **Changes to terms / privacy:** Notice via email or in-app; continued use after effective date = acceptance where lawful; material adverse changes may allow user to stop using the service.
- **MSA vs Privacy:** MSA = contract for use of the service. Privacy Policy = personal data notice + processor overview. Cross-link them. Registration checkbox already says user accepts both.

## Deliverables

Produce **two** documents:

### Document A — Privacy Policy
Include at least:
1. Who we are and contact details  
2. Scope (platform account data vs customer content where we are processor)  
3. Categories of personal data  
4. Purposes and legal bases (contract, legitimate interests, legal obligation, consent where used)  
5. Medical / special category data — only under user’s controllership; our processor role; vault encryption summary  
6. Sharing / subprocessors  
7. International transfers  
8. Retention  
9. Security  
10. Data subject rights (access, rectification, erasure, restriction, portability, objection, complaint to IDPC Malta)  
11. Cookies / local storage / device biometrics for vault unlock  
12. Community feedback data  
13. Children’s data  
14. Changes  
15. Effective date placeholder `[EFFECTIVE DATE]`

Also include a short **Data Processing summary** (or schedule) describing processor instructions: process only on documented instructions, confidentiality, security, subprocessors, deletion/return, audit cooperation at reasonable intervals.

### Document B — Master Service Agreement (MSA) / Terms of Service
Include at least:
1. Parties; acceptance; eligibility (18+, sole trader in Malta)  
2. Sole-trader scope exclusion (no Ltd / company accounts)  
3. Description of service; beta / “as available” wording if useful  
4. Accounts, security of credentials, recovery codes  
5. Subscriptions, fees, taxes, refunds (fair, Malta-friendly), suspension for non-payment  
6. User responsibilities and professional responsibility disclaimer  
7. Fiscal / tax helper disclaimer (detailed)  
8. Medical vault / profession-module specific risks  
9. Acceptable use  
10. Customer content licence; feedback licence (royalty-free to use suggestions to improve product without obligation to implement)  
11. IP ownership  
12. Privacy / GDPR cross-reference; controller–processor split  
13. Confidentiality  
14. Warranties disclaimer (service “as is” to extent permitted)  
15. Limitation of liability; indemnity  
16. Term, termination, data export / deletion  
17. Force majeure  
18. Changes to MSA  
19. Governing law, disputes, severability, entire agreement, assignment, notices  
20. Effective date placeholder `[EFFECTIVE DATE]`

## Style rules
- Use “you” / “we”.
- Clause numbering (1, 1.1, 1.2…).
- No fake statute citations.
- Do not invent EU representative details.
- Length: thorough but readable — aim ~2,500–4,500 words per document, not a novel.
- End each document with a one-paragraph plain-language summary box titled “In plain language”.

## Output format
Return:

1. `# PractisBase Privacy Policy` then full Markdown  
2. `# PractisBase Master Service Agreement` then full Markdown  
3. A short checklist of `[LAWYER REVIEW]` items and missing placeholders to fill before go-live
