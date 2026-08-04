CREATE TABLE IF NOT EXISTS engineer_reports (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT NOT NULL,
    engineer_project_id BIGINT NULL,
    engineer_pa_application_id BIGINT NULL,
    title VARCHAR(255) NOT NULL,
    report_type VARCHAR(64) NULL,
    report_number VARCHAR(120) NULL,
    surveyed_on DATE NULL,
    issued_on DATE NOT NULL,
    conclusion VARCHAR(120) NULL,
    client_name VARCHAR(255) NULL,
    client_address TEXT NULL,
    contact_person VARCHAR(255) NULL,
    contact_phone VARCHAR(64) NULL,
    site_address TEXT NULL,
    payload JSONB NOT NULL DEFAULT '{}',
    stamped_at TIMESTAMP NULL,
    issue_code VARCHAR(40) NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    CONSTRAINT engineer_reports_user_id_fkey
        FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT engineer_reports_project_id_fkey
        FOREIGN KEY (engineer_project_id) REFERENCES engineer_projects (id) ON DELETE CASCADE,
    CONSTRAINT engineer_reports_pa_id_fkey
        FOREIGN KEY (engineer_pa_application_id) REFERENCES engineer_pa_applications (id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS engineer_reports_user_id_idx
    ON engineer_reports (user_id);

CREATE INDEX IF NOT EXISTS engineer_reports_project_id_idx
    ON engineer_reports (engineer_project_id);

CREATE UNIQUE INDEX IF NOT EXISTS engineer_reports_issue_code_unique
    ON engineer_reports (issue_code);

CREATE TABLE IF NOT EXISTS engineer_report_photos (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT NOT NULL,
    engineer_report_id BIGINT NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    caption VARCHAR(255) NULL,
    sort_order INTEGER NOT NULL DEFAULT 0,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    CONSTRAINT engineer_report_photos_user_id_fkey
        FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT engineer_report_photos_report_id_fkey
        FOREIGN KEY (engineer_report_id) REFERENCES engineer_reports (id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS engineer_report_photos_report_id_idx
    ON engineer_report_photos (engineer_report_id);
