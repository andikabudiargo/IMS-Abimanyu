-- Equalisasi Unifikasi BPS: upload export BPS (bukti potong dari customer), dicocokkan per
-- Nomor Pemotongan ke bukti_potong voucher (invoice_hdr/kas_hdr/debit_note_hdr) yang posting
-- debet ke COA 1100.75. Lihat UnifikasiBpsController.
-- Butuh add_bukti_potong_columns.sql + add_bukti_potong_to_debit_note.sql sudah jalan.

CREATE TABLE IF NOT EXISTS unifikasi_bps_hdr (
    id            bigserial PRIMARY KEY,
    equal_number  varchar(255) NOT NULL UNIQUE,
    type          varchar(5) NOT NULL,          -- BPS
    periode       smallint NOT NULL,
    year          smallint NOT NULL,
    coa_account   varchar(255) NOT NULL,
    description   varchar(255) NULL,
    total_dpp     numeric(18,2) NOT NULL DEFAULT 0,
    total_pph     numeric(18,2) NOT NULL DEFAULT 0,
    created_by    varchar(255) NULL,
    updated_by    varchar(255) NULL,
    created_at    timestamp(0) without time zone NULL,
    updated_at    timestamp(0) without time zone NULL
);

CREATE TABLE IF NOT EXISTS unifikasi_bps_det (
    id                      bigserial PRIMARY KEY,
    equal_number            varchar(255) NOT NULL,
    masa_pajak              varchar(50) NULL,
    doc_number              varchar(255) NULL,  -- Nomor Pemotongan
    status                  varchar(50) NULL,
    jenis_pajak             varchar(100) NULL,
    kode_objek_pajak        varchar(50) NULL,
    nama                    varchar(255) NULL,
    dpp                     numeric(18,2) NOT NULL DEFAULT 0,
    pph                     numeric(18,2) NOT NULL DEFAULT 0,
    match_status            varchar(15) NOT NULL DEFAULT 'UNMATCHED', -- MATCHED | UNMATCHED
    match_type              varchar(10) NULL,   -- AUTO | MANUAL (MANUAL dipertahankan saat Rekonsiliasi)
    matched_voucher_number  varchar(255) NULL,
    gl_amount               numeric(18,2) NULL,
    created_by              varchar(255) NULL,
    created_at              timestamp(0) without time zone NOT NULL DEFAULT now(),
    CONSTRAINT unifikasi_bps_det_dedup UNIQUE (equal_number, doc_number)
);

CREATE INDEX IF NOT EXISTS unifikasi_bps_det_equal_number_index ON unifikasi_bps_det (equal_number);

INSERT INTO master_code (code_key, code_number, created_by, updated_by, created_at, updated_at)
SELECT k, 0, 'system', 'system', now(), now() FROM (VALUES ('BPS')) v(k)
WHERE NOT EXISTS (SELECT 1 FROM master_code WHERE code_key = v.k);

GRANT SELECT, INSERT, UPDATE, DELETE ON unifikasi_bps_hdr, unifikasi_bps_det TO abimany1_ims_asn_db;
GRANT USAGE, SELECT ON unifikasi_bps_hdr_id_seq, unifikasi_bps_det_id_seq TO abimany1_ims_asn_db;

-- Rollback:
-- DROP TABLE IF EXISTS unifikasi_bps_det;
-- DROP TABLE IF EXISTS unifikasi_bps_hdr;
-- DELETE FROM master_code WHERE code_key IN ('BPS');

