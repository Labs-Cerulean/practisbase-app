# PractisBase legal — beta R01 (5 August 2026)

Canonical in-app pages:
- `/privacy` → `resources/views/legal/privacy.blade.php`
- `/msa` → `resources/views/legal/msa.blade.php`

Source Word uploads incorporated with three product-accuracy adjustments:

1. **Privacy §3.1** — Account identity fields match signup/onboarding (name, email, profession, warrant, VAT). User ID card / passport is not collected at registration; client/patient ID numbers remain processor data under the user’s controllership.
2. **Privacy §6** — Describes the existing accountant **export pack** (user downloads and shares). There is no accountant invite / login seat yet.
3. **Privacy §7.2–7.3** — Sub-processors listed as Railway, Cloudflare (CDN + R2), Google Workspace (email), Stripe (when live). Removed the leftover “AWS” example from the draft wording.

Registration scroll box summarises the MSA and links to the full `/msa` and `/privacy` pages.
