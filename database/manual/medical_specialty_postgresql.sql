ALTER TABLE users
    ADD COLUMN IF NOT EXISTS medical_specialty varchar(32) NULL;
