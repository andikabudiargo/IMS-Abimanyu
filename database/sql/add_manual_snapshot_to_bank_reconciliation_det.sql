-- Match Manual di Reconciliation Kas & Bank sekarang dipertahankan saat Recalculate.
-- manual_snapshot = sidik jari kas_det saat di-match manual (voucher|tanggal|akun|debit|credit);
-- NULL = hasil auto-match / UNMATCHED. Kalau saat Recalculate kas_det-nya sudah beda,
-- baris dijadikan UNMATCHED. Lihat BankReconciliationController::kasDetSnapshot().

ALTER TABLE bank_reconciliation_det ADD COLUMN IF NOT EXISTS manual_snapshot varchar(500) NULL;

-- Rollback:
-- ALTER TABLE bank_reconciliation_det DROP COLUMN IF EXISTS manual_snapshot;
