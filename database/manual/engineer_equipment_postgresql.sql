CREATE TABLE IF NOT EXISTS engineer_equipment (
    id bigserial PRIMARY KEY,
    user_id bigint NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    client_id bigint NOT NULL REFERENCES clients(id) ON DELETE CASCADE,
    category varchar(60) NOT NULL DEFAULT 'other',
    name varchar(255) NOT NULL,
    make varchar(120) NULL,
    model varchar(120) NULL,
    serial_number varchar(120) NULL,
    asset_code varchar(40) NOT NULL,
    capacity_rating varchar(120) NULL,
    year_of_manufacture integer NULL,
    site_location text NULL,
    status varchar(40) NOT NULL DEFAULT 'active',
    notes text NULL,
    last_certified_on date NULL,
    next_due_on date NULL,
    created_at timestamp without time zone NULL,
    updated_at timestamp without time zone NULL
);

CREATE UNIQUE INDEX IF NOT EXISTS engineer_equipment_user_asset_code_unique
    ON engineer_equipment (user_id, asset_code);

CREATE INDEX IF NOT EXISTS engineer_equipment_user_id_index
    ON engineer_equipment (user_id);

CREATE INDEX IF NOT EXISTS engineer_equipment_client_id_index
    ON engineer_equipment (client_id);

CREATE INDEX IF NOT EXISTS engineer_equipment_next_due_on_index
    ON engineer_equipment (user_id, next_due_on);

CREATE INDEX IF NOT EXISTS engineer_equipment_status_index
    ON engineer_equipment (user_id, status);

ALTER TABLE engineer_certificates
    ADD COLUMN IF NOT EXISTS equipment_id bigint NULL;

CREATE INDEX IF NOT EXISTS engineer_certificates_equipment_id_index
    ON engineer_certificates (equipment_id);
