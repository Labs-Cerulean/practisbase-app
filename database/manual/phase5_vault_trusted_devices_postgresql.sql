CREATE TABLE IF NOT EXISTS medical_vault_devices (
    id bigserial PRIMARY KEY,
    user_id bigint NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    vault_id bigint NOT NULL REFERENCES medical_vaults(id) ON DELETE CASCADE,
    credential_id text NOT NULL,
    public_key text NOT NULL,
    attestation_format varchar(64) NULL,
    wrap_nonce varchar(128) NOT NULL,
    wrapped_dek text NOT NULL,
    device_label varchar(255) NULL,
    signature_counter bigint NOT NULL DEFAULT 0,
    last_used_at timestamp without time zone NULL,
    created_at timestamp without time zone NULL,
    updated_at timestamp without time zone NULL
);

CREATE UNIQUE INDEX IF NOT EXISTS medical_vault_devices_credential_id_unique
    ON medical_vault_devices (credential_id);

CREATE INDEX IF NOT EXISTS medical_vault_devices_user_vault_index
    ON medical_vault_devices (user_id, vault_id);
