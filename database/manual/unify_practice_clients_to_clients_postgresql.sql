INSERT INTO clients (user_id, type, name, email, phone, billing_address, profile_data, created_at, updated_at)
SELECT
    ac.user_id,
    'individual',
    ac.name,
    ac.email,
    ac.phone,
    NULLIF(TRIM(CONCAT_WS(', ', NULLIF(TRIM(ac.address), ''), NULLIF(TRIM(ac.locality), ''))), ''),
    NULL,
    COALESCE(ac.created_at, NOW()),
    COALESCE(ac.updated_at, NOW())
FROM architect_clients ac
WHERE ac.billing_client_id IS NULL
  AND NOT EXISTS (
      SELECT 1 FROM clients c
      WHERE c.user_id = ac.user_id
        AND LOWER(c.name) = LOWER(ac.name)
        AND c.deleted_at IS NULL
  );

UPDATE architect_clients ac
SET billing_client_id = c.id
FROM clients c
WHERE ac.billing_client_id IS NULL
  AND c.user_id = ac.user_id
  AND LOWER(c.name) = LOWER(ac.name)
  AND c.deleted_at IS NULL;

INSERT INTO clients (user_id, type, name, email, phone, billing_address, profile_data, created_at, updated_at)
SELECT
    ec.user_id,
    'individual',
    ec.name,
    ec.email,
    ec.phone,
    NULLIF(TRIM(CONCAT_WS(', ', NULLIF(TRIM(ec.address), ''), NULLIF(TRIM(ec.locality), ''))), ''),
    NULL,
    COALESCE(ec.created_at, NOW()),
    COALESCE(ec.updated_at, NOW())
FROM engineer_clients ec
WHERE ec.billing_client_id IS NULL
  AND NOT EXISTS (
      SELECT 1 FROM clients c
      WHERE c.user_id = ec.user_id
        AND LOWER(c.name) = LOWER(ec.name)
        AND c.deleted_at IS NULL
  );

UPDATE engineer_clients ec
SET billing_client_id = c.id
FROM clients c
WHERE ec.billing_client_id IS NULL
  AND c.user_id = ec.user_id
  AND LOWER(c.name) = LOWER(ec.name)
  AND c.deleted_at IS NULL;

ALTER TABLE architect_projects DROP CONSTRAINT IF EXISTS architect_projects_architect_client_id_fkey;
ALTER TABLE architect_documents DROP CONSTRAINT IF EXISTS architect_documents_architect_client_id_fkey;
ALTER TABLE engineer_projects DROP CONSTRAINT IF EXISTS engineer_projects_engineer_client_id_fkey;
ALTER TABLE engineer_documents DROP CONSTRAINT IF EXISTS engineer_documents_engineer_client_id_fkey;

UPDATE architect_projects ap
SET architect_client_id = ac.billing_client_id
FROM architect_clients ac
WHERE ap.architect_client_id = ac.id
  AND ac.billing_client_id IS NOT NULL;

UPDATE architect_documents ad
SET architect_client_id = ac.billing_client_id
FROM architect_clients ac
WHERE ad.architect_client_id = ac.id
  AND ac.billing_client_id IS NOT NULL;

UPDATE engineer_projects ep
SET engineer_client_id = ec.billing_client_id
FROM engineer_clients ec
WHERE ep.engineer_client_id = ec.id
  AND ec.billing_client_id IS NOT NULL;

UPDATE engineer_documents ed
SET engineer_client_id = ec.billing_client_id
FROM engineer_clients ec
WHERE ed.engineer_client_id = ec.id
  AND ec.billing_client_id IS NOT NULL;

ALTER TABLE architect_projects RENAME COLUMN architect_client_id TO client_id;
ALTER TABLE architect_documents RENAME COLUMN architect_client_id TO client_id;
ALTER TABLE engineer_projects RENAME COLUMN engineer_client_id TO client_id;
ALTER TABLE engineer_documents RENAME COLUMN engineer_client_id TO client_id;

ALTER TABLE architect_projects
    ADD CONSTRAINT architect_projects_client_id_fkey
    FOREIGN KEY (client_id) REFERENCES clients (id) ON DELETE CASCADE;

ALTER TABLE architect_documents
    ADD CONSTRAINT architect_documents_client_id_fkey
    FOREIGN KEY (client_id) REFERENCES clients (id) ON DELETE CASCADE;

ALTER TABLE engineer_projects
    ADD CONSTRAINT engineer_projects_client_id_fkey
    FOREIGN KEY (client_id) REFERENCES clients (id) ON DELETE CASCADE;

ALTER TABLE engineer_documents
    ADD CONSTRAINT engineer_documents_client_id_fkey
    FOREIGN KEY (client_id) REFERENCES clients (id) ON DELETE CASCADE;

DROP INDEX IF EXISTS architect_projects_architect_client_id_idx;
DROP INDEX IF EXISTS architect_documents_architect_client_id_idx;
DROP INDEX IF EXISTS engineer_projects_engineer_client_id_idx;
DROP INDEX IF EXISTS engineer_documents_engineer_client_id_idx;

CREATE INDEX IF NOT EXISTS architect_projects_client_id_idx ON architect_projects (client_id);
CREATE INDEX IF NOT EXISTS architect_documents_client_id_idx ON architect_documents (client_id);
CREATE INDEX IF NOT EXISTS engineer_projects_client_id_idx ON engineer_projects (client_id);
CREATE INDEX IF NOT EXISTS engineer_documents_client_id_idx ON engineer_documents (client_id);

DROP TABLE IF EXISTS architect_clients CASCADE;
DROP TABLE IF EXISTS engineer_clients CASCADE;
