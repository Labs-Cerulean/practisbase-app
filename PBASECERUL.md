# PractisBase coding preferences

Standing habits for PractisBase (Labs-Cerulean/practisbase-app). Taken from Nicholas's own briefs and corrections, and from `.cursorrules`, which every task tells the agent to follow on the checked-out commit. One-off bugs are not listed here.

## How he works

- One item at a time, unless he says otherwise. Do not start the next backlog item, and do not start building, until he asks. He closes a thread with "Done" or "Ok let's continue backlog".
- Before planning company-desk, billing, or VAT work, read `principles/practisbase.md`, `notes.md`, and `docs/BACKLOG.md`.
- He writes short, informal, often lowercase messages. Several bugs arrive as a numbered list. Acceptance is a "Done when" line.
- Say it in plain language. He will stop on jargon ("what do you mean heavy work?").
- When he narrows a report ("not all", "a good few"), keep the bug partial. When he says he loaded a file, or that a screenshot is after a deploy, believe him and look again.
- He reviews the live site, including phone width, and sends a screenshot. Trust which build that shot is.
- If a fix is a fudge, he says go back to the last honest behaviour. Do that. Do not keep a dishonest link because the screen looks fuller.
- He reports from real use of the company desk and the medical vault. Track the behaviour. Do not explain it away.
- A new agent on `13.x` does not see files that exist only on an unmerged branch. Read the branch that actually has the file.
- Verify with tests, and in the browser when a session exists. Write down what changed. Return the pull request URL and that note's path.

## Git

- Trunk is `13.x`. There is no `main` branch.
- Staging and www.practisbase.com run that same branch. They differ by database and server settings, not by a second copy of the code.
- New work branches off current `13.x` as `cursor/<short-name>-a8e3`, with a pull request into `13.x`. A revision is a new pull request.
- Do not commit onto unrelated draft branches. Leave open drafts alone unless he names them. He has named #172 (fold converted proformas) and #178 (reminder mail) as leave-alone.
- A follow-up on an open pull request for the same fix stays on that pull request. Do not push more commits onto a pull request that has already merged. Cherry-pick onto a new branch from current `13.x`.
- Do not mix a separate task into the branch. Speed, the second-click guard, patient warnings, VAT period, tax figures, and receipt matching stay apart unless he joins them.

## Stack and the screen

- Laravel 11.x, PHP 8.2+, PostgreSQL, Blade, vanilla JS.
- Inline CSS with the existing variables (`--primary-navy`, `--text-muted`, `--border-light`, `--radius-lg`, `--shadow-sm`). No Tailwind or Bootstrap unless he explicitly asks.
- Key figures sit in white cards: `border-radius: var(--radius-lg); box-shadow: var(--shadow-sm);`.
- A calculated tax figure that opens a breakdown has a dotted bottom border (`1px dotted var(--primary-navy)`) and a pointer cursor.
- Forms feel alive. Vanilla JS updates helper text (deadlines, balances) from `<select>` changes before submit.
- Modals are native: blurred overlay (`backdrop-filter: blur(4px);`) and a centered white panel.
- Do not collapse an existing detailed breakdown into one row. The TA22 line and the spillover line stay separate. Do not simplify a Blade view while adding a feature.
- Submits ignore a second click until the request finishes, and show that they are working. He noticed when that guard was missing. Pages feel slow, so people click again.
- Speed is the whole system, not the page he used as an example. A simple list that takes 10–15 seconds is too long. Name the query or view that waits, and make a single click obviously registered.
- Patient-record security stays key while making the medical vault faster. Do not weaken the vault, do not put clinical notes in the list, and keep every query scoped by `user_id`.
- A "this patient already exists" warning fires only for a real match. Not while a name, ID, or date of birth is still being typed.
- Expense attachments are not mandatory, and a missing one must be easy to see. Do not block save. He wants the file on screen for audit, next to the posting, and he wants to drop a PDF on the main expenses list.
- Show a receipt that is actually on the expense, or a draft whose text contains that invoice number. Do not invent a match from timestamps or from a nearby file.

## Data, schema, and security

