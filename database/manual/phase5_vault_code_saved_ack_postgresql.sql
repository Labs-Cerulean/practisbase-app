ALTER TABLE medical_vaults
    ADD COLUMN IF NOT EXISTS acknowledge_read_duration_seconds integer NULL;

ALTER TABLE medical_vaults
    ADD COLUMN IF NOT EXISTS code_saved_at timestamp without time zone NULL;

ALTER TABLE medical_vaults
    ADD COLUMN IF NOT EXISTS code_saved_ip varchar(45) NULL;

ALTER TABLE medical_vaults
    ADD COLUMN IF NOT EXISTS code_saved_read_duration_seconds integer NULL;

UPDATE medical_vaults
SET code_saved_at = acknowledged_at,
    code_saved_ip = acknowledged_ip
WHERE code_saved_at IS NULL;
