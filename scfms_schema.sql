-- =====================================================================
-- SCFMS — Student Concern and Feedback Management System
-- Full MySQL schema (reference version; Laravel migrations mirror this)
-- MySQL 8+ / MariaDB 10.4+ (XAMPP compatible)
-- =====================================================================

CREATE DATABASE IF NOT EXISTS scfms
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE scfms;

-- ---------------------------------------------------------------
-- roles
-- ---------------------------------------------------------------
CREATE TABLE roles (
  id          TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name        VARCHAR(30) NOT NULL UNIQUE,          -- student, teacher, admin
  description VARCHAR(150) NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------
-- users
-- ---------------------------------------------------------------
CREATE TABLE users (
  id             BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  role_id        TINYINT UNSIGNED NOT NULL,
  student_number VARCHAR(20) NULL UNIQUE,           -- required for students only
  name           VARCHAR(120) NOT NULL,
  email          VARCHAR(150) NOT NULL UNIQUE,
  password       VARCHAR(255) NOT NULL,             -- bcrypt hash
  department     VARCHAR(120) NULL,                 -- course (student) / dept (teacher)
  is_active      BOOLEAN NOT NULL DEFAULT TRUE,
  email_opt_in   BOOLEAN NOT NULL DEFAULT FALSE,    -- optional email notifications
  remember_token VARCHAR(100) NULL,
  created_at     TIMESTAMP NULL,
  updated_at     TIMESTAMP NULL,
  CONSTRAINT fk_users_role FOREIGN KEY (role_id) REFERENCES roles(id)
) ENGINE=InnoDB;

CREATE INDEX idx_users_role ON users(role_id);

-- ---------------------------------------------------------------
-- categories
-- ---------------------------------------------------------------
CREATE TABLE categories (
  id         SMALLINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name       VARCHAR(100) NOT NULL UNIQUE,          -- English
  name_fil   VARCHAR(100) NULL,                     -- Filipino label
  is_urgent  BOOLEAN NOT NULL DEFAULT FALSE,        -- bullying/safety => escalate
  is_active  BOOLEAN NOT NULL DEFAULT TRUE,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------
-- ticket_sequences (safe per-year ticket numbering: SC-2026-0001)
-- ---------------------------------------------------------------
CREATE TABLE ticket_sequences (
  year        SMALLINT UNSIGNED PRIMARY KEY,
  last_number INT UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB;

-- ---------------------------------------------------------------
-- concerns (tickets)
-- ---------------------------------------------------------------
CREATE TABLE concerns (
  id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  ticket_no     VARCHAR(20) NOT NULL UNIQUE,        -- SC-2026-0001
  user_id       BIGINT UNSIGNED NULL,               -- NULL = anonymous submission
  category_id   SMALLINT UNSIGNED NOT NULL,
  recipient_id  BIGINT UNSIGNED NULL,               -- assigned teacher/office user
  subject       VARCHAR(150) NOT NULL,
  description   TEXT NOT NULL,
  priority      ENUM('low','normal','high','urgent') NOT NULL DEFAULT 'normal',
  status        ENUM('pending','under_review','in_progress','resolved','closed')
                NOT NULL DEFAULT 'pending',
  is_anonymous  BOOLEAN NOT NULL DEFAULT FALSE,
  resolved_at   TIMESTAMP NULL,
  closed_at     TIMESTAMP NULL,
  created_at    TIMESTAMP NULL,
  updated_at    TIMESTAMP NULL,
  CONSTRAINT fk_concerns_user      FOREIGN KEY (user_id)      REFERENCES users(id)      ON DELETE SET NULL,
  CONSTRAINT fk_concerns_category  FOREIGN KEY (category_id)  REFERENCES categories(id),
  CONSTRAINT fk_concerns_recipient FOREIGN KEY (recipient_id) REFERENCES users(id)      ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE INDEX idx_concerns_status    ON concerns(status);
CREATE INDEX idx_concerns_category  ON concerns(category_id);
CREATE INDEX idx_concerns_recipient ON concerns(recipient_id);
CREATE INDEX idx_concerns_created   ON concerns(created_at);

-- ---------------------------------------------------------------
-- replies (public replies + internal notes in one thread)
-- ---------------------------------------------------------------
CREATE TABLE replies (
  id             BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  concern_id     BIGINT UNSIGNED NOT NULL,
  user_id        BIGINT UNSIGNED NOT NULL,
  body           TEXT NOT NULL,
  is_internal_note BOOLEAN NOT NULL DEFAULT FALSE,  -- visible to teacher/admin only
  created_at     TIMESTAMP NULL,
  CONSTRAINT fk_replies_concern FOREIGN KEY (concern_id) REFERENCES concerns(id) ON DELETE CASCADE,
  CONSTRAINT fk_replies_user    FOREIGN KEY (user_id)    REFERENCES users(id)
) ENGINE=InnoDB;

CREATE INDEX idx_replies_concern ON replies(concern_id);

-- ---------------------------------------------------------------
-- attachments
-- ---------------------------------------------------------------
CREATE TABLE attachments (
  id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  concern_id    BIGINT UNSIGNED NOT NULL,
  reply_id      BIGINT UNSIGNED NULL,               -- attached to a reply (optional)
  user_id       BIGINT UNSIGNED NOT NULL,
  original_name VARCHAR(255) NOT NULL,
  stored_path   VARCHAR(255) NOT NULL,              -- random name in storage/app/attachments
  mime_type     VARCHAR(100) NOT NULL,
  size_bytes    INT UNSIGNED NOT NULL,
  created_at    TIMESTAMP NULL,
  CONSTRAINT fk_att_concern FOREIGN KEY (concern_id) REFERENCES concerns(id) ON DELETE CASCADE,
  CONSTRAINT fk_att_reply   FOREIGN KEY (reply_id)   REFERENCES replies(id)  ON DELETE CASCADE,
  CONSTRAINT fk_att_user    FOREIGN KEY (user_id)    REFERENCES users(id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------
-- notifications (in-app; email sent optionally via email_opt_in)
-- ---------------------------------------------------------------
CREATE TABLE notifications (
  id         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id    BIGINT UNSIGNED NOT NULL,
  concern_id BIGINT UNSIGNED NULL,
  type       VARCHAR(40) NOT NULL,    -- new_reply, status_change, urgent_alert, assigned, rated
  title      VARCHAR(150) NOT NULL,
  body       VARCHAR(300) NOT NULL,
  is_read    BOOLEAN NOT NULL DEFAULT FALSE,
  read_at    TIMESTAMP NULL,
  created_at TIMESTAMP NULL,
  CONSTRAINT fk_notif_user    FOREIGN KEY (user_id)    REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_notif_concern FOREIGN KEY (concern_id) REFERENCES concerns(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE INDEX idx_notif_user_unread ON notifications(user_id, is_read);

-- ---------------------------------------------------------------
-- ratings (one per concern, after Resolved)
-- ---------------------------------------------------------------
CREATE TABLE ratings (
  id         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  concern_id BIGINT UNSIGNED NOT NULL UNIQUE,
  user_id    BIGINT UNSIGNED NOT NULL,
  score      TINYINT UNSIGNED NOT NULL,   -- 1..5
  comment    VARCHAR(300) NULL,
  created_at TIMESTAMP NULL,
  CONSTRAINT fk_ratings_concern FOREIGN KEY (concern_id) REFERENCES concerns(id) ON DELETE CASCADE,
  CONSTRAINT fk_ratings_user    FOREIGN KEY (user_id)    REFERENCES users(id),
  CONSTRAINT chk_score CHECK (score BETWEEN 1 AND 5)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------
-- audit_logs (status changes, assignments, identity unmasking, etc.)
-- ---------------------------------------------------------------
CREATE TABLE audit_logs (
  id         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id    BIGINT UNSIGNED NULL,          -- who performed the action
  concern_id BIGINT UNSIGNED NULL,
  action     VARCHAR(60) NOT NULL,          -- status_changed, assigned, unmasked, ...
  old_value  VARCHAR(150) NULL,
  new_value  VARCHAR(150) NULL,
  reason     VARCHAR(300) NULL,             -- required when admin unmasks identity
  ip_address VARCHAR(45) NULL,
  created_at TIMESTAMP NULL,
  CONSTRAINT fk_audit_user    FOREIGN KEY (user_id)    REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_audit_concern FOREIGN KEY (concern_id) REFERENCES concerns(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE INDEX idx_audit_concern ON audit_logs(concern_id);

-- ---------------------------------------------------------------
-- Seed: roles + starter categories
-- ---------------------------------------------------------------
INSERT INTO roles (name, description) VALUES
  ('student', 'Submits and tracks concerns'),
  ('teacher', 'Receives, replies to, and updates assigned concerns'),
  ('admin',   'Manages users, categories, assignments, and reports');

INSERT INTO categories (name, name_fil, is_urgent) VALUES
  ('Academic',        'Akademiko',         FALSE),
  ('Bullying',        'Pambu-bully',       TRUE),
  ('Facilities',      'Pasilidad',         FALSE),
  ('Grades',          'Marka',             FALSE),
  ('Teacher-Related', 'Kaugnay ng Guro',   FALSE),
  ('Safety',          'Kaligtasan',        TRUE),
  ('Others',          'Iba pa',            FALSE);
