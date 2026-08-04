CREATE TABLE IF NOT EXISTS architect_condition_reports (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT NOT NULL,
    architect_project_id BIGINT NULL,
    architect_pa_application_id BIGINT NULL,
    title VARCHAR(255) NOT NULL,
    report_type VARCHAR(64) NULL,
    report_number VARCHAR(120) NULL,
    inspected_on DATE NULL,
    issued_on DATE NOT NULL,
    client_name VARCHAR(255) NULL,
    client_address TEXT NULL,
    project_description TEXT NULL,
    inspected_address TEXT NULL,
    development_address TEXT NULL,
    payload JSONB NOT NULL DEFAULT '{}',
    stamped_at TIMESTAMP NULL,
    issue_code VARCHAR(40) NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    CONSTRAINT architect_condition_reports_user_id_fkey
        FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT architect_condition_reports_project_id_fkey
        FOREIGN KEY (architect_project_id) REFERENCES architect_projects (id) ON DELETE CASCADE,
    CONSTRAINT architect_condition_reports_pa_id_fkey
        FOREIGN KEY (architect_pa_application_id) REFERENCES architect_pa_applications (id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS architect_condition_reports_user_id_idx
    ON architect_condition_reports (user_id);

CREATE INDEX IF NOT EXISTS architect_condition_reports_project_id_idx
    ON architect_condition_reports (architect_project_id);

CREATE UNIQUE INDEX IF NOT EXISTS architect_condition_reports_issue_code_unique
    ON architect_condition_reports (issue_code);

CREATE TABLE IF NOT EXISTS architect_condition_report_photos (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT NOT NULL,
    architect_condition_report_id BIGINT NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    caption VARCHAR(255) NULL,
    sort_order INTEGER NOT NULL DEFAULT 0,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    CONSTRAINT architect_condition_report_photos_user_id_fkey
        FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT architect_condition_report_photos_report_id_fkey
        FOREIGN KEY (architect_condition_report_id) REFERENCES architect_condition_reports (id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS architect_condition_report_photos_report_id_idx
    ON architect_condition_report_photos (architect_condition_report_id);
