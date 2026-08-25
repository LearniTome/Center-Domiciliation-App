-- Final fix: Admin role should have exactly 53 permissions (matching production)
-- Remove pv_ago.view, pv_resolutions.create, pv_resolutions.edit from Admin (role_id=2)
DELETE rp FROM role_permissions rp
JOIN permissions p ON p.id = rp.permission_id
WHERE rp.role_id = 2 AND p.permission_key IN ('pv_ago.view', 'pv_resolutions.create', 'pv_resolutions.edit');
