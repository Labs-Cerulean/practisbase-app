CREATE TABLE IF NOT EXISTS promotions (
    id bigserial PRIMARY KEY,
    code varchar(40) NOT NULL,
    type varchar(32) NOT NULL,
    value numeric(10, 2) NOT NULL,
    max_uses integer NULL,
    current_uses integer NOT NULL DEFAULT 0,
    expires_at timestamp without time zone NULL,
    is_active boolean NOT NULL DEFAULT true,
    label varchar(255) NULL,
    created_at timestamp without time zone NULL,
    updated_at timestamp without time zone NULL
);

CREATE UNIQUE INDEX IF NOT EXISTS promotions_code_unique ON promotions (code);

CREATE TABLE IF NOT EXISTS referrals (
    id bigserial PRIMARY KEY,
    referrer_id bigint NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    referred_id bigint NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    status varchar(32) NOT NULL DEFAULT 'pending_payment',
    reward_amount numeric(10, 2) NULL,
    rewarded_at timestamp without time zone NULL,
    created_at timestamp without time zone NULL,
    updated_at timestamp without time zone NULL
);

CREATE UNIQUE INDEX IF NOT EXISTS referrals_referred_id_unique ON referrals (referred_id);
CREATE INDEX IF NOT EXISTS referrals_referrer_status_index ON referrals (referrer_id, status);

ALTER TABLE users
    ADD COLUMN IF NOT EXISTS credit_balance numeric(10, 2) NOT NULL DEFAULT 0;

ALTER TABLE users
    ADD COLUMN IF NOT EXISTS trial_ends_at timestamp without time zone NULL;

ALTER TABLE users
    ADD COLUMN IF NOT EXISTS applied_promotion_id bigint NULL;

ALTER TABLE users
    ADD CONSTRAINT users_applied_promotion_id_foreign
    FOREIGN KEY (applied_promotion_id) REFERENCES promotions(id) ON DELETE SET NULL;
