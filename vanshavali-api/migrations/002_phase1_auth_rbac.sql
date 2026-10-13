-- =====================================================
-- Vanshavali MySQL Schema Migration
-- Version: 002 — Phase 1: Auth, RBAC & Profiles
-- Run: mysql -u root vanshavali < migrations/002_phase1_auth_rbac.sql
-- =====================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----- Email Verification Tokens -----
CREATE TABLE IF NOT EXISTS email_verification_tokens (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id     INT UNSIGNED NOT NULL,
    token_hash  VARCHAR(64) NOT NULL,
    expires_at  DATETIME NOT NULL,
    used_at     DATETIME DEFAULT NULL,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_verify_user (user_id),
    INDEX idx_verify_hash (token_hash),
    INDEX idx_verify_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----- Password Reset Tokens -----
CREATE TABLE IF NOT EXISTS password_reset_tokens (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id     INT UNSIGNED NOT NULL,
    token_hash  VARCHAR(64) NOT NULL,
    expires_at  DATETIME NOT NULL,
    used_at     DATETIME DEFAULT NULL,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_reset_user (user_id),
    INDEX idx_reset_hash (token_hash),
    INDEX idx_reset_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----- User Profiles (extended, self-service) -----
CREATE TABLE IF NOT EXISTS user_profiles (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id         INT UNSIGNED NOT NULL UNIQUE,
    phone           VARCHAR(20) DEFAULT NULL,
    avatar_url      VARCHAR(500) DEFAULT NULL,
    bio             TEXT DEFAULT NULL,
    date_of_birth   DATE DEFAULT NULL,
    address_line1   VARCHAR(300) DEFAULT NULL,
    address_line2   VARCHAR(300) DEFAULT NULL,
    city            VARCHAR(100) DEFAULT NULL,
    state           VARCHAR(100) DEFAULT NULL,
    country         VARCHAR(100) DEFAULT NULL,
    postal_code     VARCHAR(20) DEFAULT NULL,
    language_pref   VARCHAR(10) DEFAULT 'en',
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_profile_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----- Add phone & avatar columns to users table if not present -----
-- (safe to re-run: uses ALTER IGNORE pattern)
ALTER TABLE users
    ADD COLUMN IF NOT EXISTS phone VARCHAR(20) DEFAULT NULL AFTER email,
    ADD COLUMN IF NOT EXISTS avatar_url VARCHAR(500) DEFAULT NULL AFTER phone;

SET FOREIGN_KEY_CHECKS = 1;
