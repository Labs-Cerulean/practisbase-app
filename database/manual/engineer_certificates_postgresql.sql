CREATE TABLE IF NOT EXISTS engineer_certificates (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT NOT NULL,
    engineer_project_id BIGINT NULL,
    engineer_pa_application_id BIGINT NULL,
    title VARCHAR(255) NOT NULL,
    certificate_number VARCHAR(120) NULL,
    inspected_on DATE NULL,
    issued_on DATE NOT NULL,
    expires_on DATE NULL,
    next_inspection_on DATE NULL,
    outcome VARCHAR(120) NULL,
    holder_name VARCHAR(255) NULL,
    holder_address TEXT NULL,
    contact_person VARCHAR(255) NULL,
    contact_phone VARCHAR(64) NULL,
    site_address TEXT NULL,
    payload JSONB NOT NULL DEFAULT '{}',
    stamped_at TIMESTAMP NULL,
    issue_code VARCHAR(40) NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    CONSTRAINT engineer_certificates_user_id_fkey
        FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT engineer_certificates_project_id_fkey
        FOREIGN KEY (engineer_project_id) REFERENCES engineer_projects (id) ON DELETE CASCADE,
    CONSTRAINT engineer_certificates_pa_id_fkey
        FOREIGN KEY (engineer_pa_application_id) REFERENCES engineer_pa_applications (id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS engineer_certificates_user_id_idx
    ON engineer_certificates (user_id);

CREATE INDEX IF NOT EXISTS engineer_certificates_project_id_idx
    ON engineer_certificates (engineer_project_id);

CREATE UNIQUE INDEX IF NOT EXISTS engineer_certificates_issue_code_unique
    ON engineer_certificates (issue_code);

CREATE TABLE IF NOT EXISTS engineer_certificate_photos (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT NOT NULL,
    engineer_certificate_id BIGINT NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    caption VARCHAR(255) NULL,
    sort_order INTEGER NOT NULL DEFAULT 0,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    CONSTRAINT engineer_certificate_photos_user_id_fkey
        FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT engineer_certificate_photos_certificate_id_fkey
        FOREIGN KEY (engineer_certificate_id) REFERENCES engineer_certificates (id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS engineer_certificate_photos_certificate_id_idx
    ON engineer_certificate_photos (engineer_certificate_id);
