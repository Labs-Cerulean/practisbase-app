ALTER TABLE document_stamps
    ADD COLUMN IF NOT EXISTS composed_path varchar(500) NULL;
