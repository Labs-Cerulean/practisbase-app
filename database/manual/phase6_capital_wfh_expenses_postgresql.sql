ALTER TABLE expenses
    ADD COLUMN IF NOT EXISTS business_use_percent numeric(5, 2) NULL;

ALTER TABLE users
    ADD COLUMN IF NOT EXISTS car_business_use_percent numeric(5, 2) NULL;

ALTER TABLE users
    ADD COLUMN IF NOT EXISTS home_office_percent numeric(5, 2) NULL;

CREATE TABLE IF NOT EXISTS capital_assets (
    id bigserial PRIMARY KEY,
    user_id bigint NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    expense_id bigint NULL REFERENCES expenses(id) ON DELETE SET NULL,
    asset_class varchar(32) NOT NULL,
    description varchar(1000) NOT NULL,
    purchase_date date NOT NULL,
    cost_basis numeric(12, 2) NOT NULL,
    cost_ex_vat numeric(12, 2) NOT NULL,
    vat_amount numeric(12, 2) NOT NULL DEFAULT 0,
    business_use_percent numeric(5, 2) NOT NULL DEFAULT 100,
    annual_rate numeric(5, 4) NOT NULL,
    created_at timestamp without time zone NULL,
    updated_at timestamp without time zone NULL
);

CREATE INDEX IF NOT EXISTS capital_assets_user_purchase_idx
    ON capital_assets (user_id, purchase_date);

CREATE INDEX IF NOT EXISTS capital_assets_expense_idx
    ON capital_assets (expense_id);
