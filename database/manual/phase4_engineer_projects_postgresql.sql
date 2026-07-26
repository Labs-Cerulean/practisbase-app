CREATE TABLE IF NOT EXISTS engineer_projects (
    id bigserial PRIMARY KEY,
    user_id bigint NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    name varchar(255) NOT NULL,
    reference_code varchar(100) NULL,
    discipline varchar(100) NOT NULL DEFAULT 'general',
    phase varchar(100) NOT NULL DEFAULT 'design',
    status varchar(50) NOT NULL DEFAULT 'active',
    notes text NULL,
    created_at timestamp without time zone NULL,
    updated_at timestamp without time zone NULL
);

CREATE INDEX IF NOT EXISTS engineer_projects_user_id_index ON engineer_projects (user_id);
