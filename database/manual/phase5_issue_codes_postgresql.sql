ALTER TABLE clinical_entries
    ADD COLUMN IF NOT EXISTS issue_code character varying(32) NULL;

ALTER TABLE certificates
    ADD COLUMN IF NOT EXISTS issue_code character varying(32) NULL;

CREATE UNIQUE INDEX IF NOT EXISTS clinical_entries_issue_code_unique
    ON clinical_entries (issue_code)
    WHERE issue_code IS NOT NULL;

CREATE UNIQUE INDEX IF NOT EXISTS certificates_issue_code_unique
    ON certificates (issue_code)
    WHERE issue_code IS NOT NULL;

CREATE INDEX IF NOT EXISTS clinical_entries_user_issue_code_index
    ON clinical_entries (user_id, issue_code);

CREATE INDEX IF NOT EXISTS certificates_user_issue_code_index
    ON certificates (user_id, issue_code);
