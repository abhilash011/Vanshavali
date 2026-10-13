-- =====================================================
-- Vanshavali MySQL Schema Migration
-- Version: 003 — Phase 2: Family Tree Engine
-- Run: mysql -u root vanshavali < migrations/003_phase2_family_tree.sql
-- =====================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----- Edit Proposals (proposal → approval workflow) -----
CREATE TABLE IF NOT EXISTS edit_proposals (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    proposal_type       ENUM('add_member', 'edit_member', 'add_relationship', 'edit_relationship', 'remove_member', 'remove_relationship', 'correction_request') NOT NULL,
    target_entity_type  VARCHAR(50) NOT NULL DEFAULT 'member',
    target_entity_id    INT UNSIGNED DEFAULT NULL,
    proposed_data       JSON NOT NULL,
    reason              TEXT DEFAULT NULL,
    status              ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    proposed_by         INT UNSIGNED NOT NULL,
    reviewed_by         INT UNSIGNED DEFAULT NULL,
    review_note         TEXT DEFAULT NULL,
    community_id        INT UNSIGNED DEFAULT NULL,
    created_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (proposed_by) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (community_id) REFERENCES communities(id) ON DELETE SET NULL,
    INDEX idx_proposals_status (status),
    INDEX idx_proposals_community (community_id),
    INDEX idx_proposals_proposer (proposed_by),
    INDEX idx_proposals_target (target_entity_type, target_entity_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----- Add new columns to members table -----
ALTER TABLE members
    ADD COLUMN IF NOT EXISTS is_deceased TINYINT(1) NOT NULL DEFAULT 0 AFTER death_year,
    ADD COLUMN IF NOT EXISTS birth_date DATE DEFAULT NULL AFTER birth_year,
    ADD COLUMN IF NOT EXISTS death_date DATE DEFAULT NULL AFTER death_year,
    ADD COLUMN IF NOT EXISTS gotra VARCHAR(100) DEFAULT NULL AFTER tags,
    ADD COLUMN IF NOT EXISTS native_place VARCHAR(200) DEFAULT NULL AFTER gotra,
    ADD COLUMN IF NOT EXISTS occupation VARCHAR(200) DEFAULT NULL AFTER native_place,
    ADD COLUMN IF NOT EXISTS bio TEXT DEFAULT NULL AFTER occupation;

-- ----- Add proposed_by/approved_by to relationships if not present -----
-- (These columns already exist in the schema but let's ensure they're usable)

SET FOREIGN_KEY_CHECKS = 1;
