ALTER TABLE users
    ADD COLUMN IF NOT EXISTS exclude_from_mrr boolean NOT NULL DEFAULT false;
