CREATE TABLE IF NOT EXISTS clinical_attachments (
    id bigserial PRIMARY KEY,
    user_id bigint NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    vault_id bigint NOT NULL REFERENCES medical_vaults(id) ON DELETE CASCADE,
    patient_id bigint NOT NULL REFERENCES patients(id) ON DELETE CASCADE,
    clinical_entry_id bigint NOT NULL REFERENCES clinical_entries(id) ON DELETE CASCADE,
    meta_ciphertext text NOT NULL,
    meta_nonce varchar(64) NOT NULL,
    file_nonce varchar(64) NOT NULL,
    storage_path varchar(500) NOT NULL,
    byte_size integer NOT NULL,
    ciphertext_sha256 varchar(64) NOT NULL,
    created_at timestamp without time zone NULL,
    updated_at timestamp without time zone NULL
);

CREATE INDEX IF NOT EXISTS clinical_attachments_user_entry_index
    ON clinical_attachments (user_id, clinical_entry_id);

CREATE INDEX IF NOT EXISTS clinical_attachments_user_patient_index
    ON clinical_attachments (user_id, patient_id);
