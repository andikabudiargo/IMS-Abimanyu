-- Master data: Inspection Rule - target Pass Rate & Pass Through Rate per
-- article (or a global default when article_code is NULL) per Inspection Post.
-- Looked up by Inspection Order at store/update time to flag PASS/FAIL.
-- Run manually against the target database (no Laravel migration per request).

CREATE TABLE IF NOT EXISTS inspection_rules (
    id BIGSERIAL PRIMARY KEY,
    article_code VARCHAR(255),              -- NULL = default rule for the post when no article-specific rule exists
    inspection_post_id BIGINT NOT NULL REFERENCES inspection_posts(id) ON DELETE CASCADE,
    target_pass_rate NUMERIC(5,2) NOT NULL DEFAULT 0,
    target_pass_through_rate NUMERIC(5,2) NOT NULL DEFAULT 0,
    note VARCHAR(255),
    status VARCHAR(1) NOT NULL DEFAULT '1',
    created_by VARCHAR(100),
    updated_by VARCHAR(100),
    created_at TIMESTAMP(0) WITHOUT TIME ZONE,
    updated_at TIMESTAMP(0) WITHOUT TIME ZONE
);

-- One rule per (article, post); one default (article NULL) per post.
CREATE UNIQUE INDEX IF NOT EXISTS uq_inspection_rules_scope
    ON inspection_rules (COALESCE(article_code, ''), inspection_post_id);

-- Permissions (Spatie-style table used across this app)
INSERT INTO permissions (name, guard_name, group_name, display_name, description, created_at, updated_at)
SELECT v.name, 'web', 'inspection_rule', v.display, v.display, now(), now()
FROM (VALUES
    ('inspection-rule-index', 'Inspection Rule Index'),
    ('inspection-rule-create', 'Create Inspection Rule'),
    ('inspection-rule-edit', 'Edit Inspection Rule'),
    ('inspection-rule-delete', 'Delete Inspection Rule')
) AS v(name, display)
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE permissions.name = v.name);

-- Grant to Superuser role (id=1). Assign to other roles via the Role management UI as needed.
INSERT INTO role_has_permissions (permission_id, role_id)
SELECT id, 1 FROM permissions WHERE name IN (
    'inspection-rule-index','inspection-rule-create','inspection-rule-edit','inspection-rule-delete'
) AND id NOT IN (SELECT permission_id FROM role_has_permissions WHERE role_id = 1);

-- Grant table access to the app DB user
GRANT SELECT, INSERT, UPDATE, DELETE ON inspection_rules TO abimany1_ims_asn_db;
GRANT USAGE, SELECT ON SEQUENCE inspection_rules_id_seq TO abimany1_ims_asn_db;
