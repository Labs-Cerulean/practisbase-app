ALTER TABLE clinical_entries
    ADD COLUMN IF NOT EXISTS issued_at timestamp without time zone NULL;

ALTER TABLE clinical_entries
    ADD COLUMN IF NOT EXISTS issued_by_user_id bigint NULL REFERENCES users(id) ON DELETE SET NULL;

CREATE INDEX IF NOT EXISTS clinical_entries_user_patient_type_index
    ON clinical_entries (user_id, patient_id, entry_type);

CREATE INDEX IF NOT EXISTS clinical_entries_user_issued_at_index
    ON clinical_entries (user_id, issued_at);

ALTER TABLE certificates
    ADD COLUMN IF NOT EXISTS stamped_at timestamp without time zone NULL;

CREATE INDEX IF NOT EXISTS certificates_user_stamped_at_index
    ON certificates (user_id, stamped_at);
