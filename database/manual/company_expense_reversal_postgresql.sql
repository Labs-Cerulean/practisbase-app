ALTER TABLE company_expenses
    ADD COLUMN IF NOT EXISTS reversed_at DATE NULL;

ALTER TABLE company_expenses
    ADD COLUMN IF NOT EXISTS reversal_note VARCHAR(500) NULL;
