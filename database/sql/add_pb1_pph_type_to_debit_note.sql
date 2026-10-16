-- Tambah kolom PB1 + tipe PPh (PPH23/PPH21/PPH42) ke debit_note_hdr,
-- supaya DN ke supplier bisa pakai pajak lain selain PPN/PPh23, sama seperti ap_invoice.

ALTER TABLE debit_note_hdr ADD COLUMN IF NOT EXISTS pb1 NUMERIC DEFAULT 0;
ALTER TABLE debit_note_hdr ADD COLUMN IF NOT EXISTS pph23_type VARCHAR(10) DEFAULT '';
