CREATE TABLE IF NOT EXISTS medical_vaults (
    id bigserial PRIMARY KEY,
    user_id bigint NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    recovery_verifier varchar(255) NOT NULL,
    acknowledged_at timestamp without time zone NOT NULL,
    acknowledged_ip varchar(45) NULL,
    last_backup_at timestamp without time zone NULL,
    status varchar(32) NOT NULL DEFAULT 'active',
    created_at timestamp without time zone NULL,
    updated_at timestamp without time zone NULL
);

CREATE UNIQUE INDEX IF NOT EXISTS medical_vaults_user_active_unique
    ON medical_vaults (user_id)
    WHERE status = 'active';

CREATE TABLE IF NOT EXISTS patients (
    id bigserial PRIMARY KEY,
    user_id bigint NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    vault_id bigint NOT NULL REFERENCES medical_vaults(id) ON DELETE CASCADE,
    public_ref varchar(32) NOT NULL,
    billing_client_id bigint NULL REFERENCES clients(id) ON DELETE SET NULL,
    payload_ciphertext text NOT NULL,
    payload_nonce varchar(64) NOT NULL,
    created_at timestamp without time zone NULL,
    updated_at timestamp without time zone NULL
);

CREATE UNIQUE INDEX IF NOT EXISTS patients_user_public_ref_unique ON patients (user_id, public_ref);
CREATE INDEX IF NOT EXISTS patients_user_id_index ON patients (user_id);

CREATE TABLE IF NOT EXISTS clinical_entries (
    id bigserial PRIMARY KEY,
    user_id bigint NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    vault_id bigint NOT NULL REFERENCES medical_vaults(id) ON DELETE CASCADE,
    patient_id bigint NOT NULL REFERENCES patients(id) ON DELETE CASCADE,
    entry_type varchar(32) NOT NULL DEFAULT 'journal',
    entry_date date NOT NULL,
    payload_ciphertext text NOT NULL,
    payload_nonce varchar(64) NOT NULL,
    created_at timestamp without time zone NULL,
    updated_at timestamp without time zone NULL
);

CREATE INDEX IF NOT EXISTS clinical_entries_user_patient_index ON clinical_entries (user_id, patient_id);

CREATE TABLE IF NOT EXISTS architect_projects (
    id bigserial PRIMARY KEY,
    user_id bigint NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    name varchar(255) NOT NULL,
    reference_code varchar(100) NULL,
    phase varchar(100) NOT NULL DEFAULT 'concept',
    status varchar(50) NOT NULL DEFAULT 'active',
    notes text NULL,
    created_at timestamp without time zone NULL,
    updated_at timestamp without time zone NULL
);

CREATE INDEX IF NOT EXISTS architect_projects_user_id_index ON architect_projects (user_id);

CREATE TABLE IF NOT EXISTS engineer_projects (
    id bigserial PRIMARY KEY,
    user_id bigint NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    name varchar(255) NOT NULL,
    reference_code varchar(100) NULL,
    discipline varchar(100) NOT NULL DEFAULT 'general',
    phase varchar(100) NOT NULL DEFAULT 'design',
    status varchar(50) NOT NULL DEFAULT 'active',
    notes text NULL,
    created_at timestamp without time zone NULL,
    updated_at timestamp without time zone NULL
);

CREATE INDEX IF NOT EXISTS engineer_projects_user_id_index ON engineer_projects (user_id);

CREATE TABLE IF NOT EXISTS certificates (
    id bigserial PRIMARY KEY,
    user_id bigint NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    title varchar(255) NOT NULL,
    subject_name varchar(255) NULL,
    kind varchar(50) NOT NULL DEFAULT 'certificate',
    issued_on date NOT NULL,
    expires_on date NULL,
    photo_path varchar(500) NULL,
    notes text NULL,
    created_at timestamp without time zone NULL,
    updated_at timestamp without time zone NULL
);

CREATE INDEX IF NOT EXISTS certificates_user_id_index ON certificates (user_id);
