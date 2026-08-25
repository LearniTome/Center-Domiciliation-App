-- Fix Admin and Super Admin role_permissions to match production seed_rbac.sql
-- Local had broader assignments from old seed; replace with correct ones

-- Super Admin (id=1): ALL permissions
DELETE FROM role_permissions WHERE role_id = 1;
INSERT INTO role_permissions (role_id, permission_id)
SELECT 1, id FROM permissions;

-- Admin (id=2): all except roles.manage (id=38)
DELETE FROM role_permissions WHERE role_id = 2;
INSERT INTO role_permissions (role_id, permission_id)
SELECT 2, id FROM permissions WHERE id != 38;
