CREATE TABLE users (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 name VARCHAR(100) NOT NULL,
 email VARCHAR(190) NOT NULL UNIQUE,
 password_hash VARCHAR(255) NOT NULL,
 role ENUM('manager','employee') NOT NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;
CREATE TABLE tasks (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 employee_id BIGINT UNSIGNED NOT NULL,
 manager_id BIGINT UNSIGNED NOT NULL,
 title VARCHAR(200) NOT NULL,
 description TEXT NOT NULL,
 priority ENUM('low','medium','high') NOT NULL DEFAULT 'medium',
 status ENUM('todo','in_progress','pending','completed') NOT NULL DEFAULT 'todo',
 due_date DATE NOT NULL,
 completed_at DATETIME NULL,
 version INT NOT NULL DEFAULT 1,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY(employee_id) REFERENCES users(id),
 FOREIGN KEY(manager_id) REFERENCES users(id),
 INDEX(employee_id, status, due_date), INDEX(due_date, status)
) ENGINE=InnoDB;
CREATE TABLE daily_updates (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 employee_id BIGINT UNSIGNED NOT NULL,
 work_date DATE NOT NULL,
 accomplished TEXT NOT NULL,
 todo TEXT NOT NULL,
 pending TEXT NOT NULL,
 blockers TEXT NOT NULL,
 updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 UNIQUE(employee_id,work_date),
 FOREIGN KEY(employee_id) REFERENCES users(id)
) ENGINE=InnoDB;
CREATE TABLE task_history (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 task_id BIGINT UNSIGNED NOT NULL,
 actor_id BIGINT UNSIGNED NOT NULL,
 old_status VARCHAR(20) NULL,
 new_status VARCHAR(20) NOT NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY(task_id) REFERENCES tasks(id),
 FOREIGN KEY(actor_id) REFERENCES users(id),
 INDEX(task_id,created_at)
) ENGINE=InnoDB;
CREATE TABLE login_attempts (
 email_hash CHAR(64) PRIMARY KEY,
 failures INT NOT NULL DEFAULT 0,
 blocked_until BIGINT NOT NULL DEFAULT 0
) ENGINE=InnoDB;

-- Predefined manager and employee accounts
-- Demo accounts for local assignment testing only.
-- Existing accounts with these emails are preserved, including their passwords.
START TRANSACTION;

INSERT INTO users (name, email, password_hash, role)
SELECT 'Team Manager', 'admin@gmail.com', '$2b$12$tRN9pBilqcclTbakOdlhdOSQrPHOrubVjZeVpuVFmWwdJbYUhqhTu', 'manager'
WHERE NOT EXISTS (SELECT 1 FROM users WHERE email = 'admin@gmail.com');

INSERT INTO users (name, email, password_hash, role)
SELECT 'Nital Employee', 'nital@gmail.com', '$2b$12$2HSpoZLN8cK0879ggczGsOyglFhF4k1nX7QxxIa4gaWd1hPv8V91W', 'employee'
WHERE NOT EXISTS (SELECT 1 FROM users WHERE email = 'nital@gmail.com');

COMMIT;
