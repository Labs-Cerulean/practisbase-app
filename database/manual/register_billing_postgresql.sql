ALTER TABLE users
    ADD COLUMN IF NOT EXISTS must_verify_email boolean NOT NULL DEFAULT false;

ALTER TABLE users
    ADD COLUMN IF NOT EXISTS stripe_customer_id varchar(255) NULL;

ALTER TABLE users
    ADD COLUMN IF NOT EXISTS stripe_subscription_id varchar(255) NULL;

ALTER TABLE users
    ADD COLUMN IF NOT EXISTS subscription_status varchar(32) NULL;
