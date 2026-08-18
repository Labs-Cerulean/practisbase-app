ALTER TABLE architect_projects
    ADD COLUMN IF NOT EXISTS latitude NUMERIC(10, 7) NULL;

ALTER TABLE architect_projects
    ADD COLUMN IF NOT EXISTS longitude NUMERIC(10, 7) NULL;

ALTER TABLE architect_pa_applications
    ADD COLUMN IF NOT EXISTS case_type VARCHAR(16) NOT NULL DEFAULT 'PA';

ALTER TABLE architect_pa_applications
    ADD COLUMN IF NOT EXISTS case_number VARCHAR(16) NULL;

ALTER TABLE architect_pa_applications
    ADD COLUMN IF NOT EXISTS case_year VARCHAR(4) NULL;

UPDATE architect_pa_applications
SET status = 'tracking'
WHERE status = 'active';

UPDATE architect_pa_applications
SET status = 'endorsed'
WHERE status = 'approved';
