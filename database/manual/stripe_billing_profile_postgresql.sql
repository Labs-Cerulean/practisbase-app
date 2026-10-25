ALTER TABLE users
    ADD COLUMN IF NOT EXISTS include_billing_vat boolean NOT NULL DEFAULT false;

ALTER TABLE users
    ADD COLUMN IF NOT EXISTS billing_vat_number varchar(64) NULL;
