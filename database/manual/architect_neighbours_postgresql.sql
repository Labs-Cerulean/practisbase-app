CREATE TABLE IF NOT EXISTS architect_neighbours (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT NOT NULL,
    architect_project_id BIGINT NOT NULL,
    architect_pa_application_id BIGINT NULL,
    architect_condition_report_id BIGINT NULL,
    address TEXT NOT NULL,
    premises VARCHAR(255) NULL,
    street VARCHAR(255) NULL,
    locality VARCHAR(120) NULL,
    owner_occupier_name VARCHAR(255) NULL,
    phone VARCHAR(64) NULL,
    email VARCHAR(255) NULL,
    relation VARCHAR(40) NOT NULL DEFAULT 'abutting',
    status VARCHAR(40) NOT NULL DEFAULT 'identified',
    appointment_on DATE NULL,
    notes TEXT NULL,
    latitude NUMERIC(10, 7) NULL,
    longitude NUMERIC(10, 7) NULL,
    sort_order INTEGER NOT NULL DEFAULT 0,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    CONSTRAINT architect_neighbours_user_id_fkey
        FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT architect_neighbours_project_id_fkey
        FOREIGN KEY (architect_project_id) REFERENCES architect_projects (id) ON DELETE CASCADE,
    CONSTRAINT architect_neighbours_pa_id_fkey
        FOREIGN KEY (architect_pa_application_id) REFERENCES architect_pa_applications (id) ON DELETE SET NULL,
    CONSTRAINT architect_neighbours_cr_id_fkey
        FOREIGN KEY (architect_condition_report_id) REFERENCES architect_condition_reports (id) ON DELETE SET NULL
);

CREATE INDEX IF NOT EXISTS architect_neighbours_user_id_idx
    ON architect_neighbours (user_id);

CREATE INDEX IF NOT EXISTS architect_neighbours_project_id_idx
    ON architect_neighbours (architect_project_id);

CREATE INDEX IF NOT EXISTS architect_neighbours_project_status_idx
    ON architect_neighbours (architect_project_id, status);

ALTER TABLE architect_condition_reports
    ADD COLUMN IF NOT EXISTS architect_neighbour_id BIGINT NULL;

CREATE INDEX IF NOT EXISTS architect_condition_reports_neighbour_id_idx
    ON architect_condition_reports (architect_neighbour_id);
