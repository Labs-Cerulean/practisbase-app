ALTER TABLE architect_projects
    ADD COLUMN IF NOT EXISTS engagement_type varchar(64) NOT NULL DEFAULT 'full_project';
