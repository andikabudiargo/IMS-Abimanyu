-- Equalisasi PPN: tabel detail belum menyimpan Tanggal Faktur Pajak dan DPP Nilai Lain
-- (kolom E & G di export Excel) -- keduanya dipakai di tabel detail (kolom Tanggal setelah
-- Nama, DPP Nilai Lain setelah DPP), lihat PpnPkEqualisasiImport.

ALTER TABLE ppn_pk_equalisasi_det ADD COLUMN IF NOT EXISTS tanggal varchar(10) NULL;
ALTER TABLE ppn_pk_equalisasi_det ADD COLUMN IF NOT EXISTS dpp_lain numeric(18,2) NOT NULL DEFAULT 0;

-- Rollback:
-- ALTER TABLE ppn_pk_equalisasi_det DROP COLUMN IF EXISTS tanggal;
-- ALTER TABLE ppn_pk_equalisasi_det DROP COLUMN IF EXISTS dpp_lain;
