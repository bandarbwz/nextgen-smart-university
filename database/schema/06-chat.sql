-- Chat Module Schema
-- NextGen Smart University Platform

USE nextgen_university;


CREATE TABLE IF NOT EXISTS ChatRoom (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    room_name VARCHAR(255) NOT NULL,
    room_type ENUM('Course', 'Private', 'Group', 'Announcement') NOT NULL DEFAULT 'Group',
    course_id BIGINT UNSIGNED NULL,
    section_id BIGINT UNSIGNED NULL,
    created_by BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,

    PRIMARY KEY (id),
    UNIQUE KEY uq_room_section (section_id),
    KEY idx_room_course (course_id),
    KEY idx_room_type (room_type),

    CONSTRAINT fk_room_course
        FOREIGN KEY (course_id) REFERENCES Course (id)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT fk_room_section
        FOREIGN KEY (section_id) REFERENCES Section (id)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT fk_room_created_by
        FOREIGN KEY (created_by) REFERENCES User (id)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS ChatMember (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    room_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    role ENUM('Owner', 'Lecturer', 'Student', 'Moderator') NOT NULL DEFAULT 'Student',
    joined_at DATETIME NOT NULL,
    last_read_message_id BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_member_room_user (room_id, user_id),
    KEY idx_member_room (room_id),
    KEY idx_member_user (user_id),

    CONSTRAINT fk_member_room
        FOREIGN KEY (room_id) REFERENCES ChatRoom (id)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT fk_member_user
        FOREIGN KEY (user_id) REFERENCES User (id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS Message (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    room_id BIGINT UNSIGNED NOT NULL,
    sender_id BIGINT UNSIGNED NOT NULL,
    message_type ENUM('Text', 'Image', 'Video', 'Voice', 'File', 'Sticker') NOT NULL DEFAULT 'Text',
    message TEXT NULL,
    reply_to BIGINT UNSIGNED NULL,
    edited BOOLEAN NOT NULL DEFAULT FALSE,
    edited_at DATETIME NULL,
    pinned BOOLEAN NOT NULL DEFAULT FALSE,
    pinned_by BIGINT UNSIGNED NULL,
    pinned_at DATETIME NULL,
    sent_at DATETIME NOT NULL,
    deleted_at DATETIME NULL,
    deleted_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    KEY idx_message_room (room_id),
    KEY idx_message_sender (sender_id),
    KEY idx_message_sent (sent_at),
    KEY idx_message_room_id_order (room_id, id),

    CONSTRAINT fk_message_room
        FOREIGN KEY (room_id) REFERENCES ChatRoom (id)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT fk_message_sender
        FOREIGN KEY (sender_id) REFERENCES User (id)
        ON DELETE RESTRICT ON UPDATE CASCADE,

    CONSTRAINT fk_message_reply_to
        FOREIGN KEY (reply_to) REFERENCES Message (id)
        ON DELETE SET NULL ON UPDATE CASCADE,

    CONSTRAINT fk_message_pinned_by
        FOREIGN KEY (pinned_by) REFERENCES User (id)
        ON DELETE SET NULL ON UPDATE CASCADE,

    CONSTRAINT fk_message_deleted_by
        FOREIGN KEY (deleted_by) REFERENCES User (id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS MessageAttachment (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    message_id BIGINT UNSIGNED NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    file_size INT UNSIGNED NOT NULL,
    file_type VARCHAR(50) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    KEY idx_attachment_message (message_id),

    CONSTRAINT fk_attachment_message
        FOREIGN KEY (message_id) REFERENCES Message (id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS MessageReaction (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    message_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    reaction ENUM('Like', 'Love', 'Laugh', 'Sad', 'Angry') NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_reaction_message_user (message_id, user_id),
    KEY idx_reaction_message (message_id),

    CONSTRAINT fk_reaction_message
        FOREIGN KEY (message_id) REFERENCES Message (id)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT fk_reaction_user
        FOREIGN KEY (user_id) REFERENCES User (id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS MessageRead (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    message_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    read_at DATETIME NOT NULL,

    PRIMARY KEY (id),
    UNIQUE KEY uq_read_message_user (message_id, user_id),
    KEY idx_read_message (message_id),

    CONSTRAINT fk_read_message
        FOREIGN KEY (message_id) REFERENCES Message (id)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT fk_read_user
        FOREIGN KEY (user_id) REFERENCES User (id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;
