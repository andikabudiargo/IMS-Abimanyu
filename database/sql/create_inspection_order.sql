-- Inspection Order - transactional QC inspection record (incoming / unloading /
-- buffing / touch up / final / outgoing), mirrors "Daily Inspection" from the
-- abimanyu QC module, plus a PASS/FAIL flag computed against Inspection Rule's
-- target Pass Rate & Pass Through Rate.
-- No stock movement is posted by this module - it is a QC record only.
-- Run manually against the target database (no Laravel migration per request).

CREATE TABLE IF NOT EXISTS inspection_order_hdr (
    id SERIAL PRIMARY KEY,
    order_no VARCHAR(255) NOT NULL UNIQUE,
    inspection_date DATE NOT NULL,
    inspection_post_id BIGINT NOT NULL REFERENCES inspection_posts(id),
    supplier_code VARCHAR(255),          -- third_party.kode, nullable (not every post has a supplier)
    article_code VARCHAR(255) NOT NULL,  -- article.article_code
    shift VARCHAR(20),
    check_method VARCHAR(50),
    qty_received NUMERIC DEFAULT 0,
    total_check NUMERIC DEFAULT 0,
    total_ok NUMERIC DEFAULT 0,
    total_ok_repair NUMERIC DEFAULT 0,
    total_ng NUMERIC DEFAULT 0,
    pass_rate NUMERIC(5,2) DEFAULT 0,          -- total_ok / total_check * 100
    pass_through_rate NUMERIC(5,2) DEFAULT 0,  -- (total_check - total_ng - total_ok_repair) / total_check * 100
    target_pass_rate NUMERIC(5,2),             -- snapshot from inspection_rules at save time
    target_pass_through_rate NUMERIC(5,2),
    result VARCHAR(10),                        -- PASS / FAIL, blank when no rule matched
    note TEXT,
    -- 1 DRAFT, 4 POSTED, 5 CANCELED
    status VARCHAR(1) NOT NULL DEFAULT '1',
    cancel_reason TEXT,
    created_by VARCHAR(100),
    updated_by VARCHAR(100),
    created_at TIMESTAMP(0) WITHOUT TIME ZONE,
    updated_at TIMESTAMP(0) WITHOUT TIME ZONE
);

CREATE TABLE IF NOT EXISTS inspection_order_det (
    id SERIAL PRIMARY KEY,
    inspection_order_id INTEGER NOT NULL REFERENCES inspection_order_hdr(id) ON DELETE CASCADE,
    defect_id BIGINT REFERENCES defects(id),
    category VARCHAR(10),  -- NG / NC
    qty NUMERIC DEFAULT 0,
    ok_repair NUMERIC DEFAULT 0,
    note VARCHAR(255),
    created_at TIMESTAMP(0) WITHOUT TIME ZONE
);

CREATE INDEX IF NOT EXISTS idx_inspection_order_det_hdr ON inspection_order_det(inspection_order_id);
CREATE INDEX IF NOT EXISTS idx_inspection_order_hdr_article ON inspection_order_hdr(article_code);
CREATE INDEX IF NOT EXISTS idx_inspection_order_hdr_supplier ON inspection_order_hdr(supplier_code);

-- Document numbering (format QCO-ASN-<tahun>-<bulan romawi>-<no urut>)
INSERT INTO master_code (code_key, code_number, created_by, updated_by, created_at, updated_at)
SELECT 'QCO', 0, 'system', 'system', now(), now()
WHERE NOT EXISTS (SELECT 1 FROM master_code WHERE code_key = 'QCO');

-- Permissions (Spatie-style table used across this app)
INSERT INTO permissions (name, guard_name, group_name, display_name, description, created_at, updated_at)
SELECT v.name, 'web', 'inspection_order', v.display, v.display, now(), now()
FROM (VALUES
    ('inspectionOrder-index', 'Inspection Order Index'),
    ('inspectionOrder-create', 'Create Inspection Order'),
    ('inspectionOrder-edit', 'Edit Inspection Order'),
    ('inspectionOrder-delete', 'Delete Inspection Order')
) AS v(name, display)
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE permissions.name = v.name);

-- Grant to Superuser role (id=1). Assign to other roles via the Role management UI as needed.
INSERT INTO role_has_permissions (permission_id, role_id)
SELECT id, 1 FROM permissions WHERE name IN (
    'inspectionOrder-index','inspectionOrder-create','inspectionOrder-edit','inspectionOrder-delete'
) AND id NOT IN (SELECT permission_id FROM role_has_permissions WHERE role_id = 1);

-- Grant table access to the app DB user
GRANT SELECT, INSERT, UPDATE, DELETE ON inspection_order_hdr, inspection_order_det TO abimany1_ims_asn_db;
GRANT USAGE, SELECT ON SEQUENCE inspection_order_hdr_id_seq, inspection_order_det_id_seq TO abimany1_ims_asn_db;
