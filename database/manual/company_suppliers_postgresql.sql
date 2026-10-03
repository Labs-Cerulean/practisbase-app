CREATE TABLE IF NOT EXISTS company_suppliers (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT NOT NULL,
    name VARCHAR(255) NOT NULL,
    vat_number VARCHAR(64) NULL,
    email VARCHAR(255) NULL,
    country VARCHAR(80) NULL,
    address TEXT NULL,
    default_category VARCHAR(64) NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    CONSTRAINT company_suppliers_user_id_fkey
        FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS company_suppliers_user_id_idx
    ON company_suppliers (user_id);

CREATE UNIQUE INDEX IF NOT EXISTS company_suppliers_user_name_uidx
    ON company_suppliers (user_id, lower(name));

ALTER TABLE company_expenses
    ADD COLUMN IF NOT EXISTS company_supplier_id BIGINT NULL;

ALTER TABLE company_expenses
    ADD COLUMN IF NOT EXISTS supplier_invoice_number VARCHAR(120) NULL;

CREATE INDEX IF NOT EXISTS company_expenses_user_supplier_idx
    ON company_expenses (user_id, company_supplier_id);

ALTER TABLE company_expenses
    DROP CONSTRAINT IF EXISTS company_expenses_supplier_id_fkey;

ALTER TABLE company_expenses
    ADD CONSTRAINT company_expenses_supplier_id_fkey
    FOREIGN KEY (company_supplier_id) REFERENCES company_suppliers (id) ON DELETE RESTRICT;
