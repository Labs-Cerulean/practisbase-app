CREATE TABLE IF NOT EXISTS beta_invite_codes (
    id bigserial PRIMARY KEY,
    code varchar(40) NOT NULL,
    pro_package varchar(10) NOT NULL,
    label varchar(120) NULL,
    max_uses integer NOT NULL DEFAULT 1,
    uses_count integer NOT NULL DEFAULT 0,
    expires_at timestamp without time zone NULL,
    revoked_at timestamp without time zone NULL,
    created_by_user_id bigint NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    redeemed_by_user_id bigint NULL REFERENCES users(id) ON DELETE SET NULL,
    redeemed_at timestamp without time zone NULL,
    created_at timestamp without time zone NULL,
    updated_at timestamp without time zone NULL
);

CREATE UNIQUE INDEX IF NOT EXISTS beta_invite_codes_code_unique
    ON beta_invite_codes (code);

CREATE INDEX IF NOT EXISTS beta_invite_codes_created_by_index
    ON beta_invite_codes (created_by_user_id);

ALTER TABLE users
    ADD COLUMN IF NOT EXISTS beta_invite_code_id bigint NULL;

CREATE INDEX IF NOT EXISTS users_beta_invite_code_id_index
    ON users (beta_invite_code_id);
