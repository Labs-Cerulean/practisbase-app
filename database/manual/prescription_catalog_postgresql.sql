CREATE TABLE IF NOT EXISTS prescription_catalog (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT NOT NULL,
    medicine_name VARCHAR(255) NOT NULL,
    strength VARCHAR(120) NULL,
    dose VARCHAR(255) NULL,
    quantity VARCHAR(120) NULL,
    instructions TEXT NULL,
    use_count INTEGER NOT NULL DEFAULT 1,
    last_used_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    CONSTRAINT prescription_catalog_user_id_fkey
        FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
);

CREATE UNIQUE INDEX IF NOT EXISTS prescription_catalog_user_name_strength_unique
    ON prescription_catalog (user_id, LOWER(medicine_name), LOWER(COALESCE(strength, '')));

CREATE INDEX IF NOT EXISTS prescription_catalog_user_name_idx
    ON prescription_catalog (user_id, medicine_name);

CREATE INDEX IF NOT EXISTS prescription_catalog_user_last_used_idx
    ON prescription_catalog (user_id, last_used_at DESC);
