CREATE TABLE IF NOT EXISTS engineer_documents (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT NOT NULL,
    engineer_client_id BIGINT NULL,
    engineer_project_id BIGINT NULL,
    engineer_pa_application_id BIGINT NULL,
    title VARCHAR(255) NOT NULL,
    doc_type VARCHAR(80) NOT NULL DEFAULT 'other',
    category VARCHAR(40) NOT NULL DEFAULT 'document',
    status VARCHAR(40) NOT NULL DEFAULT 'draft',
    doc_code VARCHAR(80) NULL,
    current_revision INTEGER NOT NULL DEFAULT 0,
    notes TEXT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    CONSTRAINT engineer_documents_user_id_fkey
        FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT engineer_documents_client_id_fkey
        FOREIGN KEY (engineer_client_id) REFERENCES engineer_clients (id) ON DELETE CASCADE,
    CONSTRAINT engineer_documents_project_id_fkey
        FOREIGN KEY (engineer_project_id) REFERENCES engineer_projects (id) ON DELETE CASCADE,
    CONSTRAINT engineer_documents_pa_id_fkey
        FOREIGN KEY (engineer_pa_application_id) REFERENCES engineer_pa_applications (id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS engineer_documents_user_id_idx
    ON engineer_documents (user_id);

CREATE INDEX IF NOT EXISTS engineer_documents_client_id_idx
    ON engineer_documents (engineer_client_id);

CREATE INDEX IF NOT EXISTS engineer_documents_project_id_idx
    ON engineer_documents (engineer_project_id);

CREATE INDEX IF NOT EXISTS engineer_documents_pa_id_idx
    ON engineer_documents (engineer_pa_application_id);

CREATE INDEX IF NOT EXISTS engineer_documents_user_title_idx
    ON engineer_documents (user_id, title);

CREATE TABLE IF NOT EXISTS engineer_document_revisions (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT NOT NULL,
    engineer_document_id BIGINT NOT NULL,
    revision_no INTEGER NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    mime_type VARCHAR(120) NULL,
    size_bytes BIGINT NULL,
    change_note VARCHAR(500) NULL,
    uploaded_by_user_id BIGINT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    CONSTRAINT engineer_document_revisions_user_id_fkey
        FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT engineer_document_revisions_document_id_fkey
        FOREIGN KEY (engineer_document_id) REFERENCES engineer_documents (id) ON DELETE CASCADE,
    CONSTRAINT engineer_document_revisions_uploaded_by_fkey
        FOREIGN KEY (uploaded_by_user_id) REFERENCES users (id) ON DELETE SET NULL
);

CREATE UNIQUE INDEX IF NOT EXISTS engineer_document_revisions_doc_rev_unique
    ON engineer_document_revisions (engineer_document_id, revision_no);

CREATE INDEX IF NOT EXISTS engineer_document_revisions_document_id_idx
    ON engineer_document_revisions (engineer_document_id);
