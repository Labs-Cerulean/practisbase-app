ALTER TABLE company_invoices
    ADD COLUMN IF NOT EXISTS stripe_invoice_id varchar(255) NULL;

ALTER TABLE company_payments
    ADD COLUMN IF NOT EXISTS stripe_invoice_id varchar(255) NULL;

CREATE UNIQUE INDEX IF NOT EXISTS company_invoices_stripe_invoice_uidx
    ON company_invoices (stripe_invoice_id);

CREATE UNIQUE INDEX IF NOT EXISTS company_payments_stripe_invoice_uidx
    ON company_payments (stripe_invoice_id);
