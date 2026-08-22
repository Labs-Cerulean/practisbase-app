ALTER TABLE architect_projects
    ADD COLUMN IF NOT EXISTS site_boundary_geojson JSONB NULL;
