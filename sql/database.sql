-- ======================================================
-- FIBECO Bidding System Database Schema
-- Database Name: bidding_system
-- ======================================================

-- Use the database (run this separately if needed)
-- CREATE DATABASE IF NOT EXISTS bidding_system;
-- USE bidding_system;

-- ======================================================
-- 1. USERS TABLE
-- ======================================================
DROP TABLE IF EXISTS users;
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    department VARCHAR(100) DEFAULT NULL,
    position VARCHAR(100) DEFAULT NULL,
    phone VARCHAR(20) DEFAULT NULL,
    role ENUM('admin', 'user', 'viewer') DEFAULT 'user',
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_username (username),
    INDEX idx_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ======================================================
-- 2. PUBLIC BIDDING TABLE
-- ======================================================
DROP TABLE IF EXISTS public_bidding;
CREATE TABLE public_bidding (
    id INT PRIMARY KEY AUTO_INCREMENT,
    bidding_date DATE NOT NULL,
    project_title TEXT NOT NULL,
    fund_source VARCHAR(100) NOT NULL,
    capex_project VARCHAR(100) DEFAULT NULL,
    approved_budget_contract DECIMAL(15,2) NOT NULL,
    participating_bidders TEXT,
    winning_bidder VARCHAR(200),
    winning_bid_amount DECIMAL(15,2),
    notice_of_award DATE,
    contract_date DATE,
    performance_bond_form VARCHAR(50),
    performance_bond_amount DECIMAL(15,2),
    notice_to_proceed DATE,
    purchase_order_ref VARCHAR(100),
    status ENUM('active', 'completed', 'failed', 'cancelled', 'ongoing') DEFAULT 'active',
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_bidding_date (bidding_date),
    INDEX idx_status (status),
    INDEX idx_fund_source (fund_source)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ======================================================
-- 3. SEALED BIDDING TABLE
-- ======================================================
DROP TABLE IF EXISTS sealed_bidding;
CREATE TABLE sealed_bidding (
    id INT PRIMARY KEY AUTO_INCREMENT,
    bidding_date DATE NOT NULL,
    project_title TEXT NOT NULL,
    fund_source VARCHAR(100) NOT NULL,
    participating_bidders TEXT,
    winning_bidder VARCHAR(200),
    winning_bid_amount DECIMAL(15,2),
    contract_or_po_ref VARCHAR(100),
    confidential_notes TEXT,
    status ENUM('active', 'completed', 'failed', 'cancelled', 'awarded') DEFAULT 'active',
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_bidding_date (bidding_date),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ======================================================
-- 4. PROCUREMENT MONITORING TABLE
-- ======================================================
DROP TABLE IF EXISTS procurement_monitoring;
CREATE TABLE procurement_monitoring (
    id INT PRIMARY KEY AUTO_INCREMENT,
    itb_no VARCHAR(50),
    particulars TEXT NOT NULL,
    abc DECIMAL(15,2),
    bidder_1 VARCHAR(200),
    bidder_1_price DECIMAL(15,2),
    bidder_2 VARCHAR(200),
    bidder_2_price DECIMAL(15,2),
    bidder_3 VARCHAR(200),
    bidder_3_price DECIMAL(15,2),
    bidder_4 VARCHAR(200),
    bidder_4_price DECIMAL(15,2),
    bidder_5 VARCHAR(200),
    bidder_5_price DECIMAL(15,2),
    winning_bidder VARCHAR(200),
    winning_price DECIMAL(15,2),
    remarks TEXT,
    delivery_date_per_po DATE,
    actual_delivery_date DATE,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_itb_no (itb_no)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ======================================================
-- 5. UPLOADED DOCUMENTS TABLE
-- ======================================================
DROP TABLE IF EXISTS uploaded_documents;
CREATE TABLE uploaded_documents (
    id INT PRIMARY KEY AUTO_INCREMENT,
    original_filename VARCHAR(255) NOT NULL,
    stored_filename VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_type VARCHAR(50) NOT NULL,
    file_size INT NOT NULL,
    document_type ENUM('public_bidding', 'sealed_bidding', 'procurement_monitoring', 'other') NOT NULL,
    upload_status ENUM('pending', 'processing', 'extracted', 'reviewed', 'imported', 'failed') DEFAULT 'pending',
    extracted_data JSON,
    confidence_score DECIMAL(5,2) DEFAULT NULL,
    uploaded_by INT,
    bidding_id INT NULL,
    sealed_bidding_id INT NULL,
    procurement_id INT NULL,
    error_message TEXT,
    processed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_status (upload_status),
    INDEX idx_document_type (document_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ======================================================
-- 6. FIELD MAPPING TEMPLATES
-- ======================================================
DROP TABLE IF EXISTS field_mapping_templates;
CREATE TABLE field_mapping_templates (
    id INT PRIMARY KEY AUTO_INCREMENT,
    template_name VARCHAR(100) NOT NULL,
    document_type VARCHAR(50) NOT NULL,
    field_mappings JSON NOT NULL,
    is_default BOOLEAN DEFAULT FALSE,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_document_type (document_type),
    INDEX idx_is_default (is_default)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ======================================================
-- 7. EXTRACTION LOGS
-- ======================================================
DROP TABLE IF EXISTS extraction_logs;
CREATE TABLE extraction_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    document_id INT NOT NULL,
    extraction_method VARCHAR(50),
    confidence_score DECIMAL(5,2),
    extracted_fields JSON,
    processing_time_ms INT,
    status ENUM('success', 'partial', 'failed'),
    error_details TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_document_id (document_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ======================================================
-- 8. ACTIVITY LOGS
-- ======================================================
DROP TABLE IF EXISTS activity_logs;
CREATE TABLE activity_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    action VARCHAR(100) NOT NULL,
    entity_type VARCHAR(50),
    entity_id INT,
    details TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_action (action),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ======================================================
-- 9. BIDDING CATEGORIES
-- ======================================================
DROP TABLE IF EXISTS bidding_categories;
CREATE TABLE bidding_categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    category_name VARCHAR(100) NOT NULL,
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ======================================================
-- INSERT DEFAULT ADMIN USER
-- Password: Admin@123
-- ======================================================
INSERT INTO users (username, email, password_hash, full_name, role, status) VALUES
('admin', 'admin@fibeco.gov.ph', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 'admin', 'active');

-- ======================================================
-- INSERT FIELD MAPPING TEMPLATES
-- ======================================================
INSERT INTO field_mapping_templates (template_name, document_type, field_mappings, is_default) VALUES
('FIBECO Standard Public Bidding', 'public_bidding', 
 '{"bidding_date": "Bidding Date", "project_title": "Project Title", "fund_source": "Fund Source", "capex_project": "CAPEX Project", "approved_budget_contract": "Approved Budget", "winning_bidder": "Winning Bidder", "winning_bid_amount": "Winning Bid Amount", "status": "Status"}', 
 1);

INSERT INTO field_mapping_templates (template_name, document_type, field_mappings, is_default) VALUES
('FIBECO Standard Sealed Bidding', 'sealed_bidding',
 '{"bidding_date": "Bidding Date", "project_title": "Project Title", "fund_source": "Fund Source", "winning_bidder": "Winning Bidder", "winning_bid_amount": "Winning Bid Amount", "status": "Status"}',
 1);

INSERT INTO field_mapping_templates (template_name, document_type, field_mappings, is_default) VALUES
('FIBECO Standard Procurement', 'procurement_monitoring',
 '{"itb_no": "ITB No", "particulars": "Particulars", "abc": "ABC", "winning_bidder": "Winning Bidder", "winning_price": "Winning Price", "delivery_date_per_po": "Delivery Date", "actual_delivery_date": "Actual Delivery", "remarks": "Remarks"}',
 1);

-- ======================================================
-- INSERT BIDDING CATEGORIES
-- ======================================================
INSERT INTO bidding_categories (category_name, description, sort_order) VALUES
('Substation Equipment', 'Transformers, switchgears, protection systems', 1),
('Transmission Lines', 'Conductors, poles, hardware', 2),
('Distribution Lines', 'Distribution transformers, line materials', 3),
('Metering Systems', 'Meters, CTs, PTs, AMI systems', 4),
('Vehicles & Equipment', 'Service vehicles, heavy equipment', 5),
('Office Supplies & Services', 'General procurement', 6);

-- ======================================================
-- INSERT SAMPLE DEMO USER
-- Password: User@123
-- ======================================================
INSERT INTO users (username, email, password_hash, full_name, department, position, role, status) VALUES
('jdelacruz', 'juan.delacruz@fibeco.gov.ph', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Juan Dela Cruz', 'Procurement', 'Procurement Officer', 'user', 'active');

-- ======================================================
-- INSERT SAMPLE PUBLIC BIDDING RECORDS
-- ======================================================
INSERT INTO public_bidding (bidding_date, project_title, fund_source, capex_project, approved_budget_contract, winning_bidder, winning_bid_amount, status) VALUES
('2024-01-15', 'Supply and Delivery of 500 units Distribution Transformers', 'CAPEX Project', 'CAPEX-2024-001', 15000000.00, 'ABC Electric Corporation', 14250000.00, 'completed'),
('2024-02-10', 'Rehabilitation of 69kV Subtransmission Line', 'CAPEX Project', 'CAPEX-2024-002', 25000000.00, 'XYZ Construction Inc.', 23750000.00, 'ongoing'),
('2024-03-05', 'Procurement of AMI Metering System', 'RFSC', NULL, 8500000.00, 'MeterKing Solutions', 8075000.00, 'active'),
('2024-03-20', 'Supply of Steel Poles and Line Hardwares', 'General Fund', NULL, 5200000.00, 'SteelTech Industries', 4940000.00, 'completed');

-- ======================================================
-- INSERT SAMPLE SEALED BIDDING RECORDS
-- ======================================================
INSERT INTO sealed_bidding (bidding_date, project_title, fund_source, winning_bidder, winning_bid_amount, status) VALUES
('2024-01-20', 'Negotiated Procurement - 10 MVA Power Transformer', 'CAPEX Project', 'PowerTech Systems Inc.', 25000000.00, 'awarded'),
('2024-02-15', 'Supply and Delivery of SCADA System', 'RFSC', 'Automation Solutions Corp.', 18750000.00, 'active'),
('2024-03-10', 'Security Services for FIBECO Facilities', 'General Fund', 'SecureGuard Philippines', 3600000.00, 'awarded');

-- ======================================================
-- INSERT SAMPLE PROCUREMENT MONITORING RECORDS
-- ======================================================
INSERT INTO procurement_monitoring (itb_no, particulars, abc, winning_bidder, winning_price, delivery_date_per_po, actual_delivery_date) VALUES
('PB-2024-001', 'Supply of Office Supplies and Equipment', 350000.00, 'Office Depot Inc.', 332500.00, '2024-02-15', '2024-02-10'),
('PB-2024-002', 'Vehicle Maintenance Services', 280000.00, 'AutoCare Center', 266000.00, '2024-03-01', '2024-02-28'),
('NP-2024-001', 'IT Equipment and Peripherals', 450000.00, 'TechHub Solutions', 427500.00, '2024-03-20', NULL);

-- ======================================================
-- VERIFY ALL TABLES CREATED
-- ======================================================
SELECT 'Database setup complete!' AS status;
SELECT COUNT(*) as total_tables FROM information_schema.tables WHERE table_schema = DATABASE();