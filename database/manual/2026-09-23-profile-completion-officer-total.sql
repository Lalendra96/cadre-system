ALTER TABLE profile_completion_targets
    ALTER COLUMN subject_code_id DROP NOT NULL,
    ALTER COLUMN position_id DROP NOT NULL;

-- New active handling-count records should use one row per Subject Officer:
-- user_id = assigned Subject Officer
-- subject_code_id = NULL
-- position_id = NULL
-- target_count = total number of employee profiles handled by that officer
