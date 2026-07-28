CREATE TABLE IF NOT EXISTS user_regime_segments (
    id bigserial PRIMARY KEY,
    user_id bigint NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    effective_from date NOT NULL,
    vat_status varchar(32) NOT NULL,
    employment_type varchar(32) NOT NULL,
    max_ssc_paid boolean NOT NULL DEFAULT false,
    primary_salary numeric(12, 2) NOT NULL DEFAULT 0,
    tax_computation varchar(32) NOT NULL DEFAULT 'single',
    created_at timestamp without time zone NULL,
    updated_at timestamp without time zone NULL,
    CONSTRAINT user_regime_segments_user_from_unique UNIQUE (user_id, effective_from)
);

CREATE INDEX IF NOT EXISTS user_regime_segments_user_from_idx
    ON user_regime_segments (user_id, effective_from DESC);
