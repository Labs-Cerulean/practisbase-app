ALTER TABLE users
ADD COLUMN IF NOT EXISTS clients_created_count integer NOT NULL DEFAULT 0;

ALTER TABLE clients
ADD COLUMN IF NOT EXISTS deleted_at timestamp without time zone NULL;

UPDATE clients
SET profile_data = profile_data - 'dob' - 'gender' - 'blood_type' - 'allergies'
WHERE profile_data IS NOT NULL;
