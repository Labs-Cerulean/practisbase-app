ALTER TABLE patients ADD COLUMN IF NOT EXISTS archived_at timestamp without time zone NULL;

ALTER TABLE clinical_entries ADD COLUMN IF NOT EXISTS archived_at timestamp without time zone NULL;

CREATE INDEX IF NOT EXISTS patients_user_archived_idx ON patients (user_id, archived_at);

CREATE INDEX IF NOT EXISTS clinical_entries_user_patient_archived_idx ON clinical_entries (user_id, patient_id, archived_at);
