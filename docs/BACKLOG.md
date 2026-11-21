# PractisBase backlog

Captured 18 November 2026 (Malta) from the company desk and the operator notes. Trunk is `13.x`. Nine items are open. Item 7 is split into speed and the second click. Original note numbers are kept.

The VAT chip on the desk says Q3 2026 is overdue. The MTCA portal assigned one first period, 1 August 2026 to 31 August 2027, and will not take a return until that period ends. The return is due 15 October 2027.

## First

### 2. Stop the false VAT overdue

The company calendar still uses ordinary quarterly dates, so the desk shows VAT Q3 2026 overdue on 15 November.

Done when the desk and the full calendar show the assigned period and 15 October 2027, and no longer say Q3 2026 is overdue. Open proformas stay off the return.

### 7b. A second click does nothing

Pages feel slow, so a click is repeated. A second submit can post a payment, an invoice, or an email twice.

Done when every submit control ignores a second click until the request finishes, and shows that it is working, across company books, billing, expenses, and the profession desks.

### 8. Take one client's money in one place

Monthly billing means finding each proforma, converting it, and recording each payment alone. A client page cannot take money. The invoice list cannot be searched, filtered, grouped, or sorted.

Done when a client page can record one receipt and apply it across that client's open documents, then convert the proformas that receipt settles. The invoice list can be searched, filtered, grouped by client, and sorted.

## Next

### 3. One cheque, one receipt, across invoices

Six invoices were marked as six separate payments. They were one cheque. The same split happens when one bank payment covers several invoices. There is no cheque method and no place for a cheque number. The number for this cheque was not kept.

Done when one receipt can be allocated across several invoices, with method cheque or bank transfer and a reference. A cheque recorded from now on asks for the number. The six existing payments are joined into that one cheque with the number left blank and marked as not retained. The books still add up to the same total.

### 6. Start Perit Annemarie and Ing Owen

Annemarie is a perit and Owen is an engineer. Card billing is not available.

Done when an Architect access code is issued for Annemarie and an Engineer access code for Owen. Each registers, confirms email, and lands on the matching profession desk at Full Pro, with the plan locked to the code.

### 1. Link Stripe on staging, then on the live site

Checkout code is already on `13.x`. The keys are not on the servers. Staging accepts only a sandbox secret (`sk_test_`). The live site accepts only a live secret (`sk_live_`).

Done when staging checkout uses the sandbox and test card 4242, the live site uses the live key, and each site has its own webhook to `/stripe/webhook`. Cerulean Labs Limited still issues the invoice and receipt.

### 7a. Find why the app feels slow again

Pages hesitate long enough that a person clicks twice. This is separate from the click guard.

Done when the slow pages are named, with the query or view that waits, and the worst one is quick enough that a single click is obviously registered.

## Later

### 4. Move the database to the EU

Production Postgres should run in an EU region. Staging keeps its own database. The live connection string is not copied onto staging.

### 5. Add an engineer duct sizer

The engineer desk has projects, drawings, field certificates, and reports. It has no duct sizing tool. A bill of quantities is a separate gap and is not part of this item.

Done when an engineer can size a duct from the project, keep the result on that project, and come back to it, without changing tax figures.
