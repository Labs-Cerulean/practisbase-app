CREATE TABLE IF NOT EXISTS practice_document_templates (
    id bigserial PRIMARY KEY,
    user_id bigint NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    kind varchar(32) NOT NULL,
    name varchar(255) NOT NULL,
    title_default varchar(255) NULL,
    starter_key varchar(64) NULL,
    payload jsonb NOT NULL DEFAULT '{}',
    created_at timestamp without time zone NULL,
    updated_at timestamp without time zone NULL
);

CREATE INDEX IF NOT EXISTS practice_document_templates_user_kind_idx
    ON practice_document_templates (user_id, kind);
