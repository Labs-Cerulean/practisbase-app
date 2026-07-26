CREATE TABLE IF NOT EXISTS expenses (
    id bigserial PRIMARY KEY,
    user_id bigint NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    expense_date date NOT NULL,
    category varchar(100) NOT NULL DEFAULT 'general',
    description text NOT NULL,
    amount numeric(12,2) NOT NULL,
    vat_amount numeric(12,2) NOT NULL DEFAULT 0,
    receipt_path varchar(500) NULL,
    created_at timestamp without time zone NULL,
    updated_at timestamp without time zone NULL
);

CREATE INDEX IF NOT EXISTS expenses_user_id_expense_date_index ON expenses (user_id, expense_date);

ALTER TABLE users
ADD COLUMN IF NOT EXISTS logo_path varchar(500) NULL;
