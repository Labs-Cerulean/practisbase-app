CREATE TABLE IF NOT EXISTS document_stamps (
    id bigserial PRIMARY KEY,
    user_id bigint NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    label varchar(120) NOT NULL,
    preset varchar(40) NOT NULL DEFAULT 'classic_border',
    first_name varchar(120) NOT NULL,
    last_name varchar(120) NOT NULL,
    postnominals varchar(120) NULL,
    role_title varchar(160) NOT NULL,
    warrant_number varchar(80) NULL,
    signature_path varchar(500) NULL,
    is_default boolean NOT NULL DEFAULT false,
    created_at timestamp without time zone NULL,
    updated_at timestamp without time zone NULL
);

CREATE INDEX IF NOT EXISTS document_stamps_user_id_index
    ON document_stamps (user_id);

CREATE INDEX IF NOT EXISTS document_stamps_user_default_index
    ON document_stamps (user_id, is_default);
