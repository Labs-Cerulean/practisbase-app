ALTER TABLE architect_licence_contacts
    ALTER COLUMN licence_type DROP NOT NULL;

ALTER TABLE architect_licence_contacts
    ADD COLUMN IF NOT EXISTS preferred_role_key VARCHAR(64) NULL;

ALTER TABLE architect_licence_contacts
    ADD COLUMN IF NOT EXISTS email VARCHAR(255) NULL;

ALTER TABLE architect_licence_contacts
    ADD COLUMN IF NOT EXISTS id_card VARCHAR(64) NULL;
