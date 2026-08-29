ALTER TABLE company_expenses
    ADD COLUMN IF NOT EXISTS is_reverse_charge BOOLEAN NOT NULL DEFAULT FALSE;

CREATE INDEX IF NOT EXISTS company_expenses_user_rc_idx
    ON company_expenses (user_id, is_reverse_charge);
