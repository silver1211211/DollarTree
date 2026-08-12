-- database/schema.sql - Complete database schema for Dollar Tree investment platform
-- WARNING: This is for educational purposes only. Do not use for real investment operations.

-- ============================================
-- USERS TABLE
-- Stores all user account information
-- ============================================
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    email VARCHAR(255) DEFAULT NULL UNIQUE,
    phone VARCHAR(20) DEFAULT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    auth_token VARCHAR(64) DEFAULT NULL,
    referrer_id INT DEFAULT NULL,
    referral_code VARCHAR(20) NOT NULL UNIQUE,
    
    -- Account status and type
    account_status ENUM('active', 'suspended', 'banned') DEFAULT 'active',
    is_admin BOOLEAN DEFAULT FALSE,
    is_advertiser BOOLEAN DEFAULT TRUE,
    
    -- Balances
    balance DECIMAL(20, 2) DEFAULT 0.00,
    commission_balance DECIMAL(20, 2) DEFAULT 0.00,
    total_deposited DECIMAL(20, 2) DEFAULT 0.00,
    total_withdrawn DECIMAL(20, 2) DEFAULT 0.00,
    
    -- SVIP Information
    svip_level INT DEFAULT 0,
    svip_unlock_amount DECIMAL(20, 2) DEFAULT 0.00,
    svip_activated_at DATETIME DEFAULT NULL,
    svip_expires_at DATETIME DEFAULT NULL,
    
    -- User preferences
    language VARCHAR(10) DEFAULT 'en',
    
    -- Tracking
    registration_method ENUM('email', 'phone', 'telegram') DEFAULT 'email',
    last_login_at DATETIME DEFAULT NULL,
    last_login_ip VARCHAR(45) DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Withdrawal limits
    daily_withdrawal_count INT DEFAULT 0,
    last_withdrawal_date DATE DEFAULT NULL,
    
    FOREIGN KEY (referrer_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_referrer (referrer_id),
    INDEX idx_email (email),
    INDEX idx_phone (phone),
    INDEX idx_referral_code (referral_code),
    INDEX idx_auth_token (auth_token)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- SVIP TIERS TABLE
-- Defines all SVIP levels and their benefits
-- ============================================
CREATE TABLE IF NOT EXISTS svip_tiers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    svip_level INT NOT NULL UNIQUE,
    unlock_amount DECIMAL(20, 2) NOT NULL,
    max_daily_profit DECIMAL(20, 2) NOT NULL,
    daily_tasks_limit INT DEFAULT 1,
    task_profit_per_completion DECIMAL(20, 2) NOT NULL,
    contract_duration_days INT DEFAULT 90,
    status ENUM('active', 'inactive') DEFAULT 'inactive',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_svip_level (svip_level)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- INSERT DEFAULT SVIP TIERS
-- ============================================
INSERT INTO svip_tiers (svip_level, unlock_amount, max_daily_profit, daily_tasks_limit, task_profit_per_completion) VALUES
(0, 0.00, 0.00, 0, 0.00),
(1, 16.00, 9.00, 1, 9.00),
(2, 66.00, 38.00, 1, 38.00),
(3, 166.00, 98.00, 1, 98.00),
(4, 366.00, 225.00, 1, 225.00),
(5, 777.00, 490.00, 1, 490.00),
(6, 1555.00, 1010.00, 1, 1010.00),
(7, 2666.00, 1813.00, 1, 1813.00),
(8, 5888.00, 4122.00, 1, 4122.00),
(9, 9999.00, 7199.00, 1, 7199.00),
(10, 20000.00, 14800.00, 1, 14800.00),
(11, 50000.00, 37500.00, 1, 37500.00),
(12, 80000.00, 60800.00, 1, 60800.00),
(13, 150000.00, 115500.00, 1, 115500.00),
(14, 200000.00, 156000.00, 1, 156000.00);

-- ============================================
-- DEPOSITS TABLE
-- Tracks all deposit transactions
-- ============================================
CREATE TABLE IF NOT EXISTS crypto_conversion_rates (
    symbol VARCHAR(16) PRIMARY KEY,
    usdt_rate DECIMAL(28,12) NOT NULL,
    source VARCHAR(32) NOT NULL DEFAULT 'oxapay',
    fetched_at DATETIME NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_rate_fetched_at (fetched_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS deposits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    deposit_address_id INT DEFAULT NULL,
    
    -- Transaction details
    amount DECIMAL(20, 2) NOT NULL,
    detected_amount DECIMAL(20, 2) DEFAULT NULL,
    currency VARCHAR(10) DEFAULT 'USDT',
    network VARCHAR(20) DEFAULT 'TRC20',
    
    -- Blockchain info
    transaction_hash VARCHAR(255) DEFAULT NULL,
    deposit_address VARCHAR(255) DEFAULT NULL,
    track_id VARCHAR(100) DEFAULT NULL,
    
    -- Status tracking
    status ENUM('pending', 'confirmed', 'completed', 'failed', 'expired') DEFAULT 'pending',
    
    -- Timestamps
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    confirmed_at DATETIME DEFAULT NULL,
    completed_at DATETIME DEFAULT NULL,
    
    -- Admin notes
    admin_notes TEXT DEFAULT NULL,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_status (status),
    INDEX idx_track_id (track_id),
    INDEX idx_transaction_hash (transaction_hash)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- USER DEPOSIT ADDRESSES TABLE
-- Permanent deposit addresses for users
-- ============================================
CREATE TABLE IF NOT EXISTS user_deposit_addresses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    network VARCHAR(20) NOT NULL,
    currency VARCHAR(10) DEFAULT 'USDT',
    address VARCHAR(255) NOT NULL,
    memo VARCHAR(100) DEFAULT NULL,
    track_id VARCHAR(100) DEFAULT NULL,
    status ENUM('active', 'inactive') DEFAULT 'inactive',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    last_used_at DATETIME DEFAULT NULL,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_network (user_id, network, currency),
    INDEX idx_user_id (user_id),
    INDEX idx_address (address)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- PENDING DEPOSITS TABLE (from your uploaded file)
-- ============================================
CREATE TABLE IF NOT EXISTS pending_deposits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    deposit_address_id INT DEFAULT NULL,
    track_id VARCHAR(100) DEFAULT NULL,
    amount DECIMAL(20, 2) DEFAULT 0.00,
    detected_amount DECIMAL(20, 2) DEFAULT NULL,
    currency VARCHAR(10) DEFAULT 'USDT',
    network VARCHAR(20) DEFAULT NULL,
    tx_hash VARCHAR(255) DEFAULT NULL,
    status ENUM('pending', 'completed', 'failed', 'expired') DEFAULT 'pending',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    completed_at DATETIME DEFAULT NULL,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_deposit_address_id (deposit_address_id),
    INDEX idx_status (status),
    INDEX idx_track_id (track_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- WITHDRAWALS TABLE
-- Tracks all withdrawal requests
-- ============================================
CREATE TABLE IF NOT EXISTS withdrawals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    
    -- Withdrawal details
    amount DECIMAL(20, 2) NOT NULL,
    withdrawal_fee DECIMAL(20, 2) DEFAULT 0.00,
    net_amount DECIMAL(20, 2) NOT NULL,
    currency VARCHAR(10) DEFAULT 'USDT',
    network VARCHAR(20) DEFAULT 'TRC20',
    
    -- Destination
    destination_address VARCHAR(255) NOT NULL,
    
    -- Blockchain info
    transaction_hash VARCHAR(255) DEFAULT NULL,
    
    -- Status tracking
    status ENUM('pending', 'processing', 'approved', 'completed', 'rejected', 'cancelled') DEFAULT 'pending',
    
    -- Timestamps
    requested_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    processed_at DATETIME DEFAULT NULL,
    completed_at DATETIME DEFAULT NULL,
    
    -- Admin handling
    processed_by_admin_id INT DEFAULT NULL,
    admin_notes TEXT DEFAULT NULL,
    rejection_reason VARCHAR(255) DEFAULT NULL,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_status (status),
    INDEX idx_requested_at (requested_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- DAILY TASKS TABLE
-- Tracks user daily task completion
-- ============================================
CREATE TABLE IF NOT EXISTS daily_tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    task_date DATE NOT NULL,
    svip_level INT NOT NULL,
    
    -- Task limits
    tasks_available INT DEFAULT 1,
    tasks_completed INT DEFAULT 0,
    
    -- Earnings tracking
    earnings_today DECIMAL(20, 2) DEFAULT 0.00,
    max_daily_earnings DECIMAL(20, 2) NOT NULL,
    
    -- Reset tracking
    reset_time DATETIME NOT NULL,
    
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_date (user_id, task_date),
    INDEX idx_user_id (user_id),
    INDEX idx_task_date (task_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- TASK COMPLETIONS TABLE
-- Individual task completion records
-- ============================================
CREATE TABLE IF NOT EXISTS task_completions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    daily_task_id INT NOT NULL,
    
    -- Task details
    task_type VARCHAR(50) DEFAULT 'DollarTree items',
    earnings DECIMAL(20, 2) NOT NULL,
    
    completed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (daily_task_id) REFERENCES daily_tasks(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_completed_at (completed_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- COMMISSIONS TABLE
-- Tracks referral commissions
-- ============================================
CREATE TABLE IF NOT EXISTS commissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    referrer_user_id INT NOT NULL,
    referred_user_id INT NOT NULL,
    
    -- Commission details
    commission_level INT NOT NULL, -- 1, 2, or 3
    commission_rate DECIMAL(5, 4) NOT NULL, -- 0.14, 0.02, 0.01
    
    -- Source transaction
    source_type ENUM('deposit', 'task_earning') DEFAULT 'deposit',
    source_amount DECIMAL(20, 2) NOT NULL,
    commission_amount DECIMAL(20, 2) NOT NULL,
    
    -- Status
    status ENUM('pending', 'paid', 'cancelled') DEFAULT 'paid',
    
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    paid_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (referrer_user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (referred_user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_referrer (referrer_user_id),
    INDEX idx_referred (referred_user_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- REFERRAL SETTINGS TABLE
-- Commission rates configuration
-- ============================================
CREATE TABLE IF NOT EXISTS referral_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    level INT NOT NULL UNIQUE,
    commission_rate DECIMAL(5, 4) NOT NULL,
    status ENUM('active', 'inactive') DEFAULT 'inactive',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default referral rates
INSERT INTO referral_settings (level, commission_rate) VALUES
(1, 0.14), -- 14%
(2, 0.02), -- 2%
(3, 0.01); -- 1%

-- ============================================
-- ADMIN SETTINGS TABLE
-- Platform configuration
-- ============================================
CREATE TABLE IF NOT EXISTS admin_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT,
    setting_description VARCHAR(255) DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_setting_key (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default settings
INSERT INTO admin_settings (setting_key, setting_value, setting_description) VALUES
('platform_name', 'DollarTree', 'Platform name'),
('oxapay_api_key', '', 'OxaPay merchant API key'),
('oxapay_enabled', '0', 'Enable/disable cryptocurrency deposits'),
('min_deposit_amount', '2', 'Minimum deposit amount in USDT'),
('min_withdrawal_amount', '9', 'Minimum withdrawal amount in USDT'),
('withdrawal_fee_percentage', '0', 'Withdrawal fee percentage'),
('daily_withdrawal_limit', '1', 'Maximum withdrawals per day per user'),
('auto_approve_withdrawals', '0', 'Auto-approve withdrawals (1) or manual (0)'),
('withdrawal_processing_time', '24', 'Withdrawal processing time in hours'),
('platform_announcement', 'Welcome to DollarTree investment platform!', 'Platform-wide announcement'),
('registration_bonus', '0', 'Bonus amount for new registrations');

-- ============================================
-- ANNOUNCEMENTS TABLE
-- Platform announcements and news
-- ============================================
CREATE TABLE IF NOT EXISTS announcements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    announcement_type ENUM('info', 'warning', 'success', 'danger', 'daily_modal') DEFAULT 'info',
    link_url VARCHAR(500) DEFAULT NULL,
    link_text VARCHAR(200) DEFAULT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_by_admin_id INT DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_is_active (is_active),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- USER MESSAGES TABLE
-- Non-financial notifications sent by administrators
-- ============================================
CREATE TABLE IF NOT EXISTS user_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,
    admin_id INT DEFAULT NULL,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    msg_type ENUM('all', 'svip', 'specific') DEFAULT 'specific',
    svip_level INT DEFAULT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user_read (user_id, is_read),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- ACTIVITY LOGS TABLE
-- System activity tracking
-- ============================================
CREATE TABLE IF NOT EXISTS activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,
    admin_id INT DEFAULT NULL,
    activity_type VARCHAR(50) NOT NULL,
    description TEXT,
    ip_address VARCHAR(45) DEFAULT NULL,
    user_agent VARCHAR(255) DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_activity_type (activity_type),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- VIEWS FOR REPORTING
-- ============================================

-- User statistics view
CREATE OR REPLACE VIEW user_statistics AS
SELECT 
    u.id,
    u.username,
    u.email,
    u.svip_level,
    u.balance,
    u.commission_balance,
    u.total_deposited,
    u.total_withdrawn,
    COUNT(DISTINCT r1.id) as direct_referrals,
    COUNT(DISTINCT r2.id) as level_2_referrals,
    COUNT(DISTINCT r3.id) as level_3_referrals,
    COALESCE(SUM(c.commission_amount), 0) as total_commissions_earned
FROM users u
LEFT JOIN users r1 ON r1.referrer_id = u.id
LEFT JOIN users r2 ON r2.referrer_id = r1.id
LEFT JOIN users r3 ON r3.referrer_id = r2.id
LEFT JOIN commissions c ON c.referrer_user_id = u.id
WHERE u.is_admin = FALSE
GROUP BY u.id;

-- ============================================
-- INDEXES FOR PERFORMANCE
-- ============================================
CREATE INDEX idx_users_created_at ON users(created_at);
CREATE INDEX idx_deposits_created_at ON deposits(created_at);
