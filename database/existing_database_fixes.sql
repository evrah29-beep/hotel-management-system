-- Apply to the EXISTING hotel_management database.
-- This does not create a database, table, or column. It fixes stored paths, merges stale duplicate page IDs, and corrects the existing room-type foreign key.
START TRANSACTION;

UPDATE system_pages SET url='admin/Developer/pages.php' WHERE url='admin/developer/pages.php';

-- Preserve any permissions assigned to the obsolete duplicate page rows before removing them.
INSERT IGNORE INTO user_permissions(admin_id,page_id) SELECT admin_id,27 FROM user_permissions WHERE page_id=20;
INSERT IGNORE INTO user_permissions(admin_id,page_id) SELECT admin_id,28 FROM user_permissions WHERE page_id=21;
INSERT IGNORE INTO user_permissions(admin_id,page_id) SELECT admin_id,31 FROM user_permissions WHERE page_id=22;
INSERT IGNORE INTO user_permissions(admin_id,page_id) SELECT admin_id,32 FROM user_permissions WHERE page_id=23;
INSERT IGNORE INTO user_permissions(admin_id,page_id) SELECT admin_id,33 FROM user_permissions WHERE page_id=24;
INSERT IGNORE INTO user_permissions(admin_id,page_id) SELECT admin_id,34 FROM user_permissions WHERE page_id=25;
INSERT IGNORE INTO user_permissions(admin_id,page_id) SELECT admin_id,36 FROM user_permissions WHERE page_id=26;
DELETE FROM system_pages WHERE id IN (20,21,22,23,24,25,26);

ALTER TABLE rooms DROP FOREIGN KEY room_id;
ALTER TABLE rooms ADD CONSTRAINT room_id FOREIGN KEY (room_type_id) REFERENCES room_types(id);

COMMIT;
