ALTER TABLE users
    ADD COLUMN IF NOT EXISTS clinical_note_template varchar(32) NOT NULL DEFAULT 'general';
