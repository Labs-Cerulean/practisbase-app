ALTER TABLE users
    ADD COLUMN IF NOT EXISTS clinic_phone varchar(64) NULL;

ALTER TABLE users
    ADD COLUMN IF NOT EXISTS clinic_address text NULL;

ALTER TABLE users
    ADD COLUMN IF NOT EXISTS clinical_stamp_path varchar(255) NULL;
