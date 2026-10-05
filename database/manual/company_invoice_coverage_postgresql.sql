ALTER TABLE company_invoices
    ADD COLUMN IF NOT EXISTS coverage_start DATE NULL;

ALTER TABLE company_invoices
    ADD COLUMN IF NOT EXISTS coverage_end DATE NULL;
