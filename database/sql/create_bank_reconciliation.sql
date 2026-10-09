-- Reconciliation Kas & Bank: user isi header (periode, description, type Kas/Bank)
-- lalu upload PDF rekening koran; hasil parse + match tersimpan di det.
-- matched_kas_det_id -> kas_det.id saat match ketemu (auto atau manual); null = UNMATCHED.
-- Matching hanya berdasarkan Type (voucher_type KM/KK atau BM/BK) -- scope saat ini
-- cuma 1 rekening bank (BCA), jadi tidak perlu filter akun COA spesifik. Kalau nanti
-- ada >1 rekening per Type, tambahkan lagi kolom bank_account + filter akunnya.

CREATE TABLE IF NOT EXISTS bank_reconciliation_hdr (
    id            bigserial PRIMARY KEY,
    recon_number  varchar(255) NOT NULL UNIQUE,
    periode       varchar(255) NOT NULL,
    year          smallint NOT NULL,
    description   varchar(255) NULL,
    type          varchar(4) NOT NULL,           -- KAS | BANK
    status        varchar(15) NOT NULL DEFAULT 'NEW', -- NEW | DONE
    created_by    varchar(255) NULL,
    updated_by    varchar(255) NULL,
    created_at    timestamp(0) without time zone NULL,
    updated_at    timestamp(0) without time zone NULL
);

-- Aman dijalankan ulang kalau tabel di atas sempat ke-buat lebih dulu dengan kolom lama.
ALTER TABLE bank_reconciliation_hdr DROP COLUMN IF EXISTS bank_account;

CREATE TABLE IF NOT EXISTS bank_reconciliation_det (
    id                  bigserial PRIMARY KEY,
    recon_number        varchar(255) NOT NULL,
    stmt_date           date NOT NULL,
    description         text NULL,
    amount              numeric(18,2) NOT NULL,
    mutation_type       varchar(2) NOT NULL,      -- DB | CR
    saldo               numeric(18,2) NULL,        -- saldo per tanggal (BCA cuma nampilin di transaksi terakhir tiap tanggal, bisa NULL)
    status              varchar(15) NOT NULL DEFAULT 'UNMATCHED', -- MATCHED | UNMATCHED
    matched_kas_det_id  bigint NULL,
    created_by          varchar(255) NULL,
    created_at          timestamp(0) without time zone NOT NULL DEFAULT now(),
    CONSTRAINT bank_recon_det_dedup UNIQUE (recon_number, stmt_date, description, amount, mutation_type)
);

CREATE INDEX IF NOT EXISTS bank_reconciliation_det_recon_number_index
    ON bank_reconciliation_det (recon_number);

-- ---------------------------------------------------------------------
-- Nomor urut Reconciliation Kas & Bank (BANKREC-2026-X-0001) lewat master_code,
-- pola yang sama dipakai modul lain (PO, PR, RECON, dll).
-- ---------------------------------------------------------------------
INSERT INTO master_code (code_key, code_number, created_by, updated_by, created_at, updated_at)
SELECT 'BANKREC', 0, 'system', 'system', now(), now()
WHERE NOT EXISTS (SELECT 1 FROM master_code WHERE code_key = 'BANKREC');

-- ---------------------------------------------------------------------
-- Grant akses ke user aplikasi (abimany1_ims_asn_db) untuk tabel baru di atas,
-- termasuk sequence dari kolom bigserial-nya.
-- ---------------------------------------------------------------------
GRANT SELECT, INSERT, UPDATE, DELETE ON
    bank_reconciliation_hdr,
    bank_reconciliation_det
TO abimany1_ims_asn_db;

GRANT USAGE, SELECT ON
    bank_reconciliation_hdr_id_seq,
    bank_reconciliation_det_id_seq
TO abimany1_ims_asn_db;

-- Rollback:
-- DROP TABLE IF EXISTS bank_reconciliation_det;
-- DROP TABLE IF EXISTS bank_reconciliation_hdr;
-- DELETE FROM master_code WHERE code_key = 'BANKREC';
