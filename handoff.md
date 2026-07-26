# PractisBase: Project Handoff & Development Brief

## Project Overview
PractisBase is a custom-built, highly secure fiscal reporting and invoicing ledger designed specifically for self-employed professionals in Malta. It strictly adheres to Maltese tax laws, providing real-time calculations for Progressive Income Tax, Part-Time TA22, Social Security (SSC), and VAT (Articles 10 & 11). Note: The system is a tool, not a certified accounting software, and requires strict legal disclaimers regarding tax liability.

## The SaaS Architecture & Monetization
PractisBase is a self-serve, multi-tenant SaaS application operated by Cerulean Labs Ltd. Users manage their own subscriptions via online card payments. Cursor must understand the long-term target architecture, which is split into distinct subscription tiers with specific feature flags and usage limits:

### 1. Free Tier (€0/mo)
* **Limits:** Restricted to a maximum of **5 Clients**.
* **Capabilities:** Basic Invoices & Ledger (RFPs, official invoices, payments received), Summary Dashboard, Standard Support.

### 2. Standard Tier (€15.99/mo)
* **Limits:** **Unlimited Clients**.
* **Capabilities:** Custom Branding & Logo on documents, Expense Tracking & Receipts, Document & File Uploads, Automated TA22 Form generation, Accountant VAT Export.

### 3. Pro Tiers (€49.99/mo)
All Pro tiers include everything in the Standard tier, plus a choice of one highly specialized industry package:
* **Pro Medical ⚕️:** Secure Patient Journals, Digital Prescriptions, Referral Letters. *(Crucial Constraint: Medical data must be GDPR compliant, heavily secured, and designed so that Personally Identifiable Information is delinked from medical details in the database).*
* **Pro Architect 📐:** Architect DMS, Document Stamper (auto-stamping uploaded PDFs), Project Phase Tracking. *(Domain Constraint: Must support generation of Method Statements and declarations in line with local BCA requirements).*
* **Pro Engineer ⚙️:** EMS / BMS Templates, Certification Generator, Technical Specs Export. *(Domain Constraint: Features certification logs with photo upload capabilities and expiry date management).*

---

## TODAY'S STATUS: What is Already Complete. The core engine is already functioning:
* **The Math Engine:** The `ReportController` successfully calculates complex, multi-tiered Maltese tax logic, including TA22 spillover rules, SSC caps, and VAT thresholds.
* **Database Architecture:** The foundation uses raw PostgreSQL with schemas for `users`, `invoices`, `payments`, `tax_rates` (JSON-based), `tax_payments`, and `fiscal_years`.
* **Live Fiscal UI:** `reports/index.blade.php` is fully built using raw CSS variables. It features smart warnings, clickable modal breakdowns of tax math, and dynamic VAT Article 11 progress bars.
* **Government Ledger:** A functioning ledger where users can log Provisional Tax and VAT payments, dynamically updating their Final June Settlement balance using Vanilla JS smart guides.
* **Strict Constraints:** The system blocks future-dating of documents, safely falls back to previous years' tax rates, and permanently locks closed fiscal years.

---

## Development Roadmap: What Cursor Needs to Build Next (not set in stone can be improved upon) 

### Phase 1: Subscriptions, T&Cs & Multi-Tenant Security
* Build the UI (Settings page) for the user to manage their fiscal profile and subscription tier.
* Enforce the **5-Client limit** for Free Tier users at the database and controller levels.
* Implement strict data-isolation queries (tenant scoping) to ensure users can never access another user's data.
* Draft the middleware to gate features based on the Free/Standard/Pro tiers.
* Implement the legal T&Cs acceptance flow (disclaiming tax calculation liability).

### Phase 2: The Main Dashboard & Core Ledger UI
* Build the main `/dashboard` summarizing current-year KPIs, upcoming unpaid invoices, and quick links.
* Finalize the `/ledger` CRUD frontend UI to easily create, edit, and list Clients, RFPs, and Invoices (which feed the already-built Math Engine).
* Implement a "Convert to Invoice" button that automatically transfers RFP data into an official tax document.

### Phase 3: The Standard Tier Features
* Create a dedicated Expense Ledger to replace the static `estimated_expenses` column.
* Build CRUD interfaces for logging categorized outgoing transactions, storing receipts, and calculating net profit dynamically.
* Build the Accountant VAT Export functionality (CSV/Excel generation).
* Implement the Automated TA22 Form logic.

### Phase 4: Pro Tier Foundations & Stamping
* Implement the global Pro-Tier "Document Stamper" feature (applying uploaded signatures/warrants to pages).
* Design the database schema to handle the delinking of sensitive personal data from operational data (specifically for the Medical package's GDPR requirements).
* Scaffold the specific UI routes for Medical (Patient Journals), Architecture (DMS), and Engineering (Certification Generator).

### Phase 5: Document Generation & PDF Export
* Implement a PDF generation engine (e.g., DOMPDF or Browsershot).
* Create the controller logic to download Official Tax Invoices, Pro-Forma RFPs, and Credit Notes.

---

## AI Assistant Instructions (Cursor)
1. Always reference `.cursorrules` before generating any backend logic, especially regarding IDOR protection, mass assignment, and manual database management.
2. Build with the "End Game" in mind. When designing database tables or controllers, leave logical room for the subscription limits (e.g., the 5-client Free tier cap) and the distinct industry tools.
3. Security is paramount. When building features for the Medical tier, explicitly separate Personally Identifiable Information (PII) from journal data to maintain GDPR compliance. 
4. Do not introduce frontend frameworks (Tailwind/Bootstrap, React/Vue). Stick strictly to Blade, vanilla CSS with CSS variables, and Vanilla JS.
5. Prioritize mathematical accuracy and data integrity over UI flair.
6. If you do not understand any acronyms especially around the pro tier like ems or user intent, the developer is an expert system designer. Ask him and he will clarify. 