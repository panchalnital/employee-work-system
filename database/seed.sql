-- Demo accounts for local assignment testing only.
-- Existing accounts with these emails are preserved, including their passwords.
START TRANSACTION;

INSERT INTO users (name, email, password_hash, role)
SELECT 'Team Manager', 'admin@gmail.com', '$2b$12$tRN9pBilqcclTbakOdlhdOSQrPHOrubVjZeVpuVFmWwdJbYUhqhTu', 'manager'
WHERE NOT EXISTS (SELECT 1 FROM users WHERE email = 'manager@gmail.com');

INSERT INTO users (name, email, password_hash, role)
SELECT 'Nital Employee', 'nital@gmail.com', '$2b$12$2HSpoZLN8cK0879ggczGsOyglFhF4k1nX7QxxIa4gaWd1hPv8V91W', 'employee'
WHERE NOT EXISTS (SELECT 1 FROM users WHERE email = 'nital@gmail.com');

COMMIT;
