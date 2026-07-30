CREATE TABLE IF NOT EXISTS architect_clients (
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
    CONSTRAINT architect_clients_user_id_fkey
        FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT architect_clients_billing_client_id_fkey
        FOREIGN KEY (billing_client_id) REFERENCES clients (id) ON DELETE SET NULL
);

CREATE INDEX IF NOT EXISTS architect_clients_user_id_idx
    ON architect_clients (user_id);

CREATE INDEX IF NOT EXISTS architect_clients_user_name_idx
    ON architect_clients (user_id, name);

ALTER TABLE architect_projects
    ADD COLUMN IF NOT EXISTS architect_client_id BIGINT NULL;

ALTER TABLE architect_projects
    ADD COLUMN IF NOT EXISTS site_premises VARCHAR(255) NULL;

ALTER TABLE architect_projects
    ADD COLUMN IF NOT EXISTS site_street VARCHAR(255) NULL;

ALTER TABLE architect_projects
    ADD COLUMN IF NOT EXISTS site_locality VARCHAR(120) NULL;

ALTER TABLE architect_projects
    ADD COLUMN IF NOT EXISTS site_address TEXT NULL;

ALTER TABLE architect_projects
    ADD COLUMN IF NOT EXISTS commencement_date DATE NULL;

DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM pg_constraint WHERE conname = 'architect_projects_architect_client_id_fkey'
    ) THEN
        ALTER TABLE architect_projects
            ADD CONSTRAINT architect_projects_architect_client_id_fkey
            FOREIGN KEY (architect_client_id) REFERENCES architect_clients (id) ON DELETE CASCADE;
    END IF;
END $$;

CREATE INDEX IF NOT EXISTS architect_projects_client_id_idx
    ON architect_projects (architect_client_id);

CREATE TABLE IF NOT EXISTS architect_pa_applications (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT NOT NULL,
    architect_project_id BIGINT NOT NULL,
    pa_number VARCHAR(120) NOT NULL,
    title VARCHAR(255) NULL,
    status VARCHAR(40) NOT NULL DEFAULT 'active',
    works_commencement_date DATE NULL,
    notes TEXT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    CONSTRAINT architect_pa_applications_user_id_fkey
        FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT architect_pa_applications_project_id_fkey
        FOREIGN KEY (architect_project_id) REFERENCES architect_projects (id) ON DELETE CASCADE
);

CREATE UNIQUE INDEX IF NOT EXISTS architect_pa_applications_user_pa_unique
    ON architect_pa_applications (user_id, LOWER(pa_number));

CREATE INDEX IF NOT EXISTS architect_pa_applications_project_idx
    ON architect_pa_applications (architect_project_id);

CREATE TABLE IF NOT EXISTS architect_documents (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT NOT NULL,
    architect_client_id BIGINT NULL,
    architect_project_id BIGINT NULL,
    architect_pa_application_id BIGINT NULL,
    title VARCHAR(255) NOT NULL,
    doc_type VARCHAR(80) NOT NULL DEFAULT 'other',
    category VARCHAR(40) NOT NULL DEFAULT 'document',
    status VARCHAR(40) NOT NULL DEFAULT 'draft',
    doc_code VARCHAR(80) NULL,
    current_revision INTEGER NOT NULL DEFAULT 0,
    template_key VARCHAR(120) NULL,
    notes TEXT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    CONSTRAINT architect_documents_user_id_fkey
        FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT architect_documents_client_id_fkey
        FOREIGN KEY (architect_client_id) REFERENCES architect_clients (id) ON DELETE CASCADE,
    CONSTRAINT architect_documents_project_id_fkey
        FOREIGN KEY (architect_project_id) REFERENCES architect_projects (id) ON DELETE CASCADE,
    CONSTRAINT architect_documents_pa_id_fkey
        FOREIGN KEY (architect_pa_application_id) REFERENCES architect_pa_applications (id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS architect_documents_user_idx
    ON architect_documents (user_id);

CREATE INDEX IF NOT EXISTS architect_documents_client_idx
    ON architect_documents (architect_client_id);

CREATE INDEX IF NOT EXISTS architect_documents_project_idx
    ON architect_documents (architect_project_id);

CREATE INDEX IF NOT EXISTS architect_documents_pa_idx
    ON architect_documents (architect_pa_application_id);

CREATE INDEX IF NOT EXISTS architect_documents_title_idx
    ON architect_documents (user_id, title);

CREATE TABLE IF NOT EXISTS architect_document_revisions (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT NOT NULL,
    architect_document_id BIGINT NOT NULL,
    revision_no INTEGER NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    mime_type VARCHAR(120) NULL,
    size_bytes BIGINT NULL,
    change_note VARCHAR(500) NULL,
    uploaded_by_user_id BIGINT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    CONSTRAINT architect_document_revisions_user_id_fkey
        FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT architect_document_revisions_document_id_fkey
        FOREIGN KEY (architect_document_id) REFERENCES architect_documents (id) ON DELETE CASCADE,
    CONSTRAINT architect_document_revisions_uploaded_by_fkey
        FOREIGN KEY (uploaded_by_user_id) REFERENCES users (id) ON DELETE SET NULL
);

CREATE UNIQUE INDEX IF NOT EXISTS architect_document_revisions_doc_rev_unique
    ON architect_document_revisions (architect_document_id, revision_no);

CREATE TABLE IF NOT EXISTS architect_site_parties (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT NOT NULL,
    architect_project_id BIGINT NOT NULL,
    architect_pa_application_id BIGINT NULL,
    role_key VARCHAR(80) NOT NULL,
    full_name VARCHAR(255) NOT NULL,
    id_card VARCHAR(64) NULL,
    mobile VARCHAR(64) NULL,
    email VARCHAR(255) NULL,
    address TEXT NULL,
    company_name VARCHAR(255) NULL,
    licence_type VARCHAR(40) NULL,
    licence_number VARCHAR(120) NULL,
    notes TEXT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    CONSTRAINT architect_site_parties_user_id_fkey
        FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT architect_site_parties_project_id_fkey
        FOREIGN KEY (architect_project_id) REFERENCES architect_projects (id) ON DELETE CASCADE,
    CONSTRAINT architect_site_parties_pa_id_fkey
        FOREIGN KEY (architect_pa_application_id) REFERENCES architect_pa_applications (id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS architect_site_parties_project_idx
    ON architect_site_parties (architect_project_id);

CREATE TABLE IF NOT EXISTS architect_licence_contacts (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT NOT NULL,
    licence_type VARCHAR(40) NOT NULL,
    licence_number VARCHAR(120) NULL,
    full_name VARCHAR(255) NOT NULL,
    company_name VARCHAR(255) NULL,
    mobile VARCHAR(64) NULL,
    locality VARCHAR(120) NULL,
    source VARCHAR(40) NOT NULL DEFAULT 'manual',
    notes TEXT NULL,
    last_used_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    CONSTRAINT architect_licence_contacts_user_id_fkey
        FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS architect_licence_contacts_user_type_idx
    ON architect_licence_contacts (user_id, licence_type);

CREATE INDEX IF NOT EXISTS architect_licence_contacts_user_name_idx
    ON architect_licence_contacts (user_id, LOWER(full_name));
