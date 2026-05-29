-- ======================================================
-- Additional Seed Data for FIBECO Bidding System
-- Database: bidding_system
-- ======================================================

-- ======================================================
-- INSERT ADDITIONAL BIDDING CATEGORIES
-- ======================================================
INSERT INTO bidding_categories (category_name, description, sort_order) VALUES
('Consulting Services', 'Technical and management consulting services', 7),
('Civil Works', 'Infrastructure and construction projects', 8),
('Software Development', 'IT systems and software solutions', 9)
ON DUPLICATE KEY UPDATE id = id;

-- ======================================================
-- INSERT SAMPLE ACTIVITY LOGS (for testing)
-- ======================================================
INSERT INTO activity_logs (user_id, action, entity_type, entity_id, ip_address, created_at) VALUES
(1, 'LOGIN', 'user', 1, '127.0.0.1', DATE_SUB(NOW(), INTERVAL 1 DAY)),
(1, 'CREATE_PUBLIC_BIDDING', 'public_bidding', 1, '127.0.0.1', DATE_SUB(NOW(), INTERVAL 1 DAY)),
(2, 'LOGIN', 'user', 2, '127.0.0.1', DATE_SUB(NOW(), INTERVAL 12 HOUR)),
(2, 'VIEW_SEALED_BIDDING', 'sealed_bidding', 1, '127.0.0.1', DATE_SUB(NOW(), INTERVAL 11 HOUR))
ON DUPLICATE KEY UPDATE id = id;

-- ======================================================
-- INSERT SAMPLE UPLOADED DOCUMENTS (for testing extraction)
-- ======================================================
INSERT INTO uploaded_documents (original_filename, stored_filename, file_path, file_type, file_size, document_type, upload_status, uploaded_by, created_at) VALUES
('sample_bidding.xlsx', 'sample_1.xlsx', '/uploads/bidding-documents/sample_1.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 25600, 'public_bidding', 'extracted', 1, DATE_SUB(NOW(), INTERVAL 2 DAY)),
('sample_sealed.pdf', 'sample_2.pdf', '/uploads/bidding-documents/sample_2.pdf', 'application/pdf', 102400, 'sealed_bidding', 'pending', 1, DATE_SUB(NOW(), INTERVAL 1 DAY))
ON DUPLICATE KEY UPDATE id = id;

-- ======================================================
-- UPDATE LAST LOGIN FOR USERS
-- ======================================================
UPDATE users SET last_login = DATE_SUB(NOW(), INTERVAL 1 DAY) WHERE id = 1;
UPDATE users SET last_login = DATE_SUB(NOW(), INTERVAL 12 HOUR) WHERE id = 2;

-- ======================================================
-- VERIFY DATA INSERTED
-- ======================================================
SELECT 'Seed data inserted successfully!' AS message;
SELECT 
    'Users' AS 'Table',
    COUNT(*) AS 'Record Count'
FROM users
UNION ALL
SELECT 'Public Bidding', COUNT(*) FROM public_bidding
UNION ALL
SELECT 'Sealed Bidding', COUNT(*) FROM sealed_bidding
UNION ALL
SELECT 'Procurement Monitoring', COUNT(*) FROM procurement_monitoring;