CREATE TABLE IF NOT EXISTS architect_method_statements (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT NOT NULL,
    architect_project_id BIGINT NULL,
    architect_pa_application_id BIGINT NULL,
    title VARCHAR(255) NOT NULL,
    statement_type VARCHAR(64) NULL,
    statement_number VARCHAR(120) NULL,
    issued_on DATE NOT NULL,
    commencement_note VARCHAR(255) NULL,
    client_name VARCHAR(255) NULL,
    client_address TEXT NULL,
    project_description TEXT NULL,
    site_address TEXT NULL,
    payload JSONB NOT NULL DEFAULT '{}',
    stamped_at TIMESTAMP NULL,
    issue_code VARCHAR(40) NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    CONSTRAINT architect_method_statements_user_id_fkey
        FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT architect_method_statements_project_id_fkey
        FOREIGN KEY (architect_project_id) REFERENCES architect_projects (id) ON DELETE CASCADE,
    CONSTRAINT architect_method_statements_pa_id_fkey
        FOREIGN KEY (architect_pa_application_id) REFERENCES architect_pa_applications (id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS architect_method_statements_user_id_idx
    ON architect_method_statements (user_id);

CREATE INDEX IF NOT EXISTS architect_method_statements_project_id_idx
    ON architect_method_statements (architect_project_id);

CREATE UNIQUE INDEX IF NOT EXISTS architect_method_statements_issue_code_unique
    ON architect_method_statements (issue_code);

CREATE TABLE IF NOT EXISTS architect_method_statement_photos (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT NOT NULL,
    architect_method_statement_id BIGINT NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    caption VARCHAR(255) NULL,
    sort_order INTEGER NOT NULL DEFAULT 0,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    CONSTRAINT architect_method_statement_photos_user_id_fkey
        FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT architect_method_statement_photos_statement_id_fkey
        FOREIGN KEY (architect_method_statement_id) REFERENCES architect_method_statements (id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS architect_method_statement_photos_statement_id_idx
    ON architect_method_statement_photos (architect_method_statement_id);
