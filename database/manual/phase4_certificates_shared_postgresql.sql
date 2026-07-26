CREATE TABLE IF NOT EXISTS certificates (
    id bigserial PRIMARY KEY,
    user_id bigint NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    title varchar(255) NOT NULL,
    subject_name varchar(255) NULL,
    kind varchar(50) NOT NULL DEFAULT 'certificate',
    issued_on date NOT NULL,
    expires_on date NULL,
    photo_path varchar(500) NULL,
    notes text NULL,
    created_at timestamp without time zone NULL,
    updated_at timestamp without time zone NULL
);

CREATE INDEX IF NOT EXISTS certificates_user_id_index ON certificates (user_id);

INSERT INTO certificates (user_id, title, subject_name, kind, issued_on, expires_on, photo_path, notes, created_at, updated_at)
SELECT user_id, title, subject_name, 'certificate', issued_on, expires_on, photo_path, notes, created_at, updated_at
FROM engineer_certifications
WHERE NOT EXISTS (
    SELECT 1 FROM certificates c
    WHERE c.user_id = engineer_certifications.user_id
      AND c.title = engineer_certifications.title
      AND c.issued_on = engineer_certifications.issued_on
);
