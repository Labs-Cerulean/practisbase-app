ALTER TABLE users
    ADD COLUMN IF NOT EXISTS exclude_from_kpis boolean NOT NULL DEFAULT false;
