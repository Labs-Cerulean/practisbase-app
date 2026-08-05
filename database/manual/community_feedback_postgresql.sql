CREATE TABLE IF NOT EXISTS community_feedback (
    id bigserial PRIMARY KEY,
    user_id bigint NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    category varchar(40) NOT NULL DEFAULT 'suggestion',
    subject varchar(200) NOT NULL,
    status varchar(40) NOT NULL DEFAULT 'open',
    status_note text NULL,
    staff_unread boolean NOT NULL DEFAULT true,
    user_unread boolean NOT NULL DEFAULT false,
    created_at timestamp without time zone NULL,
    updated_at timestamp without time zone NULL
);

CREATE INDEX IF NOT EXISTS community_feedback_user_id_index
    ON community_feedback (user_id);

CREATE INDEX IF NOT EXISTS community_feedback_status_index
    ON community_feedback (status);

CREATE INDEX IF NOT EXISTS community_feedback_staff_unread_index
    ON community_feedback (staff_unread);

CREATE INDEX IF NOT EXISTS community_feedback_updated_at_index
    ON community_feedback (updated_at DESC);

CREATE TABLE IF NOT EXISTS community_feedback_messages (
    id bigserial PRIMARY KEY,
    feedback_id bigint NOT NULL REFERENCES community_feedback(id) ON DELETE CASCADE,
    user_id bigint NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    body text NOT NULL,
    is_staff boolean NOT NULL DEFAULT false,
    created_at timestamp without time zone NULL,
    updated_at timestamp without time zone NULL
);

CREATE INDEX IF NOT EXISTS community_feedback_messages_feedback_id_index
    ON community_feedback_messages (feedback_id);
