ALTER TABLE payments
    ADD COLUMN IF NOT EXISTS is_transfer BOOLEAN NOT NULL DEFAULT FALSE;

CREATE UNIQUE INDEX IF NOT EXISTS invoices_user_number_unique
    ON invoices (user_id, invoice_number);
