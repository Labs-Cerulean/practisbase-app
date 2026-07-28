ALTER TABLE fiscal_years
    ADD COLUMN IF NOT EXISTS snapshot_json jsonb NULL;

ALTER TABLE users
    ADD COLUMN IF NOT EXISTS estimated_expenses_by_year jsonb NULL;
