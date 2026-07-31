ALTER TABLE company_invoices
    ADD COLUMN IF NOT EXISTS supply_date DATE NULL;
