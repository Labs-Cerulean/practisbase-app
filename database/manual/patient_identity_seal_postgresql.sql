ALTER TABLE patients ADD COLUMN IF NOT EXISTS identity_ciphertext text NULL;

ALTER TABLE patients ADD COLUMN IF NOT EXISTS identity_nonce varchar(64) NULL;
