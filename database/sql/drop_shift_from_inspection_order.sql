-- Shift field removed from Inspection Order (not needed).
-- Run manually against the target database (no Laravel migration per request).

ALTER TABLE inspection_order_hdr DROP COLUMN IF EXISTS shift;
