ALTER TABLE users
    ADD COLUMN IF NOT EXISTS last_data_backup_at timestamp without time zone NULL;
