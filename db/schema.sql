-- SCHEMA file should be idempotent, runs on every connection
CREATE TABLE IF NOT EXISTS birkman_data (
    birkman_id text,
    slack_username text,
    birkman_data json
);
