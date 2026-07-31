ALTER TABLE users
    ADD COLUMN IF NOT EXISTS company_books_enabled BOOLEAN NOT NULL DEFAULT FALSE;

CREATE TABLE IF NOT EXISTS company_profiles (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT NOT NULL UNIQUE,
    legal_name VARCHAR(255) NOT NULL,
    registration_number VARCHAR(32) NOT NULL,
    registered_office TEXT NOT NULL,
    financial_year_end_month SMALLINT NOT NULL DEFAULT 12,
    financial_year_end_day SMALLINT NOT NULL DEFAULT 31,
    first_period_start DATE NOT NULL,
    first_period_end DATE NOT NULL,
    vat_status VARCHAR(32) NOT NULL DEFAULT 'article_10',
    vat_number VARCHAR(64) NULL,
    vat_filing_frequency VARCHAR(16) NOT NULL DEFAULT 'quarterly',
    bank_name VARCHAR(120) NULL,
    bank_iban VARCHAR(64) NULL,
    share_capital_eur NUMERIC(12, 2) NOT NULL DEFAULT 1200.00,
    share_capital_received_at DATE NULL,
    payment_instructions TEXT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    CONSTRAINT company_profiles_user_id_fkey
        FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS company_clients (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT NOT NULL,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NULL,
    phone VARCHAR(64) NULL,
    billing_address TEXT NULL,
    vat_number VARCHAR(64) NULL,
    registration_number VARCHAR(64) NULL,
    notes TEXT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    CONSTRAINT company_clients_user_id_fkey
        FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS company_clients_user_id_idx
    ON company_clients (user_id);

CREATE INDEX IF NOT EXISTS company_clients_user_name_idx
    ON company_clients (user_id, name);

CREATE TABLE IF NOT EXISTS company_invoices (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT NOT NULL,
    company_client_id BIGINT NOT NULL,
    parent_document_id BIGINT NULL,
    document_number VARCHAR(64) NOT NULL,
    issue_date DATE NOT NULL,
    due_date DATE NULL,
    subtotal NUMERIC(12, 2) NOT NULL DEFAULT 0,
    vat_total NUMERIC(12, 2) NOT NULL DEFAULT 0,
    total NUMERIC(12, 2) NOT NULL DEFAULT 0,
    amount_paid NUMERIC(12, 2) NOT NULL DEFAULT 0,
    status VARCHAR(32) NOT NULL DEFAULT 'unpaid',
    type VARCHAR(32) NOT NULL,
    linked_document_id BIGINT NULL,
    items JSONB NOT NULL DEFAULT '[]',
    notes TEXT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    CONSTRAINT company_invoices_user_id_fkey
        FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT company_invoices_client_id_fkey
        FOREIGN KEY (company_client_id) REFERENCES company_clients (id) ON DELETE RESTRICT,
    CONSTRAINT company_invoices_parent_id_fkey
        FOREIGN KEY (parent_document_id) REFERENCES company_invoices (id) ON DELETE CASCADE,
    CONSTRAINT company_invoices_linked_id_fkey
        FOREIGN KEY (linked_document_id) REFERENCES company_invoices (id) ON DELETE SET NULL
);

CREATE INDEX IF NOT EXISTS company_invoices_user_id_idx
    ON company_invoices (user_id);

CREATE INDEX IF NOT EXISTS company_invoices_user_type_idx
    ON company_invoices (user_id, type);

CREATE INDEX IF NOT EXISTS company_invoices_client_id_idx
    ON company_invoices (company_client_id);

CREATE TABLE IF NOT EXISTS company_payments (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT NOT NULL,
    company_invoice_id BIGINT NOT NULL,
    amount NUMERIC(12, 2) NOT NULL,
    payment_date DATE NOT NULL,
    payment_method VARCHAR(64) NOT NULL DEFAULT 'bank_transfer',
    notes TEXT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    CONSTRAINT company_payments_user_id_fkey
        FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT company_payments_invoice_id_fkey
        FOREIGN KEY (company_invoice_id) REFERENCES company_invoices (id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS company_payments_user_id_idx
    ON company_payments (user_id);

CREATE INDEX IF NOT EXISTS company_payments_invoice_id_idx
    ON company_payments (company_invoice_id);

CREATE TABLE IF NOT EXISTS company_expenses (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT NOT NULL,
    expense_date DATE NOT NULL,
    category VARCHAR(64) NOT NULL DEFAULT 'software',
    description TEXT NOT NULL,
    amount NUMERIC(12, 2) NOT NULL,
    vat_amount NUMERIC(12, 2) NOT NULL DEFAULT 0,
    funded_by VARCHAR(16) NOT NULL DEFAULT 'company',
    director_refunded_at DATE NULL,
    refund_reference VARCHAR(120) NULL,
    receipt_path VARCHAR(500) NULL,
    is_pre_incorporation BOOLEAN NOT NULL DEFAULT FALSE,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    CONSTRAINT company_expenses_user_id_fkey
        FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS company_expenses_user_id_idx
    ON company_expenses (user_id);

CREATE INDEX IF NOT EXISTS company_expenses_user_date_idx
    ON company_expenses (user_id, expense_date);

CREATE INDEX IF NOT EXISTS company_expenses_director_open_idx
    ON company_expenses (user_id, funded_by, director_refunded_at);
