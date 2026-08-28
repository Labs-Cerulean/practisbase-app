ALTER TABLE company_invoices
    ADD COLUMN IF NOT EXISTS company_recurring_invoice_id BIGINT NULL;

ALTER TABLE company_invoices
    DROP CONSTRAINT IF EXISTS company_invoices_recurring_fkey;

ALTER TABLE company_invoices
    ADD CONSTRAINT company_invoices_recurring_fkey
        FOREIGN KEY (company_recurring_invoice_id) REFERENCES company_recurring_invoices (id) ON DELETE SET NULL;

CREATE INDEX IF NOT EXISTS company_invoices_recurring_idx
    ON company_invoices (company_recurring_invoice_id);

UPDATE company_invoices ci
SET company_recurring_invoice_id = cr.id
FROM company_recurring_invoices cr
WHERE ci.company_recurring_invoice_id IS NULL
  AND ci.user_id = cr.user_id
  AND ci.company_client_id = cr.company_client_id
  AND ci.type = 'rfp'
  AND ci.notes LIKE ('%' || 'Recurring proforma: ' || cr.title || '%');
