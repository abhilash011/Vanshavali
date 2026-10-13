-- =====================================================
-- Vanshavali MySQL Schema Migration
-- Version: 004 — Phase 3: Community & Content
-- Run: mysql -u root vanshavali < migrations/004_phase3_community_content.sql
-- =====================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- 1. Regional Chapters (Shakhas)
CREATE TABLE IF NOT EXISTS chapters (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    community_id    INT UNSIGNED NOT NULL,
    name            VARCHAR(200) NOT NULL,
    slug            VARCHAR(200) NOT NULL,
    city            VARCHAR(100) NOT NULL,
    state           VARCHAR(100),
    country         VARCHAR(100) DEFAULT 'India',
    contact_person  VARCHAR(150),
    contact_phone   VARCHAR(50),
    contact_email   VARCHAR(150),
    address         TEXT,
    member_count    INT UNSIGNED DEFAULT 0,
    status          ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (community_id) REFERENCES communities(id) ON DELETE CASCADE,
    INDEX idx_chapters_community (community_id),
    INDEX idx_chapters_city (city),
    INDEX idx_chapters_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Announcements & News Feed
CREATE TABLE IF NOT EXISTS announcements (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    community_id    INT UNSIGNED NOT NULL,
    chapter_id      INT UNSIGNED,
    title           VARCHAR(300) NOT NULL,
    slug            VARCHAR(300),
    content         TEXT NOT NULL,
    category        ENUM('general', 'festival', 'obituary', 'achievement', 'matrimonial', 'urgent') NOT NULL DEFAULT 'general',
    is_pinned       TINYINT(1) NOT NULL DEFAULT 0,
    image_url       VARCHAR(500),
    author_id       INT UNSIGNED,
    status          ENUM('draft', 'published', 'archived') NOT NULL DEFAULT 'published',
    published_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (community_id) REFERENCES communities(id) ON DELETE CASCADE,
    FOREIGN KEY (chapter_id) REFERENCES chapters(id) ON DELETE SET NULL,
    FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_announcements_community (community_id),
    INDEX idx_announcements_category (category),
    INDEX idx_announcements_status (status),
    INDEX idx_announcements_pinned (is_pinned)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Event RSVPs
CREATE TABLE IF NOT EXISTS event_rsvps (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_id        INT UNSIGNED NOT NULL,
    user_id         INT UNSIGNED,
    name            VARCHAR(150) NOT NULL,
    email           VARCHAR(150),
    phone           VARCHAR(50),
    guest_count     INT UNSIGNED NOT NULL DEFAULT 1,
    status          ENUM('attending', 'maybe', 'declined') NOT NULL DEFAULT 'attending',
    note            TEXT,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_rsvps_event (event_id),
    INDEX idx_rsvps_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Media Albums for Photo Gallery
CREATE TABLE IF NOT EXISTS media_albums (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    community_id    INT UNSIGNED NOT NULL,
    title           VARCHAR(200) NOT NULL,
    description     TEXT,
    cover_image_url VARCHAR(500),
    visibility      ENUM('public', 'community_only', 'private') NOT NULL DEFAULT 'public',
    created_by      INT UNSIGNED,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (community_id) REFERENCES communities(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_albums_community (community_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Extend Media Assets with Album ID & Title if not present
SET @col_album = (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'media_assets' AND column_name = 'album_id');
SET @sql_album = IF(@col_album = 0, 'ALTER TABLE media_assets ADD COLUMN album_id INT UNSIGNED DEFAULT NULL AFTER community_id', 'SELECT 1');
PREPARE stmt FROM @sql_album;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @col_title = (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'media_assets' AND column_name = 'title');
SET @sql_title = IF(@col_title = 0, 'ALTER TABLE media_assets ADD COLUMN title VARCHAR(200) DEFAULT NULL AFTER original_filename', 'SELECT 1');
PREPARE stmt FROM @sql_title;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 6. Seed Sample Chapters, Events, Announcements, and Albums for Sonar Samaj (community_id = 1)
INSERT INTO chapters (community_id, name, slug, city, state, contact_person, contact_phone, contact_email, address, member_count)
VALUES 
(1, 'Mumbai Central Shakha', 'mumbai-central', 'Mumbai', 'Maharashtra', 'Kishore Sonar', '+91 98200 12345', 'mumbai@sonarsamaj.org', 'Sonar Bhavan, Dadar West, Mumbai 400028', 420),
(1, 'Pune Heritage Chapter', 'pune-heritage', 'Pune', 'Maharashtra', 'Anand Vernekar', '+91 98220 54321', 'pune@sonarsamaj.org', 'Laxmi Road, Sadashiv Peth, Pune 411030', 280),
(1, 'Jaipur Royal Chapter', 'jaipur-royal', 'Jaipur', 'Rajasthan', 'Ramesh Chandra Swarnakar', '+91 94140 98765', 'jaipur@sonarsamaj.org', 'Johari Bazar, Jaipur 302003', 350)
ON DUPLICATE KEY UPDATE name=VALUES(name);

INSERT INTO events (community_id, title, description, event_type, location, start_date, end_date, contact_person, contact_info, rsvp_enabled, visibility, status)
VALUES
(1, 'Maha Shivaratri Abhishek & Parivar Milan', 'Annual community prayer and grand family gathering with traditional Vedic rituals, prasadam, and cultural presentations honoring our heritage.', 'religious', 'Sonar Bhavan Cultural Hall, Dadar, Mumbai', DATE_ADD(NOW(), INTERVAL 14 DAY), DATE_ADD(NOW(), INTERVAL 14 DAY), 'Kishore Sonar', '+91 98200 12345', 1, 'public', 'published'),
(1, 'Vanshavali Youth & Matrimonial Sammelan 2026', 'A flagship community platform bringing together educated youth, prospective brides and grooms, and families for genealogy exploration and introductions.', 'social', 'Mahalaxmi Lawns, Karve Nagar, Pune', DATE_ADD(NOW(), INTERVAL 30 DAY), DATE_ADD(NOW(), INTERVAL 31 DAY), 'Suresh Varma', '+91 98230 45678', 1, 'public', 'published'),
(1, 'Swarnakar Day & Heritage Craft Exhibition', 'Celebrating traditional goldsmith craftsmanship, jewelry artisans, honoring senior community elders, and showcasing historical artifacts.', 'cultural', 'City Palace Exhibition Ground, Jaipur', DATE_ADD(NOW(), INTERVAL 45 DAY), DATE_ADD(NOW(), INTERVAL 46 DAY), 'Ramesh Swarnakar', '+91 94140 98765', 1, 'public', 'published')
ON DUPLICATE KEY UPDATE title=VALUES(title);

INSERT INTO announcements (community_id, title, content, category, is_pinned, status, published_at)
VALUES
(1, 'Welcome to the New Vanshavali Digital Community Portal', 'We are thrilled to launch the new Vanshavali digital genealogical platform for Sonar Samaj! All family members can now register, explore our sacred roots, update lineage trees, and connect with relatives across chapters.', 'general', 1, 'published', NOW()),
(1, 'Annual Scholarship Applications Open for 2026–27', 'Community students in higher education (engineering, medicine, business, arts) can apply for education grant stipends from the Sonar Samaj Heritage Trust by end of next month.', 'achievement', 1, 'published', NOW()),
(1, 'Shradhanjali (Obituary) — In Loving Memory of Late Shri Ramdasji Sonar (1938–2026)', 'With deep sorrow, we announce the passing of respected elder Shri Ramdasji Sonar. A prayer meeting (Besna/Shanti Sabha) will be held this Sunday at Sonar Bhavan. May his soul attain Moksha.', 'obituary', 0, 'published', NOW())
ON DUPLICATE KEY UPDATE title=VALUES(title);

INSERT INTO media_albums (community_id, title, description, cover_image_url, visibility)
VALUES
(1, 'Historical Lineage Archives & Sanads', 'Preserved centuries-old copper plates, genealogical birch bark manuscripts, and ancestral certificates.', 'https://images.unsplash.com/photo-1544717305-2782549b5136?auto=format&fit=crop&q=80&w=800', 'public'),
(1, 'Annual Community Sammelan 2025 Highlights', 'Photographs from the grand gathering in Mumbai featuring traditional performances, youth felicitation, and family reunions.', 'https://images.unsplash.com/photo-1511795409834-ef04bbd61622?auto=format&fit=crop&q=80&w=800', 'public'),
(1, 'Ancient Temples & Kuldevi Shrines', 'Sacred photography of ancestral shrines, village deities, and annual pilgrimage yatras.', 'https://images.unsplash.com/photo-1599818818868-b8089943801f?auto=format&fit=crop&q=80&w=800', 'public')
ON DUPLICATE KEY UPDATE title=VALUES(title);

SET FOREIGN_KEY_CHECKS = 1;
