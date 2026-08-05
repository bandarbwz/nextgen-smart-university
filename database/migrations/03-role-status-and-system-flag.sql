-- Role Management needs two things the Role table never had: whether a role is
-- active, and whether it is one of the six the platform ships with.
--
-- The business rule says system default roles cannot be deleted. Without a flag
-- that rule can only be enforced by hard coding names in PHP, which breaks the
-- moment somebody renames a role.
--
-- Idempotent, so running it twice is harmless.

USE nextgen_university;

SET @has_status := (
    SELECT COUNT(*) FROM information_schema.columns
    WHERE table_schema = 'nextgen_university'
      AND table_name = 'Role' AND column_name = 'status'
);

SET @sql := IF(
    @has_status = 0,
    'ALTER TABLE Role
        ADD COLUMN status ENUM(''active'', ''inactive'') NOT NULL DEFAULT ''active'' AFTER description,
        ADD COLUMN is_system BOOLEAN NOT NULL DEFAULT FALSE AFTER status',
    'SELECT "Role already carries status and is_system"'
);

PREPARE statement FROM @sql;
EXECUTE statement;
DEALLOCATE PREPARE statement;

UPDATE Role
SET is_system = TRUE
WHERE name IN ('Student', 'Lecturer', 'Coordinator', 'Administrator',
               'Restaurant Owner', 'STAD Staff');
