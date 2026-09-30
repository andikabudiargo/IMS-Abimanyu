-- Master Schedule Project: approval-workflow milestone markers per line
-- (Plan/Actual/Reschedule x Draft/Approval Internal/Approval External),
-- matching the D/AI/AE/RD/RAI/RAE legend in the customer's Excel template.
-- Run manually against the target database.

ALTER TABLE msp_dtl
    ADD COLUMN IF NOT EXISTS plan_draft_date DATE,
    ADD COLUMN IF NOT EXISTS plan_ai_date DATE,
    ADD COLUMN IF NOT EXISTS plan_ae_date DATE,
    ADD COLUMN IF NOT EXISTS actual_draft_date DATE,
    ADD COLUMN IF NOT EXISTS actual_ai_date DATE,
    ADD COLUMN IF NOT EXISTS actual_ae_date DATE,
    ADD COLUMN IF NOT EXISTS resch_draft_date DATE,
    ADD COLUMN IF NOT EXISTS resch_ai_date DATE,
    ADD COLUMN IF NOT EXISTS resch_ae_date DATE;
