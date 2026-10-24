CREATE TABLE IF NOT EXISTS clinical_labels (
    id bigserial PRIMARY KEY,
    user_id bigint NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    name varchar(80) NOT NULL,
    created_at timestamp without time zone NULL,
    updated_at timestamp without time zone NULL
);

CREATE UNIQUE INDEX IF NOT EXISTS clinical_labels_user_name_unique
    ON clinical_labels (user_id, lower(name));

CREATE TABLE IF NOT EXISTS patient_actions (
    id bigserial PRIMARY KEY,
    user_id bigint NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    vault_id bigint NOT NULL REFERENCES medical_vaults(id) ON DELETE CASCADE,
    patient_id bigint NOT NULL REFERENCES patients(id) ON DELETE CASCADE,
    label_id bigint NOT NULL REFERENCES clinical_labels(id) ON DELETE CASCADE,
    note_ciphertext text NULL,
    note_nonce varchar(64) NULL,
    done_at timestamp without time zone NULL,
    created_at timestamp without time zone NULL,
    updated_at timestamp without time zone NULL
);

CREATE INDEX IF NOT EXISTS patient_actions_user_patient_index
    ON patient_actions (user_id, patient_id);

CREATE INDEX IF NOT EXISTS patient_actions_user_open_index
    ON patient_actions (user_id, done_at);

CREATE TABLE IF NOT EXISTS clinical_library_documents (
    id bigserial PRIMARY KEY,
    user_id bigint NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    vault_id bigint NOT NULL REFERENCES medical_vaults(id) ON DELETE CASCADE,
    title_ciphertext text NOT NULL,
    title_nonce varchar(64) NOT NULL,
    meta_ciphertext text NOT NULL,
    meta_nonce varchar(64) NOT NULL,
    file_nonce varchar(64) NOT NULL,
    storage_path varchar(255) NOT NULL,
    byte_size integer NOT NULL DEFAULT 0,
    ciphertext_sha256 varchar(64) NOT NULL,
    created_at timestamp without time zone NULL,
    updated_at timestamp without time zone NULL
);

CREATE INDEX IF NOT EXISTS clinical_library_documents_user_index
    ON clinical_library_documents (user_id);