- Scope every read, update, and delete with `where('user_id', Auth::id())`. Never load a row by `id` alone. Never serve another user's file. Reject paths that climb out of the user's directory.
- Schema changes are raw PostgreSQL in `database/manual`, not Laravel migrations or seeders, unless he explicitly asks.
- SQL he will paste into a GUI editor is clean ANSI. No `--` comments. No `::json` casts. Bind parameters. Do not put unescaped user input in `DB::raw`.
- Foreign keys to the user cascade (for example `user_id`).
- Mass assignment is `$fillable` or `$guarded`. Do not pass `$request->all()` into `create` or `update` without strict validation first.
- Blade output is `{{ }}`. `{!! !!}` only for HTML the app itself sanitized.
- Every `POST`, `PUT`, `PATCH`, and `DELETE` form includes `@csrf`. Deletes and year-closing are never `GET` links.
- A locked row in `fiscal_years` blocks every create, update, and delete for invoices, payments, and tax ledgers in that year.
- No future dates. Date inputs use `max="{{ date('Y-m-d') }}"`. Form requests use `before_or_equal:today`.
- Ordinary mistakes (a supplier name, an expense description, a date, a bank line) are editable in the app. Sending him to Postgres for that is a defect. The year lock and the date rules still apply.
- Do not assume onboarding is finished. Coalesce a missing profile, for example `$user->tax_computation ?: 'single'`.
- Use `max(0, $value)` on tax deductions and subtractions so a liability cannot go negative.
- Pass the calculation breakdown (the receipt of the math) to the view. Tax is not a black box.
- For a complex controller or view, give the full file. Do not hand him a truncated block to stitch together.
- Do not paste secrets, webhook values, or database URLs into Context, rules, or the repo. They belong in Cursor Dashboard → Cloud Agents → Secrets.

## Fiscal rules

Pro-forma invoices (RFPs) have €0 official fiscal weight until converted to a tax invoice. Do not include unpaid proforma cash in tax. Open proformas stay off the VAT return.

Income-tax brackets change by year and are stored as PostgreSQL JSON. Match the bracket exactly. If income is above the highest max, use `end($brackets)`. If `tax_rates` for the year is empty, use the closest previous year. Returning 0 wipes the liability.

Part-time scheme (TA22): €12,000 at a flat 10%. Net profit above that cap is added to the primary employment salary and taxed on the progressive brackets. Do not apply TA22 or the personal brackets to the company. Company tax uses the rate and rules already in the codebase. If a figure has to be assumed, show that assumption in the breakdown. Do not change tax figures as a side effect of other work. An engineer tool saved on a project does not move tax figures.

Social security:

- Part-time: 15% of net profit, up to the legal annual cap (the example cap in the rules is €4,024.65).
- Full-time: weekly bands times 52, or the pro-rata rule the code already states.

VAT:

- Article 10 is 18%. Liability is `Revenue - (Revenue / 1.18)`. Net profit excludes VAT.
- Article 11 charges no VAT. Registration is not the same as being under the threshold. If invoiced revenue crosses €35,000, show a critical warning.
- The company desk follows the period the Malta Tax and Customs Administration assigned (first period 1 August 2026–31 August 2027, due 15 October 2027). Do not replace that with ordinary quarterly Article 10 dates, and do not mark a period overdue when nothing is due. Do not file a return. Do not invent a VAT number.
- The company's first financial period ends 31 December 2026. Do not allow a later document date unless the existing code already states an exception.

Provisional tax: the June settlement is the annual liability minus the provisional-tax payments in `tax_payments`.

A cheque number that was not kept stays blank and is marked not retained. Do not invent a cheque number. A new cheque asks for the number.

## Staging, production, and Stripe

- "We didn't bring staging along" means staging must pick the change up on its own.
- Schema that staging applies on boot stays a staging boot step. Live still waits for the `database/manual` SQL to be run by hand. Do not auto-change production.
- Staging refuses to alter data if it is pointed at practisbase.com.
- Staging keeps its own database. The live connection string is not copied onto staging.
- Do not put Stripe keys in git. The repo leaves `STRIPE_SECRET`, `STRIPE_WEBHOOK_SECRET`, and the price IDs empty on purpose.
- Staging accepts only a sandbox secret. The live site accepts only a live secret. Each site has its own webhook. A live key on staging is refused, and a sandbox key on the live site is refused.
- The product works without Stripe. Access codes mint a profession-locked Full Pro code, the plan stays locked to that code, and a new account confirms email.

## Naming

| Thing | Name |
| --- | --- |
| Trunk | `13.x` |
| Work branch | `cursor/<short-name>-a8e3` |
| Schema SQL | `database/manual` |
| Company documents | `CL-INV`, `CL-RFP`, `CL-CN` |
| Proforma until it converts | RFP. After conversion it is a tax invoice |
| Perit | Architect access code and the architect desk |
| Ing | Engineer access code and the engineer desk |
| Profession desks | Doctors, periti, and engineers. These are the product |
| Company books | The operator's own desk (Cerulean Labs Limited). Not a product other users get |

## Corrections he has had to repeat

- The slow page he mentioned was an example. Fix the rest of the system too.
- Bring staging along with the change. Do not auto-apply that change to production.
- Do not invent a VAT number, a cheque number, a filing date, or a file-to-expense match.
- Do not treat a time-based guess as a link. Revert it.
- A missing receipt card is not proof the file was never stored. Find the real path, still scoped to that user.
- Optional attachments stay optional. Make the gap visible.
- Patient security outranks a faster vault.
- A warning while someone is still typing is a false positive.
- Editing a normal company record in Postgres is poor. The screen has to do it.
