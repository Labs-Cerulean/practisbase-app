ALTER TABLE architect_condition_report_photos
    ADD COLUMN IF NOT EXISTS linked_row_id VARCHAR(64) NULL;

ALTER TABLE architect_method_statement_photos
    ADD COLUMN IF NOT EXISTS linked_row_id VARCHAR(64) NULL;

ALTER TABLE engineer_certificate_photos
    ADD COLUMN IF NOT EXISTS linked_row_id VARCHAR(64) NULL;

ALTER TABLE engineer_report_photos
    ADD COLUMN IF NOT EXISTS linked_row_id VARCHAR(64) NULL;
