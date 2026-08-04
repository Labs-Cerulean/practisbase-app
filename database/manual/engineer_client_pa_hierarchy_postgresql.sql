CREATE TABLE IF NOT EXISTS engineer_clients (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT NOT NULL,
    name VARCHAR(255) NOT NULL,
    id_card VARCHAR(64) NULL,
    email VARCHAR(255) NULL,
    phone VARCHAR(64) NULL,
    address TEXT NULL,
    locality VARCHAR(120) NULL,
    billing_client_id BIGINT NULL,
    notes TEXT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    CONSTRAINT engineer_clients_user_id_fkey
        FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT engineer_clients_billing_client_id_fkey
        FOREIGN KEY (billing_client_id) REFERENCES clients (id) ON DELETE SET NULL
);

CREATE INDEX IF NOT EXISTS engineer_clients_user_id_idx
    ON engineer_clients (user_id);

CREATE INDEX IF NOT EXISTS engineer_clients_user_name_idx
    ON engineer_clients (user_id, name);

ALTER TABLE engineer_projects
    ADD COLUMN IF NOT EXISTS engineer_client_id BIGINT NULL;

ALTER TABLE engineer_projects
    ADD COLUMN IF NOT EXISTS site_premises VARCHAR(255) NULL;

ALTER TABLE engineer_projects
    ADD COLUMN IF NOT EXISTS site_street VARCHAR(255) NULL;

ALTER TABLE engineer_projects
    ADD COLUMN IF NOT EXISTS site_locality VARCHAR(120) NULL;

ALTER TABLE engineer_projects
    ADD COLUMN IF NOT EXISTS site_address TEXT NULL;

ALTER TABLE engineer_projects
    ADD COLUMN IF NOT EXISTS commencement_date DATE NULL;

ALTER TABLE engineer_projects
    DROP CONSTRAINT IF EXISTS engineer_projects_engineer_client_id_fkey;

ALTER TABLE engineer_projects
    ADD CONSTRAINT engineer_projects_engineer_client_id_fkey
    FOREIGN KEY (engineer_client_id) REFERENCES engineer_clients (id) ON DELETE CASCADE;

CREATE INDEX IF NOT EXISTS engineer_projects_client_id_idx
    ON engineer_projects (engineer_client_id);

CREATE TABLE IF NOT EXISTS engineer_pa_applications (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT NOT NULL,
    engineer_project_id BIGINT NOT NULL,
    pa_number VARCHAR(120) NULL,
    title VARCHAR(255) NULL,
    status VARCHAR(40) NOT NULL DEFAULT 'active',
    works_commencement_date DATE NULL,
    notes TEXT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    CONSTRAINT engineer_pa_applications_user_id_fkey
        FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT engineer_pa_applications_project_id_fkey
        FOREIGN KEY (engineer_project_id) REFERENCES engineer_projects (id) ON DELETE CASCADE
);

DROP INDEX IF EXISTS engineer_pa_applications_user_pa_unique;

CREATE UNIQUE INDEX engineer_pa_applications_user_pa_unique
    ON engineer_pa_applications (user_id, (LOWER(pa_number)));

CREATE INDEX IF NOT EXISTS engineer_pa_applications_project_idx
    ON engineer_pa_applications (engineer_project_id);

ALTER TABLE architect_pa_applications
    ALTER COLUMN pa_number DROP NOT NULL;

DROP INDEX IF EXISTS architect_pa_applications_user_pa_unique;

CREATE UNIQUE INDEX architect_pa_applications_user_pa_unique
    ON architect_pa_applications (user_id, (LOWER(pa_number)));
