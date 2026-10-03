CREATE TABLE IF NOT EXISTS company_expense_payments (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT NOT NULL,
    paid_on DATE NOT NULL,
    reference VARCHAR(120) NULL,
    kind VARCHAR(32) NOT NULL DEFAULT 'director_refund',
    amount NUMERIC(12, 2) NOT NULL,
    proof_path VARCHAR(500) NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    CONSTRAINT company_expense_payments_user_id_fkey
        FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS company_expense_payments_user_id_idx
    ON company_expense_payments (user_id);

ALTER TABLE company_expense_payments
    ADD COLUMN IF NOT EXISTS kind VARCHAR(32) NOT NULL DEFAULT 'director_refund';

ALTER TABLE company_expenses
    ADD COLUMN IF NOT EXISTS company_expense_payment_id BIGINT NULL;

ALTER TABLE company_expenses
    DROP CONSTRAINT IF EXISTS company_expenses_payment_id_fkey;

ALTER TABLE company_expenses
    ADD CONSTRAINT company_expenses_payment_id_fkey
    FOREIGN KEY (company_expense_payment_id)
    REFERENCES company_expense_payments (id)
    ON DELETE RESTRICT;

CREATE INDEX IF NOT EXISTS company_expenses_payment_id_idx
    ON company_expenses (company_expense_payment_id);
