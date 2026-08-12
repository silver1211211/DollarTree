# 🌳 Dollar Tree Investment Platform - EDUCATIONAL PROJECT

**⚠️ CRITICAL WARNING: This is an EDUCATIONAL project only**

This platform replicates the functionality of a cryptocurrency investment scheme for educational and research purposes. DO NOT use this for real investments or deploy it for actual use. Such schemes are often illegal and fraudulent.

---

## 📋 TABLE OF CONTENTS
1. [System Overview](#system-overview)
2. [Complete User Journey](#complete-user-journey)
3. [Developer Technical Breakdown](#developer-technical-breakdown)
4. [Database Schema](#database-schema)
5. [API Endpoints](#api-endpoints)
6. [Installation Instructions](#installation-instructions)
7. [Admin Panel](#admin-panel)
8. [Security Considerations](#security-considerations)

---

## 🎯 SYSTEM OVERVIEW

This platform simulates an investment system with the following features:
- Multi-method registration (Email, Phone)
- Cryptocurrency deposits via OxaPay integration
- SVIP tier system with 15 levels
- Daily tasks with automatic reset
- 3-level referral commission system
- Withdrawal requests with admin approval
- 24-hour withdrawal processing time
- Multi-language support
- Admin dashboard

---

## 👤 COMPLETE USER JOURNEY: TOM GREGORY EXAMPLE

### **PHASE 1: REGISTRATION** ✅

#### 👨‍💼 Investor Standpoint (Tom's Experience):

1. Tom visits: `https://yoursite.com/dollar-tree-platform/user/register.php?ref=wjgkmm`
2. Tom sees registration form with tabs:
   - "Register by Email" (selected)
   - "Register by phone"
3. Tom fills in:
   - Email: `tom.gregory@email.com`
   - Password: `TomSecure123`
   - Confirm Password: `TomSecure123`
   - Invitation Code: `wjgkmm` (pre-filled)
4. Tom clicks "Sign Up"
5. Success message appears
6. Tom is redirected to login page

#### 🔧 Developer Standpoint (Backend Process):

```sql
-- Step 1: Validate email doesn't exist
SELECT COUNT(*) FROM users WHERE email = 'tom.gregory@email.com';

-- Step 2: Find referrer by code
SELECT id FROM users WHERE referral_code = 'wjgkmm'; 
-- Returns: referrer_id = 42

-- Step 3: Generate Tom's unique referral code
-- Function generates random code, checks uniqueness
-- Generated: 'ABC123'

-- Step 4: Hash password
-- Uses bcrypt: password_hash('TomSecure123', PASSWORD_BCRYPT)
-- Result: $2y$10$92IXUNpkjO0rOQ5byMi...

-- Step 5: Generate auth token
-- Random 64-character string
-- Result: 'a7f3d8e9c2b1a0f9e8d7c6b5a4f3e2d1c0b9a8f7e6d5c4b3a2f1e0d9c8b7a6f5'

-- Step 6: INSERT new user
INSERT INTO users (
    username, email, password_hash, auth_token, 
    referrer_id, referral_code, registration_method, 
    balance, svip_level, created_at, last_login_ip
) VALUES (
    'tom.gregory@email.com',
    'tom.gregory@email.com',
    '$2y$10$92IXUNpkjO0rOQ5byMi...',
    'a7f3d8e9c2b1a0f9e8d7c6b5a4f3e2d1c0b9a8f7e6d5c4b3a2f1e0d9c8b7a6f5',
    42,  -- referrer_id
    'ABC123',  -- Tom's new referral code
    'email',
    0.00,  -- initial balance
    0,  -- SVIP level 0
    NOW(),
    '192.168.1.100'
);
-- New user_id: 157

-- Step 7: Log activity
INSERT INTO activity_logs (user_id, activity_type, description, ip_address)
VALUES (157, 'registration', 'New user registered via email', '192.168.1.100');

-- Step 8: Log referral relationship (for referrer)
INSERT INTO activity_logs (user_id, activity_type, description)
VALUES (42, 'new_referral', 'New Level 1 referral: User ID 157');
```

**Database State After Registration:**
```
users table:
+----+-------------------------+----------+-------------+--------+--------------+
| id | email                   | referrer | referral    | svip   | balance      |
|    |                         | _id      | _code       | _level |              |
+----+-------------------------+----------+-------------+--------+--------------+
| 42 | mary@email.com          | NULL     | wjgkmm      | 2      | 150.00       |
| 157| tom.gregory@email.com   | 42       | ABC123      | 0      | 0.00         |
+----+-------------------------+----------+-------------+--------+--------------+
```

---

### **PHASE 2: LOGIN** ✅

#### 👨‍💼 Investor Standpoint:

1. Tom visits login page
2. Selects "Email Login" tab
3. Enters email and password
4. Clicks "Sign In"
5. Dashboard loads

#### 🔧 Developer Standpoint:

```sql
-- Step 1: Find user by email
SELECT * FROM users WHERE email = 'tom.gregory@email.com';

-- Step 2: Verify password
-- password_verify('TomSecure123', '$2y$10$92IXUNpkjO0rOQ5byMi...')
-- Returns: TRUE

-- Step 3: Generate new auth token
-- New token: 'b8f4e9d0c3b2a1f0e9d8c7b6a5f4e3d2c1b0a9f8e7d6c5b4a3f2e1d0c9b8a7f6'

-- Step 4: Update user record
UPDATE users 
SET auth_token = 'b8f4e9d0c3b2a1f0e9d8c7b6a5f4e3d2c1b0a9f8e7d6c5b4a3f2e1d0c9b8a7f6',
    last_login_at = NOW(),
    last_login_ip = '192.168.1.100'
WHERE id = 157;

-- Step 5: Reset daily withdrawal count if new day
UPDATE users 
SET daily_withdrawal_count = 0, 
    last_withdrawal_date = '2026-02-14' 
WHERE id = 157 AND (last_withdrawal_date IS NULL OR last_withdrawal_date < '2026-02-14');

-- Step 6: Get SVIP tier info
SELECT * FROM svip_tiers WHERE svip_level = 0;
```

**Response sent to Tom:**
```json
{
    "success": true,
    "data": {
        "user_id": 157,
        "username": "tom.gregory@email.com",
        "email": "tom.gregory@email.com",
        "auth_token": "b8f4e9d0c3b2a1f0e9d8c7b6a5f4e3d2...",
        "balance": 0.00,
        "commission_balance": 0.00,
        "svip_level": 0,
        "referral_code": "ABC123"
    }
}
```

---

### **PHASE 3: FIRST DEPOSIT (UNLOCK SVIP 2)** 💰

#### 👨‍💼 Investor Standpoint:

1. Tom clicks "Recharge" button on dashboard
2. Sees network selection: TRC20-USDT, BEP20-USDT, etc.
3. Selects "TRC20-USDT"
4. Page displays:
   - QR code
   - Deposit address: `THLNjR2WqFQ3NAGMWStm235P6y5nUPeAJ7`
   - "Copy" button
   - Minimum deposit: 2 USDT
5. Tom opens his wallet app
6. Tom sends 66 USDT to the address
7. Tom waits 1-3 minutes
8. Tom sees notification: "Deposit completed!"
9. Tom's SVIP level updates to "Svip 2"
10. Tom's balance shows 66.00 USDT

#### 🔧 Developer Standpoint:

```sql
-- ===== STEP 1: Get Deposit Address Request =====
-- API: /api/get_deposit_address.php

-- Check if user already has address for TRC20
SELECT * FROM user_deposit_addresses 
WHERE user_id = 157 AND network = 'TRON' AND currency = 'USDT';
-- Result: No existing address

-- Call OxaPay API to create permanent address
POST https://api.oxapay.com/v1/payment/static-address
Headers:
    merchant_api_key: YOUR_OXAPAY_KEY
Body:
{
    "network": "TRON",
    "to_currency": "USDT",
    "callback_url": "https://yoursite.com/api/deposit_callback.php",
    "order_id": "USER_157_TRON_1708012345",
    "email": "tom.gregory@email.com"
}

-- OxaPay Response:
{
    "data": {
        "address": "THLNjR2WqFQ3NAGMWStm235P6y5nUPeAJ7",
        "track_id": "OXA-123456789",
        "network": "TRON"
    }
}

-- Save address to database
INSERT INTO user_deposit_addresses (
    user_id, network, currency, address, track_id, 
    status, created_at, last_used_at
) VALUES (
    157,
    'TRON',
    'USDT',
    'THLNjR2WqFQ3NAGMWStm235P6y5nUPeAJ7',
    'OXA-123456789',
    'active',
    NOW(),
    NOW()
);
-- New address_id: 89

-- ===== STEP 2: Tom Sends 66 USDT =====
-- Tom sends transaction on blockchain
-- TX Hash: 0xabc123def456...
-- Amount: 66 USDT
-- To: THLNjR2WqFQ3NAGMWStm235P6y5nUPeAJ7

-- ===== STEP 3: OxaPay Detects Deposit =====
-- OxaPay monitors blockchain
-- Detects incoming 66 USDT to address
-- Sends webhook callback to our server

-- ===== STEP 4: Callback Received =====
-- API: /api/deposit_callback.php

-- Callback data received:
{
    "track_id": "OXA-123456789",
    "order_id": "USER_157_TRON_1708012345",
    "status": "Paid",
    "amount": 66,
    "currency": "USDT",
    "tx_hash": "0xabc123def456...",
    "network": "TRON",
    "address": "THLNjR2WqFQ3NAGMWStm235P6y5nUPeAJ7"
}

-- Find deposit address
SELECT * FROM user_deposit_addresses 
WHERE track_id = 'OXA-123456789';
-- Result: address_id = 89, user_id = 157

-- Check if already processed
SELECT * FROM pending_deposits 
WHERE track_id = 'OXA-123456789';
-- Result: No existing record

-- Create deposit record
INSERT INTO pending_deposits (
    user_id, deposit_address_id, track_id, 
    amount, detected_amount, currency, 
    tx_hash, status, created_at, completed_at
) VALUES (
    157,
    89,
    'OXA-123456789',
    66.00,
    66.00,
    'USDT',
    '0xabc123def456...',
    'completed',
    NOW(),
    NOW()
);
-- New deposit_id: 234

-- ===== STEP 5: Update User Balance =====
-- START TRANSACTION

UPDATE users 
SET balance = balance + 66.00,
    total_deposited = total_deposited + 66.00
WHERE id = 157;

-- Balance: 0.00 + 66.00 = 66.00
-- Total deposited: 0.00 + 66.00 = 66.00

-- ===== STEP 6: Calculate New SVIP Level =====
-- New total deposited: 66.00
SELECT svip_level FROM svip_tiers 
WHERE unlock_amount <= 66.00 
AND status = 'active'
ORDER BY unlock_amount DESC 
LIMIT 1;
-- Result: svip_level = 2 (unlock_amount = 66.00)

-- Get SVIP tier details
SELECT * FROM svip_tiers WHERE svip_level = 2;
-- Result:
{
    "svip_level": 2,
    "unlock_amount": 66.00,
    "max_daily_profit": 38.00,
    "daily_tasks_limit": 1,
    "task_profit_per_completion": 38.00,
    "contract_duration_days": 90
}

-- Update user's SVIP level
UPDATE users 
SET svip_level = 2,
    svip_unlock_amount = 66.00,
    svip_activated_at = NOW(),
    svip_expires_at = DATE_ADD(NOW(), INTERVAL 90 DAY)
WHERE id = 157;

-- ===== STEP 7: Distribute Referral Commissions =====
-- Tom's upline:
-- Level 1: User 42 (Mary - referral code wjgkmm)
-- Level 2: User 15 (Mary's referrer)
-- Level 3: User 3 (Level 2's referrer)

-- Get commission rates
SELECT level, commission_rate FROM referral_settings WHERE status = 'active';
-- Level 1: 0.14 (14%)
-- Level 2: 0.02 (2%)
-- Level 3: 0.01 (1%)

-- === Level 1 Commission (Mary - User 42) ===
-- Commission: 66.00 * 0.14 = 9.24 USDT

UPDATE users 
SET commission_balance = commission_balance + 9.24 
WHERE id = 42;

INSERT INTO commissions (
    referrer_user_id, referred_user_id, commission_level, 
    commission_rate, source_type, source_amount, 
    commission_amount, status, paid_at
) VALUES (
    42,  -- Mary
    157,  -- Tom
    1,
    0.14,
    'deposit',
    66.00,
    9.24,
    'paid',
    NOW()
);

-- === Level 2 Commission (User 15) ===
-- Commission: 66.00 * 0.02 = 1.32 USDT

UPDATE users 
SET commission_balance = commission_balance + 1.32 
WHERE id = 15;

INSERT INTO commissions (
    referrer_user_id, referred_user_id, commission_level, 
    commission_rate, source_type, source_amount, 
    commission_amount, status, paid_at
) VALUES (
    15,
    157,  -- Tom
    2,
    0.02,
    'deposit',
    66.00,
    1.32,
    'paid',
    NOW()
);

-- === Level 3 Commission (User 3) ===
-- Commission: 66.00 * 0.01 = 0.66 USDT

UPDATE users 
SET commission_balance = commission_balance + 0.66 
WHERE id = 3;

INSERT INTO commissions (
    referrer_user_id, referred_user_id, commission_level, 
    commission_rate, source_type, source_amount, 
    commission_amount, status, paid_at
) VALUES (
    3,
    157,  -- Tom
    3,
    0.01,
    'deposit',
    66.00,
    0.66,
    'paid',
    NOW()
);

-- ===== STEP 8: Log Activities =====
INSERT INTO activity_logs (user_id, activity_type, description)
VALUES (157, 'deposit_completed', 'Deposit completed: 66 USDT');

INSERT INTO activity_logs (user_id, activity_type, description)
VALUES (157, 'svip_upgrade', 'SVIP level upgraded to 2');

-- COMMIT TRANSACTION
```

**Database State After Deposit:**
```
users table (Tom):
+----+-------------------------+----------+--------+--------------+------------------+
| id | email                   | svip     | balance| commission   | total_deposited  |
|    |                         | _level   |        | _balance     |                  |
+----+-------------------------+----------+--------+--------------+------------------+
| 157| tom.gregory@email.com   | 2        | 66.00  | 0.00         | 66.00            |
+----+-------------------------+----------+--------+--------------+------------------+

users table (Mary - Referrer):
+----+-------------------------+----------+--------+--------------+------------------+
| id | email                   | svip     | balance| commission   | total_deposited  |
|    |                         | _level   |        | _balance     |                  |
+----+-------------------------+----------+--------+--------------+------------------+
| 42 | mary@email.com          | 2        | 150.00 | 9.24         | 66.00            |
+----+-------------------------+----------+--------+--------------+------------------+

pending_deposits table:
+----+---------+---------------------+--------------+----------+--------+----------+
| id | user_id | track_id            | amount       | detected | status | tx_hash  |
|    |         |                     |              | _amount  |        |          |
+----+---------+---------------------+--------------+----------+--------+----------+
| 234| 157     | OXA-123456789       | 66.00        | 66.00    |completed| 0xabc.. |
+----+---------+---------------------+--------------+----------+--------+----------+

commissions table:
+----+----------+----------+-------+-------+--------+---------+----------+
| id | referrer | referred | level | rate  | source | source  | commission |
|    | _user_id | _user_id |       |       | _type  | _amount | _amount    |
+----+----------+----------+-------+-------+--------+---------+----------+
| 89 | 42       | 157      | 1     | 0.14  | deposit| 66.00   | 9.24     |
| 90 | 15       | 157      | 2     | 0.02  | deposit| 66.00   | 1.32     |
| 91 | 3        | 157      | 3     | 0.01  | deposit| 66.00   | 0.66     |
+----+----------+----------+-------+-------+--------+---------+----------+
```

---

### **PHASE 4: DAILY TASKS** 🎯

#### 👨‍💼 Investor Standpoint:

1. Tom navigates to "Task" tab
2. Sees:
   - "Task reset: 08:12:30" (countdown timer)
   - "All tasks for today: 1"
   - "Today's remaining tasks: 1"
3. Tom sees one task card:
   - Image: DollarTree items (trash can icon)
   - Price: 0.00 USDT
   - Income: 38.00 USDT (based on SVIP 2)
   - "Unlock now" button (green)
4. Tom clicks "Unlock now"
5. Success message: "Task completed! +38 USDT"
6. Balance updates: 66.00 → 104.00 USDT
7. Task card now shows "Completed" ✓
8. "Today's remaining tasks: 0"
9. Tomorrow at 00:00:00, tasks reset

#### 🔧 Developer Standpoint:

```sql
-- ===== STEP 1: Get Daily Tasks Request =====
-- API: /api/get_daily_tasks.php or /user/tasks.php

-- Get user's SVIP tier
SELECT * FROM svip_tiers WHERE svip_level = 2;
-- Result:
{
    "daily_tasks_limit": 1,
    "task_profit_per_completion": 38.00,
    "max_daily_profit": 38.00
}

-- Get or create today's task record
SELECT * FROM daily_tasks 
WHERE user_id = 157 AND task_date = '2026-02-14';
-- Result: No record for today

-- Create task record for today
-- Reset time: tomorrow at 00:00:00 = '2026-02-15 00:00:00'
INSERT INTO daily_tasks (
    user_id, task_date, svip_level, tasks_available, 
    tasks_completed, earnings_today, max_daily_earnings, reset_time
) VALUES (
    157,
    '2026-02-14',
    2,
    1,  -- based on SVIP 2
    0,
    0.00,
    38.00,  -- max daily profit for SVIP 2
    '2026-02-15 00:00:00'
);
-- New daily_task_id: 445

-- ===== STEP 2: Tom Clicks "Unlock now" =====
-- API: /api/complete_task.php

-- Validate: Check tasks available
SELECT * FROM daily_tasks WHERE id = 445;
-- tasks_completed (0) < tasks_available (1) ✓
-- earnings_today (0.00) < max_daily_earnings (38.00) ✓

-- START TRANSACTION

-- Update task record
UPDATE daily_tasks 
SET tasks_completed = tasks_completed + 1,
    earnings_today = earnings_today + 38.00
WHERE id = 445;

-- Update user balance
UPDATE users 
SET balance = balance + 38.00 
WHERE id = 157;
-- Balance: 66.00 + 38.00 = 104.00

-- Record task completion
INSERT INTO task_completions (
    user_id, daily_task_id, task_type, earnings, completed_at
) VALUES (
    157,
    445,
    'DollarTree items',
    38.00,
    NOW()
);
-- New completion_id: 789

-- ===== STEP 3: Distribute Referral Commissions on Task Earnings =====
-- Same upline as deposit

-- Level 1 (Mary - User 42): 38.00 * 0.14 = 5.32 USDT
UPDATE users SET commission_balance = commission_balance + 5.32 WHERE id = 42;

INSERT INTO commissions (
    referrer_user_id, referred_user_id, commission_level, 
    commission_rate, source_type, source_amount, commission_amount
) VALUES (42, 157, 1, 0.14, 'task_earning', 38.00, 5.32);

-- Level 2 (User 15): 38.00 * 0.02 = 0.76 USDT
UPDATE users SET commission_balance = commission_balance + 0.76 WHERE id = 15;

INSERT INTO commissions (
    referrer_user_id, referred_user_id, commission_level, 
    commission_rate, source_type, source_amount, commission_amount
) VALUES (15, 157, 2, 0.02, 'task_earning', 38.00, 0.76);

-- Level 3 (User 3): 38.00 * 0.01 = 0.38 USDT
UPDATE users SET commission_balance = commission_balance + 0.38 WHERE id = 3;

INSERT INTO commissions (
    referrer_user_id, referred_user_id, commission_level, 
    commission_rate, source_type, source_amount, commission_amount
) VALUES (3, 157, 3, 0.01, 'task_earning', 38.00, 0.38);

-- Log activity
INSERT INTO activity_logs (user_id, activity_type, description)
VALUES (157, 'task_completed', 'Task completed: Earned 38.00 USDT');

-- COMMIT TRANSACTION

-- ===== STEP 4: Calculate Time Until Reset =====
-- Reset time: 2026-02-15 00:00:00
-- Current time: 2026-02-14 15:47:30
-- Difference: 8 hours, 12 minutes, 30 seconds
```

**Database State After Task:**
```
users table (Tom):
+----+-------------------------+----------+--------+--------------+
| id | email                   | svip     | balance| commission   |
|    |                         | _level   |        | _balance     |
+----+-------------------------+----------+--------+--------------+
| 157| tom.gregory@email.com   | 2        | 104.00 | 0.00         |
+----+-------------------------+----------+--------+--------------+

daily_tasks table:
+----+---------+------------+-----------+----------+----------+------------+---------+
| id | user_id | task_date  | svip_level| tasks    | tasks    | earnings   | max     |
|    |         |            |           | available| completed| _today     | _daily  |
+----+---------+------------+-----------+----------+----------+------------+---------+
| 445| 157     | 2026-02-14 | 2         | 1        | 1        | 38.00      | 38.00   |
+----+---------+------------+-----------+----------+----------+------------+---------+

task_completions table:
+----+---------+---------------+--------------+----------+---------------------+
| id | user_id | daily_task_id | task_type    | earnings | completed_at        |
+----+---------+---------------+--------------+----------+---------------------+
| 789| 157     | 445           | DollarTree   | 38.00    | 2026-02-14 15:47:30 |
|    |         |               | items        |          |                     |
+----+---------+---------------+--------------+----------+---------------------+
```

**AUTOMATIC TASK RESET** ⏰

At midnight (00:00:00 on 2026-02-15), the system automatically makes new tasks available:

```sql
-- CRON JOB runs at 00:00:00 daily
-- OR checked when user accesses tasks page

-- When Tom accesses tasks on 2026-02-15:
SELECT * FROM daily_tasks 
WHERE user_id = 157 AND task_date = '2026-02-15';
-- Result: No record

-- New task record created:
INSERT INTO daily_tasks (
    user_id, task_date, svip_level, tasks_available, 
    tasks_completed, earnings_today, max_daily_earnings, reset_time
) VALUES (
    157,
    '2026-02-15',
    2,
    1,
    0,
    0.00,
    38.00,
    '2026-02-16 00:00:00'  -- Next day
);

-- Tom can complete task again!
```

---

### **PHASE 5: WITHDRAWAL** 💸

#### 👨‍💼 Investor Standpoint:

1. Tom clicks "Withdraw" on dashboard
2. Tom sees withdrawal form:
   - Current balance: 104.00 USDT
   - Network selection: TRC20-USDT (selected)
   - Withdrawal Address field
   - Amount field
   - "Submit Withdrawal" button
3. Tom enters:
   - Address: `TXyz9876543210AbCdEfGhIjKlMnOpQrStUv`
   - Amount: `50`
4. Tom clicks "Submit Withdrawal"
5. Success message: "Withdrawal request submitted! Processing time: up to 24 hours"
6. Tom's balance updates: 104.00 → 54.00 USDT
7. Tom sees withdrawal in "Pending" status
8. **24 hours later (maximum)**, admin approves
9. Admin sends 50 USDT to Tom's address
10. Withdrawal status changes to "Completed"

#### 🔧 Developer Standpoint:

```sql
-- ===== STEP 1: Submit Withdrawal Request =====
-- API: /api/submit_withdrawal.php

-- Validate withdrawal amount
-- Minimum: 9 USDT (from settings)
-- Tom's request: 50 USDT ✓

-- Validate address
-- Length > 20 characters ✓

-- Check balance
SELECT balance, commission_balance FROM users WHERE id = 157;
-- Result: balance = 104.00, commission_balance = 0.00
-- Total available: 104.00
-- Requested: 50.00 ✓

-- Check daily withdrawal limit
SELECT daily_withdrawal_count, last_withdrawal_date FROM users WHERE id = 157;
-- Result: count = 0, date = NULL
-- Limit: 1 per day
-- Can withdraw ✓

-- Calculate fees
-- Fee percentage: 0% (from settings)
-- Withdrawal fee: 50.00 * 0 = 0.00
-- Net amount: 50.00

-- START TRANSACTION

-- Deduct from balance
UPDATE users 
SET balance = balance - 50.00,
    daily_withdrawal_count = daily_withdrawal_count + 1,
    last_withdrawal_date = '2026-02-14'
WHERE id = 157;
-- Balance: 104.00 - 50.00 = 54.00

-- Create withdrawal record
INSERT INTO withdrawals (
    user_id, amount, withdrawal_fee, net_amount, 
    currency, network, destination_address, 
    status, requested_at
) VALUES (
    157,
    50.00,
    0.00,
    50.00,
    'USDT',
    'TRC20',
    'TXyz9876543210AbCdEfGhIjKlMnOpQrStUv',
    'pending',
    NOW()
);
-- New withdrawal_id: 67

-- Log activity
INSERT INTO activity_logs (user_id, activity_type, description)
VALUES (157, 'withdrawal_request', 'Withdrawal requested: 50 USDT to TXyz9876...');

-- COMMIT TRANSACTION
```

**Database State After Withdrawal Request:**
```
users table (Tom):
+----+-------------------------+----------+--------+--------------+-------------------------+
| id | email                   | svip     | balance| daily        | last_withdrawal_date    |
|    |                         | _level   |        | _withdrawal  |                         |
|    |                         |          |        | _count       |                         |
+----+-------------------------+----------+--------+--------------+-------------------------+
| 157| tom.gregory@email.com   | 2        | 54.00  | 1            | 2026-02-14              |
+----+-------------------------+----------+--------+--------------+-------------------------+

withdrawals table:
+----+---------+--------+------+----------+----------+-------+-----------+------------------------+------------+
| id | user_id | amount | fee  | net      | currency | network| destination| status    | requested_at           |
|    |         |        |      | _amount  |          |        | _address   |           |                        |
+----+---------+--------+------+----------+----------+--------+-----------+-----------+------------------------+
| 67 | 157     | 50.00  | 0.00 | 50.00    | USDT     | TRC20  | TXyz9876...| pending   | 2026-02-14 15:50:00    |
+----+---------+--------+------+----------+----------+--------+-----------+-----------+------------------------+
```

**ADMIN APPROVAL PROCESS** 👨‍💼

```sql
-- ===== Admin Reviews Withdrawal (Next Day) =====
-- Admin visits: /admin/withdrawals.php

-- Admin sees pending withdrawal:
SELECT w.*, u.username, u.email 
FROM withdrawals w
JOIN users u ON w.user_id = u.id
WHERE w.status = 'pending'
ORDER BY w.requested_at ASC;

-- Admin checks:
-- 1. User account is legitimate ✓
-- 2. No suspicious activity ✓
-- 3. Amount is reasonable ✓

-- Admin clicks "Approve"
UPDATE withdrawals 
SET status = 'approved',
    processed_at = NOW(),
    processed_by_admin_id = 1
WHERE id = 67;

-- Admin manually sends 50 USDT to TXyz9876... using their wallet
-- TX Hash: 0xdef789ghi012...

-- Admin enters TX hash
UPDATE withdrawals 
SET status = 'completed',
    transaction_hash = '0xdef789ghi012...',
    completed_at = NOW()
WHERE id = 67;

-- Update user's total withdrawn
UPDATE users 
SET total_withdrawn = total_withdrawn + 50.00 
WHERE id = 157;

-- Log activity
INSERT INTO activity_logs (
    user_id, admin_id, activity_type, description
) VALUES (
    157, 1, 'withdrawal_completed', 
    'Withdrawal completed: 50 USDT, TX: 0xdef789ghi012...'
);
```

**Final Database State:**
```
withdrawals table:
+----+---------+--------+------+----------+-----------+-----------+-----------------------+---------------------+
| id | user_id | amount | fee  | net      | status    | tx_hash   | requested_at          | completed_at        |
+----+---------+--------+------+----------+-----------+-----------+-----------------------+---------------------+
| 67 | 157     | 50.00  | 0.00 | 50.00    | completed | 0xdef789..| 2026-02-14 15:50:00   | 2026-02-15 10:30:00 |
+----+---------+--------+------+----------+-----------+-----------+-----------------------+---------------------+

users table (Tom - Final):
+----+-------------------------+----------+--------+----------+------------------+------------------+
| id | email                   | svip     | balance| commission| total_deposited  | total_withdrawn  |
|    |                         | _level   |        | _balance  |                  |                  |
+----+-------------------------+----------+--------+----------+------------------+------------------+
| 157| tom.gregory@email.com   | 2        | 54.00  | 0.00     | 66.00            | 50.00            |
+----+-------------------------+----------+--------+----------+------------------+------------------+
```

---

## 🔧 ADDITIONAL FEATURES

### **Language Change**

```sql
-- User changes language to Spanish
UPDATE users SET language = 'es' WHERE id = 157;

-- System loads /languages/es.php for all text
```

### **Password Change**

```sql
-- API: /api/change_password.php
-- User provides old password and new password

-- Verify old password
SELECT password_hash FROM users WHERE id = 157;
-- password_verify('TomSecure123', hash) ✓

-- Hash new password
-- new_hash = password_hash('NewPassword456', PASSWORD_BCRYPT)

UPDATE users 
SET password_hash = 'new_hash'
WHERE id = 157;
```

---

## 📊 COMPLETE TECHNICAL SUMMARY

**Total Tables Used:** 14

1. **users** - User accounts
2. **svip_tiers** - VIP level configurations
3. **user_deposit_addresses** - Permanent deposit addresses
4. **pending_deposits** - Deposit transactions
5. **deposits** - Alternative deposit records
6. **withdrawals** - Withdrawal requests
7. **daily_tasks** - Daily task tracking
8. **task_completions** - Individual task records
9. **commissions** - Referral commission records
10. **referral_settings** - Commission rate configuration
11. **admin_settings** - Platform settings
12. **announcements** - Platform announcements
13. **activity_logs** - System activity tracking
14. **user_statistics** (VIEW) - Reporting view

**Total API Endpoints:** 15+

**Total User Pages:** 12+

**Total Admin Pages:** 8+

---

## ⚠️ EDUCATIONAL DISCLAIMER

This system is designed to demonstrate how fraudulent investment platforms work. Key indicators of fraud:

1. **Unsustainable returns** - 38 USDT daily on 66 USDT deposit = 57% daily return
2. **Pyramid structure** - Requires continuous new deposits to pay old users
3. **Cryptocurrency only** - Harder to trace and recover
4. **Brand impersonation** - Uses legitimate Dollar Tree branding
5. **Withdrawal restrictions** - Admin approval, daily limits, delays

**DO NOT USE THIS FOR REAL OPERATIONS**

---

End of Documentation
