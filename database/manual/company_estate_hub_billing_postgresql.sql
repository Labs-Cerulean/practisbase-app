ALTER TABLE company_recurring_invoices
    ADD COLUMN IF NOT EXISTS package_sections JSONB NOT NULL DEFAULT '["os"]';

ALTER TABLE company_recurring_invoices
    ADD COLUMN IF NOT EXISTS agreed_rate_os NUMERIC(12, 2) NULL;

ALTER TABLE company_recurring_invoices
    ADD COLUMN IF NOT EXISTS agreed_rate_plant NUMERIC(12, 2) NULL;

ALTER TABLE company_recurring_invoices
    ADD COLUMN IF NOT EXISTS agreed_rate_sales NUMERIC(12, 2) NULL;

ALTER TABLE company_recurring_invoices
    ADD COLUMN IF NOT EXISTS start_date DATE NULL;

ALTER TABLE company_recurring_invoices
    ADD COLUMN IF NOT EXISTS sla_path VARCHAR(500) NULL;

ALTER TABLE company_recurring_invoices
    ADD COLUMN IF NOT EXISTS sla_original_name VARCHAR(255) NULL;

ALTER TABLE company_recurring_invoices
    ADD COLUMN IF NOT EXISTS auto_email BOOLEAN NOT NULL DEFAULT FALSE;

ALTER TABLE company_recurring_invoices
    ADD COLUMN IF NOT EXISTS auto_reminders BOOLEAN NOT NULL DEFAULT FALSE;

ALTER TABLE company_recurring_invoices
    ADD COLUMN IF NOT EXISTS reminder_include_statement BOOLEAN NOT NULL DEFAULT TRUE;

ALTER TABLE company_recurring_invoices
    DROP CONSTRAINT IF EXISTS company_recurring_invoices_package_sections_check;

ALTER TABLE company_recurring_invoices
    ADD CONSTRAINT company_recurring_invoices_package_sections_check
        CHECK (jsonb_typeof(package_sections) = 'array');
