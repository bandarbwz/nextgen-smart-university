-- Role Management Module Schema
-- NextGen Smart University Platform
--
-- Follows docs/FEATURES/19-Role-Management.md.
--
-- Role, Permission and RolePermission already existed, built with the
-- Authentication module. This file adds only what was missing: the audit trail.
--
-- The feature document also lists a UserRole table holding user_id, role_id,
-- assigned_by and assigned_at. That table is deliberately NOT created. A user's
-- role already lives in User.role_id, which every authentication query and the
-- JWT payload read from. A second table holding the same fact would be two
-- competing sources of truth, and the first time they disagreed somebody would
-- be granted or denied access wrongly.
--
-- The intent behind UserRole, knowing who changed a role and when, is met by
-- AuthorizationLog below, which records role assignments alongside every other
-- authorization change.

USE nextgen_university;


CREATE TABLE IF NOT EXISTS AuthorizationLog (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    action ENUM(
        'Role Created', 'Role Updated', 'Role Deleted',
        'Permissions Assigned', 'User Role Changed'
    ) NOT NULL,
    role_id BIGINT UNSIGNED NULL,
    target_user_id BIGINT UNSIGNED NULL,
    performed_by BIGINT UNSIGNED NOT NULL,
    detail VARCHAR(500) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    KEY idx_authorization_log_role (role_id),
    KEY idx_authorization_log_user (target_user_id),
    KEY idx_authorization_log_created (created_at),

    CONSTRAINT fk_authorization_log_role
        FOREIGN KEY (role_id) REFERENCES Role (id)
        ON DELETE SET NULL ON UPDATE CASCADE,

    CONSTRAINT fk_authorization_log_target_user
        FOREIGN KEY (target_user_id) REFERENCES User (id)
        ON DELETE SET NULL ON UPDATE CASCADE,

    CONSTRAINT fk_authorization_log_performed_by
        FOREIGN KEY (performed_by) REFERENCES User (id)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;
