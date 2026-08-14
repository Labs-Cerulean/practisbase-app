ALTER TABLE users
    ADD COLUMN IF NOT EXISTS clinical_note_template varchar(64) NOT NULL DEFAULT 'general';

CREATE TABLE IF NOT EXISTS clinical_note_templates (
    id bigserial PRIMARY KEY,
    user_id bigint NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    name varchar(120) NOT NULL,
    fields_json json NOT NULL,
    sort_order integer NOT NULL DEFAULT 0,
    created_at timestamp without time zone NULL,
    updated_at timestamp without time zone NULL
);

CREATE INDEX IF NOT EXISTS clinical_note_templates_user_id_index
    ON clinical_note_templates (user_id);

CREATE UNIQUE INDEX IF NOT EXISTS clinical_note_templates_user_name_unique
    ON clinical_note_templates (user_id, lower(name));
