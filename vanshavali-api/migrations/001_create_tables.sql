-- =====================================================
-- Vanshavali MySQL Schema Migration
-- Version: 001 — Core tables
-- Run: mysql -u root vanshavali < migrations/001_create_tables.sql
-- =====================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----- Communities & Chapters -----
CREATE TABLE IF NOT EXISTS communities (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(200) NOT NULL,
    slug            VARCHAR(200) NOT NULL UNIQUE,
    region          VARCHAR(200),
    language_prefs  JSON,
    contact_email   VARCHAR(200),
    contact_phone   VARCHAR(50),
    description     TEXT,
    logo_url        VARCHAR(500),
    policies        JSON,
    status          ENUM('active', 'inactive', 'pending') NOT NULL DEFAULT 'active',
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_communities_slug (slug),
    INDEX idx_communities_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----- User Accounts -----
CREATE TABLE IF NOT EXISTS users (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(100) NOT NULL,
    email           VARCHAR(200) NOT NULL UNIQUE,
    password        VARCHAR(255) NOT NULL,
    role            ENUM('visitor', 'member', 'family_coordinator', 'content_moderator', 'community_admin', 'platform_admin')
                    NOT NULL DEFAULT 'member',
    community_id    INT UNSIGNED,
    status          ENUM('active', 'pending', 'suspended', 'deleted') NOT NULL DEFAULT 'pending',
    email_verified_at DATETIME,
    login_attempts  INT UNSIGNED NOT NULL DEFAULT 0,
    last_attempt    DATETIME,
    last_login      DATETIME,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (community_id) REFERENCES communities(id) ON DELETE SET NULL,
    INDEX idx_users_email (email),
    INDEX idx_users_community (community_id),
    INDEX idx_users_role (role),
    INDEX idx_users_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----- Refresh Tokens -----
CREATE TABLE IF NOT EXISTS refresh_tokens (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id     INT UNSIGNED NOT NULL,
    token_hash  VARCHAR(64) NOT NULL,
    revoked     TINYINT(1) NOT NULL DEFAULT 0,
    expires_at  DATETIME NOT NULL,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_refresh_user (user_id),
    INDEX idx_refresh_hash (token_hash),
    INDEX idx_refresh_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----- Members (family tree nodes) -----
CREATE TABLE IF NOT EXISTS members (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id             INT UNSIGNED,
    community_id        INT UNSIGNED,
    name                VARCHAR(200) NOT NULL,
    gender              ENUM('male', 'female', 'other') DEFAULT NULL,
    birth_year          INT,
    death_year          INT,
    relationship_label  VARCHAR(100),
    profile_image       VARCHAR(500),
    tags                JSON,
    order_id            INT,
    is_minor            TINYINT(1) NOT NULL DEFAULT 0,
    visibility          ENUM('private', 'family_only', 'community_only', 'public') NOT NULL DEFAULT 'family_only',
    created_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (community_id) REFERENCES communities(id) ON DELETE SET NULL,
    INDEX idx_members_community (community_id),
    INDEX idx_members_user (user_id),
    INDEX idx_members_name (name),
    FULLTEXT idx_members_search (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----- Relationships (family tree edges) -----
CREATE TABLE IF NOT EXISTS relationships (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    member_id           INT UNSIGNED NOT NULL,
    related_member_id   INT UNSIGNED NOT NULL,
    relationship_type   ENUM('parent', 'child', 'spouse', 'sibling', 'guardian', 'adopted', 'step', 'unknown')
                        NOT NULL,
    verification_status ENUM('proposed', 'approved', 'rejected') NOT NULL DEFAULT 'approved',
    effective_date      DATE,
    proposed_by         INT UNSIGNED,
    approved_by         INT UNSIGNED,
    created_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE,
    FOREIGN KEY (related_member_id) REFERENCES members(id) ON DELETE CASCADE,
    FOREIGN KEY (proposed_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_rel_member (member_id),
    INDEX idx_rel_related (related_member_id),
    INDEX idx_rel_type (relationship_type),
    UNIQUE KEY uk_relationship (member_id, related_member_id, relationship_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----- Events & Announcements -----
CREATE TABLE IF NOT EXISTS events (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    community_id    INT UNSIGNED,
    title           VARCHAR(300) NOT NULL,
    description     TEXT,
    event_type      ENUM('religious', 'cultural', 'educational', 'social', 'meeting', 'celebration', 'other')
                    NOT NULL DEFAULT 'other',
    location        VARCHAR(300),
    start_date      DATETIME,
    end_date        DATETIME,
    contact_person  VARCHAR(200),
    contact_info    VARCHAR(200),
    rsvp_enabled    TINYINT(1) NOT NULL DEFAULT 0,
    visibility      ENUM('public', 'community_only', 'members_only') NOT NULL DEFAULT 'public',
    status          ENUM('draft', 'published', 'archived') NOT NULL DEFAULT 'draft',
    published_by    INT UNSIGNED,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (community_id) REFERENCES communities(id) ON DELETE SET NULL,
    FOREIGN KEY (published_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_events_community (community_id),
    INDEX idx_events_date (start_date),
    INDEX idx_events_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----- Media Assets -----
CREATE TABLE IF NOT EXISTS media_assets (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    owner_user_id       INT UNSIGNED,
    community_id        INT UNSIGNED,
    file_path           VARCHAR(500) NOT NULL,
    file_type           VARCHAR(50),
    file_size           INT UNSIGNED,
    original_filename   VARCHAR(300),
    alt_text            VARCHAR(300),
    consent_confirmed   TINYINT(1) NOT NULL DEFAULT 0,
    visibility          ENUM('private', 'family_only', 'community_only', 'public') NOT NULL DEFAULT 'private',
    moderation_status   ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    created_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (owner_user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (community_id) REFERENCES communities(id) ON DELETE SET NULL,
    INDEX idx_media_owner (owner_user_id),
    INDEX idx_media_community (community_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----- Audit Logs -----
CREATE TABLE IF NOT EXISTS audit_logs (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    actor_user_id   INT UNSIGNED,
    action          VARCHAR(100) NOT NULL,
    entity_type     VARCHAR(50) NOT NULL,
    entity_id       INT UNSIGNED,
    before_state    JSON,
    after_state     JSON,
    ip_address      VARCHAR(45),
    user_agent      VARCHAR(500),
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (actor_user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_audit_actor (actor_user_id),
    INDEX idx_audit_action (action),
    INDEX idx_audit_entity (entity_type, entity_id),
    INDEX idx_audit_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
