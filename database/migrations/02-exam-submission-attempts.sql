-- Allow a reset examination to be sat again.
--
-- ExamSubmission originally held one row per student per examination, which
-- made a reset impossible: the old row blocked the new one. An attempt number
-- lets the retake exist alongside the original rather than replacing it, so the
-- first attempt stays on record.
--
-- Idempotent, so running it twice is harmless.

USE nextgen_university;

SET @column_exists := (
    SELECT COUNT(*) FROM information_schema.columns
    WHERE table_schema = 'nextgen_university'
      AND table_name = 'ExamSubmission'
      AND column_name = 'attempt_number'
);

SET @sql := IF(
    @column_exists = 0,
    'ALTER TABLE ExamSubmission
        ADD COLUMN attempt_number SMALLINT UNSIGNED NOT NULL DEFAULT 1 AFTER student_id,
        ADD COLUMN reset_at DATETIME NULL AFTER attempt_number,
        DROP INDEX uq_exam_submission_student,
        ADD UNIQUE KEY uq_exam_submission_student (exam_id, student_id, attempt_number)',
    'SELECT "ExamSubmission already carries attempt_number"'
);

PREPARE statement FROM @sql;
EXECUTE statement;
DEALLOCATE PREPARE statement;
