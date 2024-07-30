PRAGMA foreign_keys=OFF;
BEGIN TRANSACTION;

-- Create the database tables
CREATE TABLE IF NOT EXISTS attachment (
    id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    note_id INTEGER NOT NULL,
    -- Stores the contents of the file, binary or plain text
    file BLOB NOT NULL,
    -- Stores the file's name, retrieved from the email
    filename TEXT NOT NULL,
    -- Stores the file's content type value, retrieved from the email
    filetype TEXT NOT NULL,
    CONSTRAINT fk_attachment_note
        FOREIGN KEY (note_id)
            REFERENCES note (id)
            NOT DEFERRABLE INITIALLY IMMEDIATE
);

CREATE TABLE IF NOT EXISTS note (
    id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    user_id INTEGER DEFAULT NULL,
    details BLOB DEFAULT NULL,
    CONSTRAINT fk_note_user
        FOREIGN KEY (user_id)
            REFERENCES user (id)
            NOT DEFERRABLE INITIALLY IMMEDIATE
);

CREATE TABLE IF NOT EXISTS user (
    id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    name VARCHAR(200) NOT NULL,
    email VARCHAR(200) NOT NULL,
    phone_number VARCHAR(15) NOT NULL
);

-- Add indexes on the database
CREATE INDEX IF NOT EXISTS idx_attachment_note ON attachment (note_id);
CREATE INDEX IF NOT EXISTS idx_note_user ON note (user_id);
CREATE INDEX IF NOT EXISTS note_idx ON note (details);
CREATE UNIQUE INDEX IF NOT EXISTS uniq_user_name ON user (name);
CREATE UNIQUE INDEX IF NOT EXISTS uniq_user_email ON user (email);
CREATE UNIQUE INDEX IF NOT EXISTS uniq_user_phone ON user (phone_number);

-- Add some initial users to the database
INSERT INTO user(name, email, phone_number) VALUES('Billy Joel', 'example@example.org', '+11234567890');
INSERT INTO note(user_id, details) VALUES (1, 'Here are the details of the note');

COMMIT;