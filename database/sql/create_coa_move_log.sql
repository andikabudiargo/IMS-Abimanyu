-- Audit trail for the COA-move tool (CoaMoveController). One row per
-- table.column touched by a move, so every merged document can be traced
-- back to its original account even though the lookup column itself was
-- overwritten.

CREATE TABLE IF NOT EXISTS coa_move_log (
    id bigserial PRIMARY KEY,
    batch_id varchar(36) NOT NULL,
    source_account varchar(50) NOT NULL,
    target_account varchar(50) NOT NULL,
    table_name varchar(100) NOT NULL,
    column_name varchar(100) NOT NULL,
    row_count integer NOT NULL,
    affected_pks jsonb NULL,
    moved_by varchar(100) NOT NULL,
    moved_at timestamp NOT NULL DEFAULT now()
);

-- Rollback:
-- DROP TABLE IF EXISTS coa_move_log;
