ALTER TABLE document_stamps
    ADD COLUMN IF NOT EXISTS warrant_number varchar(80) NULL;
