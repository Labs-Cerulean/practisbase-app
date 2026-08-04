CREATE TABLE IF NOT EXISTS company_gl_accounts (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT NOT NULL,
    account_code VARCHAR(16) NOT NULL,
    name VARCHAR(255) NOT NULL,
    type VARCHAR(32) NOT NULL,
    balance_sheet_category VARCHAR(64) NULL,
    pl_group VARCHAR(64) NULL,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    CONSTRAINT company_gl_accounts_user_id_fkey
        FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT company_gl_accounts_type_check
        CHECK (type IN ('asset', 'liability', 'equity', 'revenue', 'expense')),
    CONSTRAINT company_gl_accounts_user_code_unique
        UNIQUE (user_id, account_code)
);

CREATE INDEX IF NOT EXISTS company_gl_accounts_user_type_idx
    ON company_gl_accounts (user_id, type);

CREATE TABLE IF NOT EXISTS company_fiscal_periods (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT NOT NULL,
    label VARCHAR(120) NOT NULL,
    starts_on DATE NOT NULL,
    ends_on DATE NOT NULL,
    locked_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    CONSTRAINT company_fiscal_periods_user_id_fkey
        FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT company_fiscal_periods_user_range_unique
        UNIQUE (user_id, starts_on, ends_on)
);

CREATE TABLE IF NOT EXISTS company_journal_entries (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT NOT NULL,
    entry_date DATE NOT NULL,
    description VARCHAR(500) NOT NULL,
    source_type VARCHAR(64) NULL,
    source_id BIGINT NULL,
    source_key VARCHAR(120) NULL,
    status VARCHAR(32) NOT NULL DEFAULT 'posted',
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    CONSTRAINT company_journal_entries_user_id_fkey
        FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT company_journal_entries_status_check
        CHECK (status IN ('draft', 'posted', 'reconciled', 'reversed')),
    CONSTRAINT company_journal_entries_source_key_unique
        UNIQUE (user_id, source_key)
);

CREATE INDEX IF NOT EXISTS company_journal_entries_user_date_idx
    ON company_journal_entries (user_id, entry_date);

CREATE INDEX IF NOT EXISTS company_journal_entries_user_source_idx
    ON company_journal_entries (user_id, source_type, source_id);

CREATE TABLE IF NOT EXISTS company_journal_lines (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT NOT NULL,
    journal_entry_id BIGINT NOT NULL,
    gl_account_id BIGINT NOT NULL,
    company_client_id BIGINT NULL,
    side VARCHAR(16) NOT NULL,
    amount NUMERIC(14, 2) NOT NULL,
    memo VARCHAR(500) NULL,
    bank_statement_line_id BIGINT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    CONSTRAINT company_journal_lines_user_id_fkey
        FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT company_journal_lines_entry_id_fkey
        FOREIGN KEY (journal_entry_id) REFERENCES company_journal_entries (id) ON DELETE CASCADE,
    CONSTRAINT company_journal_lines_account_id_fkey
        FOREIGN KEY (gl_account_id) REFERENCES company_gl_accounts (id) ON DELETE RESTRICT,
    CONSTRAINT company_journal_lines_client_id_fkey
        FOREIGN KEY (company_client_id) REFERENCES company_clients (id) ON DELETE SET NULL,
    CONSTRAINT company_journal_lines_side_check
        CHECK (side IN ('debit', 'credit')),
    CONSTRAINT company_journal_lines_amount_check
        CHECK (amount > 0)
);

CREATE INDEX IF NOT EXISTS company_journal_lines_entry_id_idx
    ON company_journal_lines (journal_entry_id);

CREATE INDEX IF NOT EXISTS company_journal_lines_account_id_idx
    ON company_journal_lines (gl_account_id);

CREATE INDEX IF NOT EXISTS company_journal_lines_client_id_idx
    ON company_journal_lines (company_client_id);

CREATE TABLE IF NOT EXISTS company_bank_statement_lines (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT NOT NULL,
    statement_date DATE NOT NULL,
    description VARCHAR(500) NOT NULL,
    amount NUMERIC(14, 2) NOT NULL,
    status VARCHAR(32) NOT NULL DEFAULT 'unreconciled',
    matched_journal_line_id BIGINT NULL,
    import_batch VARCHAR(64) NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    CONSTRAINT company_bank_statement_lines_user_id_fkey
        FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT company_bank_statement_lines_status_check
        CHECK (status IN ('unreconciled', 'matched', 'ignored')),
    CONSTRAINT company_bank_statement_lines_match_fkey
        FOREIGN KEY (matched_journal_line_id) REFERENCES company_journal_lines (id) ON DELETE SET NULL
);

CREATE INDEX IF NOT EXISTS company_bank_statement_lines_user_status_idx
    ON company_bank_statement_lines (user_id, status, statement_date);

ALTER TABLE company_journal_lines
    DROP CONSTRAINT IF EXISTS company_journal_lines_bank_line_fkey;

ALTER TABLE company_journal_lines
    ADD CONSTRAINT company_journal_lines_bank_line_fkey
        FOREIGN KEY (bank_statement_line_id) REFERENCES company_bank_statement_lines (id) ON DELETE SET NULL;

CREATE TABLE IF NOT EXISTS company_recurring_invoices (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT NOT NULL,
    company_client_id BIGINT NOT NULL,
    title VARCHAR(255) NOT NULL,
    day_of_month SMALLINT NOT NULL DEFAULT 1,
    next_issue_on DATE NOT NULL,
    due_days SMALLINT NOT NULL DEFAULT 14,
    items JSONB NOT NULL DEFAULT '[]',
    notes TEXT NULL,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    last_generated_on DATE NULL,
    last_invoice_id BIGINT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    CONSTRAINT company_recurring_invoices_user_id_fkey
        FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT company_recurring_invoices_client_id_fkey
        FOREIGN KEY (company_client_id) REFERENCES company_clients (id) ON DELETE RESTRICT,
    CONSTRAINT company_recurring_invoices_last_invoice_fkey
        FOREIGN KEY (last_invoice_id) REFERENCES company_invoices (id) ON DELETE SET NULL,
    CONSTRAINT company_recurring_invoices_day_check
        CHECK (day_of_month BETWEEN 1 AND 28)
);

CREATE INDEX IF NOT EXISTS company_recurring_invoices_user_next_idx
    ON company_recurring_invoices (user_id, is_active, next_issue_on);

CREATE TABLE IF NOT EXISTS company_dividends (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT NOT NULL,
    declared_on DATE NOT NULL,
    paid_on DATE NULL,
    amount NUMERIC(14, 2) NOT NULL,
    description VARCHAR(500) NOT NULL,
    status VARCHAR(32) NOT NULL DEFAULT 'declared',
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    CONSTRAINT company_dividends_user_id_fkey
        FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT company_dividends_status_check
        CHECK (status IN ('declared', 'paid', 'cancelled')),
    CONSTRAINT company_dividends_amount_check
        CHECK (amount > 0)
);

CREATE UNIQUE INDEX IF NOT EXISTS company_invoices_user_number_unique
    ON company_invoices (user_id, document_number);

ALTER TABLE company_payments
    ADD COLUMN IF NOT EXISTS is_transfer BOOLEAN NOT NULL DEFAULT FALSE;

CREATE TABLE IF NOT EXISTS company_books_locks (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT NOT NULL,
    locked_through DATE NOT NULL,
    note VARCHAR(500) NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    CONSTRAINT company_books_locks_user_id_fkey
        FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT company_books_locks_user_unique
        UNIQUE (user_id)
);
