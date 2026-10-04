CREATE TABLE IF NOT EXISTS company_personal_receipts (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT NOT NULL,
    received_on DATE NOT NULL,
    amount NUMERIC(12, 2) NOT NULL,
    description VARCHAR(500) NOT NULL,
    reference VARCHAR(120) NULL,
    returned_on DATE NULL,
    return_reference VARCHAR(120) NULL,
    proof_path VARCHAR(500) NULL,
    reversed_at DATE NULL,
    reversal_note VARCHAR(500) NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    CONSTRAINT company_personal_receipts_user_id_fkey
        FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS company_personal_receipts_user_id_idx
    ON company_personal_receipts (user_id);

CREATE INDEX IF NOT EXISTS company_personal_receipts_open_idx
    ON company_personal_receipts (user_id, returned_on, reversed_at);
